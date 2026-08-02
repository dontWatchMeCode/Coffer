<?php

use App\Models\Subscription;
use App\Models\User;

it('subscription fields are shared by the create form', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/subscriptions')
        ->click('[title="Create subscription"]')
        ->fill('#create-subscription-name', 'Browser Subscription')
        ->fill('#create-subscription-price', '12.50')
        ->click('form button[type="submit"]');

    waitForBrowserText($page, 'Browser Subscription');

    expect(Subscription::query()->where('name', 'Browser Subscription')->exists())
        ->toBeTrue();
    $page->assertNoJavaScriptErrors();
});
