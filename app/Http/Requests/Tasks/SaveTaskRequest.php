<?php

namespace App\Http\Requests\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTaskRequest extends FormRequest
{
    use AuthorizesTeamResource;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->isTeamMember()) {
            return false;
        }

        if (! $this->isMethod('patch')) {
            return true;
        }

        $taskId = $this->route('task');

        $team = $this->currentTeam();

        return filled($taskId) && $team instanceof Team && Task::query()
            ->whereBelongsTo($team)
            ->whereKey($taskId)
            ->exists();
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
            'project_id' => [
                ...$this->requiredRule(),
                'integer',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->where('team_id', $team->id)),
            ],
            'assigned_to' => [
                ...$this->optionalOnPatchRule(),
                'nullable',
                'integer',
                Rule::exists('team_members', 'user_id')->where(fn ($query) => $query->where('team_id', $team->id)),
            ],
            'title' => [...$this->requiredRule(), 'string', 'max:255'],
            'description' => [...$this->optionalOnPatchRule(), 'nullable', 'string'],
            'status' => [...$this->requiredRule(), Rule::enum(TaskStatus::class)],
            'progress' => [...$this->optionalOnPatchRule(), 'sometimes', 'integer', 'between:0,100'],
            'position' => [...$this->optionalOnPatchRule(), 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('assigned_to')) {
            $data['assigned_to'] = blank($this->input('assigned_to')) ? null : $this->input('assigned_to');
        }

        if ($this->isMethod('post') && ! $this->has('position')) {
            $data['position'] = 0;
        }

        if ($this->isMethod('post') && ! $this->has('progress')) {
            $data['progress'] = 0;
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    /**
     * Get the rules that make a field required for creates and optional for patches.
     *
     * @return list<string>
     */
    protected function requiredRule(): array
    {
        return $this->isMethod('patch') ? ['sometimes'] : ['required'];
    }

    /**
     * Get the rules that make a field optional on patches.
     *
     * @return list<string>
     */
    protected function optionalOnPatchRule(): array
    {
        return $this->isMethod('patch') ? ['sometimes'] : [];
    }
}
