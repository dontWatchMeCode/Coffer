<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tasks;

use App\Concerns\ProvidesActivityHistory;
use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Services\TaskPageDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskPageController extends Controller
{
    use ProvidesActivityHistory;
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function __construct(private readonly TaskPageDataService $dataService) {}

    /**
     * Display the task management page for the current team.
     */
    public function index(Request $request, Team $currentTeam): Response
    {
        $projects = $this->dataService->projectsWithCounts($currentTeam);
        $projectData = $this->dataService->projectData($projects);

        return Inertia::render('tasks/Index', [
            'projects' => $projectData,
            'stats' => $this->dataService->stats($projectData),
        ]);
    }

    /**
     * Display the task edit page.
     */
    public function edit(Request $request, Team $currentTeam, int $project, int $task): Response
    {
        $project = $this->dataService->findProject($currentTeam, $project);

        $task = Task::query()
            ->whereBelongsTo($currentTeam)
            ->whereBelongsTo($project)
            ->with([
                'project:id,name',
                'assignee:id,name',
                'creator:id,name',
                'recordTags' => fn ($query) => $query->orderBy('name'),
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

        $comments = $task->comments->all();
        $commentActivities = $this->activityHistoryPayloadForModels($comments);

        return Inertia::render('tasks/Edit', [
            'project' => $this->dataService->projectPayload($project),
            'task' => $this->dataService->taskPayload($task, [
                'commentsCount' => $task->comments_count ?? 0,
            ]),
            'comments' => array_map(
                fn (TaskComment $comment): array => $this->dataService->commentPayload($comment, [
                    'activityHistory' => $commentActivities[$comment->getKey()] ?? [],
                ]),
                $comments,
            ),
            'members' => $this->dataService->memberPayload($members),
            'statuses' => $this->dataService->statusPayload(),
            'projects' => array_values(array_map(fn (Project $p): array => [
                'id' => $p->id,
                'name' => $p->name,
            ], $projects->all())),
            'recordLinks' => $this->recordLinksPayload($task, $currentTeam),
            'recordTags' => $this->recordTagsPayload($task, $currentTeam),
            'activityHistory' => $this->activityHistoryPayload($task),
        ]);
    }

    /**
     * Display the selected project task page.
     */
    public function show(Request $request, Team $currentTeam, int $project): Response
    {
        $project = $this->dataService->findProject($currentTeam, $project);
        $project->load(['recordTags' => fn ($query) => $query->orderBy('name')]);

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
            'project' => $this->dataService->projectPayload($project),
            'tasks' => array_map(fn (Task $task): array => $this->dataService->taskPayload($task, [
                'commentsCount' => $task->comments_count ?? 0,
            ]), $tasks->all()),
            'members' => $this->dataService->memberPayload($members),
            'statuses' => $this->dataService->statusPayload(),
            'recordLinks' => $this->recordLinksPayload($project, $currentTeam),
            'recordTags' => $this->recordTagsPayload($project, $currentTeam),
            'activityHistory' => $this->activityHistoryPayload($project),
        ]);
    }
}
