<?php

use App\Models\RecordCollection;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('collections page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->get(route('team.collections.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('collections/Index')
            ->has('collections'));
});

test('collections page shows collections for current team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    RecordCollection::factory()->create([
        'team_id' => $team->id,
        'title' => 'Launch Collection',
        'description' => 'Records for launch',
    ]);

    RecordCollection::factory()->create([
        'title' => 'Other Team Collection',
    ]);

    actingAs($user)
        ->get(route('team.collections.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('collections/Index')
            ->has('collections', 1)
            ->where('collections.0.title', 'Launch Collection')
            ->where('collections.0.description', 'Records for launch'));
});

test('collection show page can be rendered with links and tags payloads', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $collection = RecordCollection::factory()->create([
        'team_id' => $team->id,
        'title' => 'Decision Set',
    ]);

    actingAs($user)
        ->get(route('team.collections.show', ['current_team' => $team, 'collection' => $collection]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('collections/Show')
            ->where('collection.id', $collection->id)
            ->where('collection.title', 'Decision Set')
            ->has('recordLinks')
            ->has('recordTags'));
});

test('a collection can be created', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.collections.store', ['current_team' => $team]), [
            'title' => 'New Collection',
            'description' => 'Grouped records',
        ])
        ->assertRedirect(route('team.collections.show', [
            'current_team' => $team,
            'collection' => RecordCollection::whereTitle('New Collection')->first()->id,
        ]));

    $collection = RecordCollection::where('team_id', $team->id)->first();

    expect($collection->title)->toBe('New Collection');
    expect($collection->description)->toBe('Grouped records');
});

test('a collection requires a title', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.collections.store', ['current_team' => $team]), [
            'description' => 'Missing title',
        ])
        ->assertSessionHasErrors(['title']);
});

test('a collection can be updated', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $collection = RecordCollection::factory()->create([
        'team_id' => $team->id,
        'title' => 'Old Title',
        'description' => 'Old description',
    ]);

    actingAs($user)
        ->patch(route('team.collections.update', ['current_team' => $team, 'collection' => $collection]), [
            'title' => 'New Title',
            'description' => 'New description',
        ])
        ->assertRedirect(route('team.collections.show', ['current_team' => $team, 'collection' => $collection->id]));

    $collection = $collection->fresh();

    expect($collection->title)->toBe('New Title');
    expect($collection->description)->toBe('New description');
});

test('a collection can be deleted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $collection = RecordCollection::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->delete(route('team.collections.destroy', ['current_team' => $team, 'collection' => $collection]))
        ->assertRedirect(route('team.collections.index', ['current_team' => $team]));

    expect($collection->fresh())->toBeNull();
});

test('guests cannot access collections page', function () {
    $team = Team::factory()->create();

    $this
        ->get(route('team.collections.index', ['current_team' => $team]))
        ->assertRedirect(route('login'));
});

test('non-members cannot manage collections', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs($user)
        ->post(route('team.collections.store', ['current_team' => $otherTeam]), [
            'title' => 'Nope',
        ])
        ->assertForbidden();
});
