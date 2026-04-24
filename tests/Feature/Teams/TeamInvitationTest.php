<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('team invitations can be created', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $response = $this
        ->actingAs($owner)
        ->post(route('teams.invitations.store', $team), [
            'email' => 'invited@example.com',
            'role' => TeamRole::Member->value,
        ]);

    $response->assertRedirect(route('teams.edit', $team));

    $this->assertDatabaseHas('team_invitations', [
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'role' => TeamRole::Member->value,
    ]);
});

test('team invitations can be created by admins', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);

    $response = $this
        ->actingAs($admin)
        ->post(route('teams.invitations.store', $team), [
            'email' => 'invited@example.com',
            'role' => TeamRole::Member->value,
        ]);

    $response->assertRedirect(route('teams.edit', $team));
});

test('existing team members cannot be invited', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create(['email' => 'member@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $response = $this
        ->actingAs($owner)
        ->post(route('teams.invitations.store', $team), [
            'email' => 'member@example.com',
            'role' => TeamRole::Member->value,
        ]);

    $response->assertSessionHasErrors('email');
});

test('duplicate invitations cannot be created', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($owner)
        ->post(route('teams.invitations.store', $team), [
            'email' => 'invited@example.com',
            'role' => TeamRole::Member->value,
        ]);

    $response->assertSessionHasErrors('email');
});

test('team invitations cannot be created by members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $response = $this
        ->actingAs($member)
        ->post(route('teams.invitations.store', $team), [
            'email' => 'invited@example.com',
            'role' => TeamRole::Member->value,
        ]);

    $response->assertForbidden();
});

test('team invitations can be cancelled by owners', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($owner)
        ->delete(route('teams.invitations.destroy', [$team, $invitation]));

    $response->assertRedirect(route('teams.edit', $team));

    $this->assertDatabaseMissing('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('team invitations can be accepted', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'role' => TeamRole::Member,
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->post(route('invitations.accept', $invitation));

    $response->assertRedirect(route('dashboard'));

    expect($invitedUser->fresh()->belongsToTeam($team))->toBeTrue();
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('team invitations cannot be accepted by uninvited user', function () {
    $owner = User::factory()->create();
    $uninvitedUser = User::factory()->create(['email' => 'uninvited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($uninvitedUser)
        ->post(route('invitations.accept', $invitation));

    $response->assertSessionHasErrors('invitation');

    expect($uninvitedUser->fresh()->belongsToTeam($team))->toBeFalse();
});

test('team invitation acceptance page can be viewed', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'role' => TeamRole::Member,
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('invitations.show', $invitation));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('invitations/Accept')
        ->has('invitation')
    );
});

test('team invitation show page requires authentication', function () {
    $invitation = TeamInvitation::factory()->create();

    $response = $this->get(route('invitations.show', $invitation));

    $response->assertRedirect(route('login'));
});

test('team invitation show page rejects wrong email', function () {
    $owner = User::factory()->create();
    $uninvitedUser = User::factory()->create(['email' => 'uninvited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($uninvitedUser)
        ->get(route('invitations.show', $invitation));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHasErrors('invitation');
});

test('team invitation show page rejects expired invitation', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('invitations.show', $invitation));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHasErrors('invitation');
});

test('team invitation show page rejects already accepted invitation', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
        'accepted_at' => now(),
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('invitations.show', $invitation));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHasErrors('invitation');
});

test('expired invitations cannot be accepted', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->post(route('invitations.accept', $invitation));

    $response->assertSessionHasErrors('invitation');

    expect($invitedUser->fresh()->belongsToTeam($team))->toBeFalse();
});
