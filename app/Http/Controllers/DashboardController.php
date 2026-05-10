<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon as CarbonBase;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $user->loadTeamContext();

        $team = $request->route('current_team');
        $currentTeam = $team instanceof Team ? $team : $user->currentTeam;

        if (! $currentTeam instanceof Team) {
            return Inertia::render('Dashboard', [
                'dashboard' => null,
            ]);
        }

        $today = CarbonImmutable::today();

        return Inertia::render('Dashboard', [
            'dashboard' => [
                'stats' => $this->stats($currentTeam, $today),
                'today' => [
                    'tasks' => $this->dueTasks($currentTeam, $today),
                    'events' => $this->todayEvents($currentTeam, $today),
                ],
                'recent' => [
                    'activity' => $this->recentTasks($currentTeam),
                ],
            ],
        ]);
    }

    /**
     * @return array{openTasks: int, dueToday: int, overdue: int, eventsToday: int, activeProjects: int}
     */
    private function stats(Team $team, CarbonImmutable $today): array
    {
        return [
            'openTasks' => $this->openTasksQuery($team)->count(),
            'dueToday' => $this->openTasksQuery($team)->whereDate('due_at', $today)->count(),
            'overdue' => $this->openTasksQuery($team)->whereDate('due_at', '<', $today)->count(),
            'eventsToday' => CalendarEvent::query()->whereBelongsTo($team)->whereDate('date', $today)->count(),
            'activeProjects' => Project::query()->whereBelongsTo($team)->where('archived', false)->count(),
        ];
    }

    /**
     * @return list<array{id: int, title: string, status: string, project: string|null, dueAt: string|null, isOverdue: bool, url: string}>
     */
    private function dueTasks(Team $team, CarbonImmutable $today): array
    {
        /** @var EloquentCollection<int, Task> $tasks */
        $tasks = $this->openTasksQuery($team)
            ->whereDate('due_at', '<=', $today)
            ->with('project:id,name')
            ->orderBy('due_at')
            ->limit(6)
            ->get();

        return array_values(
            $tasks
                ->map(fn (Task $task): array => [
                    'id' => (int) $task->id,
                    'title' => $task->title,
                    'status' => (string) $task->getRawOriginal('status'),
                    'project' => $task->project?->name,
                    'dueAt' => $this->date($task->due_at),
                    'isOverdue' => $task->due_at !== null && $this->toCarbon($task->due_at)->isBefore($today),
                    'url' => route('team.tasks.edit', ['current_team' => $team, 'project' => $task->project_id, 'task' => $task]),
                ])
                ->all()
        );
    }

    /**
     * @return list<array{id: int, title: string, time: string|null, url: string}>
     */
    private function todayEvents(Team $team, CarbonImmutable $today): array
    {
        return array_values(
            CalendarEvent::query()
                ->whereBelongsTo($team)
                ->whereDate('date', $today)
                ->orderBy('time')
                ->orderBy('title')
                ->limit(6)
                ->get(['id', 'title', 'time'])
                ->map(fn (CalendarEvent $event): array => [
                    'id' => (int) $event->id,
                    'title' => $event->title,
                    'time' => $event->time,
                    'url' => route('team.calendar.events.edit', ['current_team' => $team, 'event' => $event]),
                ])
                ->all()
        );
    }

    /**
     * @return list<array{id: int, type: string, title: string, subtitle: string|null, updatedAt: string|null, url: string}>
     */
    private function recentTasks(Team $team): array
    {
        return array_values(
            Task::query()
                ->whereBelongsTo($team)
                ->with('project:id,name')
                ->latest('updated_at')
                ->limit(8)
                ->get()
                ->map(fn (Task $task): array => [
                    'id' => (int) $task->id,
                    'type' => 'Task',
                    'title' => $task->title,
                    'subtitle' => $task->project?->name,
                    'updatedAt' => $this->dateTime($task->updated_at),
                    'url' => route('team.tasks.edit', ['current_team' => $team, 'project' => $task->project_id, 'task' => $task]),
                ])
                ->all()
        );
    }

    /**
     * @return Builder<Task>
     */
    private function openTasksQuery(Team $team): Builder
    {
        return Task::query()
            ->whereBelongsTo($team)
            ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Dropped->value]);
    }

    private function toCarbon(mixed $value): CarbonInterface
    {
        return $value instanceof CarbonInterface ? $value : CarbonBase::parse($value);
    }

    private function date(mixed $value): ?string
    {
        return $value instanceof CarbonInterface ? $value->toDateString() : null;
    }

    private function dateTime(mixed $value): ?string
    {
        return $value instanceof CarbonInterface ? $value->toISOString() : null;
    }
}
