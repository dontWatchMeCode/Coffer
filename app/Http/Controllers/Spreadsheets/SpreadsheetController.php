<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spreadsheets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Spreadsheets\SaveSpreadsheetRequest;
use App\Http\Requests\Spreadsheets\SaveSpreadsheetWorkbookRequest;
use App\Models\SpreadsheetWorkbook;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class SpreadsheetController extends Controller
{
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
        $spreadsheet = SpreadsheetWorkbook::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($spreadsheet);

        $this->authorize('restore', $spreadsheet);

        $spreadsheet->restore();

        return to_route('team.spreadsheets.trash', ['current_team' => $currentTeam]);
    }

    public function forceDestroy(Team $currentTeam, int $spreadsheet): RedirectResponse
    {
        $spreadsheet = SpreadsheetWorkbook::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($spreadsheet);

        $this->authorize('forceDelete', $spreadsheet);

        $spreadsheet->forceDelete();

        return to_route('team.spreadsheets.trash', ['current_team' => $currentTeam]);
    }

    private function spreadsheetForTeam(Team $currentTeam, int $spreadsheet): SpreadsheetWorkbook
    {
        return SpreadsheetWorkbook::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($spreadsheet);
    }
}
