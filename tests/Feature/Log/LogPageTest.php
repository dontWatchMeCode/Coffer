<?php

use App\Models\LogEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('log page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->get(route('team.log.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('log/Index')
            ->has('entries'));
});

test('log page shows entries for current team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Team thought',
        'category' => 'Ops',
    ]);

    LogEntry::factory()->create([
        'body' => 'Other team thought',
    ]);

    actingAs($user)
        ->get(route('team.log.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('log/Index')
            ->has('entries', 1)
            ->where('entries.0.body', 'Team thought')
            ->where('entries.0.category', 'Ops'));
});

test('log entries are ordered oldest first', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $first = LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'First entry',
    ]);

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Second entry',
    ]);

    actingAs($user)
        ->get(route('team.log.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.id', $first->id)
            ->where('entries.0.body', 'First entry')
            ->where('entries.1.body', 'Second entry'));
});

test('a log entry can be created', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.log.store', ['current_team' => $team]), [
            'body' => 'Quick thought',
        ])
        ->assertRedirect();

    $entry = LogEntry::where('team_id', $team->id)->first();

    expect($entry->body)->toBe('Quick thought');
});

test('a log entry can be created with a category', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.log.store', ['current_team' => $team]), [
            'body' => 'Investigated deploy',
            'category' => 'Ops',
        ])
        ->assertRedirect();

    $entry = LogEntry::where('team_id', $team->id)->first();

    expect($entry->body)->toBe('Investigated deploy')
        ->and($entry->category)->toBe('Ops');
});

test('a log entry requires a body', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.log.store', ['current_team' => $team]), [
            'body' => '',
        ])
        ->assertSessionHasErrors(['body']);
});

test('a log entry category may not be longer than eighty characters', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.log.store', ['current_team' => $team]), [
            'body' => 'Quick thought',
            'category' => str_repeat('a', 81),
        ])
        ->assertSessionHasErrors(['category']);
});

test('a log entry can be deleted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $entry = LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Delete me',
    ]);

    actingAs($user)
        ->delete(route('team.log.destroy', [
            'current_team' => $team,
            'logEntry' => $entry->id,
        ]))
        ->assertRedirect();

    expect(LogEntry::find($entry->id))->toBeNull();
});

test('a log entry from another team cannot be deleted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $otherEntry = LogEntry::factory()->create([
        'body' => 'Other team entry',
    ]);

    actingAs($user)
        ->delete(route('team.log.destroy', [
            'current_team' => $team,
            'logEntry' => $otherEntry->id,
        ]))
        ->assertForbidden();
});

test('log page has no show route', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->get(route('team.log.index', ['current_team' => $team]))
        ->assertOk();
});

test('log entry payload includes expected fields', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $entry = LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Payload test',
        'category' => 'Research',
    ]);

    actingAs($user)
        ->get(route('team.log.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 1)
            ->where('entries.0.id', $entry->id)
            ->where('entries.0.body', 'Payload test')
            ->where('entries.0.category', 'Research')
            ->has('entries.0.createdAt'));
});

test('unauthenticated user cannot access log', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->get(route('team.log.index', ['current_team' => $team]))
        ->assertRedirect(route('login'));
});
