<?php

declare(strict_types=1);

namespace App\Http\Controllers\Calendar;

use App\Enums\InsightsTimeRange;
use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarInsightsController extends Controller
{
    public function index(Request $request, Team $currentTeam): Response
    {
        $range = InsightsTimeRange::fromRequest($request);
        $today = CarbonImmutable::today();

        $window = $range->window($today);

        /** @var EloquentCollection<int, CalendarEvent> $events */
        $events = CalendarEvent::query()
            ->whereBelongsTo($currentTeam)
            ->where('date', '>=', $window['start']->toDateString())
            ->where('date', '<', $window['end']->addDay()->toDateString())
            ->get();

        return Inertia::render('calendar/Insights', [
            'range' => $range->value,
            'rangeOptions' => InsightsTimeRange::options(),
            'insights' => [
                'kpis' => $this->kpis($currentTeam, $today),
                'eventsPerMonth' => $this->eventsPerMonth($events, $window),
                'upcoming' => $this->upcoming($currentTeam, $today),
            ],
        ]);
    }

    /**
     * @return array{thisMonth: int, next7Days: int, next30Days: int}
     */
    private function kpis(Team $team, CarbonImmutable $today): array
    {
        $monthStart = $today->copy()->startOfMonth();

        return [
            'thisMonth' => $this->countEvents($team, $monthStart, $monthStart->addMonth()),
            'next7Days' => $this->countEvents($team, $today, $today->addDays(8)),
            'next30Days' => $this->countEvents($team, $today, $today->addDays(31)),
        ];
    }

    private function countEvents(Team $team, CarbonImmutable $start, CarbonImmutable $endExclusive): int
    {
        return CalendarEvent::query()
            ->whereBelongsTo($team)
            ->where('date', '>=', $start->toDateString())
            ->where('date', '<', $endExclusive->toDateString())
            ->count();
    }

    /**
     * @param  EloquentCollection<int, CalendarEvent>  $events
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $window
     * @return list<array{month: string, count: int}>
     */
    private function eventsPerMonth(EloquentCollection $events, array $window): array
    {
        /** @var array<string, array{month: string, count: int}> $byMonth */
        $byMonth = array_map(
            fn (array $row): array => ['month' => $row['month'], 'count' => 0],
            InsightsTimeRange::monthBuckets($window),
        );

        foreach ($events as $event) {
            $date = $event->getAttribute('date');
            if (! $date instanceof CarbonInterface) {
                continue;
            }

            $key = $date->format('Y-m');
            if (! isset($byMonth[$key])) {
                continue;
            }

            $byMonth[$key]['count']++;
        }

        return array_map(
            fn (array $row): array => [
                'month' => $row['month'],
                'count' => (int) $row['count'],
            ],
            array_values($byMonth),
        );
    }

    /**
     * @return list<array{id: int, title: string, date: string|null, time: string|null, url: string}>
     */
    private function upcoming(Team $team, CarbonImmutable $today): array
    {
        /** @var EloquentCollection<int, CalendarEvent> $events */
        $events = CalendarEvent::query()
            ->whereBelongsTo($team)
            ->where('date', '>=', $today->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->limit(8)
            ->get(['id', 'title', 'date', 'time']);

        return array_values($events->map(fn (CalendarEvent $event): array => [
            'id' => (int) $event->id,
            'title' => $event->title,
            'date' => $this->formatDate($event),
            'time' => $this->formatTime($event->time),
            'url' => route('team.calendar.events.edit', ['current_team' => $team, 'event' => $event]),
        ])->all());
    }

    private function formatDate(CalendarEvent $event): ?string
    {
        $date = $event->getAttribute('date');

        return $date instanceof CarbonInterface ? $date->toDateString() : null;
    }

    private function formatTime(mixed $time): ?string
    {
        if (! is_string($time) || $time === '') {
            return null;
        }

        return substr($time, 0, 5);
    }
}
