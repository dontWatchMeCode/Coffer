<?php

declare(strict_types=1);

namespace App\Http\Controllers\Log;

use App\Http\Controllers\Controller;
use App\Http\Requests\Log\DeleteLogEntryRequest;
use App\Http\Requests\Log\SaveLogEntryRequest;
use App\Models\LogEntry;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class LogEntryController extends Controller
{
    public function store(SaveLogEntryRequest $request, Team $currentTeam): RedirectResponse
    {
        $this->authorize('create', LogEntry::class);

        LogEntry::create([
            ...$request->validated(),
            'team_id' => $currentTeam->id,
        ]);

        return back();
    }

    public function destroy(DeleteLogEntryRequest $request, Team $currentTeam, int $logEntry): RedirectResponse
    {
        $entry = LogEntry::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($logEntry);

        $this->authorize('delete', $entry);

        $entry->delete();

        return back();
    }

    public function restore(Team $currentTeam, int $logEntry): RedirectResponse
    {
        $entry = LogEntry::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($logEntry);

        $this->authorize('restore', $entry);

        $entry->restore();

        return to_route('team.log.trash', [
            'current_team' => $currentTeam,
        ]);
    }

    public function forceDestroy(Team $currentTeam, int $logEntry): RedirectResponse
    {
        $entry = LogEntry::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($logEntry);

        $this->authorize('forceDelete', $entry);

        $entry->forceDelete();

        return to_route('team.log.trash', [
            'current_team' => $currentTeam,
        ]);
    }
}
