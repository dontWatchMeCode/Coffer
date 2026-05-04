<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contacts;

use App\Concerns\ProvidesActivityHistory;
use App\Concerns\ProvidesRecordLinks;
use App\Concerns\ProvidesRecordTags;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactPageController extends Controller
{
    use ProvidesActivityHistory;
    use ProvidesRecordLinks;
    use ProvidesRecordTags;

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
                'links' => $contact->links,
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
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($contact);

        return Inertia::render('contacts/Show', [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phoneNumbers' => $contact->phone_numbers,
                'emailAddresses' => $contact->email_addresses,
                'links' => $contact->links,
                'address' => $contact->address,
                'additionalInfo' => $contact->additional_info,
                'createdAt' => $contact->created_at?->format(\DateTimeInterface::ATOM),
                'updatedAt' => $contact->updated_at?->format(\DateTimeInterface::ATOM),
            ],
            'recordLinks' => $this->recordLinksPayload($contact, $currentTeam),
            'recordTags' => $this->recordTagsPayload($contact, $currentTeam),
            'activityHistory' => $this->activityHistoryPayload($contact),
        ]);
    }
}
