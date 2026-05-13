<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use Illuminate\Foundation\Http\FormRequest;

class SaveCalendarEventRequest extends FormRequest
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
            'title' => $this->isMethod('patch') ? ['sometimes', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'description' => $this->isMethod('patch') ? ['sometimes', 'nullable', 'string'] : ['nullable', 'string'],
            'date' => $this->isMethod('patch') ? ['sometimes', 'date'] : ['required', 'date'],
            'time' => $this->isMethod('patch') ? ['sometimes', 'nullable', 'date_format:H:i'] : ['nullable', 'date_format:H:i'],
        ];
    }
}
