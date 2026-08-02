<?php

declare(strict_types=1);

namespace App\Http\Requests\Spreadsheets;

use Illuminate\Foundation\Http\FormRequest;

class SaveSpreadsheetRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
