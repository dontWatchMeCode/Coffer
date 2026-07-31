<?php

declare(strict_types=1);

namespace App\Http\Requests\Spreadsheets;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use Illuminate\Foundation\Http\FormRequest;

class SaveSpreadsheetRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        return $this->isTeamMember();
    }

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
