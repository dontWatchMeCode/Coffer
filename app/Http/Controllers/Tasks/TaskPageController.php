<?php

namespace App\Http\Controllers\Tasks;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TaskPageController extends Controller
{
    /**
     * Display the task management page for the current team.
     */
    public function index(Request $request, Team $currentTeam): Response
    {
        $projects = $this->teamProjects($currentTeam);
        $projectData = $this->projectData($projects);

        return Inertia::render('tasks/Index', [
            'projects' => $projectData,
            'stats' => $this->stats($projectData),
        ]);
    }

    /**
     * Display the task edit page.
     */
    public function edit(Request $request, Team $currentTeam, int $project, int $task): Response
    {
        $project = $this->findTeamProject($currentTeam, $project);

        $task = Task::query()
            ->whereBelongsTo($currentTeam)
            ->whereBelongsTo($project)
            ->with([
                'project:id,name',
                'assignee:id,name',
                'creator:id,name',
                'comments' => fn ($query) => $query
                    ->with('user:id,name')
                    ->latest(),
            ])
            ->withCount('comments')
            ->findOrFail($task);

        $members = $currentTeam->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']);

        $projects = Project::query()
            ->whereBelongsTo($currentTeam)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('tasks/Edit', [
            'project' => $this->projectPayloadWithCounts($project),
            'task' => $this->taskPayload($task, [
                'commentsCount' => $task->comments_count ?? 0,
            ]),
            'comments' => array_map(
                $this->commentPayload(...),
                $task->comments->all(),
            ),
            'members' => $this->memberPayload($members),
            'statuses' => $this->statusPayload(),
            'projects' => array_values(array_map(fn (Project $p): array => [
                'id' => $p->id,
                'name' => $p->name,
            ], $projects->all())),
        ]);
    }

    /**
     * Display the selected project task page.
     */
    public function show(Request $request, Team $currentTeam, int $project): Response
    {
        $project = $this->findTeamProject($currentTeam, $project);

        $tasks = Task::query()
            ->whereBelongsTo($currentTeam)
            ->whereBelongsTo($project)
            ->with(['project:id,name', 'assignee:id,name', 'creator:id,name'])
            ->withCount('comments')
            ->orderBy('status')
            ->orderBy('position')
            ->orderByDesc('updated_at')
            ->get();

        $members = $currentTeam->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']);

        return Inertia::render('tasks/Show', [
            'project' => $this->projectPayloadWithCounts($project),
            'tasks' => array_map(fn (Task $task): array => $this->taskPayload($task, [
                'commentsCount' => $task->comments_count ?? 0,
            ]), $tasks->all()),
            'members' => $this->memberPayload($members),
            'statuses' => $this->statusPayload(),
        ]);
    }

    /**
     * Transform a project for the frontend.
     *
     * @return array<string, mixed>
     */
    protected function projectPayloadWithCounts(Project $project): array
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
    protected function taskPayload(Task $task, array $extra = []): array
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
     * @return array<string, mixed>
     */
    protected function commentPayload(TaskComment $comment): array
    {
        $createdAt = $comment->getAttribute('created_at');
        $updatedAt = $comment->getAttribute('updated_at');

        return [
            'id' => $comment->id,
            'taskId' => $comment->task_id,
            'userId' => $comment->user_id,
            'userName' => $comment->user?->name,
            'body' => $comment->body,
            'createdAt' => $createdAt instanceof DateTimeInterface
                ? $createdAt->format(DateTimeInterface::ATOM)
                : null,
            'updatedAt' => $updatedAt instanceof DateTimeInterface
                ? $updatedAt->format(DateTimeInterface::ATOM)
                : null,
        ];
    }

    /**
     * Transform team members for the frontend.
     *
     * @param  Collection<int, User>  $members
     * @return list<array{id: int, name: string, email: string}>
     */
    protected function memberPayload(Collection $members): array
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
    protected function statusPayload(): array
    {
        return array_map(fn (TaskStatus $status): array => [
            'value' => $status->value,
            'label' => Str::headline($status->value),
        ], TaskStatus::cases());
    }

    /**
     * Get the team projects used by the tasks pages.
     *
     * @return Collection<int, Project>
     */
    protected function teamProjects(Team $currentTeam): Collection
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
    protected function findTeamProject(Team $currentTeam, int $projectId): Project
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
    protected function projectData(Collection|array $projects): array
    {
        return array_values(array_map(
            $this->projectPayloadWithCounts(...),
            is_array($projects) ? $projects : [...$projects],
        ));
    }

    /**
     * Build overview stats.
     *
     * @param  list<array<string, mixed>>  $projectData
     * @return array<string, int>
     */
    protected function stats(array $projectData): array
    {
        return [
            'projectCount' => count($projectData),
            'activeProjectCount' => count(array_filter($projectData, fn (array $project): bool => ! $project['isArchived'])),
            'openTaskCount' => array_sum(array_map(fn (array $project): int => $project['openTasksCount'], $projectData)),
            'closedTaskCount' => array_sum(array_map(
                fn (array $project): int => $project['closedTasksCount'] ?? 0,
                $projectData,
            )),
        ];
    }
}
