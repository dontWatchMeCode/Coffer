<?php

declare(strict_types=1);

namespace App\Http\Requests\Log;

use App\Http\Requests\Tasks\AuthorizesTeamResource;
use Illuminate\Foundation\Http\FormRequest;

class SaveLogEntryRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        return $this->isTeamMember();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:80'],
        ];
    }
}
