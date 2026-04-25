<?php

use App\Models\Bookmark;
use App\Models\User;

it('bookmarks page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    visit('/'.$team->slug.'/bookmarks')
        ->assertSee('Bookmarks')
        ->assertNoJavaScriptErrors();
});

it('bookmarks page shows existing bookmarks', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Laravel Docs',
        'url' => 'https://laravel.com',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/bookmarks')
        ->assertSee('Laravel Docs')
        ->assertNoJavaScriptErrors();
});

it('bookmark show page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $bookmark = Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Vue.js Guide',
        'url' => 'https://vuejs.org',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/bookmarks/'.$bookmark->id)
        ->assertSee('Vue.js Guide')
        ->assertNoJavaScriptErrors();
});
