<?php

declare(strict_types=1);

namespace App\Http\Requests\Notes;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class SaveNoteRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        return $this->isTeamMember();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rulesFor('note', $this->isMethod('patch'), includeNoteBlockIds: true);
    }
}
