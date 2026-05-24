<?php

use App\Models\Contact;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('team settings can disable and enable features', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->patch(route('teams.update', ['team' => $team]), [
            'name' => $team->name,
            'feature_settings' => [
                'tasks' => true,
                'calendar' => true,
                'contacts' => false,
                'bookmarks' => true,
                'subscriptions' => true,
                'notes' => true,
                'log' => true,
                'collections' => true,
            ],
        ])
        ->assertRedirect(route('teams.edit', ['team' => $team->fresh()->slug]));

    expect($team->fresh()->hasFeature('contacts'))->toBeFalse();
});

test('disabled features block routes and preserve records', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    $team->update(['feature_settings' => array_replace($team->featureSettings(), ['contacts' => false])]);

    actingAs($user)
        ->get(route('team.contacts.index', ['current_team' => $team]))
        ->assertNotFound();

    expect(Contact::query()->whereKey($contact->id)->exists())->toBeTrue();
});

test('disabled features are excluded from search', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Hidden Contact',
    ]);

    $team->update(['feature_settings' => array_replace($team->featureSettings(), ['contacts' => false])]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'Hidden']))
        ->assertOk()
        ->assertJsonPath('contacts', []);
});

test('disabled features are excluded from linked-record candidate search', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);

    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Hidden Candidate',
    ]);

    $team->update(['feature_settings' => array_replace($team->featureSettings(), ['contacts' => false])]);

    actingAs($user)
        ->getJson(route('team.links.candidates', [
            'current_team' => $team,
            'q' => 'Hidden',
            'from_type' => 'task',
            'from_id' => $task->id,
        ]))
        ->assertOk()
        ->assertJsonPath('records', []);
});

test('disabled feature records cannot be tagged or linked directly', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    $team->update(['feature_settings' => array_replace($team->featureSettings(), ['contacts' => false])]);

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'to_type' => 'contact',
            'to_id' => $contact->id,
        ])
        ->assertNotFound();

    actingAs($user)
        ->postJson(route('team.tags.store', ['current_team' => $team]), [
            'from_type' => 'contact',
            'from_id' => $contact->id,
            'name' => 'Hidden',
        ])
        ->assertNotFound();
});

test('disabled feature activity history is hidden', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    $team->update(['feature_settings' => array_replace($team->featureSettings(), ['contacts' => false])]);

    actingAs($user)
        ->getJson(route('team.activity-history.index', [
            'current_team' => $team,
            'subject_type' => 'contact',
            'subject_id' => $contact->id,
        ]))
        ->assertOk()
        ->assertJsonPath('activities', [])
        ->assertJsonPath('total', 0);
});
