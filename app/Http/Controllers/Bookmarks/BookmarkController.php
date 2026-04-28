<?php

namespace App\Http\Controllers\Bookmarks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bookmarks\DeleteBookmarkRequest;
use App\Http\Requests\Bookmarks\SaveBookmarkRequest;
use App\Models\Bookmark;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class BookmarkController extends Controller
{
    public function store(SaveBookmarkRequest $request, Team $currentTeam): RedirectResponse
    {
        $validated = $request->validated();

        $bookmark = Bookmark::create([
            ...$this->withoutTags($validated),
            'team_id' => $currentTeam->id,
        ]);

        $this->syncTagsIfPresent($bookmark, $validated, $currentTeam);

        return to_route('team.bookmarks.show', [
            'current_team' => $currentTeam,
            'bookmark' => $bookmark->id,
        ]);
    }

    public function update(SaveBookmarkRequest $request, Team $currentTeam, int $bookmark): RedirectResponse
    {
        $bookmark = Bookmark::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($bookmark);

        $validated = $request->validated();

        $bookmark->update($this->withoutTags($validated));

        $this->syncTagsIfPresent($bookmark, $validated, $currentTeam);

        return to_route('team.bookmarks.show', [
            'current_team' => $currentTeam,
            'bookmark' => $bookmark->id,
        ]);
    }

    public function destroy(DeleteBookmarkRequest $request, Team $currentTeam, int $bookmark): RedirectResponse
    {
        $bookmark = Bookmark::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($bookmark);

        $bookmark->delete();

        return to_route('team.bookmarks.index', [
            'current_team' => $currentTeam,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function withoutTags(array $validated): array
    {
        Arr::forget($validated, 'tags');

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncTagsIfPresent(Bookmark $bookmark, array $validated, Team $currentTeam): void
    {
        $tagNames = $validated['tags'] ?? null;

        if (is_array($tagNames)) {
            $bookmark->syncRecordTagNames($tagNames, $currentTeam);
        }
    }
}
