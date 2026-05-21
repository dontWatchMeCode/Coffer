<?php

use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\LogEntry;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('bookmarks index returns cursor-paginated data', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Bookmark::factory()->count(30)->create(['team_id' => $team->id]);

    actingAs($user)
        ->get(route('team.bookmarks.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookmarks/Index')
            ->has('bookmarks')
            ->has('bookmarks.data')
        );
});

test('bookmarks index supports search filter', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Bookmark::factory()->create(['team_id' => $team->id, 'title' => 'Laravel Docs', 'url' => 'https://laravel.com']);
    Bookmark::factory()->create(['team_id' => $team->id, 'title' => 'Vue Router', 'url' => 'https://router.vuejs.org']);
    Bookmark::factory()->create(['team_id' => $team->id, 'title' => 'Tailwind CSS', 'url' => 'https://tailwindcss.com']);

    actingAs($user)
        ->get(route('team.bookmarks.index', ['current_team' => $team, 'search' => 'Laravel']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookmarks/Index')
            ->has('bookmarks.data', 1)
            ->where('bookmarks.data.0.title', 'Laravel Docs')
        );
});

test('bookmarks index paginates with cursor', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Bookmark::factory()->count(30)->create(['team_id' => $team->id]);

    $response = actingAs($user)
        ->get(route('team.bookmarks.index', ['current_team' => $team]))
        ->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('bookmarks/Index')
        ->has('bookmarks.data', 25)
    );
});

test('contacts index returns cursor-paginated data with search', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Contact::factory()->create(['team_id' => $team->id, 'name' => 'Alice Johnson']);
    Contact::factory()->create(['team_id' => $team->id, 'name' => 'Bob Smith']);

    actingAs($user)
        ->get(route('team.contacts.index', ['current_team' => $team, 'search' => 'Alice']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/Index')
            ->has('contacts.data', 1)
            ->where('contacts.data.0.name', 'Alice Johnson')
        );
});

test('notes index returns cursor-paginated data', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Note::factory()->count(5)->create(['team_id' => $team->id]);

    actingAs($user)
        ->get(route('team.notes.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Index')
            ->has('notes.data')
        );
});

test('collections index returns cursor-paginated data', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    RecordCollection::factory()->count(5)->create(['team_id' => $team->id]);

    actingAs($user)
        ->get(route('team.collections.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('collections/Index')
            ->has('collections.data')
        );
});

test('subscriptions index returns cursor-paginated data with search', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Subscription::factory()->create(['team_id' => $team->id, 'name' => 'Netflix', 'description' => null, 'category' => null]);
    Subscription::factory()->create(['team_id' => $team->id, 'name' => 'Spotify', 'description' => null, 'category' => null]);

    actingAs($user)
        ->get(route('team.subscriptions.index', ['current_team' => $team, 'search' => 'Net']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('subscriptions/Index')
            ->has('subscriptions.data', 1)
            ->where('subscriptions.data.0.name', 'Netflix')
        );
});

test('log index returns cursor-paginated data with search', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    LogEntry::factory()->create(['team_id' => $team->id, 'body' => 'Important meeting notes']);
    LogEntry::factory()->create(['team_id' => $team->id, 'body' => 'Quick reminder']);

    actingAs($user)
        ->get(route('team.log.index', ['current_team' => $team, 'search' => 'Important']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('log/Index')
            ->has('entries.data', 1)
            ->where('entries.data.0.body', 'Important meeting notes')
        );
});

test('search filter isolates to current team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $otherTeam = User::factory()->create()->currentTeam;
    Bookmark::factory()->create(['team_id' => $otherTeam->id, 'title' => 'Secret Bookmark']);

    Bookmark::factory()->create(['team_id' => $team->id, 'title' => 'My Bookmark']);

    actingAs($user)
        ->get(route('team.bookmarks.index', ['current_team' => $team, 'search' => 'Secret']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookmarks/Index')
            ->has('bookmarks.data', 0)
        );
});

test('calendar index returns paginated future events', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    CalendarEvent::factory()->create(['team_id' => $team->id, 'title' => 'Future Event', 'date' => now()->addDays(5)]);
    CalendarEvent::factory()->create(['team_id' => $team->id, 'title' => 'Past Event', 'date' => now()->subDays(5)]);

    actingAs($user)
        ->get(route('team.calendar.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/Index')
            ->has('events.data', 1)
            ->where('events.data.0.title', 'Future Event')
        );
});

test('calendar index supports search filter', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    CalendarEvent::factory()->create(['team_id' => $team->id, 'title' => 'Sprint Planning', 'date' => now()->addDays(5)]);
    CalendarEvent::factory()->create(['team_id' => $team->id, 'title' => 'Retrospective', 'date' => now()->addDays(10)]);

    actingAs($user)
        ->get(route('team.calendar.index', ['current_team' => $team, 'search' => 'Sprint']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/Index')
            ->has('events.data', 1)
            ->where('events.data.0.title', 'Sprint Planning')
        );
});

test('task show page returns paginated tasks', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    Task::factory()->count(3)->create(['team_id' => $team->id, 'project_id' => $project->id, 'created_by' => $user->id]);

    actingAs($user)
        ->get(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Show')
            ->has('tasks.data', 3)
        );
});

test('task show page supports search filter', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id, 'created_by' => $user->id, 'title' => 'Design database schema']);
    Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id, 'created_by' => $user->id, 'title' => 'Build API endpoints']);

    actingAs($user)
        ->get(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id, 'search' => 'database']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Show')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.title', 'Design database schema')
        );
});
