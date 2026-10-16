<?php

use App\Models\Contact;
use App\Models\User;

it('contacts page shows heading and existing contacts', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Jane Smith',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/contacts')
        ->assertSee('Contacts')
        ->assertSee('Manage your team address book.')
        ->assertSee('Jane Smith')
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
        ->assertSee('Original Contact')
        ->click('Edit')
        ->fill('#edit-contact-name', 'Updated Contact')
        ->click('Save changes');

    waitForBrowserText($page, 'Updated Contact');

    expect($contact->fresh()->name)->toBe('Updated Contact');
    $page->assertNoJavaScriptErrors();
});
