<?php

use App\Models\Note;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('returns paginated activity history', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id]);
    $note->update(['title' => 'Updated']);
    $note->update(['title' => 'Again']);

    $url = route('team.activity-history.index', [
        'current_team' => $team,
        'subject_type' => 'note',
        'subject_id' => $note->id,
    ]);

    $response = actingAs($user)
        ->getJson($url)
        ->assertOk()
        ->assertJsonStructure([
            'activities' => [],
            'total',
            'has_more',
        ]);

    expect($response->json('total'))->toBe(3);
    expect($response->json('has_more'))->toBeFalse();
    expect($response->json('activities'))->toHaveCount(3);

    $ids = collect($response->json('activities'))->pluck('id')->all();
    expect($ids)->toBe(collect($ids)->sortDesc()->values()->all());
});

it('paginates activity history', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id]);

    for ($i = 0; $i < 20; $i++) {
        $note->update(['title' => 'Title '.$i]);
    }

    $base = [
        'current_team' => $team,
        'subject_type' => 'note',
        'subject_id' => $note->id,
    ];

    $pageOne = actingAs($user)
        ->getJson(route('team.activity-history.index', [...$base, 'page' => 1]))
        ->assertOk();

    expect($pageOne->json('total'))->toBe(21);
    expect($pageOne->json('activities'))->toHaveCount(15);
    expect($pageOne->json('has_more'))->toBeTrue();

    $pageTwo = actingAs($user)
        ->getJson(route('team.activity-history.index', [...$base, 'page' => 2]))
        ->assertOk();

    expect($pageTwo->json('total'))->toBe(21);
    expect($pageTwo->json('activities'))->toHaveCount(6);
    expect($pageTwo->json('has_more'))->toBeFalse();

    $pageOneIds = collect($pageOne->json('activities'))->pluck('id')->all();
    $pageTwoIds = collect($pageTwo->json('activities'))->pluck('id')->all();

    expect($pageOneIds)->toBe(collect($pageOneIds)->sortDesc()->values()->all());
    expect($pageTwoIds)->toBe(collect($pageTwoIds)->sortDesc()->values()->all());
    expect(array_intersect($pageOneIds, $pageTwoIds))->toBe([]);
    expect(array_merge($pageOneIds, $pageTwoIds))->toHaveCount(21);

    $terminal = actingAs($user)
        ->getJson(route('team.activity-history.index', [...$base, 'page' => 3]))
        ->assertOk();

    expect($terminal->json('activities'))->toHaveCount(0);
    expect($terminal->json('total'))->toBe(21);
    expect($terminal->json('has_more'))->toBeFalse();
});

it('returns 422 for invalid subject type', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $url = route('team.activity-history.index', [
        'current_team' => $team,
        'subject_type' => 'invalid',
        'subject_id' => 999,
    ]);

    actingAs($user)
        ->getJson($url)
        ->assertStatus(422);
});

it('requires team membership', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $url = route('team.activity-history.index', [
        'current_team' => $otherTeam,
        'subject_type' => 'note',
        'subject_id' => 1,
    ]);

    actingAs($user)
        ->getJson($url)
        ->assertForbidden();
});

it('returns empty for cross-team note', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherUser = User::factory()->create();
    $otherNote = Note::factory()->create(['team_id' => $otherUser->currentTeam->id]);

    $url = route('team.activity-history.index', [
        'current_team' => $team,
        'subject_type' => 'note',
        'subject_id' => $otherNote->id,
    ]);

    actingAs($user)
        ->getJson($url)
        ->assertOk()
        ->assertJson([
            'activities' => [],
            'total' => 0,
        ]);
});

it('requires valid subject_type', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $url = route('team.activity-history.index', [
        'current_team' => $team,
        'subject_type' => '',
        'subject_id' => 1,
    ]);

    actingAs($user)
        ->getJson($url)
        ->assertStatus(422);
});
