<?php

declare(strict_types=1);

namespace App\Http\Requests\Notes;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Models\Note;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class DeleteNoteRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        if (! $this->isTeamMember()) {
            return false;
        }

        $noteId = $this->route('note');
        $team = $this->currentTeam();

        return filled($noteId) && $team instanceof Team && Note::query()
            ->whereBelongsTo($team)
            ->whereKey($noteId)
            ->exists();
    }
}
