<?php

use App\Models\LogEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

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
            ->has('entries.data', 1)
            ->where('entries.data.0.body', 'Team thought')
            ->where('entries.data.0.category', 'Ops')
            ->has('categories'));
});

test('log entries are ordered newest first', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'First entry',
        'created_at' => now()->subMinute(),
    ]);

    $latest = LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Second entry',
        'created_at' => now(),
    ]);

    actingAs($user)
        ->get(route('team.log.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.data.0.id', $latest->id)
            ->where('entries.data.0.body', 'Second entry')
            ->where('entries.data.1.body', 'First entry'));
});

test('log entries with matching timestamps are ordered by newest id first', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $createdAt = now();

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'First entry',
        'created_at' => $createdAt,
    ]);

    $latest = LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Second entry',
        'created_at' => $createdAt,
    ]);

    actingAs($user)
        ->get(route('team.log.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.data.0.id', $latest->id)
            ->where('entries.data.0.body', 'Second entry')
            ->where('entries.data.1.body', 'First entry'));
});

test('log entries can be filtered by multiple categories', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Ops entry',
        'category' => 'Ops',
        'created_at' => now()->subMinutes(2),
    ]);

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Research entry',
        'category' => 'Research',
        'created_at' => now()->subMinute(),
    ]);

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Ignored entry',
        'category' => 'Personal',
        'created_at' => now(),
    ]);

    actingAs($user)
        ->get(route('team.log.index', [
            'current_team' => $team,
            'categories' => ['Ops', 'Research'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries.data', 2)
            ->where('entries.data.0.body', 'Research entry')
            ->where('entries.data.1.body', 'Ops entry'));
});

test('log entries can be filtered by legacy singular category parameter', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Ops entry',
        'category' => 'Ops',
    ]);

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Research entry',
        'category' => 'Research',
    ]);

    actingAs($user)
        ->get(route('team.log.index', [
            'current_team' => $team,
            'category' => 'Ops',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.body', 'Ops entry'));
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

test('a log entry can be updated', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $entry = LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Original thought',
        'category' => 'Ops',
    ]);

    actingAs($user)
        ->patch(route('team.log.update', [
            'current_team' => $team,
            'logEntry' => $entry->id,
        ]), [
            'body' => 'Updated thought',
            'category' => 'Research',
        ])
        ->assertRedirect();

    expect($entry->refresh()->body)->toBe('Updated thought')
        ->and($entry->category)->toBe('Research');
});

test('a log entry update requires a body', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $entry = LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Original thought',
    ]);

    actingAs($user)
        ->patch(route('team.log.update', [
            'current_team' => $team,
            'logEntry' => $entry->id,
        ]), [
            'body' => '',
            'category' => null,
        ])
        ->assertSessionHasErrors(['body']);
});

test('log entry updates record activity history', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $entry = LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Original thought',
        'category' => 'Ops',
    ]);

    actingAs($user)
        ->patch(route('team.log.update', [
            'current_team' => $team,
            'logEntry' => $entry->id,
        ]), [
            'body' => 'Updated thought',
            'category' => 'Research',
        ])
        ->assertRedirect();

    $activity = Activity::where('subject_type', $entry->getMorphClass())
        ->where('subject_id', $entry->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes['attributes'])->toMatchArray([
            'body' => 'Updated thought',
            'category' => 'Research',
        ])
        ->and($activity->attribute_changes['old'])->toMatchArray([
            'body' => 'Original thought',
            'category' => 'Ops',
        ]);
});

test('a log entry from another team cannot be updated', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $otherEntry = LogEntry::factory()->create([
        'body' => 'Other team entry',
    ]);

    actingAs($user)
        ->patch(route('team.log.update', [
            'current_team' => $team,
            'logEntry' => $otherEntry->id,
        ]), [
            'body' => 'Updated thought',
            'category' => null,
        ])
        ->assertNotFound();
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
        ->assertNotFound();
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
            ->has('entries.data', 1)
            ->where('entries.data.0.id', $entry->id)
            ->where('entries.data.0.body', 'Payload test')
            ->where('entries.data.0.category', 'Research')
            ->has('entries.data.0.createdAt')
            ->where('entries.data.0.activityHistory.subject_type', 'log_entry')
            ->where('entries.data.0.activityHistory.subject_id', $entry->id)
            ->where('entries.data.0.activityHistory.total', 1));
});

test('unauthenticated user cannot access log', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->get(route('team.log.index', ['current_team' => $team]))
        ->assertRedirect(route('login'));
});
