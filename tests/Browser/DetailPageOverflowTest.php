<?php

use App\Models\Contact;
use App\Models\User;

it('does not overflow the page on short detail content', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Ada Lovelace',
        'phone_numbers' => [],
        'email_addresses' => [],
        'links' => [],
        'address' => null,
        'additional_info' => null,
    ]);
    $this->actingAs($user);

    $page = visit("/{$team->slug}/contacts/{$contact->id}")->resize(1280, 900);
    $page->assertSee('Ada Lovelace');

    $overflow = $page->script(
        'document.documentElement.scrollHeight > window.innerHeight + 1',
    );

    expect($overflow)->toBeFalse();
    $page->assertNoJavaScriptErrors();
});

it('starts detail at the top when opened from a scrolled list', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    foreach (range(0, 24) as $index) {
        Contact::factory()->create([
            'team_id' => $team->id,
            'name' => sprintf('Scroll Contact %02d', $index),
            'phone_numbers' => [],
            'email_addresses' => [],
            'links' => [],
            'address' => null,
            'additional_info' => null,
        ]);
    }
    $this->actingAs($user);

    $page = visit("/{$team->slug}/contacts")->resize(1280, 900);
    $page->assertSee('Scroll Contact 00');

    $page->script('window.scrollTo(0, document.body.scrollHeight)');
    $scrolled = $page->script('window.scrollY > 100');

    expect($scrolled)->toBeTrue();

    $page->click('Scroll Contact 24');
    $page->assertSee('Review contact details');

    expect($page->script('window.scrollY'))->toBe(0);
    $page->assertNoJavaScriptErrors();
});
