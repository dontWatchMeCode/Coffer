<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notes;

use App\Actions\Records\SaveNote;
use App\Concerns\HandlesTrashedRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notes\DeleteNoteRequest;
use App\Http\Requests\Notes\SaveNoteRequest;
use App\Models\Note;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class NoteController extends Controller
{
    use HandlesTrashedRecords;

    public function __construct(private readonly SaveNote $saveNote) {}

    public function store(SaveNoteRequest $request, Team $currentTeam): RedirectResponse
    {
        $this->authorize('create', Note::class);

        $validated = $request->validated();

        if (array_key_exists('blocks', $validated) && $validated['blocks'] === null) {
            $validated['blocks'] = [];
        }

        $note = $this->saveNote->execute(new Note, [
            'team_id' => $currentTeam->id,
            ...$validated,
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

        $this->authorize('update', $note);

        $validated = $request->validated();

        if (array_key_exists('blocks', $validated) && $validated['blocks'] === null) {
            $validated['blocks'] = [];
        }

        $this->saveNote->execute($note, $validated);

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

        $this->authorize('delete', $note);

        $note->delete();

        return to_route('team.notes.index', [
            'current_team' => $currentTeam,
        ]);
    }

    public function restore(Team $currentTeam, int $note): RedirectResponse
    {
        return $this->restoreTrashedRecord($currentTeam, $note, Note::class, 'team.notes.trash');
    }

    public function forceDestroy(Team $currentTeam, int $note): RedirectResponse
    {
        return $this->forceDeleteTrashedRecord($currentTeam, $note, Note::class, 'team.notes.trash');
    }
}
