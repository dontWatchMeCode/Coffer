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
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->search($search, ['name', 'address', 'additional_info', 'phone_numbers', 'email_addresses', 'links']))
            ->orderBy('name')
            ->simplePaginate(25);

        return Inertia::render('contacts/Index', [
            'contacts' => Inertia::scroll($contacts->through(fn (Contact $contact): array => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phoneNumbers' => $contact->phone_numbers,
                'emailAddresses' => $contact->email_addresses,
                'links' => $contact->links,
                'address' => $contact->address,
                'additionalInfo' => $contact->additional_info,
                'createdAt' => $contact->created_at?->format(\DateTimeInterface::ATOM),
                'updatedAt' => $contact->updated_at?->format(\DateTimeInterface::ATOM),
            ])),
        ]);
    }

    public function trash(Request $request, Team $currentTeam): Response
    {
        $contacts = Contact::onlyTrashed()
            ->whereBelongsTo($currentTeam)
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->search($search, ['name', 'address', 'additional_info', 'phone_numbers', 'email_addresses', 'links']))
            ->orderByDesc('deleted_at')
            ->simplePaginate(25);

        return Inertia::render('contacts/Trash', [
            'contacts' => Inertia::scroll($contacts->through(function (Contact $contact): array {
                $deletedAt = $contact->getAttribute('deleted_at');

                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'phoneNumbers' => $contact->phone_numbers,
                    'emailAddresses' => $contact->email_addresses,
                    'links' => $contact->links,
                    'address' => $contact->address,
                    'additionalInfo' => $contact->additional_info,
                    'createdAt' => $contact->created_at?->format(\DateTimeInterface::ATOM),
                    'updatedAt' => $contact->updated_at?->format(\DateTimeInterface::ATOM),
                    'deletedAt' => $deletedAt instanceof \DateTimeInterface ? $deletedAt->format(\DateTimeInterface::ATOM) : null,
                ];
            })),
        ]);
    }

    public function show(Request $request, Team $currentTeam, int $contact): Response
    {
        $contact = Contact::query()
            ->whereBelongsTo($currentTeam)
            ->with(['recordTags' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($contact);

        return Inertia::render('contacts/Index', [
            'contacts' => Inertia::optional(fn () => Inertia::scroll(
                Contact::query()
                    ->whereBelongsTo($currentTeam)
                    ->when($request->string('search')->toString(), fn ($q, $search) => $q->search($search, ['name', 'address', 'additional_info', 'phone_numbers', 'email_addresses', 'links']))
                    ->orderBy('name')
                    ->simplePaginate(25)
                    ->through(fn (Contact $c): array => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'phoneNumbers' => $c->phone_numbers,
                        'emailAddresses' => $c->email_addresses,
                        'links' => $c->links,
                        'address' => $c->address,
                        'additionalInfo' => $c->additional_info,
                        'createdAt' => $c->created_at?->format(\DateTimeInterface::ATOM),
                        'updatedAt' => $c->updated_at?->format(\DateTimeInterface::ATOM),
                    ])
            )),
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
            'activityHistory' => $this->activityHistoryConfig($contact),
        ]);
    }
}
