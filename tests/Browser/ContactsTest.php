<?php

use App\Models\Contact;
use App\Models\User;

it('contacts page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    visit('/'.$team->slug.'/contacts')
        ->assertSee('Contacts')
        ->assertNoJavaScriptErrors();
});

it('contacts page shows existing contacts', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Jane Smith',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/contacts')
        ->assertSee('Jane Smith')
        ->assertNoJavaScriptErrors();
});

it('contact show page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'John Doe',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/contacts/'.$contact->id)
        ->assertSee('John Doe')
        ->assertNoJavaScriptErrors();
});
