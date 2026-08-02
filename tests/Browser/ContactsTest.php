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

it('contact fields are shared by the edit form', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Original Contact',
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/contacts/'.$contact->id)
        ->click('Edit')
        ->fill('#edit-contact-name', 'Updated Contact')
        ->click('Save changes');

    waitForBrowserText($page, 'Updated Contact');

    expect($contact->fresh()->name)->toBe('Updated Contact');
    $page->assertNoJavaScriptErrors();
});
