<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProjectRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        return $this->isTeamMember();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->route('current_team');

        if (! $team instanceof Team) {
            abort(404);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')
                    ->where(fn ($query) => $query->where('team_id', $team->id))
                    ->ignore($this->route('project')),
            ],
            'description' => ['nullable', 'string'],
            'archived' => ['nullable', 'boolean'],
            'status_options' => ['nullable', 'array'],
            'status_options.*.value' => ['required', 'string', 'regex:/^[a-z0-9_]+$/', 'distinct'],
            'status_options.*.label' => ['required', 'string', 'max:40'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('archived')) {
            $data['archived'] = $this->boolean('archived');
        }

        if ($this->has('status_options_present') && ! $this->has('status_options')) {
            $data['status_options'] = [];
        } elseif ($this->isMethod('post') && $team = $this->route('current_team')) {
            if ($team instanceof Team) {
                $data['status_options'] = $team->taskStatusDefaults();
            }
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }
}
