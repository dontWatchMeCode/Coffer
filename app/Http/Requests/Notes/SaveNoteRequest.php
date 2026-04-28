<?php

declare(strict_types=1);

namespace App\Http\Requests\Notes;

use App\Http\Requests\Tasks\AuthorizesTeamResource;
use App\Models\Note;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class SaveNoteRequest extends FormRequest
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

        $noteId = $this->route('note');
        $team = $this->currentTeam();

        return filled($noteId) && $team instanceof Team && Note::query()
            ->whereBelongsTo($team)
            ->whereKey($noteId)
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
            'body' => $sometimes
                ? ['sometimes', 'nullable', 'string']
                : ['nullable', 'string'],
        ];
    }
}
