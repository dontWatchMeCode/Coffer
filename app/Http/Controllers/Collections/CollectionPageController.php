<?php

declare(strict_types=1);

namespace App\Http\Controllers\Collections;

use App\Concerns\ProvidesActivityHistory;
use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\RecordCollection;
use App\Models\Team;
use DateTimeInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionPageController extends Controller
{
    use ProvidesActivityHistory;
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function index(Request $request, Team $currentTeam): Response
    {
        $search = $request->string('search')->toString();

        $collections = RecordCollection::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->when($search, function ($q) use ($search): void {
                $q->where(function ($q) use ($search): void {
                    $q->search($search, ['title', 'description'])
                        ->orWhereHas('recordTags', fn ($q) => $q->where('name', 'like', sprintf('%%%s%%', addcslashes($search, '%_\\'))));
                });
            })
            ->orderByDesc('updated_at')
            ->simplePaginate(25);

        return Inertia::render('collections/Index', [
            'collections' => Inertia::scroll($collections->through(fn (RecordCollection $collection): array => $this->collectionPayload($collection))),
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
            'recordLinks' => $this->recordLinksPayload($collection, $currentTeam, includeDrawingData: true),
            'recordTags' => $this->recordTagsPayload($collection, $currentTeam),
            'activityHistory' => $this->activityHistoryPayload($collection),
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
