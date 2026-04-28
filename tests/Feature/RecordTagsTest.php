<?php

use App\Models\Bookmark;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('an existing team tag can be attached to a record', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Important', 'slug' => 'important']);

    actingAs($user)
        ->postJson(route('team.tags.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'tag_id' => $tag->id,
        ])
        ->assertCreated()
        ->assertJsonPath('tag.name', 'Important');

    expect($task->fresh()->recordTags()->pluck('tags.id')->all())->toBe([$tag->id]);
});

test('a new tag can be created from typed text', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->postJson(route('team.tags.store', ['current_team' => $team]), [
            'from_type' => 'contact',
            'from_id' => $contact->id,
            'name' => 'Needs Follow Up',
        ])
        ->assertCreated()
        ->assertJsonPath('tag.slug', 'needs-follow-up');

    $tag = Tag::query()->where('slug', 'needs-follow-up')->firstOrFail();

    expect($tag->team_id)->toBe($team->id);
    expect($contact->fresh()->recordTags()->pluck('tags.id')->all())->toBe([$tag->id]);
});

test('tag candidates search existing tags and excludes attached tags', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);
    $attached = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Frontend', 'slug' => 'frontend']);
    $available = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Follow Up', 'slug' => 'follow-up']);
    Tag::factory()->create(['team_id' => Team::factory(), 'name' => 'Foreign Follow Up', 'slug' => 'foreign-follow-up']);

    $task->recordTags()->attach($attached->id);

    $response = actingAs($user)
        ->getJson(route('team.tags.candidates', [
            'current_team' => $team,
            'q' => 'fo',
            'from_type' => 'task',
            'from_id' => $task->id,
        ]))
        ->assertOk();

    $ids = array_column($response->json('tags'), 'id');

    expect($ids)->toContain($available->id);
    expect($ids)->not->toContain($attached->id);
});

test('duplicate record tags are prevented', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id]);

    $task->recordTags()->attach($tag->id);

    actingAs($user)
        ->postJson(route('team.tags.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'tag_id' => $tag->id,
        ])
        ->assertUnprocessable()
        ->assertJson(['message' => 'Tag already exists on this record.']);
});

test('a tag can be detached from a record', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id]);

    $task->recordTags()->attach($tag->id);

    actingAs($user)
        ->deleteJson(route('team.tags.destroy', ['current_team' => $team]).'?'.http_build_query([
            'from_type' => 'task',
            'from_id' => $task->id,
            'tag_id' => $tag->id,
        ]))
        ->assertOk()
        ->assertJson(['message' => 'Tag removed.']);

    expect($task->fresh()->recordTags()->count())->toBe(0);
});

test('cross-team tags cannot be attached', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);
    $otherTag = Tag::factory()->create(['team_id' => Team::factory()]);

    actingAs($user)
        ->postJson(route('team.tags.store', ['current_team' => $team]), [
            'from_type' => 'task',
            'from_id' => $task->id,
            'tag_id' => $otherTag->id,
        ])
        ->assertNotFound()
        ->assertJson(['message' => 'Tag not found.']);
});

test('guests and non-members cannot access tag endpoints', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $this->postJson(route('team.tags.store', ['current_team' => $team]))
        ->assertUnauthorized();

    actingAs($user)
        ->getJson(route('team.tags.candidates', ['current_team' => $team]))
        ->assertForbidden();
});

test('record tags appear on task edit page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Planning', 'slug' => 'planning']);

    $task->recordTags()->attach($tag->id);

    actingAs($user)
        ->get(route('team.tasks.edit', ['current_team' => $team, 'project' => $project->id, 'task' => $task->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recordTags')
            ->where('recordTags.tags.0.name', 'Planning'));
});

test('a bookmark can be tagged through the shared tag endpoint', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $bookmark = Bookmark::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->postJson(route('team.tags.store', ['current_team' => $team]), [
            'from_type' => 'bookmark',
            'from_id' => $bookmark->id,
            'name' => 'docs',
        ])
        ->assertCreated();

    expect($bookmark->fresh()->recordTags()->pluck('name')->all())->toBe(['docs']);
});

test('a note can be tagged through the shared tag endpoint', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->postJson(route('team.tags.store', ['current_team' => $team]), [
            'from_type' => 'note',
            'from_id' => $note->id,
            'name' => 'research',
        ])
        ->assertCreated();

    expect($note->fresh()->recordTags()->pluck('name')->all())->toBe(['research']);
});
