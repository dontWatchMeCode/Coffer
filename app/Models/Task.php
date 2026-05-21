<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\Filterable;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['team_id', 'project_id', 'assigned_to', 'created_by', 'title', 'description', 'status', 'progress', 'position', 'due_at'])]
class Task extends Model implements LinkableRecord
{
    use BelongsToTeam;
    use Filterable;

    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tasks')
            ->logOnly(['project_id', 'assigned_to', 'created_by', 'title', 'description', 'status', 'progress', 'position', 'due_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::saving(function (Task $task): void {
            static::syncCompletionTimestamp($task);

            if ($task->isDirty('project_id') || $task->isDirty('team_id')) {
                $project = Project::withoutGlobalScopes()->find($task->project_id);

                if ($project === null) {
                    throw new LogicException('The selected project does not exist.');
                }

                if ((int) $project->team_id !== (int) $task->team_id) {
                    throw new LogicException('The task must belong to the same team as its project.');
                }
            }

            if ($task->isDirty('created_by') || $task->isDirty('team_id')) {
                static::ensureCreatorBelongsToTeam($task);
            }

            if (! $task->isDirty('assigned_to') && ! $task->isDirty('team_id')) {
                return;
            }

            if ($task->assigned_to === null) {
                return;
            }

            if (! Membership::userBelongsToTeam((int) $task->assigned_to, (int) $task->team_id)) {
                throw new LogicException('The assignee must belong to the task team.');
            }
        });
    }

    /**
     * Ensure the task creator belongs to the owning team.
     */
    protected static function ensureCreatorBelongsToTeam(Task $task): void
    {
        if ($task->created_by === null) {
            return;
        }

        if (! Membership::userBelongsToTeam((int) $task->created_by, (int) $task->team_id)) {
            throw new LogicException('The task creator must belong to the task team.');
        }
    }

    /**
     * Sync the completion timestamp with the current status.
     */
    protected static function syncCompletionTimestamp(Task $task): void
    {
        if (! $task->isDirty('status')) {
            return;
        }

        $newStatus = $task->getAttributes()['status'] ?? $task->getRawOriginal('status');
        $status = is_string($newStatus) ? TaskStatus::tryFrom($newStatus) : null;

        if ($status === TaskStatus::Completed) {
            $task->completed_at ??= now()->toDateTimeString();

            return;
        }

        $task->completed_at = null;
    }

    /**
     * Get the project that owns the task.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the assigned user.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created the task.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the comments for the task.
     *
     * @return HasMany<TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'completed_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }
}
