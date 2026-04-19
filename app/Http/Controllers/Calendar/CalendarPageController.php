<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarPageController extends Controller
{
    public function index(Request $request, Team $currentTeam): Response
    {
        $events = CalendarEvent::query()
            ->whereBelongsTo($currentTeam)
            ->orderBy('date')
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('calendar/Index', [
            'events' => $events->map(function (CalendarEvent $event): array {
                $date = $event->getAttribute('date');
                $createdAt = $event->getAttribute('created_at');
                $updatedAt = $event->getAttribute('updated_at');

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => $date instanceof \DateTimeInterface
                        ? $date->format('Y-m-d')
                        : null,
                    'createdAt' => $createdAt instanceof \DateTimeInterface
                        ? $createdAt->format(\DateTimeInterface::ATOM)
                        : null,
                    'updatedAt' => $updatedAt instanceof \DateTimeInterface
                        ? $updatedAt->format(\DateTimeInterface::ATOM)
                        : null,
                ];
            })->values()->all(),
        ]);
    }

    public function edit(Request $request, Team $currentTeam, int $event): Response
    {
        $event = CalendarEvent::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($event);

        $date = $event->getAttribute('date');

        return Inertia::render('calendar/Edit', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'date' => $date instanceof \DateTimeInterface
                    ? $date->format('Y-m-d')
                    : null,
            ],
        ]);
    }
}
