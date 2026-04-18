<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactPageController extends Controller
{
    public function index(Request $request, Team $currentTeam): Response
    {
        $contacts = Contact::query()
            ->whereBelongsTo($currentTeam)
            ->orderBy('name')
            ->get();

        return Inertia::render('contacts/Index', [
            'contacts' => $contacts->map(fn (Contact $contact): array => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phoneNumbers' => $contact->phone_numbers,
                'emailAddresses' => $contact->email_addresses,
                'address' => $contact->address,
                'additionalInfo' => $contact->additional_info,
                'createdAt' => $contact->created_at?->format(\DateTimeInterface::ATOM),
                'updatedAt' => $contact->updated_at?->format(\DateTimeInterface::ATOM),
            ])->values()->all(),
        ]);
    }

    public function show(Request $request, Team $currentTeam, int $contact): Response
    {
        $contact = Contact::query()
            ->whereBelongsTo($currentTeam)
            ->findOrFail($contact);

        return Inertia::render('contacts/Show', [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phoneNumbers' => $contact->phone_numbers,
                'emailAddresses' => $contact->email_addresses,
                'address' => $contact->address,
                'additionalInfo' => $contact->additional_info,
                'createdAt' => $contact->created_at?->format(\DateTimeInterface::ATOM),
                'updatedAt' => $contact->updated_at?->format(\DateTimeInterface::ATOM),
            ],
        ]);
    }
}
