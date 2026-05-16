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

test('note show page includes block changes in activity history', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'blocks' => [
                ['type' => 'text', 'position' => 0, 'payload' => ['content' => 'Hello']],
                ['type' => 'mermaid', 'position' => 1, 'payload' => ['content' => 'graph TD']],
            ],
        ]);

    actingAs($user)
        ->get(route('team.notes.show', ['current_team' => $team, 'note' => $note]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Show')
            ->has('activityHistory', 2)
            ->where('activityHistory.0.event', 'blocks_updated')
            ->has('activityHistory.0.blockChanges.added', 2)
            ->where('activityHistory.0.blockChanges.added.0.type', 'text')
            ->where('activityHistory.0.blockChanges.added.0.payload.content', 'Hello')
            ->where('activityHistory.0.blockChanges.added.1.type', 'mermaid')
            ->where('activityHistory.0.blockChanges.added.1.payload.content', 'graph TD'));
});

test('syncBlocks creates new blocks and logs activity', function () {
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

    $activity = $note->activitiesAsSubject()
        ->where('event', 'blocks_updated')
        ->first();

    $added = $activity->properties['block_changes']['added'];

    expect($activity)->not->toBeNull()
        ->and($added)->toHaveCount(2)
        ->and($added[0]['type'])->toBe('text')
        ->and($added[0]['payload']['content'])->toBe('First')
        ->and($added[1]['payload']['content'])->toBe('Second')
        ->and($activity->properties['block_changes']['updated'])->toBe([])
        ->and($activity->properties['block_changes']['removed'])->toBe([]);
});

test('syncBlocks updates existing and removes stale blocks and logs activity', function () {
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

    $activity = $note->activitiesAsSubject()
        ->where('event', 'blocks_updated')
        ->first();

    $updated = $activity->properties['block_changes']['updated'];
    $removed = $activity->properties['block_changes']['removed'];

    expect($activity)->not->toBeNull()
        ->and($activity->properties['block_changes']['added'])->toBeEmpty()
        ->and($updated)->toHaveCount(1)
        ->and($updated[0]['old_payload']['content'])->toBe('Keep')
        ->and($updated[0]['payload']['content'])->toBe('Updated')
        ->and($removed)->toHaveCount(1)
        ->and($removed[0]['payload']['content'])->toBe('Remove');
});

test('syncBlocks only logs blocks whose payload changed', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id]);
    $blockA = $note->blocks()->create(['type' => 'text', 'position' => 0, 'payload' => ['content' => 'Unchanged']]);
    $blockB = $note->blocks()->create(['type' => 'mermaid', 'position' => 1, 'payload' => ['content' => 'graph TD']]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'blocks' => [
                ['id' => $blockA->id, 'type' => 'text', 'position' => 0, 'payload' => ['content' => 'Unchanged']],
                ['id' => $blockB->id, 'type' => 'mermaid', 'position' => 1, 'payload' => ['content' => 'graph LR']],
            ],
        ])
        ->assertRedirect();

    $activity = $note->activitiesAsSubject()
        ->where('event', 'blocks_updated')
        ->first();

    $updated = $activity->properties['block_changes']['updated'];

    expect($activity)->not->toBeNull()
        ->and($activity->properties['block_changes']['added'])->toBeEmpty()
        ->and($activity->properties['block_changes']['removed'])->toBeEmpty()
        ->and($updated)->toHaveCount(1)
        ->and($updated[0]['type'])->toBe('mermaid')
        ->and($updated[0]['old_payload']['content'])->toBe('graph TD')
        ->and($updated[0]['payload']['content'])->toBe('graph LR');
});

test('syncBlocks ignores excalidraw viewport-only changes', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id]);
    $block = $note->blocks()->create([
        'type' => 'excalidraw',
        'position' => 0,
        'payload' => [
            'scene' => [
                'type' => 'excalidraw',
                'elements' => [['id' => 'rect1', 'type' => 'rectangle']],
                'appState' => ['scrollX' => 0, 'scrollY' => 0, 'zoom' => 1],
                'files' => [],
            ],
        ],
    ]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'blocks' => [
                [
                    'id' => $block->id,
                    'type' => 'excalidraw',
                    'position' => 0,
                    'payload' => [
                        'scene' => [
                            'type' => 'excalidraw',
                            'elements' => [['id' => 'rect1', 'type' => 'rectangle']],
                            'appState' => ['scrollX' => 100, 'scrollY' => 200, 'zoom' => 2],
                            'files' => [],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $activity = $note->activitiesAsSubject()
        ->where('event', 'blocks_updated')
        ->first();

    expect($activity)->toBeNull();
});

test('syncBlocks with no changes does not log block activity', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id]);
    $block = $note->blocks()->create(['type' => 'text', 'position' => 0, 'payload' => ['content' => 'Same']]);

    actingAs($user)
        ->patch(route('team.notes.update', ['current_team' => $team, 'note' => $note]), [
            'blocks' => [
                ['id' => $block->id, 'type' => 'text', 'position' => 0, 'payload' => ['content' => 'Same']],
            ],
        ])
        ->assertRedirect();

    $activity = $note->activitiesAsSubject()
        ->where('event', 'blocks_updated')
        ->first();

    expect($activity)->toBeNull();
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
