<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\McpToken;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'abilities' => ['required', 'array'],
            'abilities.collections' => ['required', 'string', Rule::in(McpToken::PERMISSION_LEVELS)],
            'abilities.notes' => ['required', 'string', Rule::in(McpToken::PERMISSION_LEVELS)],
            'abilities.bookmarks' => ['required', 'string', Rule::in(McpToken::PERMISSION_LEVELS)],
            'abilities.contacts' => ['required', 'string', Rule::in(McpToken::PERMISSION_LEVELS)],
            'abilities.calendar' => ['required', 'string', Rule::in(McpToken::PERMISSION_LEVELS)],
            'abilities.tasks' => ['required', 'string', Rule::in(McpToken::PERMISSION_LEVELS)],
            'abilities.task_projects' => ['required', 'array'],
            'abilities.task_projects.mode' => ['required', 'string', Rule::in(['all', 'only'])],
            'abilities.task_projects.ids' => ['array'],
            'abilities.task_projects.ids.*' => ['integer'],
        ];
    }
}
