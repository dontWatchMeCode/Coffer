<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contacts;

use App\Concerns\HandlesTrashedRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\DeleteContactRequest;
use App\Http\Requests\Contacts\SaveContactRequest;
use App\Models\Contact;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    use HandlesTrashedRecords;

    public function store(SaveContactRequest $request, Team $currentTeam): RedirectResponse
    {
        $this->authorize('create', Contact::class);

        $contact = Contact::create([
            ...$request->validated(),
            'team_id' => $currentTeam->id,
        ]);

        return to_route('team.contacts.show', [
            'current_team' => $currentTeam,
            'contact' => $contact->id,
        ]);
    }

    public function update(SaveContactRequest $request, Team $currentTeam, int $contact): RedirectResponse
    {
        $contact = Contact::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($contact);

        $this->authorize('update', $contact);

        $contact->update($request->validated());

        return to_route('team.contacts.show', [
            'current_team' => $currentTeam,
            'contact' => $contact->id,
        ]);
    }

    public function destroy(DeleteContactRequest $request, Team $currentTeam, int $contact): RedirectResponse
    {
        $contact = Contact::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($contact);

        $this->authorize('delete', $contact);

        $contact->delete();

        return to_route('team.contacts.index', [
            'current_team' => $currentTeam,
        ]);
    }

    public function restore(Team $currentTeam, int $contact): RedirectResponse
    {
        return $this->restoreTrashedRecord($currentTeam, $contact, Contact::class, 'team.contacts.trash');
    }

    public function forceDestroy(Team $currentTeam, int $contact): RedirectResponse
    {
        return $this->forceDeleteTrashedRecord($currentTeam, $contact, Contact::class, 'team.contacts.trash');
    }
}
