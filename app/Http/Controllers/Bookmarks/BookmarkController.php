<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bookmarks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bookmarks\DeleteBookmarkRequest;
use App\Http\Requests\Bookmarks\SaveBookmarkRequest;
use App\Models\Bookmark;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class BookmarkController extends Controller
{
    public function store(SaveBookmarkRequest $request, Team $currentTeam): RedirectResponse
    {
        $bookmark = Bookmark::create([
            ...$request->validated(),
            'team_id' => $currentTeam->id,
        ]);

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

        $bookmark->update($request->validated());

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
}
