<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bookmarks;

use App\Concerns\ProvidesActivityHistory;
use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookmarkPageController extends Controller
{
    use ProvidesActivityHistory;
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function index(Request $request, Team $currentTeam): Response
    {
        $bookmarks = Bookmark::query()
            ->whereBelongsTo($currentTeam)
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->search($search, ['title', 'description', 'url']))
            ->orderByDesc('created_at')
            ->simplePaginate(25);

        return Inertia::render('bookmarks/Index', [
            'bookmarks' => Inertia::scroll($bookmarks->through(fn (Bookmark $bookmark): array => [
                'id' => $bookmark->id,
                'title' => $bookmark->title,
                'url' => $bookmark->url,
                'description' => $bookmark->description,
                'notes' => $bookmark->notes,
                'createdAt' => $bookmark->created_at?->format(\DateTimeInterface::ATOM),
                'updatedAt' => $bookmark->updated_at?->format(\DateTimeInterface::ATOM),
            ])),
        ]);
    }

    public function show(Request $request, Team $currentTeam, int $bookmark): Response
    {
        $bookmark = Bookmark::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($bookmark);

        return Inertia::render('bookmarks/Show', [
            'bookmark' => [
                'id' => $bookmark->id,
                'title' => $bookmark->title,
                'url' => $bookmark->url,
                'description' => $bookmark->description,
                'notes' => $bookmark->notes,
                'createdAt' => $bookmark->created_at?->format(\DateTimeInterface::ATOM),
                'updatedAt' => $bookmark->updated_at?->format(\DateTimeInterface::ATOM),
            ],
            'recordLinks' => $this->recordLinksPayload($bookmark, $currentTeam),
            'recordTags' => $this->recordTagsPayload($bookmark, $currentTeam),
            'activityHistory' => $this->activityHistoryPayload($bookmark),
        ]);
    }
}
