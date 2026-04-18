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

test('phone_numbers can be stored as JSON array', function () {
    $phones = [
        ['label' => 'Mobile', 'value' => '+1 555-0123'],
        ['label' => 'Work', 'value' => '+1 555-0456'],
    ];

    $contact = Contact::factory()->create(['phone_numbers' => $phones]);

    expect($contact->phone_numbers)->toBe($phones);
});

test('email_addresses can be stored as JSON array', function () {
    $emails = [
        ['label' => 'Work', 'value' => 'john@work.com'],
        ['label' => 'Personal', 'value' => 'john@home.com'],
    ];

    $contact = Contact::factory()->create(['email_addresses' => $emails]);

    expect($contact->email_addresses)->toBe($emails);
});

test('phone_numbers is nullable', function () {
    $contact = Contact::factory()->create(['phone_numbers' => null]);

    expect($contact->phone_numbers)->toBeNull();
});

test('email_addresses is nullable', function () {
    $contact = Contact::factory()->create(['email_addresses' => null]);

    expect($contact->email_addresses)->toBeNull();
});

test('address is nullable', function () {
    $contact = Contact::factory()->create(['address' => null]);

    expect($contact->address)->toBeNull();
});

test('additional_info is nullable', function () {
    $contact = Contact::factory()->create(['additional_info' => null]);

    expect($contact->additional_info)->toBeNull();
});

test('empty phone arrays are stored as empty arrays', function () {
    $contact = Contact::factory()->create(['phone_numbers' => []]);

    expect($contact->phone_numbers)->toBe([]);
});

test('deleting a team cascades to its contacts', function () {
    $team = Team::factory()->create();
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    $team->forceDelete();

    expect($contact->fresh())->toBeNull();
});
