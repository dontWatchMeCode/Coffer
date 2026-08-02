<?php

declare(strict_types=1);

namespace App\Http\Requests\Collections;

use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class SaveCollectionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rulesFor('collection', $this->isMethod('patch'));
    }
}
