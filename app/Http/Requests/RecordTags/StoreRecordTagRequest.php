<?php

declare(strict_types=1);

namespace App\Http\Requests\RecordTags;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Models\RecordLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class StoreRecordTagRequest extends FormRequest
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
            'from_type' => ['required', 'string', Rule::in(array_keys(RecordLink::taggableMap()))],
            'from_id' => ['required', 'integer', 'min:1'],
            'tag_id' => ['nullable', 'integer', 'min:1', 'required_without:name'],
            'name' => ['nullable', 'string', 'max:50', 'required_without:tag_id'],
        ];
    }
}
