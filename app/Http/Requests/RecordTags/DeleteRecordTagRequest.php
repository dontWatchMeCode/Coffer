<?php

declare(strict_types=1);

namespace App\Http\Requests\RecordTags;

use App\Models\RecordLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteRecordTagRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'from_type' => [Rule::required(), Rule::string(), Rule::in(array_keys(RecordLink::taggableMap()))],
            'from_id' => [Rule::required(), 'integer', 'min:1'],
            'tag_id' => [Rule::required(), 'integer', 'min:1'],
        ];
    }
}
