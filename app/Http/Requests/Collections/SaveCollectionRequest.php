<?php

declare(strict_types=1);

namespace App\Http\Requests\Collections;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Models\RecordCollection;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class SaveCollectionRequest extends FormRequest
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

        $collectionId = $this->route('collection');
        $team = $this->currentTeam();

        return filled($collectionId) && $team instanceof Team && RecordCollection::query()
            ->whereBelongsTo($team)
            ->whereKey($collectionId)
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
            'description' => $sometimes
                ? ['sometimes', 'nullable', 'string']
                : ['nullable', 'string'],
        ];
    }
}
