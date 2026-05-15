<?php

use App\Models\Note;
use App\Models\RteBlock;
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

    Note::factory()
        ->withTextBlock('Important launch details')
        ->create([
            'team_id' => $team->id,
            'title' => 'Planning Notes',
        ]);

    Note::factory()->create([
        'title' => 'Other Team Notes',
    ]);

    actingAs($user)
        ->get(route('team.notes.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Index')
            ->has('notes.data', 1)
            ->where('notes.data.0.title', 'Planning Notes')
            ->where('notes.data.0.excerpt', null));
});

test('note show page can be rendered with blocks payload', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Decision Log',
    ]);
    $note->blocks()->create([
        'type' => 'text',
        'position' => 0,
        'payload' => ['content' => '**Approved** launch plan'],
    ]);

    actingAs($user)
        ->get(route('team.notes.show', ['current_team' => $team, 'note' => $note]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Show')
            ->where('note.id', $note->id)
            ->where('note.title', 'Decision Log')
            ->has('note.blocks', 1)
            ->where('note.blocks.0.type', 'text')
            ->where('note.blocks.0.payload.content', '**Approved** launch plan')
            ->where('startInEditMode', false)
            ->has('recordLinks')
            ->has('recordTags'));
});

test('a note can be created with a text block', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.notes.store', ['current_team' => $team]), [
            'title' => 'New Note',
            'blocks' => [
                ['type' => 'text', 'position' => 0, 'payload' => ['content' => 'Body text']],
            ],
        ])
        ->assertRedirect(route('team.notes.show', [
            'current_team' => $team,
            'note' => Note::whereTitle('New Note')->first()->id,
        ]));

    $note = Note::where('team_id', $team->id)->first();

    expect($note->title)->toBe('New Note');
    expect($note->blocks)->toHaveCount(1);
    expect($note->blocks->first()->type)->toBe('text');
    expect($note->blocks->first()->payload['content'])->toBe('Body text');
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

test('a note can be created with an excalidraw block', function () {
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
            'blocks' => [
                ['type' => 'excalidraw', 'position' => 0, 'payload' => ['scene' => $drawingData]],
            ],
        ])
        ->assertRedirect(route('team.notes.show', [
            'current_team' => $team,
            'note' => Note::whereTitle('Sketch')->first()->id,
        ]));

    $note = Note::where('team_id', $team->id)->first();

    expect($note->blocks)->toHaveCount(1);
    $block = $note->blocks->first();
    expect($block->type)->toBe('excalidraw');
    expect($block->payload['scene'])->toBe($drawingData);
});

test('a note requires a title', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.notes.store', ['current_team' => $team]), [
            'title' => '',
        ])
        ->assertSessionHasErrors(['title']);
});

test('a note can be updated with blocks', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Old Title',
    ]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'title' => 'New Title',
            'blocks' => [
                ['type' => 'text', 'position' => 0, 'payload' => ['content' => 'New body']],
            ],
        ])
        ->assertRedirect(route('team.notes.show', ['current_team' => $team, 'note' => $note->id]));

    $note = $note->fresh();

    expect($note->title)->toBe('New Title');
    expect($note->blocks)->toHaveCount(1);
    expect($note->blocks->first()->payload['content'])->toBe('New body');
});

test('updating a note title logs an activity', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Old Title',
    ]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'title' => 'New Title',
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
    ]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'title' => 'Same Title',
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

test('syncBlocks creates new blocks', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'blocks' => [
                ['type' => 'text', 'position' => 0, 'payload' => ['content' => 'First']],
                ['type' => 'text', 'position' => 1, 'payload' => ['content' => 'Second']],
            ],
        ])
        ->assertRedirect();

    expect(RteBlock::where('blockable_id', $note->id)->count())->toBe(2);
});

test('syncBlocks updates existing and removes stale blocks', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id]);
    $blockA = $note->blocks()->create(['type' => 'text', 'position' => 0, 'payload' => ['content' => 'Keep']]);
    $note->blocks()->create(['type' => 'text', 'position' => 1, 'payload' => ['content' => 'Remove']]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'blocks' => [
                ['id' => $blockA->id, 'type' => 'text', 'position' => 0, 'payload' => ['content' => 'Updated']],
            ],
        ])
        ->assertRedirect();

    $note = $note->fresh();
    expect($note->blocks)->toHaveCount(1);
    expect($note->blocks->first()->id)->toBe($blockA->id);
    expect($note->blocks->first()->payload['content'])->toBe('Updated');
});

test('blocks are validated for allowed types', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'blocks' => [
                ['type' => 'invalid', 'position' => 0],
            ],
        ])
        ->assertSessionHasErrors(['blocks.0.type']);
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
