<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Enums\TaskStatus;
use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Models\Project;
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

        $statusValues = $this->projectStatusValues($team);

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
            'status' => [...$this->requiredRule(), Rule::in($statusValues)],
            'progress' => [...$this->optionalOnPatchRule(), 'sometimes', 'integer', 'between:0,100'],
            'time_estimate' => [...$this->optionalOnPatchRule(), 'nullable', 'integer', 'min:0'],
            'position' => [...$this->optionalOnPatchRule(), 'nullable', 'integer', 'min:0'],
            'due_at' => [...$this->optionalOnPatchRule(), 'nullable', 'date'],
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

        if ($this->has('due_at')) {
            $data['due_at'] = blank($this->input('due_at')) ? null : $this->input('due_at');
        }

        if ($this->has('time_estimate')) {
            $data['time_estimate'] = blank($this->input('time_estimate')) ? null : $this->input('time_estimate');
        }

        if ($this->isMethod('post') && ! $this->has('position')) {
            $data['position'] = 0;
        }

        if ($this->isMethod('post') && ! $this->has('progress')) {
            $data['progress'] = 0;
        }

        if ($this->isMethod('post') && ! $this->has('status')) {
            $team = $this->route('current_team');

            if ($team instanceof Team) {
                $data['status'] = $this->projectStatusValues($team)[0] ?? TaskStatus::Planned->value;
            }
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

    /**
     * @return list<string>
     */
    private function projectStatusValues(Team $team): array
    {
        $projectId = $this->input('project_id');

        if ($projectId === null && $this->route('task') !== null) {
            $task = Task::query()
                ->whereBelongsTo($team)
                ->find($this->route('task'));

            if ($task instanceof Task) {
                $projectId = $task->project_id;
            }
        }

        return Project::taskStatusValuesFor($team, $projectId);
    }
}
