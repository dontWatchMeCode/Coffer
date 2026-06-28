<?php

use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\RecordLink;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

test('a link can be created between two records', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => Project::factory()->create(['team_id' => $team->id])->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ])
        ->assertCreated()
        ->assertJson(['message' => 'Link created.']);

    expect(RecordLink::count())->toBe(1);
});

test('backlinks are visible from both sides', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => Project::factory()->create(['team_id' => $team->id])->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ]);

    $taskLinks = $task->formattedLinkedRecords($team);
    $contactLinks = $contact->formattedLinkedRecords($team);

    expect($taskLinks)->toHaveCount(1);
    expect($taskLinks[0]['id'])->toBe($contact->id);
    expect($taskLinks[0]['type'])->toBe('contact');
    expect($taskLinks[0]['url'])->toBe(route('team.contacts.show', ['current_team' => $team, 'contact' => $contact]));

    expect($contactLinks)->toHaveCount(1);
    expect($contactLinks[0]['id'])->toBe($task->id);
    expect($contactLinks[0]['type'])->toBe('task');
    expect($contactLinks[0]['url'])->toBe(route('team.tasks.edit', ['current_team' => $team, 'project' => $task->project_id, 'task' => $task]));
});

test('linked records with the same id across different types are all visible', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $contact = Contact::factory()->create(['team_id' => $team->id]);
    $bookmark = Bookmark::factory()->create(['team_id' => $team->id]);
    $note = Note::factory()->create(['team_id' => $team->id]);

    actingAs($user);

    RecordLink::create([
        'team_id' => $team->id,
        'left_type' => $bookmark->linkableType(),
        'left_id' => $bookmark->id,
        'right_type' => $contact->linkableType(),
        'right_id' => $contact->id,
    ]);

    RecordLink::create([
        'team_id' => $team->id,
        'left_type' => $contact->linkableType(),
        'left_id' => $contact->id,
        'right_type' => $note->linkableType(),
        'right_id' => $note->id,
    ]);

    $links = $contact->formattedLinkedRecords($team);

    expect($links)->toHaveCount(2);
    expect(array_column($links, 'type'))->toContain('bookmark', 'note');
});

test('duplicate links are prevented', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => Project::factory()->create(['team_id' => $team->id])->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ])
        ->assertCreated();

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'contact',
            'from_id' => $contact->id,
            'to_type' => 'task',
            'to_id' => $task->id,
        ])
        ->assertUnprocessable()
        ->assertJson(['message' => 'Link already exists.']);

    expect(RecordLink::count())->toBe(1);
});

test('self-links are rejected', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => Project::factory()->create(['team_id' => $team->id])->id]);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'task',
            'to_id' => $task->id,
        ])
        ->assertUnprocessable()
        ->assertJson(['message' => 'Cannot link a record to itself.']);
});

test('cross-team links are rejected', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => Project::factory()->create(['team_id' => $team->id])->id]);
    $contact = Contact::factory()->create(['team_id' => $otherTeam->id]);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ])
        ->assertNotFound()
        ->assertJson(['message' => 'Record not found.']);
});

test('a link can be destroyed', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => Project::factory()->create(['team_id' => $team->id])->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ]);

    actingAs($user)
        ->deleteJson(route('team.links.destroy', ['current_team' => $team]).'?'.http_build_query([
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ]))
        ->assertOk()
        ->assertJson(['message' => 'Link removed.']);

    expect(RecordLink::count())->toBe(0);
});

test('candidates search excludes current and already linked records', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => Project::factory()->create(['team_id' => $team->id])->id]);
    $linkedContact = Contact::factory()->create(['team_id' => $team->id, 'name' => 'Linked Contact']);
    $unlinkedContact = Contact::factory()->create(['team_id' => $team->id, 'name' => 'Unlinked Contact']);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $linkedContact->id,
        ]);

    $response = actingAs($user)
        ->getJson(route('team.links.candidates', ['current_team' => $team]).'?q=Contact&from_type=task&from_id='.$task->id)
        ->assertOk();

    $records = $response->json('records');
    $ids = array_column($records, 'id');

    expect($ids)->not->toContain($task->id);
    expect($ids)->not->toContain($linkedContact->id);
    expect($ids)->toContain($unlinkedContact->id);
});

test('candidates search supports record type prefixes', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create(['team_id' => $team->id, 'name' => 'Shared project']);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id, 'title' => 'Current task']);

    Contact::factory()->create(['team_id' => $team->id, 'name' => 'Shared contact']);
    CalendarEvent::factory()->create(['team_id' => $team->id, 'title' => 'Shared event']);
    Bookmark::factory()->create(['team_id' => $team->id, 'title' => 'Shared bookmark']);
    Note::factory()->create(['team_id' => $team->id, 'title' => 'Shared note']);
    RecordCollection::factory()->create(['team_id' => $team->id, 'title' => 'Shared collection']);

    actingAs($user)
        ->getJson(route('team.links.candidates', [
            'current_team' => $team,
            'q' => 'c: Shared',
            'from_type' => 'task',
            'from_id' => $task->id,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'records')
        ->assertJsonPath('records.0.type', 'contact');

    actingAs($user)
        ->getJson(route('team.links.candidates', [
            'current_team' => $team,
            'q' => 'e: Shared',
            'from_type' => 'task',
            'from_id' => $task->id,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'records')
        ->assertJsonPath('records.0.type', 'calendar_event');

    actingAs($user)
        ->getJson(route('team.links.candidates', [
            'current_team' => $team,
            'q' => 'b: Shared',
            'from_type' => 'task',
            'from_id' => $task->id,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'records')
        ->assertJsonPath('records.0.type', 'bookmark');

    actingAs($user)
        ->getJson(route('team.links.candidates', [
            'current_team' => $team,
            'q' => 'n: Shared',
            'from_type' => 'task',
            'from_id' => $task->id,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'records')
        ->assertJsonPath('records.0.type', 'note');

    actingAs($user)
        ->getJson(route('team.links.candidates', [
            'current_team' => $team,
            'q' => 'l: Shared',
            'from_type' => 'task',
            'from_id' => $task->id,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'records')
        ->assertJsonPath('records.0.type', 'collection');
});

test('collections can be linked to other records', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $collection = RecordCollection::factory()->create([
        'team_id' => $team->id,
        'description' => 'Collection preview',
    ]);
    $note = Note::factory()
        ->withTextBlock('Note preview')
        ->create([
            'team_id' => $team->id,
            'title' => 'Linked note',
        ]);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'collection',
            'from_id' => $collection->id,
            'to_type' => 'note',
            'to_id' => $note->id,
        ])
        ->assertCreated();

    $collectionLinks = $collection->formattedLinkedRecords($team);
    $noteLinks = $note->formattedLinkedRecords($team);

    expect($collectionLinks[0]['type'])->toBe('note');
    expect($collectionLinks[0]['title'])->toBe('Linked note');
    expect($collectionLinks[0]['preview'])->toBe('Note preview');
    expect($noteLinks[0]['type'])->toBe('collection');
    expect($noteLinks[0]['url'])->toBe(route('team.collections.show', ['current_team' => $team, 'collection' => $collection]));
    expect($noteLinks[0]['preview'])->toBe('Collection preview');
});

test('linked record drawing data is opt in', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $collection = RecordCollection::factory()->create(['team_id' => $team->id]);
    $note = Note::factory()->create([
        'team_id' => $team->id,
    ]);
    $note->blocks()->create([
        'type' => 'excalidraw',
        'position' => 0,
        'payload' => [
            'scene' => [
                'type' => 'excalidraw',
                'elements' => [
                    ['id' => 'box-1', 'type' => 'rectangle'],
                ],
            ],
        ],
    ]);

    RecordLink::create([
        'team_id' => $team->id,
        'left_type' => $collection->linkableType(),
        'left_id' => $collection->id,
        'right_type' => $note->linkableType(),
        'right_id' => $note->id,
    ]);

    actingAs($user);

    expect($collection->formattedLinkedRecords($team)[0]['drawingData'])->toBeNull();

    $drawingData = $collection->formattedLinkedRecords($team, includeDrawingData: true)[0]['drawingData'];
    expect($drawingData)->not->toBeNull();
    expect($drawingData['type'])->toBe('excalidraw');
    expect($drawingData['elements'])->toBe([['id' => 'box-1', 'type' => 'rectangle']]);
});

test('unknown candidate search prefix is treated as literal query', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id, 'title' => 'Current task']);
    Contact::factory()->create(['team_id' => $team->id, 'name' => 'Shared contact']);

    actingAs($user)
        ->getJson(route('team.links.candidates', [
            'current_team' => $team,
            'q' => 'x: Shared',
            'from_type' => 'task',
            'from_id' => $task->id,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'records');
});

test('prefix-only empty candidate query returns empty results', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id, 'title' => 'Current task']);
    Contact::factory()->create(['team_id' => $team->id, 'name' => 'Shared contact']);

    actingAs($user)
        ->getJson(route('team.links.candidates', [
            'current_team' => $team,
            'q' => 'c:',
            'from_type' => 'task',
            'from_id' => $task->id,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'records');
});

test('guests cannot access link endpoints', function () {
    $team = Team::factory()->create();

    $this->postJson(route('team.links.store', ['current_team' => $team]))
        ->assertUnauthorized();

    $this->deleteJson(route('team.links.destroy', ['current_team' => $team]))
        ->assertUnauthorized();

    $this->getJson(route('team.links.candidates', ['current_team' => $team]))
        ->assertUnauthorized();
});

test('non-members cannot access link endpoints', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $otherTeam]))
        ->assertForbidden();

    actingAs($user)
        ->deleteJson(route('team.links.destroy', ['current_team' => $otherTeam]))
        ->assertForbidden();

    actingAs($user)
        ->getJson(route('team.links.candidates', ['current_team' => $otherTeam]))
        ->assertForbidden();
});

test('record links appear on task edit page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('recordLinks'));
});

test('record links are cleaned up when a linked model is permanently deleted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => Project::factory()->create(['team_id' => $team->id])->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ]);

    expect(RecordLink::count())->toBe(1);

    $contact->forceDelete();

    expect(RecordLink::count())->toBe(0);
});

test('creating a link logs activity on both sides', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => Project::factory()->create(['team_id' => $team->id])->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id, 'name' => 'John Doe']);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ])
        ->assertCreated();

    $taskActivities = Activity::where('subject_type', $task->getMorphClass())
        ->where('subject_id', $task->id)
        ->whereIn('event', ['linked', 'unlinked'])
        ->orderByDesc('id')
        ->get();
    $contactActivities = Activity::where('subject_type', $contact->getMorphClass())
        ->where('subject_id', $contact->id)
        ->whereIn('event', ['linked', 'unlinked'])
        ->orderByDesc('id')
        ->get();

    expect($taskActivities)->toHaveCount(1);
    expect($taskActivities->first()->event)->toBe('linked');
    expect($taskActivities->first()->description)->toBe('Linked to contact: John Doe');
    expect($taskActivities->first()->properties['relation_changes']['type'])->toBe('link');

    expect($contactActivities)->toHaveCount(1);
    expect($contactActivities->first()->event)->toBe('linked');
    expect($contactActivities->first()->description)->toBe('Linked to task: '.$task->title);
});

test('destroying a link logs activity on both sides', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => Project::factory()->create(['team_id' => $team->id])->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id, 'name' => 'Jane Doe']);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ]);

    actingAs($user)
        ->deleteJson(route('team.links.destroy', ['current_team' => $team]).'?'.http_build_query([
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ]))
        ->assertOk();

    $taskActivities = Activity::where('subject_type', $task->getMorphClass())
        ->where('subject_id', $task->id)
        ->whereIn('event', ['linked', 'unlinked'])
        ->orderByDesc('id')
        ->get();
    $contactActivities = Activity::where('subject_type', $contact->getMorphClass())
        ->where('subject_id', $contact->id)
        ->whereIn('event', ['linked', 'unlinked'])
        ->orderByDesc('id')
        ->get();

    expect($taskActivities)->toHaveCount(2);
    expect($taskActivities->first()->event)->toBe('unlinked');
    expect($taskActivities->first()->description)->toBe('Unlinked from contact: Jane Doe');

    expect($contactActivities)->toHaveCount(2);
    expect($contactActivities->first()->event)->toBe('unlinked');
    expect($contactActivities->first()->description)->toBe('Unlinked from task: '.$task->title);
});

test('permanently deleting a record logs unlink activity on the surviving side', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => Project::factory()->create(['team_id' => $team->id])->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id, 'name' => 'Survivor']);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ]);

    $task->forceDelete();

    $contactActivities = Activity::where('subject_type', $contact->getMorphClass())
        ->where('subject_id', $contact->id)
        ->orderByDesc('id')
        ->get();

    expect($contactActivities)->toHaveCount(3);
    expect($contactActivities->first()->event)->toBe('unlinked');
    expect($contactActivities->first()->description)->toBe('Unlinked from task: '.$task->title);
});

test('link activity appears in activity history payload', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id, 'title' => 'Note A']);
    $contact = Contact::factory()->create(['team_id' => $team->id, 'name' => 'Contact B']);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'note',
            'from_id' => $note->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ]);

    actingAs($user)
        ->get(route('team.notes.show', ['current_team' => $team, 'note' => $note->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notes/Index')
            ->has('activityHistory')
            ->where('activityHistory.subject_type', 'note')
            ->whereType('activityHistory.total', 'integer'));
});
