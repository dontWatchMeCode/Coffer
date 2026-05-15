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
            'blocks' => ['sometimes', 'nullable', 'array', 'max:50'],
            'blocks.*.id' => ['sometimes', 'nullable', 'integer'],
            'blocks.*.type' => ['required', 'string', Rule::in(['text', 'excalidraw', 'mermaid'])],
            'blocks.*.position' => ['required', 'integer', 'min:0'],
            'blocks.*.payload' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
