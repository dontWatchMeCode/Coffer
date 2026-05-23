<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TaskPageDataService
{
    /**
     * Get the team projects used by the tasks pages.
     *
     * @return Collection<int, Project>
     */
    public function projectsWithCounts(Team $currentTeam): Collection
    {
        return Project::query()
            ->whereBelongsTo($currentTeam)
            ->withCount('tasks')
            ->withCount([
                'tasks as open_tasks_count' => fn ($query) => $query
                    ->whereNot('status', TaskStatus::Completed->value)
                    ->whereNot('status', TaskStatus::Dropped->value),
                'tasks as closed_tasks_count' => fn ($query) => $query
                    ->whereIn('status', [
                        TaskStatus::Completed->value,
                        TaskStatus::Dropped->value,
                    ]),
            ])
            ->orderBy('archived')
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve a project that belongs to the current team.
     */
    public function findProject(Team $currentTeam, int $projectId): Project
    {
        return Project::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($projectId);
    }

    /**
     * Transform projects for the frontend.
     *
     * @param  Collection<int, Project>|array<int, Project>  $projects
     * @return list<array<string, mixed>>
     */
    public function projectData(Collection|array $projects): array
    {
        return array_values(array_map(
            $this->projectPayload(...),
            is_array($projects) ? $projects : [...$projects],
        ));
    }

    /**
     * Build overview stats.
     *
     * @param  list<array<string, mixed>>  $projectData
     * @return array<string, int>
     */
    public function stats(array $projectData): array
    {
        return [
            'projectCount' => count($projectData),
            'activeProjectCount' => count(array_filter($projectData, fn (array $project): bool => ! $project['isArchived'])),
            'openTaskCount' => array_sum(array_map(fn (array $project): int => $project['openTasksCount'] ?? 0, $projectData)),
            'closedTaskCount' => array_sum(array_map(
                fn (array $project): int => $project['closedTasksCount'] ?? 0,
                $projectData,
            )),
        ];
    }

    /**
     * Transform a project for the frontend.
     *
     * @return array<string, mixed>
     */
    public function projectPayload(Project $project): array
    {
        $payload = [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'isArchived' => (bool) $project->getAttribute('archived'),
        ];

        if ($project->offsetExists('tasks_count')) {
            $payload['tasksCount'] = (int) $project->getAttribute('tasks_count');
        }

        if ($project->offsetExists('open_tasks_count')) {
            $payload['openTasksCount'] = (int) $project->getAttribute('open_tasks_count');
        }

        if ($project->offsetExists('closed_tasks_count')) {
            $payload['closedTasksCount'] = (int) $project->getAttribute('closed_tasks_count');
        }

        return $payload;
    }

    /**
     * Transform a task for the frontend.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function taskPayload(Task $task, array $extra = []): array
    {
        $completedAt = $task->getAttribute('completed_at');
        $dueAt = $task->getAttribute('due_at');
        $updatedAt = $task->getAttribute('updated_at');

        return [
            'id' => $task->id,
            'projectId' => $task->project_id,
            'projectName' => $task->project?->name,
            'title' => $task->title,
            'description' => $task->description,
            'status' => (string) $task->getRawOriginal('status'),
            'progress' => $task->progress,
            'timeEstimate' => $task->time_estimate,
            'position' => $task->position,
            'assigneeId' => $task->assigned_to,
            'assigneeName' => $task->assignee?->name,
            'creatorId' => $task->created_by,
            'creatorName' => $task->creator?->name,
            'updatedAt' => $updatedAt instanceof DateTimeInterface
                ? $updatedAt->format(DateTimeInterface::ATOM)
                : null,
            'completedAt' => $completedAt instanceof DateTimeInterface
                ? $completedAt->format(DateTimeInterface::ATOM)
                : null,
            'dueAt' => $dueAt instanceof DateTimeInterface
                ? $dueAt->format(DateTimeInterface::ATOM)
                : null,
            ...$extra,
        ];
    }

    /**
     * Transform a task comment for the frontend.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function commentPayload(TaskComment $comment, array $extra = []): array
    {
        $createdAt = $comment->getAttribute('created_at');
        $updatedAt = $comment->getAttribute('updated_at');

        return [
            'id' => $comment->id,
            'taskId' => $comment->task_id,
            'userId' => $comment->user_id,
            'userName' => $comment->user?->name,
            'blocks' => $comment->blocks->map(fn ($block): array => $block->toPayloadArray())->all(),
            'source' => $comment->source ?? 'user',
            'mcpTokenName' => $comment->mcp_token_name,
            'createdAt' => $createdAt instanceof DateTimeInterface
                ? $createdAt->format(DateTimeInterface::ATOM)
                : null,
            'updatedAt' => $updatedAt instanceof DateTimeInterface
                ? $updatedAt->format(DateTimeInterface::ATOM)
                : null,
            ...$extra,
        ];
    }

    /**
     * Transform team members for the frontend.
     *
     * @param  Collection<int, User>  $members
     * @return list<array{id: int, name: string, email: string}>
     */
    public function memberPayload(Collection $members): array
    {
        return array_values(array_map(fn (User $member): array => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
        ], $members->all()));
    }

    /**
     * Transform available task statuses for the frontend.
     *
     * @return list<array{value: string, label: string}>
     */
    public function statusPayload(): array
    {
        return array_map(fn (TaskStatus $status): array => [
            'value' => $status->value,
            'label' => Str::headline($status->value),
        ], TaskStatus::cases());
    }
}
