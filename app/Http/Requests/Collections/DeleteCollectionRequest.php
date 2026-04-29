<?php

declare(strict_types=1);

namespace App\Http\Requests\Collections;

use App\Http\Requests\Tasks\AuthorizesTeamResource;
use App\Models\RecordCollection;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class DeleteCollectionRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        $collectionId = $this->route('collection');
        $team = $this->currentTeam();

        return $this->isTeamMember() && filled($collectionId) && $team instanceof Team && RecordCollection::query()
            ->whereBelongsTo($team)
            ->whereKey($collectionId)
            ->exists();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
