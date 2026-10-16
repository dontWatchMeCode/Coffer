<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Models\Project;
use App\Models\Team;
use App\Validation\DomainRecordValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveProjectRequest extends FormRequest
{
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
            'project',
            $this->isMethod('patch'),
            $team,
            $this->all(),
            $this->routeProject($team),
        );
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

    private function routeProject(Team $team): ?Project
    {
        $routeProject = $this->route('project');

        if ($routeProject instanceof Project) {
            return (int) $routeProject->team_id === (int) $team->id ? $routeProject : null;
        }

        if (! is_numeric($routeProject)) {
            return null;
        }

        return Project::query()
            ->whereBelongsTo($team)
            ->find((int) $routeProject);
    }
}
