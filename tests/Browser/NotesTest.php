<?php

use App\Models\Note;
use App\Models\User;

it('notes page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    visit('/'.$team->slug.'/notes')
        ->assertSee('Notes')
        ->assertSee('Capture team knowledge')
        ->assertNoJavaScriptErrors();
});

it('notes page shows existing notes and filters them', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Architecture Decision',
        'body' => 'Use boring technology.',
    ]);

    Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Sprint Notes',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/notes')
        ->assertSee('Architecture Decision')
        ->assertSee('Sprint Notes')
        ->fill('[data-testid="notes-search-input"]', 'Architecture')
        ->assertSee('Architecture Decision')
        ->assertDontSee('Sprint Notes')
        ->assertNoJavaScriptErrors();
});

it('note show page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Launch Checklist',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/notes/'.$note->id)
        ->assertSee('Launch Checklist')
        ->assertSee('Linked Records')
        ->assertSee('Tags')
        ->assertNoJavaScriptErrors();
});
