<?php

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('contacts are scoped to the authenticated users current team', function () {
    $user = User::factory()->create();
    $secondaryTeam = Team::factory()->create();

    $secondaryTeam->members()->attach($user, ['role' => 'member']);
    $user->switchTeam($secondaryTeam);

    $visibleContact = Contact::factory()->create(['team_id' => $secondaryTeam->id]);
    Contact::factory()->create();

    actingAs($user);

    expect(Contact::pluck('id')->all())->toBe([$visibleContact->id]);
});

test('team id is filled from the authenticated users current team', function () {
    $user = User::factory()->create();

    actingAs($user);

    $contact = Contact::create([
        'name' => 'John Doe',
    ]);

    expect($contact->team_id)->toBe($user->current_team_id);
});

test('team scoped records require an explicit team when unauthenticated', function () {
    expect(fn () => Contact::create([
        'name' => 'John Doe',
    ]))->toThrow(LogicException::class);
});

test('team scoped records require a current team to query', function () {
    Contact::factory()->create();

    expect(fn () => Contact::query()->get())->toThrow(LogicException::class);
});

test('team scoped records must match the current team when updating', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $otherTeam->members()->attach($user, ['role' => 'member']);

    $contact = Contact::factory()->create(['team_id' => $user->current_team_id]);

    $user->switchTeam($otherTeam);
    actingAs($user);

    $contact = Contact::withoutGlobalScopes()->findOrFail($contact->id);

    expect(fn () => $contact->update([
        'name' => 'Cross-team update',
    ]))->toThrow(LogicException::class);
});

test('contact json attributes round-trip through the database', function (string $attribute, array $value) {
    $contact = Contact::factory()->create([$attribute => $value]);

    expect($contact->fresh()->getAttribute($attribute))->toBe($value);
})->with([
    'phones' => ['phone_numbers', [['label' => 'Mobile', 'value' => '+1 555-0123'], ['label' => 'Work', 'value' => '+1 555-0456']]],
    'emails' => ['email_addresses', [['label' => 'Work', 'value' => 'john@work.com'], ['label' => 'Personal', 'value' => 'john@home.com']]],
    'links' => ['links', [['label' => 'Website', 'value' => 'https://example.com'], ['label' => 'LinkedIn', 'value' => 'https://linkedin.com/in/john']]],
    'empty phones' => ['phone_numbers', []],
]);

test('contact nullable attributes round-trip through the database', function (string $attribute) {
    $contact = Contact::factory()->create([$attribute => null]);

    expect($contact->fresh()->getAttribute($attribute))->toBeNull();
})->with([
    'phone_numbers',
    'email_addresses',
    'links',
    'address',
    'additional_info',
]);

test('deleting a team cascades to its contacts', function () {
    $team = Team::factory()->create();
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    $team->forceDelete();

    expect($contact->fresh())->toBeNull();
});
