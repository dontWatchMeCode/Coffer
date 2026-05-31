<?php

use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\LogEntry;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('search requires authentication', function () {
    $team = Team::factory()->create();

    get(route('team.search', ['current_team' => $team, 'q' => 'test']))
        ->assertRedirect(route('login'));
});

test('search returns matching team records', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    expect($team)->not->toBeNull();

    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Special task title',
        'description' => 'Task description',
    ]);

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Special contact name',
    ]);

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Special event title',
    ]);

    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Special project name',
    ]);

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Special note title',
    ]);

    RecordCollection::factory()->create([
        'team_id' => $team->id,
        'title' => 'Special collection title',
    ]);

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Special log entry body',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'Special']))
        ->assertOk()
        ->assertJsonPath('tasks.0.title', 'Special task title')
        ->assertJsonPath('contacts.0.title', 'Special contact name')
        ->assertJsonPath('events.0.title', 'Special event title')
        ->assertJsonPath('projects.0.title', 'Special project name')
        ->assertJsonPath('notes.0.title', 'Special note title')
        ->assertJsonPath('collections.0.title', 'Special collection title')
        ->assertJsonPath('log_entries.0.title', 'Special log entry body');
});

test('search does not return records from other teams', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    Task::factory()->create([
        'team_id' => $otherTeam->id,
        'project_id' => Project::factory()->create(['team_id' => $otherTeam->id]),
        'title' => 'Secret task',
    ]);

    Contact::factory()->create([
        'team_id' => $otherTeam->id,
        'name' => 'Secret contact',
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Secret event',
    ]);

    Project::factory()->create([
        'team_id' => $otherTeam->id,
        'name' => 'Secret project',
    ]);

    Note::factory()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Secret note',
    ]);

    RecordCollection::factory()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Secret collection',
    ]);

    LogEntry::factory()->create([
        'team_id' => $otherTeam->id,
        'body' => 'Secret log',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'Secret']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(0, 'projects')
        ->assertJsonCount(0, 'notes')
        ->assertJsonCount(0, 'collections')
        ->assertJsonCount(0, 'log_entries');
});

test('search returns empty results for blank query', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => '']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(0, 'projects')
        ->assertJsonCount(0, 'notes')
        ->assertJsonCount(0, 'collections')
        ->assertJsonCount(0, 'log_entries');
});

test('search treats sql wildcard characters literally', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Save 100% now',
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Save 100 points now',
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'release_candidate',
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'release candidate',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => '100%']))
        ->assertOk()
        ->assertJsonCount(1, 'tasks')
        ->assertJsonPath('tasks.0.title', 'Save 100% now');

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'release_']))
        ->assertOk()
        ->assertJsonCount(1, 'tasks')
        ->assertJsonPath('tasks.0.title', 'release_candidate');
});

test('search requires team membership', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $otherTeam, 'q' => 'test']))
        ->assertForbidden();
});

test('prefix t: filters to tasks only', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'My task',
    ]);

    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'My contact',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 't: My']))
        ->assertOk()
        ->assertJsonCount(1, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(0, 'projects')
        ->assertJsonCount(0, 'bookmarks');
});

test('prefix c: filters to contacts only', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'My task',
    ]);

    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'My contact',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'c: My']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(1, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(0, 'projects')
        ->assertJsonCount(0, 'bookmarks');
});

test('prefix e: filters to events only', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'My task',
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'My event',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'e: My']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(1, 'events')
        ->assertJsonCount(0, 'projects')
        ->assertJsonCount(0, 'bookmarks');
});

test('prefix p: filters to projects only', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'My task',
    ]);

    Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'My project',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'p: My']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(1, 'projects')
        ->assertJsonCount(0, 'bookmarks');
});

test('prefix b: filters to bookmarks only', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'My task',
    ]);

    Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'My bookmark',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'b: My']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(0, 'projects')
        ->assertJsonCount(1, 'bookmarks');
});

test('prefix n: filters to notes only', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'My task',
    ]);

    Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'My note',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'n: My']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(0, 'projects')
        ->assertJsonCount(0, 'bookmarks')
        ->assertJsonCount(1, 'notes');
});

test('prefix l: filters to collections only', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'My task',
    ]);

    RecordCollection::factory()->create([
        'team_id' => $team->id,
        'title' => 'My collection',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'l: My']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(0, 'projects')
        ->assertJsonCount(0, 'bookmarks')
        ->assertJsonCount(0, 'notes')
        ->assertJsonCount(1, 'collections');
});

test('prefix g: filters to log entries only', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'My task',
    ]);

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'My log entry',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'g: My']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(0, 'projects')
        ->assertJsonCount(0, 'bookmarks')
        ->assertJsonCount(0, 'notes')
        ->assertJsonCount(1, 'log_entries');
});

test('log entries link to log list page in search results', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Unique log body text',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'Unique log']))
        ->assertOk()
        ->assertJsonCount(1, 'log_entries')
        ->assertJsonPath('log_entries.0.url', route('team.log.index', ['current_team' => $team]));
});

test('prefix is case-insensitive', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'My task',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'T: My']))
        ->assertOk()
        ->assertJsonCount(1, 'tasks')
        ->assertJsonCount(0, 'contacts');
});

test('unknown prefix is treated as literal query', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'My task',
    ]);

    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'My contact',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'x: My']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts');
});

test('prefix-only empty query returns empty results', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'My task',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 't:']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks')
        ->assertJsonCount(0, 'contacts')
        ->assertJsonCount(0, 'events')
        ->assertJsonCount(0, 'projects')
        ->assertJsonCount(0, 'bookmarks')
        ->assertJsonCount(0, 'collections');
});

test('tag filter returns records with matching tag', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Urgent', 'slug' => 'urgent']);

    $taggedTask = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Tagged task',
    ]);
    $taggedTask->recordTags()->attach($tag);

    $untaggedTask = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Other task',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => '#urgent']))
        ->assertOk()
        ->assertJsonCount(1, 'tasks')
        ->assertJsonPath('tasks.0.title', 'Tagged task');
});

test('tag filter works alongside text query', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Work', 'slug' => 'work']);

    $matchingTask = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Deploy project',
    ]);
    $matchingTask->recordTags()->attach($tag);

    $nonMatchingTask = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Deploy something',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 'Deploy #work']))
        ->assertOk()
        ->assertJsonCount(1, 'tasks')
        ->assertJsonPath('tasks.0.title', 'Deploy project');
});

test('tag filter works with prefix search', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Bug', 'slug' => 'bug']);

    $taggedTask = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Fix issue',
    ]);
    $taggedTask->recordTags()->attach($tag);

    $taggedBookmark = Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Bug report link',
    ]);
    $taggedBookmark->recordTags()->attach($tag);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => 't: #bug']))
        ->assertOk()
        ->assertJsonCount(1, 'tasks')
        ->assertJsonCount(0, 'bookmarks');
});

test('tag filter does not return records from other teams', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    $otherTag = Tag::factory()->create(['team_id' => $otherTeam->id, 'name' => 'Secret', 'slug' => 'secret']);

    $otherTask = Task::factory()->create([
        'team_id' => $otherTeam->id,
        'project_id' => Project::factory()->create(['team_id' => $otherTeam->id]),
        'title' => 'Secret task',
    ]);
    $otherTask->recordTags()->attach($otherTag);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => '#secret']))
        ->assertOk()
        ->assertJsonCount(0, 'tasks');
});

test('tag filter across multiple record types', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Important', 'slug' => 'important']);

    $taggedTask = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Important task',
    ]);
    $taggedTask->recordTags()->attach($tag);

    $taggedNote = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Important note',
    ]);
    $taggedNote->recordTags()->attach($tag);

    $untaggedTask = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => Project::factory()->create(['team_id' => $team->id]),
        'title' => 'Regular task',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => '#important']))
        ->assertOk()
        ->assertJsonCount(1, 'tasks')
        ->assertJsonPath('tasks.0.title', 'Important task')
        ->assertJsonCount(1, 'notes')
        ->assertJsonPath('notes.0.title', 'Important note');
});

test('tag filter returns empty for models without tags', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $tag = Tag::factory()->create(['team_id' => $team->id, 'name' => 'Important', 'slug' => 'important']);

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Important log entry',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team, 'q' => '#important']))
        ->assertOk()
        ->assertJsonCount(0, 'log_entries');
});
