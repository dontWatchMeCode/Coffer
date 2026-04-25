<?php

use App\Models\Project;
use App\Models\User;

it('tasks page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Project::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/tasks')
        ->assertSee('Tasks')
        ->assertSee('Choose a project to work on or create a new one.')
        ->assertNoJavaScriptErrors();
});

it('calendar page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    visit('/'.$team->slug.'/calendar')
        ->assertSee('Calendar')
        ->assertNoJavaScriptErrors();
});

it('contacts page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    visit('/'.$team->slug.'/contacts')
        ->assertSee('Contacts')
        ->assertNoJavaScriptErrors();
});

it('bookmarks page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    visit('/'.$team->slug.'/bookmarks')
        ->assertSee('Bookmarks')
        ->assertNoJavaScriptErrors();
});
