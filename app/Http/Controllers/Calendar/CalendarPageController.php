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
            'events' => $events->map(fn (CalendarEvent $event): array => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'date' => $event->date?->format('Y-m-d'),
                'createdAt' => $event->created_at?->format(\DateTimeInterface::ATOM),
                'updatedAt' => $event->updated_at?->format(\DateTimeInterface::ATOM),
            ])->values()->all(),
        ]);
    }
}
