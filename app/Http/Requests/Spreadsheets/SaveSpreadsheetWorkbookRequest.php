<?php

declare(strict_types=1);

namespace App\Http\Requests\Spreadsheets;

use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveSpreadsheetWorkbookRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rulesFor('spreadsheet', $this->isMethod('patch'), null, $this->all());
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                DomainRecordValidation::assertSpreadsheetSnapshot($validator, $this->input('snapshot'));
            },
        ];
    }
}
