<?php

use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Project;
use App\Models\RecordLink;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

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

test('record links are cleaned up when a linked model is deleted', function () {
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

    $contact->delete();

    expect(RecordLink::count())->toBe(0);
});
