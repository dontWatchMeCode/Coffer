<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Validation\DomainRecordValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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

        return DomainRecordValidation::rulesFor(
            'task',
            $this->isMethod('patch'),
            $team,
            $this->all(),
            $this->routeTask($team),
            requiredWhenPresent: [
                'project_id' => false,
                'title' => false,
                'status' => false,
            ],
        );
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

    private function routeTask(Team $team): ?Task
    {
        $routeTask = $this->route('task');

        if ($routeTask instanceof Task) {
            return (int) $routeTask->team_id === (int) $team->id ? $routeTask : null;
        }

        if (! is_numeric($routeTask)) {
            return null;
        }

        return Task::query()
            ->whereBelongsTo($team)
            ->find((int) $routeTask);
    }

    /**
     * @return list<string>
     */
    private function projectStatusValues(Team $team): array
    {
        $projectId = $this->input('project_id');

        if ($projectId === null) {
            $projectId = $this->routeTask($team)?->project_id;
        }

        return Project::taskStatusValuesFor($team, $projectId);
    }
}
