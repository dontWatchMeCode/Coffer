<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tasks;

use App\Enums\InsightsTimeRange;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class TaskInsightsController extends Controller
{
    public function index(Request $request, Team $currentTeam): Response
    {
        $range = InsightsTimeRange::fromRequest($request);
        $today = CarbonImmutable::today();

        $window = $range->window($today);
        $project = $request->filled('project')
            ? Project::query()
                ->whereBelongsTo($currentTeam)
                ->findOrFail($request->integer('project'))
            : null;

        /** @var EloquentCollection<int, Task> $tasks */
        $tasks = $this->scopedTasksQuery($currentTeam, $project)
            ->whereBetween('created_at', [$window['start']->startOfDay()->toDateTimeString(), $window['end']->endOfDay()->toDateTimeString()])
            ->with('assignee:id,name')
            ->get();

        $overall = $this->overallKpis($currentTeam, $today, $project);
        $projects = Project::query()
            ->whereBelongsTo($currentTeam)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('tasks/Insights', [
            'range' => $range->value,
            'rangeOptions' => InsightsTimeRange::options(),
            'selectedProjectId' => $project?->id,
            'projectOptions' => $projects->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
            ])->values()->all(),
            'insights' => [
                'kpis' => $overall,
                'statusDistribution' => $this->statusDistribution($tasks),
                'assignmentDistribution' => $this->assignmentDistribution($tasks),
                'createdTrend' => $this->createdTrend($tasks, $window),
            ],
        ]);
    }

    /**
     * @return array{completionRate: float, overdue: int, totalOpen: int}
     */
    private function overallKpis(Team $team, CarbonImmutable $today, ?Project $project): array
    {
        $total = $this->scopedTasksQuery($team, $project)
            ->count();
        $completed = $this->scopedTasksQuery($team, $project)
            ->where('status', TaskStatus::Completed->value)
            ->count();

        $open = $this->openTasksQuery($team, $project)->count();
        $overdue = $this->openTasksQuery($team, $project)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', $today)
            ->count();

        return [
            'completionRate' => $total > 0 ? round($completed / $total * 100, 1) : 0.0,
            'overdue' => $overdue,
            'totalOpen' => $open,
        ];
    }

    /**
     * @param  EloquentCollection<int, Task>  $tasks
     * @return list<array{status: string, label: string, count: int}>
     */
    private function statusDistribution(EloquentCollection $tasks): array
    {
        /** @var Collection<string, int> $grouped */
        $grouped = $tasks
            ->groupBy(fn (Task $task): string => (string) $task->getRawOriginal('status'))
            ->map(fn (Collection $items): int => $items->count());

        return array_values(collect(TaskStatus::cases())
            ->map(fn (TaskStatus $status): array => [
                'status' => $status->value,
                'label' => str($status->value)->headline()->toString(),
                'count' => $grouped->get($status->value, 0),
            ])
            ->all());
    }

    /**
     * @param  EloquentCollection<int, Task>  $tasks
     * @return list<array{assignee: string, count: int}>
     */
    private function assignmentDistribution(EloquentCollection $tasks): array
    {
        /** @var Collection<string, int> $grouped */
        $grouped = $tasks
            ->groupBy(function (Task $task): string {
                $assignee = $task->assignee;

                return $assignee !== null ? $assignee->name : 'Unassigned';
            })
            ->map(fn (Collection $items): int => $items->count());

        return array_values($grouped
            ->sortByDesc(fn (int $count): int => $count)
            ->map(fn (int $count, string $assignee): array => [
                'assignee' => $assignee,
                'count' => $count,
            ])
            ->all());
    }

    /**
     * @param  EloquentCollection<int, Task>  $tasks
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $window
     * @return list<array{month: string, created: int}>
     */
    private function createdTrend(EloquentCollection $tasks, array $window): array
    {
        /** @var array<string, array{month: string, created: int}> $byMonth */
        $byMonth = array_map(
            fn (array $row): array => ['month' => $row['month'], 'created' => 0],
            InsightsTimeRange::monthBuckets($window),
        );

        foreach ($tasks as $task) {
            $createdAt = $task->getAttribute('created_at');
            if (! $createdAt instanceof CarbonInterface) {
                continue;
            }

            $key = $createdAt->format('Y-m');
            if (! isset($byMonth[$key])) {
                continue;
            }

            $byMonth[$key]['created']++;
        }

        return array_map(
            fn (array $row): array => [
                'month' => $row['month'],
                'created' => (int) $row['created'],
            ],
            array_values($byMonth),
        );
    }

    /**
     * @return Builder<Task>
     */
    private function openTasksQuery(Team $team, ?Project $project): Builder
    {
        return $this->scopedTasksQuery($team, $project)
            ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Dropped->value]);
    }

    /**
     * @return Builder<Task>
     */
    private function scopedTasksQuery(Team $team, ?Project $project): Builder
    {
        $query = Task::query()->whereBelongsTo($team);

        if ($project instanceof Project) {
            $query->whereBelongsTo($project);
        }

        return $query;
    }
}
