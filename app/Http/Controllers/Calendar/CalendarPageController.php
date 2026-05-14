<?php

declare(strict_types=1);

namespace App\Http\Controllers\Calendar;

use App\Concerns\ProvidesActivityHistory;
use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarPageController extends Controller
{
    use ProvidesActivityHistory;
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function index(Request $request, Team $currentTeam): Response
    {
        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);
        $search = $request->string('search')->toString();

        $base = Carbon::create($year, $month, 1) ?? now();
        $start = $base->copy()->subMonth()->startOfMonth()->toDateString();
        $end = $base->copy()->addMonth()->endOfMonth()->toDateString();

        $calendarEvents = CalendarEvent::query()
            ->whereBelongsTo($currentTeam)
            ->when($search, fn ($q) => $q->search($search, ['title', 'description']))
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('time')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (CalendarEvent $event): array => $this->formatEvent($event, includeTimestamps: true))
            ->values()
            ->all();

        $paginatedEvents = CalendarEvent::query()
            ->whereBelongsTo($currentTeam)
            ->when($search, fn ($q) => $q->search($search, ['title', 'description']))
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->orderByDesc('updated_at')
            ->simplePaginate(25);

        return Inertia::render('calendar/Index', [
            'calendarEvents' => $calendarEvents,
            'events' => Inertia::scroll($paginatedEvents->through(fn (CalendarEvent $event): array => $this->formatEvent($event, includeTimestamps: true))),
        ]);
    }

    public function edit(Request $request, Team $currentTeam, int $event): Response
    {
        $event = CalendarEvent::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($event);

        return Inertia::render('calendar/Edit', [
            'event' => $this->formatEvent($event, includeTimestamps: true),
            'recordLinks' => $this->recordLinksPayload($event, $currentTeam),
            'recordTags' => $this->recordTagsPayload($event, $currentTeam),
            'activityHistory' => $this->activityHistoryPayload($event),
        ]);
    }

    /**
     * @return array{id: int, title: string, description: string|null, date: string|null, time: string|null, createdAt?: string|null, updatedAt?: string|null}
     */
    private function formatEvent(CalendarEvent $event, bool $includeTimestamps = false): array
    {
        $date = $event->getAttribute('date');
        $data = [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'date' => $date instanceof \DateTimeInterface
                ? $date->format('Y-m-d')
                : null,
            'time' => is_string($event->time) && $event->time !== ''
                ? substr($event->time, 0, 5)
                : null,
        ];

        if ($includeTimestamps) {
            $createdAt = $event->getAttribute('created_at');
            $updatedAt = $event->getAttribute('updated_at');

            $data['createdAt'] = $createdAt instanceof \DateTimeInterface
                ? $createdAt->format(\DateTimeInterface::ATOM)
                : null;
            $data['updatedAt'] = $updatedAt instanceof \DateTimeInterface
                ? $updatedAt->format(\DateTimeInterface::ATOM)
                : null;
        }

        return $data;
    }
}
