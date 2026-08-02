<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spreadsheets;

use App\Concerns\HandlesTrashedRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Spreadsheets\SaveSpreadsheetRequest;
use App\Http\Requests\Spreadsheets\SaveSpreadsheetWorkbookRequest;
use App\Models\SpreadsheetWorkbook;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class SpreadsheetController extends Controller
{
    use HandlesTrashedRecords;

    public function store(SaveSpreadsheetRequest $request, Team $currentTeam): RedirectResponse
    {
        $this->authorize('create', SpreadsheetWorkbook::class);

        $spreadsheet = SpreadsheetWorkbook::create([
            'team_id' => $currentTeam->id,
            'title' => $request->validated('title'),
            'snapshot' => SpreadsheetWorkbook::defaultSnapshot(),
        ]);

        return to_route('team.spreadsheets.show', [
            'current_team' => $currentTeam,
            'spreadsheet' => $spreadsheet,
        ]);
    }

    public function saveWorkbook(SaveSpreadsheetWorkbookRequest $request, Team $currentTeam, int $spreadsheet): RedirectResponse
    {
        $spreadsheet = $this->spreadsheetForTeam($currentTeam, $spreadsheet);

        $this->authorize('update', $spreadsheet);

        $spreadsheet->update($request->validated());

        return back();
    }

    public function destroy(Team $currentTeam, int $spreadsheet): RedirectResponse
    {
        $spreadsheet = $this->spreadsheetForTeam($currentTeam, $spreadsheet);

        $this->authorize('delete', $spreadsheet);

        $spreadsheet->delete();

        return to_route('team.spreadsheets.index', ['current_team' => $currentTeam]);
    }

    public function restore(Team $currentTeam, int $spreadsheet): RedirectResponse
    {
        return $this->restoreTrashedRecord($currentTeam, $spreadsheet, SpreadsheetWorkbook::class, 'team.spreadsheets.trash');
    }

    public function forceDestroy(Team $currentTeam, int $spreadsheet): RedirectResponse
    {
        return $this->forceDeleteTrashedRecord($currentTeam, $spreadsheet, SpreadsheetWorkbook::class, 'team.spreadsheets.trash');
    }

    private function spreadsheetForTeam(Team $currentTeam, int $spreadsheet): SpreadsheetWorkbook
    {
        return SpreadsheetWorkbook::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($spreadsheet);
    }
}
