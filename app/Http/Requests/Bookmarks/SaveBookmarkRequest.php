<?php

declare(strict_types=1);

namespace App\Http\Requests\Bookmarks;

use App\Http\Requests\Tasks\AuthorizesTeamResource;
use App\Models\Bookmark;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class SaveBookmarkRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        if (! $this->isTeamMember()) {
            return false;
        }

        if (! $this->isMethod('patch')) {
            return true;
        }

        $bookmarkId = $this->route('bookmark');
        $team = $this->currentTeam();

        return filled($bookmarkId) && $team instanceof Team && Bookmark::query()
            ->whereBelongsTo($team)
            ->whereKey($bookmarkId)
            ->exists();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $sometimes = $this->isMethod('patch');

        return [
            'title' => $sometimes
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'url' => $sometimes
                ? ['sometimes', 'required', 'string', 'url', 'max:2048']
                : ['required', 'string', 'url', 'max:2048'],
            'description' => $sometimes
                ? ['sometimes', 'nullable', 'string', 'max:500']
                : ['nullable', 'string', 'max:500'],
            'notes' => $sometimes
                ? ['sometimes', 'nullable', 'string']
                : ['nullable', 'string'],
        ];
    }
}
