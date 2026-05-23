<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Services\ActivityLogger;
use Database\Factories\TaskCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['team_id', 'task_id', 'user_id', 'source', 'mcp_token_id', 'mcp_token_name'])]
class TaskComment extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<TaskCommentFactory> */
    use HasFactory;

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task_comments')
            ->logOnly(['updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return MorphMany<RteBlock, $this>
     */
    public function blocks(): MorphMany
    {
        return $this->morphMany(RteBlock::class, 'blockable');
    }

    /**
     * @param  array<int, array{id?: int, type: string, position: int, payload?: array<string, mixed>|null}>  $blocks
     */
    public function syncBlocks(array $blocks): void
    {
        $this->getConnection()->transaction(function () use ($blocks): void {
            $existingIds = $this->blocks()->pluck('id')->all();
            $incomingIds = collect($blocks)
                ->filter(fn (array $block): bool => isset($block['id']))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => in_array($id, $existingIds, true))
                ->all();

            $removedBlocks = $this->blocks()
                ->whereNotIn('id', $incomingIds)
                ->get(['id', 'type', 'position', 'payload']);

            $removed = $removedBlocks
                ->map(fn (RteBlock $block): array => [
                    'type' => $block->type,
                    'position' => $block->position,
                    'payload' => $block->payload,
                ])
                ->values()
                ->all();

            foreach ($removedBlocks as $removedBlock) {
                $removedBlock->delete();
            }

            $existingBlocks = $this->blocks()
                ->whereIn('id', $incomingIds)
                ->get()
                ->keyBy('id');

            $added = [];
            $updated = [];

            foreach ($blocks as $block) {
                $data = [
                    'type' => $block['type'],
                    'position' => $block['position'],
                    'payload' => $block['payload'] ?? null,
                ];

                if (isset($block['id']) && in_array($block['id'], $existingIds, true)) {
                    $oldBlock = $existingBlocks->get($block['id']);
                    $oldType = $oldBlock?->type;
                    $oldPayload = $oldBlock?->payload;

                    $oldBlock?->fill($data)->save();

                    if ($oldType !== $data['type'] || ! $this->blockPayloadEquals($oldPayload, $data['payload'], $block['type'])) {
                        $updated[] = [
                            'type' => $block['type'],
                            'position' => $block['position'],
                            'old_payload' => $oldPayload,
                            'payload' => $block['payload'] ?? null,
                        ];
                    }
                } else {
                    $this->blocks()->create($data);
                    $added[] = $data;
                }
            }

            $this->disableLogging();
            $this->touch();
            $this->enableLogging();
            $this->unsetRelation('blocks');

            ActivityLogger::logBlockChanges($this, [
                'added' => $added,
                'updated' => $updated,
                'removed' => $removed,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private function blockPayloadEquals(?array $old, ?array $new, string $type): bool
    {
        if ($old === $new) {
            return true;
        }

        if ($old === null || $new === null) {
            return false;
        }

        if ($type === 'excalidraw') {
            return $this->excalidrawPayloadEquals($old, $new);
        }

        if ($type === 'text' || $type === 'mermaid') {
            return ($old['content'] ?? null) === ($new['content'] ?? null);
        }

        return $old == $new;
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function excalidrawPayloadEquals(array $old, array $new): bool
    {
        $stripViewport = function (array $payload): array {
            $scene = $payload['scene'] ?? [];
            $appState = $scene['appState'] ?? [];
            unset($appState['scrollX'], $appState['scrollY'], $appState['zoom']);
            $scene['appState'] = $appState;
            $payload['scene'] = $scene;

            return $payload;
        };

        return $stripViewport($old) === $stripViewport($new);
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
