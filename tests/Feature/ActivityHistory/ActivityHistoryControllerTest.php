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

    expect($response->json('total'))->toBeGreaterThanOrEqual(2);
    expect($response->json('has_more'))->toBeFalse();
    expect($response->json('activities'))->toHaveCount(3);
});

it('paginates activity history', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create(['team_id' => $team->id]);

    for ($i = 0; $i < 20; $i++) {
        $note->update(['title' => 'Title '.$i]);
    }

    $url = route('team.activity-history.index', [
        'current_team' => $team,
        'subject_type' => 'note',
        'subject_id' => $note->id,
        'page' => 1,
    ]);

    $response = actingAs($user)
        ->getJson($url)
        ->assertOk();

    expect(count($response->json('activities')))->toBeLessThanOrEqual(15);
    expect($response->json('total'))->toBeGreaterThanOrEqual(10);
    expect($response->json('has_more'))->toBeTrue();
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
