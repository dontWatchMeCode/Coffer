<?php

use App\Models\Bookmark;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

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
    expect(Tag::query()->whereKey($tag->id)->exists())->toBeFalse();
});

test('a detached tag is kept when another record still uses it', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $firstTask = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);
    $secondTask = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id]);

    $firstTask->recordTags()->attach($tag->id);
    $secondTask->recordTags()->attach($tag->id);

    actingAs($user)
        ->deleteJson(route('team.tags.destroy', ['current_team' => $team]).'?'.http_build_query([
            'from_type' => 'task',
            'from_id' => $firstTask->id,
            'tag_id' => $tag->id,
        ]))
        ->assertOk();

    expect($firstTask->fresh()->recordTags()->count())->toBe(0);
    expect($secondTask->fresh()->recordTags()->whereKey($tag->id)->exists())->toBeTrue();
    expect(Tag::query()->whereKey($tag->id)->exists())->toBeTrue();
});

test('unused tags are cleaned up when a tagged record is permanently deleted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id]);

    actingAs($user);
    $task->recordTags()->attach($tag->id);

    $task->forceDelete();

    expect(Tag::query()->whereKey($tag->id)->exists())->toBeFalse();
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

test('a collection can be tagged through the shared tag endpoint', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $collection = RecordCollection::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->postJson(route('team.tags.store', ['current_team' => $team]), [
            'from_type' => 'collection',
            'from_id' => $collection->id,
            'name' => 'planning',
        ])
        ->assertCreated();

    expect($collection->fresh()->recordTags()->pluck('name')->all())->toBe(['planning']);
});

test('attaching a tag logs an activity on the record', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Important']);

    actingAs($user)
        ->postJson(route('team.tags.store', ['current_team' => $team]), [
            'from_type' => 'note',
            'from_id' => $note->id,
            'tag_id' => $tag->id,
        ])
        ->assertCreated();

    $activities = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->id)
        ->orderByDesc('id')
        ->get();

    expect($activities)->toHaveCount(2);
    expect($activities->first()->event)->toBe('tagged');
    expect($activities->first()->description)->toBe('Added tag Important');
    expect($activities->first()->properties['relation_changes']['type'])->toBe('tag');
    expect($activities->first()->properties['relation_changes']['action'])->toBe('added');
});

test('detaching a tag logs an activity on the record', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Draft']);

    $note->recordTags()->attach($tag->id);

    actingAs($user)
        ->deleteJson(route('team.tags.destroy', ['current_team' => $team]).'?'.http_build_query([
            'from_type' => 'note',
            'from_id' => $note->id,
            'tag_id' => $tag->id,
        ]))
        ->assertOk();

    $activities = Activity::where('subject_type', $note->getMorphClass())
        ->where('subject_id', $note->id)
        ->orderByDesc('id')
        ->get();

    expect($activities)->toHaveCount(2);
    expect($activities->first()->event)->toBe('untagged');
    expect($activities->first()->description)->toBe('Removed tag Draft');
    expect($activities->first()->properties['relation_changes']['action'])->toBe('removed');
});

test('tag activity appears in activity history payload', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);
    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Review']);

    actingAs($user)
        ->postJson(route('team.tags.store', ['current_team' => $team]), [
            'from_type' => 'note',
            'from_id' => $note->id,
            'tag_id' => $tag->id,
        ])
        ->assertCreated();

    actingAs($user)
        ->get(route('team.notes.show', ['current_team' => $team, 'note' => $note->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notes/Index')
            ->has('activityHistory')
            ->where('activityHistory.subject_type', 'note')
            ->whereType('activityHistory.total', 'integer'));
});
