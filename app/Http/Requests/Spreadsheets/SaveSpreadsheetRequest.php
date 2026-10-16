<?php

declare(strict_types=1);

namespace App\Http\Requests\Spreadsheets;

use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class SaveSpreadsheetRequest extends FormRequest
{
    /**
     * Title-only: store() discards any snapshot and boots a default workbook,
     * so snapshot shape is deliberately not validated here.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rulesFor('spreadsheet', false, null, ['title' => $this->input('title')]);
    }
}
