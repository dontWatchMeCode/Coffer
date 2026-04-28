<?php

namespace App\Http\Controllers\Calendar;

use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarPageController extends Controller
{
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

    public function index(Request $request, Team $currentTeam): Response
    {
        $events = CalendarEvent::query()
            ->whereBelongsTo($currentTeam)
            ->orderBy('date')
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('calendar/Index', [
            'events' => $events->map(fn (CalendarEvent $event): array => $this->formatEvent($event, includeTimestamps: true))->values()->all(),
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
        ]);
    }

    /**
     * @return array{id: int, title: string, description: string|null, date: string|null, createdAt?: string|null, updatedAt?: string|null}
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
