<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTaskCommentRequest extends FormRequest
{
    use AuthorizesTeamResource;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->isTeamMember();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'blocks' => ['required', 'array', 'min:1', 'max:50'],
            'blocks.*.id' => ['sometimes', 'nullable', 'integer'],
            'blocks.*.type' => ['required', 'string', Rule::in(['text', 'excalidraw', 'mermaid'])],
            'blocks.*.position' => ['required', 'integer', 'min:0'],
            'blocks.*.payload' => ['sometimes', 'nullable', 'array'],
            'blocks.*.payload.content' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ];
    }
}
