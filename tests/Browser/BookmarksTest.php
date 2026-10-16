<?php

use App\Models\Bookmark;
use App\Models\User;

it('bookmarks page shows heading and existing bookmarks', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Laravel Docs',
        'url' => 'https://laravel.com',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/bookmarks')
        ->assertSee('Bookmarks')
        ->assertSee('Track useful links for your team.')
        ->assertSee('Laravel Docs')
        ->assertNoJavaScriptErrors();
});

it('bookmark fields are shared by the edit form', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $bookmark = Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Original Bookmark',
        'url' => 'https://example.com',
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/bookmarks/'.$bookmark->id)
        ->assertSee('Original Bookmark')
        ->assertSee('https://example.com')
        ->click('Edit')
        ->fill('#edit-bookmark-title', 'Updated Bookmark')
        ->click('Save changes');

    waitForBrowserText($page, 'Updated Bookmark');

    expect($bookmark->fresh()->title)->toBe('Updated Bookmark');
    $page->assertNoJavaScriptErrors();
});
