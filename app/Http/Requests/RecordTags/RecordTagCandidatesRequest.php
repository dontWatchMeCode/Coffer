<?php

declare(strict_types=1);

namespace App\Http\Requests\RecordTags;

use App\Models\RecordLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class RecordTagCandidatesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string|In>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'from_type' => ['required', 'string', Rule::in(array_keys(RecordLink::taggableMap()))],
            'from_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
