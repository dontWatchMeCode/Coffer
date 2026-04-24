<?php

declare(strict_types=1);

namespace App\Http\Requests\Bookmarks;

use App\Http\Requests\Tasks\AuthorizesTeamResource;
use App\Models\Bookmark;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class DeleteBookmarkRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        if (! $this->isTeamMember()) {
            return false;
        }

        $bookmarkId = $this->route('bookmark');
        $team = $this->currentTeam();

        return filled($bookmarkId) && $team instanceof Team && Bookmark::query()
            ->whereBelongsTo($team)
            ->whereKey($bookmarkId)
            ->exists();
    }
}
