<?php

use App\Models\Note;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('notes page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->get(route('team.notes.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Index')
            ->has('notes'));
});

test('notes page shows notes for current team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Planning Notes',
        'body' => 'Important launch details',
    ]);

    Note::factory()->create([
        'title' => 'Other Team Notes',
    ]);

    actingAs($user)
        ->get(route('team.notes.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Index')
            ->has('notes', 1)
            ->where('notes.0.title', 'Planning Notes')
            ->where('notes.0.excerpt', 'Important launch details'));
});

test('note show page can be rendered with links and tags payloads', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Decision Log',
        'body' => '**Approved** launch plan',
    ]);

    actingAs($user)
        ->get(route('team.notes.show', ['current_team' => $team, 'note' => $note]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Show')
            ->where('note.id', $note->id)
            ->where('note.title', 'Decision Log')
            ->where('note.body', '**Approved** launch plan')
            ->has('recordLinks')
            ->has('recordTags'));
});

test('a note can be created', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.notes.store', ['current_team' => $team]), [
            'title' => 'New Note',
            'body' => 'Body text',
        ])
        ->assertRedirect(route('team.notes.show', [
            'current_team' => $team,
            'note' => Note::whereTitle('New Note')->first()->id,
        ]));

    $note = Note::where('team_id', $team->id)->first();

    expect($note->title)->toBe('New Note');
    expect($note->body)->toBe('Body text');
});

test('a note requires a title', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.notes.store', ['current_team' => $team]), [
            'body' => 'Missing title',
        ])
        ->assertSessionHasErrors(['title']);
});

test('a note can be updated', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Old Title',
        'body' => 'Old body',
    ]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'title' => 'New Title',
            'body' => 'New body',
        ])
        ->assertRedirect(route('team.notes.show', ['current_team' => $team, 'note' => $note->id]));

    $note = $note->fresh();

    expect($note->title)->toBe('New Title');
    expect($note->body)->toBe('New body');
});

test('a note can be deleted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->delete(route('team.notes.destroy', ['current_team' => $team, 'note' => $note]))
        ->assertRedirect(route('team.notes.index', ['current_team' => $team]));

    expect($note->fresh())->toBeNull();
});

test('guests cannot access notes page', function () {
    $team = Team::factory()->create();

    $this
        ->get(route('team.notes.index', ['current_team' => $team]))
        ->assertRedirect(route('login'));
});

test('non-members cannot manage notes', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->post(route('team.notes.store', ['current_team' => $otherTeam]), [
            'title' => 'Nope',
        ])
        ->assertForbidden();
});
