<?php

use App\Models\Note;
use App\Models\RecordCollection;
use App\Models\RecordLink;
use App\Models\User;

it('collections page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    visit('/'.$team->slug.'/collections')
        ->assertSee('Collections')
        ->assertSee('Group linked records')
        ->assertNoJavaScriptErrors();
});

it('collections page shows existing collections and filters them', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    RecordCollection::factory()->create([
        'team_id' => $team->id,
        'title' => 'Hiring Packet',
        'description' => 'Candidate research and interview notes.',
    ]);

    RecordCollection::factory()->create([
        'team_id' => $team->id,
        'title' => 'Launch Plan',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/collections')
        ->assertSee('Hiring Packet')
        ->assertSee('Launch Plan')
        ->fill('[data-testid="collections-search-input"]', 'Hiring')
        ->assertSee('Hiring Packet')
        ->assertDontSee('Launch Plan')
        ->assertNoJavaScriptErrors();
});

it('collection show page can be rendered with linked records', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $collection = RecordCollection::factory()->create([
        'team_id' => $team->id,
        'title' => 'Research Set',
    ]);

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Competitor Notes',
    ]);

    RecordLink::create([
        'team_id' => $team->id,
        'left_type' => $collection->linkableType(),
        'left_id' => $collection->id,
        'right_type' => $note->linkableType(),
        'right_id' => $note->id,
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/collections/'.$collection->id)
        ->assertSee('Research Set')
        ->assertSee('Linked Records')
        ->assertSee('Competitor Notes')
        ->assertNoJavaScriptErrors();
});
