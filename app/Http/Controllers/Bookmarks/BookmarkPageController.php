<?php

namespace App\Http\Controllers\Bookmarks;

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
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function index(Request $request, Team $currentTeam): Response
    {
        $bookmarks = Bookmark::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->orderBy('is_archived')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('bookmarks/Index', [
            'bookmarks' => $bookmarks->map(fn (Bookmark $bookmark): array => [
                'id' => $bookmark->id,
                'title' => $bookmark->title,
                'url' => $bookmark->url,
                'description' => $bookmark->description,
                'tags' => $this->bookmarkTagNames($bookmark),
                'notes' => $bookmark->notes,
                'isArchived' => $bookmark->is_archived,
                'createdAt' => $bookmark->created_at?->format(\DateTimeInterface::ATOM),
                'updatedAt' => $bookmark->updated_at?->format(\DateTimeInterface::ATOM),
            ])->values()->all(),
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
                'tags' => $this->bookmarkTagNames($bookmark),
                'notes' => $bookmark->notes,
                'isArchived' => $bookmark->is_archived,
                'createdAt' => $bookmark->created_at?->format(\DateTimeInterface::ATOM),
                'updatedAt' => $bookmark->updated_at?->format(\DateTimeInterface::ATOM),
            ],
            'recordLinks' => $this->recordLinksPayload($bookmark, $currentTeam),
            'recordTags' => $this->recordTagsPayload($bookmark, $currentTeam),
        ]);
    }

    /**
     * @return array<int, string>|null
     */
    private function bookmarkTagNames(Bookmark $bookmark): ?array
    {
        $recordTags = array_column($bookmark->formattedRecordTags(), 'name');

        if ($recordTags !== []) {
            return $recordTags;
        }

        $legacyTags = $bookmark->getAttribute('tags');

        return is_array($legacyTags) ? $legacyTags : null;
    }
}
