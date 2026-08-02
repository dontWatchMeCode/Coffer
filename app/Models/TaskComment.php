<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasRteBlocks;
use Database\Factories\TaskCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['team_id', 'task_id', 'user_id', 'source', 'mcp_token_id', 'mcp_token_name'])]
class TaskComment extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<TaskCommentFactory> */
    use HasFactory;

    use HasRteBlocks;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task_comments')
            ->logOnly(['updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function afterBlocksSynced(): void
    {
        $this->disableLogging();
        $this->touch();
        $this->enableLogging();
        $this->unsetRelation('blocks');
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::deleting(function (TaskComment $comment): void {
            $comment->blocks()->delete();
        });

        static::saving(function (TaskComment $comment): void {
            if (! $comment->isDirty('task_id') && ! $comment->isDirty('team_id')) {
                if (! $comment->isDirty('user_id')) {
                    return;
                }

                static::ensureUserBelongsToTeam($comment);

                return;
            }

            $task = Task::withoutGlobalScopes()->find($comment->task_id);

            if ($task === null) {
                throw new LogicException('The selected task does not exist.');
            }

            if ((int) $task->team_id !== (int) $comment->team_id) {
                throw new LogicException('The comment must belong to the same team as its task.');
            }

            static::ensureUserBelongsToTeam($comment);
        });
    }

    /**
     * Ensure the comment author belongs to the owning team.
     */
    protected static function ensureUserBelongsToTeam(TaskComment $comment): void
    {
        $belongsToTeam = Membership::userBelongsToTeam((int) $comment->user_id, (int) $comment->team_id);

        if (! $belongsToTeam) {
            throw new LogicException('The comment author must belong to the comment team.');
        }
    }

    /**
     * Get the task that owns the comment.
     *
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who wrote the comment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
