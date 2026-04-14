<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\SaveTaskCommentRequest;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class TaskCommentController extends Controller
{
    /**
     * Store a newly created task comment.
     */
    public function store(SaveTaskCommentRequest $request, Team $currentTeam, int $task): RedirectResponse
    {
        $task = Task::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($task);

        $task->comments()->create([
            'team_id' => $currentTeam->id,
            'user_id' => $request->user()->id,
            'body' => $request->validated()['body'],
        ]);

        return back();
    }
}
