<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\SaveProjectRequest;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    /**
     * Store a newly created project.
     */
    public function store(SaveProjectRequest $request, Team $currentTeam): RedirectResponse
    {
        $this->authorize('create', Project::class);

        Project::create([
            ...$request->validated(),
            'team_id' => $currentTeam->id,
        ]);

        return to_route('team.tasks.index', ['current_team' => $currentTeam]);
    }

    /**
     * Update the specified project.
     */
    public function update(SaveProjectRequest $request, Team $currentTeam, int $project): RedirectResponse
    {
        $project = Project::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($project);

        $this->authorize('update', $project);

        $project->update($request->validated());

        return to_route('team.tasks.show', ['current_team' => $currentTeam, 'project' => $project]);
    }
}
