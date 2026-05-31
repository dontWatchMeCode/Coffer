<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notes;

use App\Concerns\EscapesLikeWildcards;
use App\Concerns\ProvidesActivityHistory;
use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotePageController extends Controller
{
    use EscapesLikeWildcards;
    use ProvidesActivityHistory;
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function index(Request $request, Team $currentTeam): Response
    {
        $search = $request->string('search')->toString();

        $notes = Note::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->when($search, function ($q) use ($search): void {
                $q->where(function ($q) use ($search): void {
                    $q->search($search, ['title'])
                        ->orWhereHas('recordTags', function (Builder $query) use ($search): void {
                            $this->whereLikeEscaped($query, 'name', $this->likePattern($search));
                        });
                });
            })
            ->orderByDesc('updated_at')
            ->simplePaginate(25);

        return Inertia::render('notes/Index', [
            'notes' => Inertia::scroll($notes->through(fn (Note $note): array => $this->notePayload($note, includeBlocks: false))),
        ]);
    }

    public function trash(Request $request, Team $currentTeam): Response
    {
        $search = $request->string('search')->toString();

        $notes = Note::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->when($search, function ($q) use ($search): void {
                $q->where(function ($q) use ($search): void {
                    $q->search($search, ['title'])
                        ->orWhereHas('recordTags', function (Builder $query) use ($search): void {
                            $this->whereLikeEscaped($query, 'name', $this->likePattern($search));
                        });
                });
            })
            ->orderByDesc('deleted_at')
            ->simplePaginate(25);

        return Inertia::render('notes/Trash', [
            'notes' => Inertia::scroll($notes->through(fn (Note $note): array => $this->notePayload($note, includeBlocks: false, includeDeletedAt: true))),
        ]);
    }

    public function show(Team $currentTeam, int $note): Response
    {
        $note = Note::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name'), 'blocks'])
            ->findOrFail($note);

        return Inertia::render('notes/Show', [
            'note' => $this->notePayload($note),
            'recordLinks' => $this->recordLinksPayload($note, $currentTeam),
            'recordTags' => $this->recordTagsPayload($note, $currentTeam),
            'activityHistory' => $this->activityHistoryConfig($note),
            'startInEditMode' => session()->pull('edit', false),
        ]);
    }

    /**
     * @return array{id: int, title: string, blocks: array<int, array{id: int, type: string, position: int, payload: array<string, mixed>|null}>, excerpt: string|null, tags: array<int, array{id: int, name: string, slug: string}>, createdAt: string|null, updatedAt: string|null, deletedAt?: string|null}
     */
    protected function notePayload(Note $note, bool $includeBlocks = true, bool $includeDeletedAt = false): array
    {
        $payload = [
            'id' => $note->id,
            'title' => $note->title,
            'blocks' => $includeBlocks
                ? $note->blocks->map(fn ($block): array => $block->toPayloadArray())->all()
                : [],
            'excerpt' => $includeBlocks ? $this->excerptFromBlocks($note) : null,
            'tags' => $note->formattedRecordTags(),
            'createdAt' => $note->created_at?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $note->updated_at?->format(\DateTimeInterface::ATOM),
        ];

        if ($includeDeletedAt) {
            $deletedAt = $note->getAttribute('deleted_at');

            $payload['deletedAt'] = $deletedAt instanceof \DateTimeInterface
                ? $deletedAt->format(\DateTimeInterface::ATOM)
                : null;
        }

        return $payload;
    }

    protected function excerptFromBlocks(Note $note): ?string
    {
        return $note->textExcerpt(180) ?? ($note->hasDrawingBlock() ? 'Excalidraw drawing' : null);
    }
}
