<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notes\DeleteNoteRequest;
use App\Http\Requests\Notes\SaveNoteRequest;
use App\Models\Note;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class NoteController extends Controller
{
    public function store(SaveNoteRequest $request, Team $currentTeam): RedirectResponse
    {
        $note = Note::create([
            ...$request->validated(),
            'team_id' => $currentTeam->id,
        ]);

        return to_route('team.notes.show', [
            'current_team' => $currentTeam,
            'note' => $note->id,
        ])->with('edit', true);
    }

    public function update(SaveNoteRequest $request, Team $currentTeam, int $note): RedirectResponse
    {
        $note = Note::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($note);

        $note->update($request->validated());

        return to_route('team.notes.show', [
            'current_team' => $currentTeam,
            'note' => $note->id,
        ]);
    }

    public function destroy(DeleteNoteRequest $request, Team $currentTeam, int $note): RedirectResponse
    {
        $note = Note::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($note);

        $note->delete();

        return to_route('team.notes.index', [
            'current_team' => $currentTeam,
        ]);
    }
}
