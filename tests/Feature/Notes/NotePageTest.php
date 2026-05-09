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
            ->where('note.format', 'text')
            ->where('note.drawingData', null)
            ->where('startInEditMode', false)
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
    expect($note->format)->toBe('text');
});

test('creating a note flashes edit mode for the show page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = actingAs($user)
        ->post(route('team.notes.store', ['current_team' => $team]), [
            'title' => 'New Note',
        ]);

    $response->assertSessionHas('edit', true);

    $note = Note::where('team_id', $team->id)->first();

    actingAs($user)
        ->get(route('team.notes.show', ['current_team' => $team, 'note' => $note]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Show')
            ->where('startInEditMode', true));
});

test('an excalidraw note can be created', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $drawingData = [
        'type' => 'excalidraw',
        'version' => 2,
        'elements' => [
            ['id' => 'rectangle-1', 'type' => 'rectangle'],
        ],
        'appState' => ['name' => 'Sketch'],
        'files' => [],
    ];

    actingAs($user)
        ->post(route('team.notes.store', ['current_team' => $team]), [
            'title' => 'Sketch',
            'format' => 'excalidraw',
            'drawing_data' => $drawingData,
        ])
        ->assertRedirect(route('team.notes.show', [
            'current_team' => $team,
            'note' => Note::whereTitle('Sketch')->first()->id,
        ]));

    $note = Note::where('team_id', $team->id)->first();

    expect($note->format)->toBe('excalidraw');
    expect($note->drawing_data)->toBe($drawingData);
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

test('updating a note logs an activity', function () {
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
        ->assertRedirect();

    $activities = $note->activitiesAsSubject()->orderByDesc('id')->get();

    expect($activities)->toHaveCount(2);
    expect($activities->first()->event)->toBe('updated');
    expect($activities->first()->causer->id)->toBe($user->id);
    expect($activities->first()->attribute_changes['attributes'])->toHaveKey('title');
    expect($activities->first()->attribute_changes['old']['title'])->toBe('Old Title');
});

test('no-op update does not log an activity', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Same Title',
        'body' => 'Same body',
    ]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'title' => 'Same Title',
            'body' => 'Same body',
        ])
        ->assertRedirect();

    $activities = $note->activitiesAsSubject()->orderByDesc('id')->get();

    expect($activities)->toHaveCount(1);
    expect($activities->first()->event)->toBe('created');
});

test('note show page includes activity history', function () {
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
        ]);

    actingAs($user)
        ->get(route('team.notes.show', ['current_team' => $team, 'note' => $note]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Show')
            ->has('activityHistory', 2)
            ->where('activityHistory.0.event', 'updated')
            ->where('activityHistory.0.causerName', $user->name)
            ->has('activityHistory.0.changedFields'));
});

test('panning the canvas does not show drawing_data as changed', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $drawingData = [
        'type' => 'excalidraw',
        'version' => 2,
        'elements' => [
            ['id' => 'rect-1', 'type' => 'rectangle', 'x' => 100, 'y' => 100, 'width' => 50, 'height' => 50],
        ],
        'appState' => ['name' => 'Test', 'scrollX' => 0, 'scrollY' => 0, 'zoom' => 1],
        'files' => [],
    ];

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'format' => 'excalidraw',
        'drawing_data' => $drawingData,
    ]);

    $pannedDrawingData = $drawingData;
    $pannedDrawingData['appState']['scrollX'] = 500;
    $pannedDrawingData['appState']['scrollY'] = 300;

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'drawing_data' => $pannedDrawingData,
        ]);

    actingAs($user)
        ->get(route('team.notes.show', ['current_team' => $team, 'note' => $note]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Show')
            ->has('activityHistory', 1)
            ->where('activityHistory.0.event', 'created'));
});

test('a note can be updated to excalidraw format', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Old Title',
        'body' => 'Old body',
    ]);

    $drawingData = [
        'type' => 'excalidraw',
        'version' => 2,
        'elements' => [
            ['id' => 'arrow-1', 'type' => 'arrow'],
        ],
        'appState' => ['name' => 'New Sketch'],
        'files' => [],
    ];

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'title' => 'New Sketch',
            'format' => 'excalidraw',
            'body' => 'Old body',
            'drawing_data' => $drawingData,
        ])
        ->assertRedirect(route('team.notes.show', ['current_team' => $team, 'note' => $note->id]));

    $note = $note->fresh();

    expect($note->title)->toBe('New Sketch');
    expect($note->format)->toBe('excalidraw');
    expect($note->drawing_data)->toBe($drawingData);
    expect($note->body)->toBeNull();
});

test('switching a note from excalidraw to text clears drawing_data', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Drawing',
        'format' => 'excalidraw',
        'drawing_data' => ['type' => 'excalidraw', 'elements' => []],
    ]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'title' => 'Converted',
            'format' => 'text',
            'body' => 'New body',
        ])
        ->assertRedirect(route('team.notes.show', ['current_team' => $team, 'note' => $note->id]));

    $note = $note->fresh();

    expect($note->format)->toBe('text');
    expect($note->body)->toBe('New body');
    expect($note->drawing_data)->toBeNull();
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
