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
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('bookmarks index traverses simple-paginated pages in order', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    foreach (range(1, 30) as $i) {
        Bookmark::factory()->create([
            'team_id' => $team->id,
            'title' => sprintf('Bookmark %02d', $i),
            'created_at' => now()->subMinutes(30 - $i),
            'updated_at' => now()->subMinutes(30 - $i),
        ]);
    }

    $pageOneTitles = collect(range(30, 6, -1))->map(fn (int $i): string => sprintf('Bookmark %02d', $i))->all();
    $pageTwoTitles = collect(range(5, 1, -1))->map(fn (int $i): string => sprintf('Bookmark %02d', $i))->all();

    actingAs($user)
        ->get(route('team.bookmarks.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookmarks/Index')
            ->has('bookmarks.data', 25)
            ->where('bookmarks.current_page', 1)
            ->where('bookmarks.per_page', 25)
            ->where('bookmarks.data', fn (Collection $data): bool => $data->pluck('title')->all() === $pageOneTitles)
            ->whereNotNull('bookmarks.next_page_url')
            ->whereNull('bookmarks.prev_page_url')
        );

    actingAs($user)
        ->get(route('team.bookmarks.index', ['current_team' => $team, 'page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookmarks/Index')
            ->has('bookmarks.data', 5)
            ->where('bookmarks.current_page', 2)
            ->where('bookmarks.per_page', 25)
            ->where('bookmarks.data', fn (Collection $data): bool => $data->pluck('title')->all() === $pageTwoTitles)
            ->whereNull('bookmarks.next_page_url')
            ->whereNotNull('bookmarks.prev_page_url')
        );

    actingAs($user)
        ->get(route('team.bookmarks.index', ['current_team' => $team, 'page' => 3]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookmarks/Index')
            ->has('bookmarks.data', 0)
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

test('contacts index supports search filter', function () {
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

test('notes index traverses simple-paginated pages in order', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    foreach (range(1, 30) as $i) {
        Note::factory()->create([
            'team_id' => $team->id,
            'title' => sprintf('Note %02d', $i),
            'created_at' => now()->subMinutes(30 - $i),
            'updated_at' => now()->subMinutes(30 - $i),
        ]);
    }

    $pageOneTitles = collect(range(30, 6, -1))->map(fn (int $i): string => sprintf('Note %02d', $i))->all();
    $pageTwoTitles = collect(range(5, 1, -1))->map(fn (int $i): string => sprintf('Note %02d', $i))->all();

    actingAs($user)
        ->get(route('team.notes.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Index')
            ->has('notes.data', 25)
            ->where('notes.current_page', 1)
            ->where('notes.per_page', 25)
            ->where('notes.data', fn (Collection $data): bool => $data->pluck('title')->all() === $pageOneTitles)
            ->whereNotNull('notes.next_page_url')
            ->whereNull('notes.prev_page_url')
        );

    actingAs($user)
        ->get(route('team.notes.index', ['current_team' => $team, 'page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Index')
            ->has('notes.data', 5)
            ->where('notes.current_page', 2)
            ->where('notes.per_page', 25)
            ->where('notes.data', fn (Collection $data): bool => $data->pluck('title')->all() === $pageTwoTitles)
            ->whereNull('notes.next_page_url')
            ->whereNotNull('notes.prev_page_url')
        );

    actingAs($user)
        ->get(route('team.notes.index', ['current_team' => $team, 'page' => 3]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notes/Index')
            ->has('notes.data', 0)
        );
});

test('collections index traverses simple-paginated pages in order', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    foreach (range(1, 30) as $i) {
        RecordCollection::factory()->create([
            'team_id' => $team->id,
            'title' => sprintf('Collection %02d', $i),
            'created_at' => now()->subMinutes(30 - $i),
            'updated_at' => now()->subMinutes(30 - $i),
        ]);
    }

    $pageOneTitles = collect(range(30, 6, -1))->map(fn (int $i): string => sprintf('Collection %02d', $i))->all();
    $pageTwoTitles = collect(range(5, 1, -1))->map(fn (int $i): string => sprintf('Collection %02d', $i))->all();

    actingAs($user)
        ->get(route('team.collections.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('collections/Index')
            ->has('collections.data', 25)
            ->where('collections.current_page', 1)
            ->where('collections.per_page', 25)
            ->where('collections.data', fn (Collection $data): bool => $data->pluck('title')->all() === $pageOneTitles)
            ->whereNotNull('collections.next_page_url')
            ->whereNull('collections.prev_page_url')
        );

    actingAs($user)
        ->get(route('team.collections.index', ['current_team' => $team, 'page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('collections/Index')
            ->has('collections.data', 5)
            ->where('collections.current_page', 2)
            ->where('collections.per_page', 25)
            ->where('collections.data', fn (Collection $data): bool => $data->pluck('title')->all() === $pageTwoTitles)
            ->whereNull('collections.next_page_url')
            ->whereNotNull('collections.prev_page_url')
        );

    actingAs($user)
        ->get(route('team.collections.index', ['current_team' => $team, 'page' => 3]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('collections/Index')
            ->has('collections.data', 0)
        );
});

test('subscriptions index supports search filter', function () {
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

test('log index supports search filter', function () {
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

test('task show page traverses simple-paginated tasks in position order', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    foreach (range(1, 30) as $i) {
        Task::factory()->create([
            'team_id' => $team->id,
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => sprintf('Task %02d', $i),
            'position' => $i,
        ]);
    }

    $pageOneTitles = collect(range(1, 25))->map(fn (int $i): string => sprintf('Task %02d', $i))->all();
    $pageTwoTitles = collect(range(26, 30))->map(fn (int $i): string => sprintf('Task %02d', $i))->all();

    actingAs($user)
        ->get(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Show')
            ->has('tasks.data', 25)
            ->where('tasks.current_page', 1)
            ->where('tasks.per_page', 25)
            ->where('tasks.data', fn (Collection $data): bool => $data->pluck('title')->all() === $pageOneTitles)
            ->whereNotNull('tasks.next_page_url')
            ->whereNull('tasks.prev_page_url')
        );

    actingAs($user)
        ->get(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id, 'page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Show')
            ->has('tasks.data', 5)
            ->where('tasks.current_page', 2)
            ->where('tasks.per_page', 25)
            ->where('tasks.data', fn (Collection $data): bool => $data->pluck('title')->all() === $pageTwoTitles)
            ->whereNull('tasks.next_page_url')
            ->whereNotNull('tasks.prev_page_url')
        );

    actingAs($user)
        ->get(route('team.tasks.show', ['current_team' => $team, 'project' => $project->id, 'page' => 3]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Show')
            ->has('tasks.data', 0)
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
