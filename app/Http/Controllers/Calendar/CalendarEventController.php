<?php

declare(strict_types=1);

namespace App\Http\Controllers\Calendar;

use App\Concerns\HandlesTrashedRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\DeleteCalendarEventRequest;
use App\Http\Requests\Calendar\SaveCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class CalendarEventController extends Controller
{
    use HandlesTrashedRecords;

    public function store(SaveCalendarEventRequest $request, Team $currentTeam): RedirectResponse
    {
        $this->authorize('create', CalendarEvent::class);

        CalendarEvent::create([
            ...$request->validated(),
            'team_id' => $currentTeam->id,
        ]);

        return back();
    }

    public function update(SaveCalendarEventRequest $request, Team $currentTeam, int $event): RedirectResponse
    {
        $event = CalendarEvent::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($event);

        $this->authorize('update', $event);

        $event->update($request->validated());

        return back();
    }

    public function destroy(DeleteCalendarEventRequest $request, Team $currentTeam, int $event): RedirectResponse
    {
        $event = CalendarEvent::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($event);

        $this->authorize('delete', $event);

        $event->delete();

        return to_route('team.calendar.index', ['current_team' => $currentTeam]);
    }

    public function restore(Team $currentTeam, int $event): RedirectResponse
    {
        return $this->restoreTrashedRecord($currentTeam, $event, CalendarEvent::class, 'team.calendar.trash');
    }

    public function forceDestroy(Team $currentTeam, int $event): RedirectResponse
    {
        return $this->forceDeleteTrashedRecord($currentTeam, $event, CalendarEvent::class, 'team.calendar.trash');
    }
}
