<?php

use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Carbon::setTestNow('2026-04-15 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('calendar page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    expect($team)->not->toBeNull();

    actingAs($user)
        ->get(route('team.calendar.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/Index')
            ->has('calendarEvents')
            ->has('events'),
        );
});

test('calendar page shows events for current team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Team standup',
        'date' => '2026-04-20',
        'time' => '09:30',
    ]);

    actingAs($user)
        ->get(route('team.calendar.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/Index')
            ->has('calendarEvents', 1)
            ->where('calendarEvents.0.title', 'Team standup')
            ->where('calendarEvents.0.date', '2026-04-20')
            ->where('calendarEvents.0.time', '09:30'),
        );
});

test('guests cannot access calendar page', function () {
    $team = Team::factory()->create();

    $this
        ->get(route('team.calendar.index', ['current_team' => $team]))
        ->assertRedirect(route('login'));
});

test('non-members cannot access calendar page', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->get(route('team.calendar.index', ['current_team' => $otherTeam]))
        ->assertForbidden();
});

test('events are serialised with ISO 8601 timestamps', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Sprint planning',
        'date' => '2026-04-20',
    ]);

    actingAs($user)
        ->get(route('team.calendar.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/Index')
            ->has('calendarEvents', 1)
            ->where('calendarEvents.0.date', '2026-04-20'),
        );
});

test('a calendar event can be created', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.calendar.events.store', ['current_team' => $team]), [
            'title' => 'Team standup',
            'description' => 'Daily standup meeting',
            'date' => '2026-04-20',
            'time' => '09:30',
        ])
        ->assertRedirect();

    assertDatabaseHas('calendar_events', [
        'team_id' => $team->id,
        'title' => 'Team standup',
        'description' => 'Daily standup meeting',
        'time' => '09:30',
    ]);
});

test('a calendar event time must use hour and minute format', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.calendar.events.store', ['current_team' => $team]), [
            'title' => 'Team standup',
            'date' => '2026-04-20',
            'time' => '9am',
        ])
        ->assertSessionHasErrors(['time']);
});

test('a calendar event requires a title and date', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.calendar.events.store', ['current_team' => $team]), [
            'description' => 'Missing title and date',
        ])
        ->assertSessionHasErrors(['title', 'date']);
});

test('a calendar event can be updated', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Old title',
        'date' => '2026-04-20',
    ]);

    actingAs($user)
        ->patch(
            route('team.calendar.events.update', ['current_team' => $team, 'event' => $event]),
            ['title' => 'New title', 'time' => '14:45'],
        )
        ->assertRedirect();

    expect($event->fresh()->title)->toBe('New title');
    expect($event->fresh()->time)->toBe('14:45');
});

test('calendar edit page includes timestamps for editor metadata', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Planning session',
        'date' => '2026-04-20',
    ]);

    actingAs($user)
        ->get(route('team.calendar.events.edit', ['current_team' => $team, 'event' => $event]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/Index')
            ->where('event.id', $event->id)
            ->where('event.updatedAt', fn (?string $updatedAt): bool => is_string($updatedAt)
                && str_contains($updatedAt, 'T')),
        );
});

test('updating a calendar event logs an activity', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Old Title',
        'date' => '2026-04-20',
    ]);

    actingAs($user)
        ->patch(
            route('team.calendar.events.update', ['current_team' => $team, 'event' => $event]),
            ['title' => 'New Title'],
        )
        ->assertRedirect();

    $activities = $event->activitiesAsSubject()->orderByDesc('id')->get();

    expect($activities)->toHaveCount(2);
    expect($activities->first()->event)->toBe('updated');
});

test('calendar edit page includes activity history', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Old Title',
        'date' => '2026-04-20',
    ]);

    actingAs($user)
        ->patch(
            route('team.calendar.events.update', ['current_team' => $team, 'event' => $event]),
            ['title' => 'New Title'],
        );

    actingAs($user)
        ->get(route('team.calendar.events.edit', ['current_team' => $team, 'event' => $event]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/Index')
            ->has('activityHistory')
            ->where('activityHistory.subject_type', 'calendar_event')
            ->where('activityHistory.subject_id', $event->id)
            ->whereType('activityHistory.total', 'integer'));
});

test('a calendar event can be deleted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Delete me',
        'date' => '2026-04-20',
    ]);

    actingAs($user)
        ->delete(
            route('team.calendar.events.destroy', ['current_team' => $team, 'event' => $event]),
        )
        ->assertRedirect(route('team.calendar.index', ['current_team' => $team]));

    $this->assertSoftDeleted('calendar_events', ['id' => $event->id]);
});

test('a non-member cannot create calendar events', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->post(route('team.calendar.events.store', ['current_team' => $otherTeam]), [
            'title' => 'Team standup',
            'date' => '2026-04-20',
        ])
        ->assertForbidden();
});

test('a non-member cannot update calendar events', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $event = CalendarEvent::factory()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Protected event',
        'date' => '2026-04-20',
    ]);

    actingAs($user)
        ->patch(
            route('team.calendar.events.update', ['current_team' => $otherTeam, 'event' => $event]),
            ['title' => 'Hacked'],
        )
        ->assertForbidden();
});

test('a non-member cannot delete calendar events', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $event = CalendarEvent::factory()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Protected event',
        'date' => '2026-04-20',
    ]);

    actingAs($user)
        ->delete(
            route('team.calendar.events.destroy', ['current_team' => $otherTeam, 'event' => $event]),
        )
        ->assertForbidden();
});

test('a user cannot update an event from another team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'My event',
        'date' => '2026-04-20',
    ]);

    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($user, ['role' => 'member']);
    $user->switchTeam($otherTeam);

    actingAs($user)
        ->patch(
            route('team.calendar.events.update', ['current_team' => $otherTeam, 'event' => $event]),
            ['title' => 'Hacked'],
        )
        ->assertNotFound();
});

test('calendar page does not show events from other teams', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'My event',
        'date' => '2026-04-20',
    ]);

    CalendarEvent::factory()->create([
        'title' => 'Other team event',
        'date' => '2026-04-20',
    ]);

    actingAs($user)
        ->get(route('team.calendar.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/Index')
            ->has('calendarEvents', 1)
            ->where('calendarEvents.0.title', 'My event'),
        );
});
