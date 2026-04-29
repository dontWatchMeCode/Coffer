<?php

declare(strict_types=1);

namespace App\Http\Controllers\Collections;

use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\RecordCollection;
use App\Models\Team;
use DateTimeInterface;
use Inertia\Inertia;
use Inertia\Response;

class CollectionPageController extends Controller
{
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function index(Team $currentTeam): Response
    {
        $collections = RecordCollection::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('collections/Index', [
            'collections' => $collections->map(fn (RecordCollection $collection): array => $this->collectionPayload($collection))->values()->all(),
        ]);
    }

    public function show(Team $currentTeam, int $collection): Response
    {
        $collection = RecordCollection::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($collection);

        return Inertia::render('collections/Show', [
            'collection' => $this->collectionPayload($collection),
            'recordLinks' => $this->recordLinksPayload($collection, $currentTeam),
            'recordTags' => $this->recordTagsPayload($collection, $currentTeam),
        ]);
    }

    /**
     * @return array{id: int, title: string, description: string|null, tags: array<int, array{id: int, name: string, slug: string}>, createdAt: string|null, updatedAt: string|null}
     */
    protected function collectionPayload(RecordCollection $collection): array
    {
        return [
            'id' => $collection->id,
            'title' => $collection->title,
            'description' => $collection->description,
            'tags' => $collection->formattedRecordTags(),
            'createdAt' => $collection->created_at?->format(DateTimeInterface::ATOM),
            'updatedAt' => $collection->updated_at?->format(DateTimeInterface::ATOM),
        ];
    }
}
