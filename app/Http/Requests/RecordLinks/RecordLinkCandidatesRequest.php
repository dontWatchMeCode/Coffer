<?php

declare(strict_types=1);

namespace App\Http\Requests\RecordLinks;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Models\RecordLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class RecordLinkCandidatesRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        return $this->isTeamMember();
    }

    /**
     * @return array<string, array<int, string|In>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'from_type' => ['required', 'string', Rule::in(array_keys(RecordLink::linkableMap()))],
            'from_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
