<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\DeleteTaskCommentRequest;
use App\Http\Requests\Tasks\SaveTaskCommentRequest;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
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

        $user = $request->user();
        abort_if($user === null, 401);

        $task->comments()->create([
            'team_id' => $currentTeam->id,
            'user_id' => $user->id,
            'body' => $request->validated()['body'],
        ]);

        return back();
    }

    /**
     * Update an existing task comment.
     */
    public function update(SaveTaskCommentRequest $request, Team $currentTeam, int $task, int $comment): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $comment = $this->resolveComment($currentTeam, $task, $comment, $user);

        $comment->update([
            'body' => $request->validated()['body'],
        ]);

        return back();
    }

    /**
     * Remove an existing task comment.
     */
    public function destroy(DeleteTaskCommentRequest $request, Team $currentTeam, int $task, int $comment): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $comment = $this->resolveComment($currentTeam, $task, $comment, $user);

        $comment->delete();

        return back();
    }

    protected function resolveComment(Team $currentTeam, int $taskId, int $commentId, User $user): TaskComment
    {
        $task = Task::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($taskId);

        return TaskComment::query()
            ->whereBelongsTo($currentTeam)
            ->whereBelongsTo($task)
            ->whereBelongsTo($user)
            ->findOrFail($commentId);
    }
}
