<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notes;

use App\Concerns\ProvidesActivityHistory;
use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class NotePageController extends Controller
{
    use ProvidesActivityHistory;
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function index(Team $currentTeam): Response
    {
        $notes = Note::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('notes/Index', [
            'notes' => $notes->map(fn (Note $note): array => $this->notePayload($note, includeDrawingData: false))->values()->all(),
        ]);
    }

    public function show(Team $currentTeam, int $note): Response
    {
        $note = Note::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($note);

        return Inertia::render('notes/Show', [
            'note' => $this->notePayload($note),
            'recordLinks' => $this->recordLinksPayload($note, $currentTeam),
            'recordTags' => $this->recordTagsPayload($note, $currentTeam),
            'activityHistory' => $this->activityHistoryPayload($note),
            'startInEditMode' => session()->pull('edit', false),
        ]);
    }

    /**
     * @return array{id: int, title: string, body: string|null, format: string, drawingData: array<string, mixed>|null, excerpt: string|null, tags: array<int, array{id: int, name: string, slug: string}>, createdAt: string|null, updatedAt: string|null}
     */
    protected function notePayload(Note $note, bool $includeDrawingData = true): array
    {
        $format = $note->format ?? 'text';

        return [
            'id' => $note->id,
            'title' => $note->title,
            'body' => $note->body,
            'format' => $format,
            'drawingData' => $includeDrawingData ? $note->drawing_data : null,
            'excerpt' => $format === 'excalidraw'
                ? 'Excalidraw drawing'
                : (str($note->body ?? '')->stripTags()->squish()->limit(180)->toString() ?: null),
            'tags' => $note->formattedRecordTags(),
            'createdAt' => $note->created_at?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $note->updated_at?->format(\DateTimeInterface::ATOM),
        ];
    }
}
