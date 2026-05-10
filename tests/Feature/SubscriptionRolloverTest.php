<?php

use App\Models\Subscription;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->currentTeam;
});

it('rolls over monthly subscriptions past their billing date', function () {
    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'billing_cycle' => 'monthly',
        'next_billing_date' => now()->subDays(1)->toDateString(),
        'is_active' => true,
    ]);

    $this->artisan('subscriptions:rollover');

    $subscription->refresh();

    expect($subscription->next_billing_date->format('Y-m-d'))
        ->toBe(now()->subDays(1)->addMonth()->format('Y-m-d'));
});

it('rolls over weekly subscriptions', function () {
    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'billing_cycle' => 'weekly',
        'next_billing_date' => now()->subDays(3)->toDateString(),
        'is_active' => true,
    ]);

    $this->artisan('subscriptions:rollover');

    $subscription->refresh();

    expect($subscription->next_billing_date->format('Y-m-d'))
        ->toBe(now()->subDays(3)->addWeek()->format('Y-m-d'));
});

it('rolls over yearly subscriptions', function () {
    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'billing_cycle' => 'yearly',
        'next_billing_date' => now()->subDays(1)->toDateString(),
        'is_active' => true,
    ]);

    $this->artisan('subscriptions:rollover');

    $subscription->refresh();

    expect($subscription->next_billing_date->format('Y-m-d'))
        ->toBe(now()->subDays(1)->addYear()->format('Y-m-d'));
});

it('skips inactive subscriptions', function () {
    $originalDate = now()->subDays(1)->toDateString();

    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'billing_cycle' => 'monthly',
        'next_billing_date' => $originalDate,
        'is_active' => false,
    ]);

    $this->artisan('subscriptions:rollover');

    $subscription->refresh();

    expect($subscription->next_billing_date->format('Y-m-d'))->toBe($originalDate);
});

it('skips subscriptions without a next billing date', function () {
    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'next_billing_date' => null,
        'is_active' => true,
    ]);

    $this->artisan('subscriptions:rollover')->assertSuccessful();

    $subscription->refresh();

    expect($subscription->next_billing_date)->toBeNull();
});

it('skips subscriptions with future billing dates', function () {
    $futureDate = now()->addWeek()->toDateString();

    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'billing_cycle' => 'monthly',
        'next_billing_date' => $futureDate,
        'is_active' => true,
    ]);

    $this->artisan('subscriptions:rollover');

    $subscription->refresh();

    expect($subscription->next_billing_date->format('Y-m-d'))->toBe($futureDate);
});

it('defaults to monthly for unknown billing cycles', function () {
    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'billing_cycle' => null,
        'next_billing_date' => now()->subDays(1)->toDateString(),
        'is_active' => true,
    ]);

    $this->artisan('subscriptions:rollover');

    $subscription->refresh();

    expect($subscription->next_billing_date->format('Y-m-d'))
        ->toBe(now()->subDays(1)->addMonth()->format('Y-m-d'));
});
