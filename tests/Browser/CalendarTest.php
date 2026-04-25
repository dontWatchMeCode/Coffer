<?php

use App\Models\CalendarEvent;
use App\Models\User;

it('calendar page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    visit('/'.$team->slug.'/calendar')
        ->assertSee('Calendar')
        ->assertNoJavaScriptErrors();
});

it('calendar page shows existing events', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Team Standup',
        'date' => now()->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/calendar');

    $page->assertNoJavaScriptErrors();

    // Events may be rendered inside the calendar grid rather than as plain text.
    $hasEvent = $page->script('document.body.innerText.includes("Team Standup")');

    expect($hasEvent)->toBeTrue('Expected to find "Team Standup" on the calendar page');
});
