<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProjectRequest extends FormRequest
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

        $projectId = $this->route('project');

        $team = $this->currentTeam();

        return filled($projectId) && $team instanceof Team && Project::query()
            ->whereBelongsTo($team)
            ->whereKey($projectId)
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
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('archived')) {
            return;
        }

        $this->merge([
            'archived' => $this->boolean('archived'),
        ]);
    }
}
