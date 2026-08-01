<?php

use App\Actions\Records\CreateRecordLink;
use App\Models\Bookmark;
use App\Models\Contact;
use App\Models\Note;
use App\Models\RecordLink;
use App\Models\Tag;
use App\Models\User;
use App\Services\ActivityLogger;
use Spatie\Activitylog\Models\Activity;

it('logs a tag attachment with correct properties', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Important']);

    ActivityLogger::logTagAttached($note, $tag, $user);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'tagged')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Added tag Important')
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->causer_type)->toBe($user->getMorphClass())
        ->and($activity->properties['relation_changes']['type'])->toBe('tag')
        ->and($activity->properties['relation_changes']['action'])->toBe('added')
        ->and($activity->properties['relation_changes']['target']['id'])->toBe($tag->id)
        ->and($activity->properties['relation_changes']['target']['name'])->toBe('Important');
});

it('logs a tag detachment with correct properties', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Draft']);

    ActivityLogger::logTagDetached($note, $tag, $user);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'untagged')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Removed tag Draft')
        ->and($activity->properties['relation_changes']['action'])->toBe('removed')
        ->and($activity->properties['relation_changes']['target']['id'])->toBe($tag->id);
});

it('logs a tag sync with added and removed tags', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);

    ActivityLogger::logTagsSynced($note, ['Alpha', 'Beta'], ['Gamma'], $user);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'tags_updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Tags updated: added Alpha, Beta, removed Gamma')
        ->and($activity->properties['relation_changes']['added'])->toBe(['Alpha', 'Beta'])
        ->and($activity->properties['relation_changes']['removed'])->toBe(['Gamma']);
});

it('skips logging when tag sync has no changes', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);

    $countBefore = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->count();

    ActivityLogger::logTagsSynced($note, [], [], $user);

    $countAfter = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->count();

    expect($countAfter)->toBe($countBefore);
});

it('logs tag sync with only added tags', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);

    ActivityLogger::logTagsSynced($note, ['New'], [], $user);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'tags_updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Tags updated: added New');
});

it('logs tag sync with only removed tags', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);

    ActivityLogger::logTagsSynced($note, [], ['Old'], $user);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'tags_updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Tags updated: removed Old');
});

it('logs link creation on both sides', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id, 'title' => 'Linked note']);
    $contact = Contact::factory()->create(['team_id' => $team->id, 'name' => 'Linked contact']);

    [$leftType, $leftId, $rightType, $rightId] = CreateRecordLink::normalizePair(
        'note', $note->id,
        'contact', $contact->id,
    );

    $link = RecordLink::create([
        'team_id' => $team->id,
        'left_type' => $leftType,
        'left_id' => $leftId,
        'right_type' => $rightType,
        'right_id' => $rightId,
    ]);

    ActivityLogger::logLinkCreated($link, $user);

    $noteActivities = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'linked')
        ->get();

    $contactActivities = Activity::where('subject_type', $contact->getMorphClass())
        ->where('subject_id', $contact->getKey())
        ->where('event', 'linked')
        ->get();

    expect($noteActivities)->toHaveCount(1)
        ->and($noteActivities->first()->properties['relation_changes']['type'])->toBe('link')
        ->and($noteActivities->first()->properties['relation_changes']['action'])->toBe('added');

    expect($contactActivities)->toHaveCount(1)
        ->and($contactActivities->first()->properties['relation_changes']['type'])->toBe('link')
        ->and($contactActivities->first()->properties['relation_changes']['action'])->toBe('added');
});

it('logs link destruction on both sides', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id, 'title' => 'Linked note']);
    $contact = Contact::factory()->create(['team_id' => $team->id, 'name' => 'Linked contact']);

    [$leftType, $leftId, $rightType, $rightId] = CreateRecordLink::normalizePair(
        'note', $note->id,
        'contact', $contact->id,
    );

    $link = RecordLink::create([
        'team_id' => $team->id,
        'left_type' => $leftType,
        'left_id' => $leftId,
        'right_type' => $rightType,
        'right_id' => $rightId,
    ]);

    ActivityLogger::logLinkDestroyed($link, $user);

    $noteActivities = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'unlinked')
        ->get();

    $contactActivities = Activity::where('subject_type', $contact->getMorphClass())
        ->where('subject_id', $contact->getKey())
        ->where('event', 'unlinked')
        ->get();

    expect($noteActivities)->toHaveCount(1)
        ->and($noteActivities->first()->properties['relation_changes']['action'])->toBe('removed');

    expect($contactActivities)->toHaveCount(1)
        ->and($contactActivities->first()->properties['relation_changes']['action'])->toBe('removed');
});

it('attributes activity to the correct causer', function () {
    $user = User::factory()->create(['name' => 'Alice']);
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Review']);

    ActivityLogger::logTagAttached($note, $tag, $user);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'tagged')
        ->with('causer')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer)->not->toBeNull()
        ->and($activity->causer->name)->toBe('Alice');
});

it('logs activity without a causer', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'System']);

    ActivityLogger::logTagAttached($note, $tag);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'tagged')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull();
});

it('logs link cleanup when a record is deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id, 'title' => 'Deleted note']);
    $bookmark = Bookmark::factory()->create(['team_id' => $team->id, 'title' => 'Remaining bookmark']);

    [$leftType, $leftId, $rightType, $rightId] = CreateRecordLink::normalizePair(
        'bookmark', $bookmark->id,
        'note', $note->id,
    );

    $link = RecordLink::create([
        'team_id' => $team->id,
        'left_type' => $leftType,
        'left_id' => $leftId,
        'right_type' => $rightType,
        'right_id' => $rightId,
    ]);

    ActivityLogger::logLinkCleanup($note, $link, $user);

    $bookmarkActivities = Activity::where('subject_type', $bookmark->getMorphClass())
        ->where('subject_id', $bookmark->getKey())
        ->where('event', 'unlinked')
        ->get();

    expect($bookmarkActivities)->toHaveCount(1)
        ->and($bookmarkActivities->first()->description)->toContain('Deleted note');
});

it('logs block changes with added blocks', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);

    ActivityLogger::logBlockChanges($note, [
        'added' => [
            ['type' => 'text', 'position' => 0, 'payload' => ['content' => 'Hello']],
            ['type' => 'mermaid', 'position' => 1, 'payload' => ['content' => 'graph TD']],
        ],
    ], $user);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'blocks_updated')
        ->first();

    $added = $activity->properties['block_changes']['added'];

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Blocks updated: 2 added')
        ->and($added)->toHaveCount(2)
        ->and($added[0]['type'])->toBe('text')
        ->and($added[0]['payload']['content'])->toBe('Hello')
        ->and($added[1]['payload']['content'])->toBe('graph TD')
        ->and($activity->causer_id)->toBe($user->id);
});

it('logs block changes with all change types', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);

    ActivityLogger::logBlockChanges($note, [
        'added' => [['type' => 'excalidraw', 'position' => 2, 'payload' => ['scene' => ['elements' => []]]]],
        'updated' => [['type' => 'text', 'position' => 0, 'old_payload' => ['content' => 'Old'], 'payload' => ['content' => 'New']]],
        'removed' => [['type' => 'mermaid', 'position' => 1, 'payload' => ['content' => 'graph LR']]],
    ], $user);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'blocks_updated')
        ->first();

    $changes = $activity->properties['block_changes'];

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Blocks updated: 1 added, 1 updated, 1 removed')
        ->and($changes['added'])->toHaveCount(1)
        ->and($changes['added'][0]['payload']['scene'])->toBe(['elements' => []])
        ->and($changes['updated'])->toHaveCount(1)
        ->and($changes['updated'][0]['old_payload']['content'])->toBe('Old')
        ->and($changes['updated'][0]['payload']['content'])->toBe('New')
        ->and($changes['removed'])->toHaveCount(1)
        ->and($changes['removed'][0]['payload']['content'])->toBe('graph LR');
});

it('skips logging block changes when there are none', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);

    $countBefore = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->count();

    ActivityLogger::logBlockChanges($note, [], $user);

    $countAfter = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->count();

    expect($countAfter)->toBe($countBefore);
});

it('logs block changes without a causer', function () {
    $note = Note::factory()->create();

    ActivityLogger::logBlockChanges($note, [
        'added' => [['type' => 'text', 'position' => 0, 'payload' => ['content' => 'Hello']]],
    ]);

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'blocks_updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBeNull();
});

it('does not persist activity with only empty field changes', function () {
    $note = Note::factory()->create();

    activity()
        ->performedOn($note)
        ->withChanges([
            'old' => ['description' => null],
            'attributes' => ['description' => '<p> </p>'],
        ])
        ->event('updated')
        ->log('Updated note');

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'updated')
        ->first();

    expect($activity)->toBeNull();
});

it('does not persist activity with only drawing viewport changes', function () {
    $note = Note::factory()->create();

    activity()
        ->performedOn($note)
        ->withChanges([
            'old' => [
                'drawing_data' => [
                    'elements' => [['id' => 'rect1', 'type' => 'rectangle']],
                    'appState' => ['scrollX' => 0, 'scrollY' => 0, 'zoom' => 1],
                    'files' => [],
                ],
            ],
            'attributes' => [
                'drawing_data' => [
                    'elements' => [['id' => 'rect1', 'type' => 'rectangle']],
                    'appState' => ['scrollX' => 100, 'scrollY' => 200, 'zoom' => 2],
                    'files' => [],
                ],
            ],
        ])
        ->event('updated')
        ->log('Updated drawing');

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'updated')
        ->first();

    expect($activity)->toBeNull();
});

it('persists activity with significant field changes', function () {
    $note = Note::factory()->create();

    activity()
        ->performedOn($note)
        ->withChanges([
            'old' => ['title' => 'Old title'],
            'attributes' => ['title' => 'New title'],
        ])
        ->event('updated')
        ->log('Updated note');

    $activity = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->getKey())
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes['attributes']['title'])->toBe('New title');
});
