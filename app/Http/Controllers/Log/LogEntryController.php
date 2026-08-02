<?php

declare(strict_types=1);

namespace App\Http\Controllers\Log;

use App\Concerns\HandlesTrashedRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Log\SaveLogEntryRequest;
use App\Models\LogEntry;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class LogEntryController extends Controller
{
    use HandlesTrashedRecords;

    public function store(SaveLogEntryRequest $request, Team $currentTeam): RedirectResponse
    {
        $this->authorize('create', LogEntry::class);

        LogEntry::create([
            ...$request->validated(),
            'team_id' => $currentTeam->id,
        ]);

        return back();
    }

    public function update(SaveLogEntryRequest $request, Team $currentTeam, int $logEntry): RedirectResponse
    {
        $entry = LogEntry::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($logEntry);

        $this->authorize('update', $entry);

        $entry->update($request->validated());

        return back();
    }

    public function destroy(Team $currentTeam, int $logEntry): RedirectResponse
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
        return $this->restoreTrashedRecord($currentTeam, $logEntry, LogEntry::class, 'team.log.trash');
    }

    public function forceDestroy(Team $currentTeam, int $logEntry): RedirectResponse
    {
        return $this->forceDeleteTrashedRecord($currentTeam, $logEntry, LogEntry::class, 'team.log.trash');
    }
}
