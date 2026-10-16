<?php

use App\Models\Note;
use App\Models\RecordCollection;
use App\Models\RecordLink;
use App\Models\User;

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
        ->assertSee('Collections')
        ->assertSee('Group linked records')
        ->assertSee('Hiring Packet')
        ->assertSee('Launch Plan')
        ->fill('[data-testid="collections-search-input"]', 'Hiring')
        ->assertSee('Hiring Packet')
        ->assertDontSee('Launch Plan')
        ->assertNoJavaScriptErrors();
});

it('collection title can be updated from the show page', function () {
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

    $page = visit('/'.$team->slug.'/collections/'.$collection->id)
        ->assertSee('Research Set')
        ->assertSee('Linked Records')
        ->assertSee('Competitor Notes')
        ->click('Edit')
        ->fill('#collection-title', 'Updated Research Set')
        ->click('Save changes');

    waitForBrowserText($page, 'Updated Research Set');

    expect($collection->fresh()->title)->toBe('Updated Research Set');
    $page->assertNoJavaScriptErrors();
});
