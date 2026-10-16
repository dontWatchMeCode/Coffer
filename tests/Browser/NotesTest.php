<?php

use App\Models\Note;
use App\Models\User;

it('notes page shows existing notes and filters them', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Note::factory()
        ->withTextBlock('Use boring technology.')
        ->create([
            'team_id' => $team->id,
            'title' => 'Architecture Decision',
        ]);

    Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Sprint Notes',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/notes')
        ->assertSee('Notes')
        ->assertSee('Capture team knowledge')
        ->assertSee('Architecture Decision')
        ->assertSee('Sprint Notes')
        ->fill('[data-testid="notes-search-input"]', 'Architecture')
        ->assertSee('Architecture Decision')
        ->assertDontSee('Sprint Notes')
        ->assertNoJavaScriptErrors();
});

it('note title can be updated from the show page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Launch Checklist',
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/notes/'.$note->id)
        ->assertSee('Launch Checklist')
        ->assertSee('Linked Records')
        ->assertSee('Tags')
        ->click('Edit')
        ->fill('#edit-note-title', 'Updated Note Title')
        ->click('Save changes');

    waitForBrowserText($page, 'Updated Note Title');

    expect($note->fresh()->title)->toBe('Updated Note Title');
    $page->assertNoJavaScriptErrors();
});
