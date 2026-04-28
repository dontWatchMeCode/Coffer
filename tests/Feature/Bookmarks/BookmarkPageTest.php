<?php

use App\Models\Bookmark;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('bookmarks page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    expect($team)->not->toBeNull();

    actingAs($user)
        ->get(route('team.bookmarks.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookmarks/Index')
            ->has('bookmarks'),
        );
});

test('bookmarks page shows bookmarks for current team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Laravel Docs',
        'url' => 'https://laravel.com',
        'description' => 'Official documentation',
    ]);

    actingAs($user)
        ->get(route('team.bookmarks.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookmarks/Index')
            ->has('bookmarks', 1)
            ->where('bookmarks.0.title', 'Laravel Docs')
            ->where('bookmarks.0.url', 'https://laravel.com')
            ->where('bookmarks.0.description', 'Official documentation'),
        );
});

test('guests cannot access bookmarks page', function () {
    $team = Team::factory()->create();

    $this
        ->get(route('team.bookmarks.index', ['current_team' => $team]))
        ->assertRedirect(route('login'));
});

test('non-members cannot access bookmarks page', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->get(route('team.bookmarks.index', ['current_team' => $otherTeam]))
        ->assertForbidden();
});

test('a bookmark can be created', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.bookmarks.store', ['current_team' => $team]), [
            'title' => 'Vue Docs',
            'url' => 'https://vuejs.org',
            'description' => 'Vue.js documentation',
            'notes' => 'Great framework',
        ])
        ->assertRedirect(route('team.bookmarks.show', ['current_team' => $team, 'bookmark' => 1]));

    $bookmark = Bookmark::where('team_id', $team->id)->first();

    expect($bookmark->title)->toBe('Vue Docs');
    expect($bookmark->url)->toBe('https://vuejs.org');
    expect($bookmark->description)->toBe('Vue.js documentation');
    expect($bookmark->notes)->toBe('Great framework');
    expect($bookmark->is_archived)->toBeFalse();
});

test('a bookmark requires a title and url', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.bookmarks.store', ['current_team' => $team]), [
            'description' => 'Missing title and url',
        ])
        ->assertSessionHasErrors(['title', 'url']);
});

test('a bookmark url must be valid', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.bookmarks.store', ['current_team' => $team]), [
            'title' => 'Invalid URL',
            'url' => 'not-a-url',
        ])
        ->assertSessionHasErrors(['url']);
});

test('a bookmark can be updated', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $bookmark = Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Old Title',
        'url' => 'https://old.com',
    ]);

    actingAs($user)
        ->patch(
            route('team.bookmarks.update', ['current_team' => $team, 'bookmark' => $bookmark]),
            [
                'title' => 'New Title',
                'url' => 'https://new.com',
                'description' => 'Updated description',
                'notes' => 'Updated notes',
                'is_archived' => true,
            ],
        )
        ->assertRedirect(route('team.bookmarks.show', ['current_team' => $team, 'bookmark' => $bookmark->id]));

    $bookmark = $bookmark->fresh();

    expect($bookmark->title)->toBe('New Title');
    expect($bookmark->url)->toBe('https://new.com');
    expect($bookmark->description)->toBe('Updated description');
    expect($bookmark->notes)->toBe('Updated notes');
    expect($bookmark->is_archived)->toBeTrue();
});

test('a bookmark can be deleted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $bookmark = Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Delete Me',
    ]);

    actingAs($user)
        ->delete(
            route('team.bookmarks.destroy', ['current_team' => $team, 'bookmark' => $bookmark]),
        )
        ->assertRedirect(route('team.bookmarks.index', ['current_team' => $team]));

    expect($bookmark->fresh())->toBeNull();
});

test('a non-member cannot create bookmarks', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->post(route('team.bookmarks.store', ['current_team' => $otherTeam]), [
            'title' => 'Hacked',
            'url' => 'https://evil.com',
        ])
        ->assertForbidden();
});

test('a non-member cannot update bookmarks', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $bookmark = Bookmark::factory()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Protected Bookmark',
    ]);

    actingAs($user)
        ->patch(
            route('team.bookmarks.update', ['current_team' => $otherTeam, 'bookmark' => $bookmark]),
            ['title' => 'Hacked'],
        )
        ->assertForbidden();
});

test('a non-member cannot delete bookmarks', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $bookmark = Bookmark::factory()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Protected Bookmark',
    ]);

    actingAs($user)
        ->delete(
            route('team.bookmarks.destroy', ['current_team' => $otherTeam, 'bookmark' => $bookmark]),
        )
        ->assertForbidden();
});

test('a user cannot update a bookmark from another team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $bookmark = Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'My Bookmark',
    ]);

    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($user, ['role' => 'member']);
    $user->switchTeam($otherTeam);

    actingAs($user)
        ->patch(
            route('team.bookmarks.update', ['current_team' => $otherTeam, 'bookmark' => $bookmark]),
            ['title' => 'Hacked'],
        )
        ->assertForbidden();
});

test('bookmarks page does not show bookmarks from other teams', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'My Bookmark',
    ]);

    Bookmark::factory()->create([
        'title' => 'Other Team Bookmark',
    ]);

    actingAs($user)
        ->get(route('team.bookmarks.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookmarks/Index')
            ->has('bookmarks', 1)
            ->where('bookmarks.0.title', 'My Bookmark'),
        );
});

test('bookmark show page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $bookmark = Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Vue Docs',
        'url' => 'https://vuejs.org',
        'description' => 'Vue.js documentation',
        'notes' => 'Great framework',
        'is_archived' => false,
    ]);

    actingAs($user)
        ->get(route('team.bookmarks.show', ['current_team' => $team, 'bookmark' => $bookmark]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookmarks/Show')
            ->has('bookmark')
            ->where('bookmark.id', $bookmark->id)
            ->where('bookmark.title', 'Vue Docs')
            ->where('bookmark.url', 'https://vuejs.org')
            ->where('bookmark.description', 'Vue.js documentation')
            ->where('bookmark.notes', 'Great framework')
            ->where('bookmark.isArchived', false)
            ->where('bookmark.updatedAt', fn (?string $updatedAt): bool => is_string($updatedAt)
                && str_contains($updatedAt, 'T')),
        );
});

test('guests cannot access bookmark show page', function () {
    $team = Team::factory()->create();

    $this
        ->get(route('team.bookmarks.show', ['current_team' => $team, 'bookmark' => 1]))
        ->assertRedirect(route('login'));
});

test('non-members cannot access bookmark show page', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->get(route('team.bookmarks.show', ['current_team' => $otherTeam, 'bookmark' => 1]))
        ->assertForbidden();
});

test('creating a bookmark redirects to show page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.bookmarks.store', ['current_team' => $team]), [
            'title' => 'Redirect Test',
            'url' => 'https://example.com',
        ])
        ->assertRedirect(route('team.bookmarks.show', [
            'current_team' => $team,
            'bookmark' => Bookmark::whereTitle('Redirect Test')->first()->id,
        ]));
});

test('deleting a bookmark redirects to index page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $bookmark = Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Will Be Deleted',
    ]);

    actingAs($user)
        ->delete(route('team.bookmarks.destroy', ['current_team' => $team, 'bookmark' => $bookmark]))
        ->assertRedirect(route('team.bookmarks.index', ['current_team' => $team]));
});

test('bookmarks can be created with nullable fields', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.bookmarks.store', ['current_team' => $team]), [
            'title' => 'Minimal Bookmark',
            'url' => 'https://example.com',
        ])
        ->assertRedirect();

    $bookmark = Bookmark::where('team_id', $team->id)->first();

    expect($bookmark->description)->toBeNull();
    expect($bookmark->notes)->toBeNull();
    expect($bookmark->is_archived)->toBeFalse();
});

test('bookmarks search returns results', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Laravel Docs',
        'url' => 'https://laravel.com',
    ]);

    Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Vue Docs',
        'url' => 'https://vuejs.org',
    ]);

    actingAs($user)
        ->getJson(route('team.search', ['current_team' => $team]).'?q=laravel')
        ->assertOk()
        ->assertJsonPath('bookmarks.0.title', 'Laravel Docs');
});
