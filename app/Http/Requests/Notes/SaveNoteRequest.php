<?php

declare(strict_types=1);

namespace App\Http\Requests\Notes;

use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class SaveNoteRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rulesFor('note', $this->isMethod('patch'), includeNoteBlockIds: true);
    }
}
