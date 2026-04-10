<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\SaveTaskRequest;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    /**
     * Store a newly created task.
     */
    public function store(SaveTaskRequest $request, Team $currentTeam): RedirectResponse
    {
        $validated = $request->validated();

        Task::create([
            ...$validated,
            'team_id' => $currentTeam->id,
            'created_by' => $request->user()?->id,
        ]);

        return to_route('team.tasks.show', ['current_team' => $currentTeam, 'project' => $validated['project_id']]);
    }

    /**
     * Update the specified task.
     */
    public function update(SaveTaskRequest $request, Team $currentTeam, int $task): RedirectResponse
    {
        $task = Task::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($task);
        $validated = $request->validated();

        $task->update($validated);

        $referer = $request->headers->get('referer', '');

        if (! str_contains((string) $referer, '/tasks/'.$task->id.'/edit')) {
            return back();
        }

        return to_route('team.tasks.edit', ['current_team' => $currentTeam, 'project' => $validated['project_id'] ?? $task->project_id, 'task' => $task]);
    }
}
