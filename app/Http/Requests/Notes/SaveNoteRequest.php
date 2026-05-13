<?php

declare(strict_types=1);

namespace App\Http\Requests\Notes;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveNoteRequest extends FormRequest
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
        $sometimes = $this->isMethod('patch');

        return [
            'title' => $sometimes
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'body' => $sometimes
                ? ['sometimes', 'nullable', 'string']
                : ['nullable', 'string'],
            'format' => ['sometimes', 'required', 'string', Rule::in(['text', 'excalidraw'])],
            'drawing_data' => $sometimes
                ? ['sometimes', 'nullable', 'array']
                : ['nullable', 'array'],
        ];
    }
}
