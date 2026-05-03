<?php

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('contacts page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    expect($team)->not->toBeNull();

    actingAs($user)
        ->get(route('team.contacts.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/Index')
            ->has('contacts'),
        );
});

test('contacts page shows contacts for current team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'John Doe',
        'email_addresses' => [
            ['label' => 'Work', 'value' => 'john@example.com'],
        ],
        'links' => [
            ['label' => 'Website', 'value' => 'https://example.com'],
        ],
        'phone_numbers' => [
            ['label' => 'Mobile', 'value' => '+1 555-0123'],
        ],
    ]);

    actingAs($user)
        ->get(route('team.contacts.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/Index')
            ->has('contacts', 1)
            ->where('contacts.0.name', 'John Doe')
            ->where('contacts.0.emailAddresses.0.value', 'john@example.com')
            ->where('contacts.0.links.0.value', 'https://example.com')
            ->where('contacts.0.phoneNumbers.0.value', '+1 555-0123'),
        );
});

test('guests cannot access contacts page', function () {
    $team = Team::factory()->create();

    $this
        ->get(route('team.contacts.index', ['current_team' => $team]))
        ->assertRedirect(route('login'));
});

test('non-members cannot access contacts page', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->get(route('team.contacts.index', ['current_team' => $otherTeam]))
        ->assertForbidden();
});

test('a contact can be created with multiple phones emails and links', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $phones = [
        ['label' => 'Mobile', 'value' => '+1 555-0123'],
        ['label' => 'Work', 'value' => '+1 555-0456'],
    ];

    $emails = [
        ['label' => 'Work', 'value' => 'jane@work.com'],
        ['label' => 'Personal', 'value' => 'jane@home.com'],
    ];

    $links = [
        ['label' => 'Website', 'value' => 'https://example.com'],
        ['label' => 'LinkedIn', 'value' => 'https://linkedin.com/in/jane'],
    ];

    actingAs($user)
        ->post(route('team.contacts.store', ['current_team' => $team]), [
            'name' => 'Jane Doe',
            'phone_numbers' => $phones,
            'email_addresses' => $emails,
            'links' => $links,
            'address' => '123 Main St',
            'additional_info' => 'Software Engineer',
        ])
        ->assertRedirect(route('team.contacts.show', ['current_team' => $team, 'contact' => 1]));

    $contact = Contact::where('team_id', $team->id)->first();

    expect($contact->name)->toBe('Jane Doe');
    expect($contact->phone_numbers)->toBe($phones);
    expect($contact->email_addresses)->toBe($emails);
    expect($contact->links)->toBe($links);
    expect($contact->address)->toBe('123 Main St');
    expect($contact->additional_info)->toBe('Software Engineer');
});

test('a contact can be created with no phones or emails', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.contacts.store', ['current_team' => $team]), [
            'name' => 'Minimal Contact',
            'phone_numbers' => [],
            'email_addresses' => [],
            'links' => [],
        ])
        ->assertRedirect();

    $contact = Contact::where('team_id', $team->id)->first();

    expect($contact->phone_numbers)->toBe([]);
    expect($contact->email_addresses)->toBe([]);
    expect($contact->links)->toBe([]);
});

test('a contact requires a name', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.contacts.store', ['current_team' => $team]), [
            'phone_numbers' => [
                ['label' => 'Mobile', 'value' => '+1 555-0123'],
            ],
        ])
        ->assertSessionHasErrors(['name']);
});

test('a contact phone row without a value is ignored', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.contacts.store', ['current_team' => $team]), [
            'name' => 'Jane Doe',
            'phone_numbers' => [
                ['label' => 'Mobile'],
            ],
        ])
        ->assertRedirect();

    expect(Contact::where('team_id', $team->id)->first()->phone_numbers)->toBe([]);
});

test('a contact email row without a value is ignored', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.contacts.store', ['current_team' => $team]), [
            'name' => 'Jane Doe',
            'email_addresses' => [
                ['label' => 'Work'],
            ],
        ])
        ->assertRedirect();

    expect(Contact::where('team_id', $team->id)->first()->email_addresses)->toBe([]);
});

test('a contact email value must be valid', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.contacts.store', ['current_team' => $team]), [
            'name' => 'Jane Doe',
            'email_addresses' => [
                ['label' => 'Work', 'value' => 'not-an-email'],
            ],
        ])
        ->assertSessionHasErrors(['email_addresses.0.value']);
});

test('a contact link value must be a valid URL', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.contacts.store', ['current_team' => $team]), [
            'name' => 'Jane Doe',
            'links' => [
                ['label' => 'Website', 'value' => 'not-a-url'],
            ],
        ])
        ->assertSessionHasErrors(['links.0.value']);
});

test('a contact link row without a value is ignored', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.contacts.store', ['current_team' => $team]), [
            'name' => 'Jane Doe',
            'links' => [
                ['label' => 'Website'],
            ],
        ])
        ->assertRedirect();

    expect(Contact::where('team_id', $team->id)->first()->links)->toBe([]);
});

test('a contact can be updated with new phones emails and links', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Old Name',
        'phone_numbers' => [['label' => 'Old', 'value' => '+1 555-0000']],
    ]);

    $newPhones = [
        ['label' => 'Mobile', 'value' => '+1 555-9999'],
    ];

    $newEmails = [
        ['label' => 'Work', 'value' => 'new@work.com'],
    ];

    $newLinks = [
        ['label' => 'Website', 'value' => 'https://new.example.com'],
    ];

    actingAs($user)
        ->patch(
            route('team.contacts.update', ['current_team' => $team, 'contact' => $contact]),
            [
                'name' => 'New Name',
                'phone_numbers' => $newPhones,
                'email_addresses' => $newEmails,
                'links' => $newLinks,
            ],
        )
        ->assertRedirect(route('team.contacts.show', ['current_team' => $team, 'contact' => $contact->id]));

    $contact = $contact->fresh();

    expect($contact->name)->toBe('New Name');
    expect($contact->phone_numbers)->toBe($newPhones);
    expect($contact->email_addresses)->toBe($newEmails);
    expect($contact->links)->toBe($newLinks);
});

test('empty contact entry rows are removed when saving', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Jane Doe',
    ]);

    actingAs($user)
        ->patch(
            route('team.contacts.update', ['current_team' => $team, 'contact' => $contact]),
            [
                'name' => 'Jane Doe',
                'phone_numbers' => [
                    ['label' => 'Mobile', 'value' => '+1 555-9999'],
                    ['label' => 'Empty phone', 'value' => ''],
                ],
                'email_addresses' => [
                    ['label' => 'Work', 'value' => 'jane@example.com'],
                    ['label' => 'Empty email', 'value' => ''],
                ],
                'links' => [
                    ['label' => 'Website', 'value' => 'https://example.com'],
                    ['label' => 'Empty link', 'value' => ''],
                ],
            ],
        )
        ->assertRedirect(route('team.contacts.show', ['current_team' => $team, 'contact' => $contact->id]));

    $contact = $contact->fresh();

    expect($contact->phone_numbers)->toBe([
        ['label' => 'Mobile', 'value' => '+1 555-9999'],
    ]);
    expect($contact->email_addresses)->toBe([
        ['label' => 'Work', 'value' => 'jane@example.com'],
    ]);
    expect($contact->links)->toBe([
        ['label' => 'Website', 'value' => 'https://example.com'],
    ]);
});

test('a contact can be deleted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Delete Me',
    ]);

    actingAs($user)
        ->delete(
            route('team.contacts.destroy', ['current_team' => $team, 'contact' => $contact]),
        )
        ->assertRedirect(route('team.contacts.index', ['current_team' => $team]));

    expect($contact->fresh())->toBeNull();
});

test('a non-member cannot create contacts', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->post(route('team.contacts.store', ['current_team' => $otherTeam]), [
            'name' => 'Jane Doe',
        ])
        ->assertForbidden();
});

test('a non-member cannot update contacts', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $contact = Contact::factory()->create([
        'team_id' => $otherTeam->id,
        'name' => 'Protected Contact',
    ]);

    actingAs($user)
        ->patch(
            route('team.contacts.update', ['current_team' => $otherTeam, 'contact' => $contact]),
            ['name' => 'Hacked'],
        )
        ->assertForbidden();
});

test('a non-member cannot delete contacts', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $contact = Contact::factory()->create([
        'team_id' => $otherTeam->id,
        'name' => 'Protected Contact',
    ]);

    actingAs($user)
        ->delete(
            route('team.contacts.destroy', ['current_team' => $otherTeam, 'contact' => $contact]),
        )
        ->assertForbidden();
});

test('a user cannot update a contact from another team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'My Contact',
    ]);

    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($user, ['role' => 'member']);
    $user->switchTeam($otherTeam);

    actingAs($user)
        ->patch(
            route('team.contacts.update', ['current_team' => $otherTeam, 'contact' => $contact]),
            ['name' => 'Hacked'],
        )
        ->assertForbidden();
});

test('contacts page does not show contacts from other teams', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'My Contact',
    ]);

    Contact::factory()->create([
        'name' => 'Other Team Contact',
    ]);

    actingAs($user)
        ->get(route('team.contacts.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/Index')
            ->has('contacts', 1)
            ->where('contacts.0.name', 'My Contact'),
        );
});

test('all fields can be nullable except name', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.contacts.store', ['current_team' => $team]), [
            'name' => 'Minimal Contact',
        ])
        ->assertRedirect();

    $contact = Contact::where('team_id', $team->id)->first();

    expect($contact->phone_numbers)->toBeNull();
    expect($contact->email_addresses)->toBeNull();
    expect($contact->links)->toBeNull();
    expect($contact->address)->toBeNull();
    expect($contact->additional_info)->toBeNull();
});

test('contact show page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Jane Doe',
        'phone_numbers' => [
            ['label' => 'Work', 'value' => '+1 555-0111'],
        ],
        'email_addresses' => [
            ['label' => 'Primary', 'value' => 'jane@example.com'],
        ],
        'links' => [
            ['label' => 'Website', 'value' => 'https://example.com'],
        ],
        'address' => '123 Main St',
        'additional_info' => 'Key stakeholder',
    ]);

    actingAs($user)
        ->get(route('team.contacts.show', ['current_team' => $team, 'contact' => $contact]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/Show')
            ->has('contact')
            ->where('contact.id', $contact->id)
            ->where('contact.name', 'Jane Doe')
            ->where('contact.phoneNumbers.0.value', '+1 555-0111')
            ->where('contact.emailAddresses.0.value', 'jane@example.com')
            ->where('contact.links.0.value', 'https://example.com')
            ->where('contact.address', '123 Main St')
            ->where('contact.additionalInfo', 'Key stakeholder')
            ->where('contact.updatedAt', fn (?string $updatedAt): bool => is_string($updatedAt)
                && str_contains($updatedAt, 'T')),
        );
});

test('guests cannot access contact show page', function () {
    $team = Team::factory()->create();

    $this
        ->get(route('team.contacts.show', ['current_team' => $team, 'contact' => 1]))
        ->assertRedirect(route('login'));
});

test('non-members cannot access contact show page', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->get(route('team.contacts.show', ['current_team' => $otherTeam, 'contact' => 1]))
        ->assertForbidden();
});

test('creating a contact redirects to show page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.contacts.store', ['current_team' => $team]), [
            'name' => 'Redirect Test',
        ])
        ->assertRedirect(route('team.contacts.show', [
            'current_team' => $team,
            'contact' => Contact::whereName('Redirect Test')->first()->id,
        ]));
});

test('deleting a contact redirects to index page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Will Be Deleted',
    ]);

    actingAs($user)
        ->delete(route('team.contacts.destroy', ['current_team' => $team, 'contact' => $contact]))
        ->assertRedirect(route('team.contacts.index', ['current_team' => $team]));
});
