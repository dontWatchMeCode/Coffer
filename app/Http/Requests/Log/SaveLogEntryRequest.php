<?php

declare(strict_types=1);

namespace App\Http\Requests\Log;

use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class SaveLogEntryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rulesFor('log_entry', false);
    }
}
