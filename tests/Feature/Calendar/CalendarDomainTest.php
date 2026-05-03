<?php

use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('calendar events are scoped to the authenticated users current team', function () {
    $user = User::factory()->create();
    $secondaryTeam = Team::factory()->create();

    $secondaryTeam->members()->attach($user, ['role' => 'member']);
    $user->switchTeam($secondaryTeam);

    $visibleEvent = CalendarEvent::factory()->create(['team_id' => $secondaryTeam->id]);
    CalendarEvent::factory()->create();

    actingAs($user);

    expect(CalendarEvent::pluck('id')->all())->toBe([$visibleEvent->id]);
});

test('team id is filled from the authenticated users current team', function () {
    $user = User::factory()->create();

    actingAs($user);

    $event = CalendarEvent::create([
        'title' => 'Team standup',
        'date' => '2026-04-20',
    ]);

    expect($event->team_id)->toBe($user->current_team_id);
});

test('team scoped records require an explicit team when unauthenticated', function () {
    expect(fn () => CalendarEvent::create([
        'title' => 'Team standup',
        'date' => '2026-04-20',
    ]))->toThrow(LogicException::class);
});

test('team scoped records require a current team to query', function () {
    CalendarEvent::factory()->create();

    expect(fn () => CalendarEvent::query()->get())->toThrow(LogicException::class);
});

test('team scoped records must match the current team when updating', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $otherTeam->members()->attach($user, ['role' => 'member']);

    $event = CalendarEvent::factory()->create(['team_id' => $user->current_team_id]);

    $user->switchTeam($otherTeam);
    actingAs($user);

    $event = CalendarEvent::withoutGlobalScopes()->findOrFail($event->id);

    expect(fn () => $event->update([
        'title' => 'Cross-team update',
    ]))->toThrow(LogicException::class);
});

test('date is cast properly', function () {
    $event = CalendarEvent::factory()->create(['date' => '2026-04-20']);

    expect($event->date->format('Y-m-d'))->toBe('2026-04-20');
});

test('time is fillable', function () {
    $event = CalendarEvent::factory()->create(['time' => '14:30']);

    expect($event->time)->toBe('14:30');
});

test('description is nullable', function () {
    $event = CalendarEvent::factory()->create(['description' => null]);

    expect($event->description)->toBeNull();
});

test('deleting a team cascades to its calendar events', function () {
    $team = Team::factory()->create();
    $event = CalendarEvent::factory()->create(['team_id' => $team->id]);

    $team->forceDelete();

    expect($event->fresh())->toBeNull();
});
