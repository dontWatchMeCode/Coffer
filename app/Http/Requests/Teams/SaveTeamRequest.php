<?php

declare(strict_types=1);

namespace App\Http\Requests\Teams;

use App\Rules\TeamName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveTeamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new TeamName],
            'default_task_status_options' => ['nullable', 'array'],
            'default_task_status_options.*.value' => ['required', 'string', 'regex:/^[a-z0-9_]+$/', 'distinct'],
            'default_task_status_options.*.label' => ['required', 'string', 'max:40'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('default_task_status_options_present') && ! $this->has('default_task_status_options')) {
            $this->merge([
                'default_task_status_options' => [],
            ]);

            return;
        }
    }
}
