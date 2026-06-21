<?php

use App\Models\Subscription;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
});

it('stores a first billing date on create', function () {
    $this->post("/{$this->team->slug}/subscriptions", [
        'name' => 'Netflix',
        'price' => 15.99,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'first_billing_date' => '2026-01-15',
    ])->assertRedirect();

    $subscription = Subscription::query()->whereBelongsTo($this->team)->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->first_billing_date->format('Y-m-d'))->toBe('2026-01-15');
});

it('stores a null first billing date on create', function () {
    $this->post("/{$this->team->slug}/subscriptions", [
        'name' => 'Spotify',
        'price' => 9.99,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
    ])->assertRedirect();

    $subscription = Subscription::query()->whereBelongsTo($this->team)->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->first_billing_date)->toBeNull();
});

it('updates first billing date on edit', function () {
    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'first_billing_date' => '2026-01-15',
    ]);

    $this->patch("/{$this->team->slug}/subscriptions/{$subscription->id}", [
        'name' => $subscription->name,
        'first_billing_date' => '2026-03-01',
    ])->assertRedirect();

    expect($subscription->fresh()->first_billing_date->format('Y-m-d'))->toBe('2026-03-01');
});

it('clears first billing date on edit', function () {
    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'first_billing_date' => '2026-01-15',
    ]);

    $this->patch("/{$this->team->slug}/subscriptions/{$subscription->id}", [
        'name' => $subscription->name,
        'first_billing_date' => null,
    ])->assertRedirect();

    expect($subscription->fresh()->first_billing_date)->toBeNull();
});

it('validates first billing date is before next billing date', function () {
    $this->post("/{$this->team->slug}/subscriptions", [
        'name' => 'Netflix',
        'price' => 15.99,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'first_billing_date' => '2026-06-15',
        'next_billing_date' => '2026-01-15',
    ])->assertSessionHasErrors('first_billing_date');
});

it('passes validation when first billing date is before next billing date', function () {
    $this->post("/{$this->team->slug}/subscriptions", [
        'name' => 'Netflix',
        'price' => 15.99,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'first_billing_date' => '2026-01-15',
        'next_billing_date' => '2026-06-15',
    ])->assertRedirect();

    $subscription = Subscription::query()->whereBelongsTo($this->team)->first();

    expect($subscription->first_billing_date->format('Y-m-d'))->toBe('2026-01-15');
});

it('passes validation when first billing date is null regardless of next billing date', function () {
    $this->post("/{$this->team->slug}/subscriptions", [
        'name' => 'Netflix',
        'price' => 15.99,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'next_billing_date' => '2026-06-15',
    ])->assertRedirect();
});

it('passes validation when next billing date is null regardless of first billing date', function () {
    $this->post("/{$this->team->slug}/subscriptions", [
        'name' => 'Netflix',
        'price' => 15.99,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'first_billing_date' => '2026-01-15',
    ])->assertRedirect();
});

it('returns first billing date in subscription payload', function () {
    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'first_billing_date' => '2026-01-15',
    ]);

    $response = $this->get("/{$this->team->slug}/subscriptions/{$subscription->id}");

    $response->assertInertia(fn ($page) => $page
        ->component('subscriptions/Show')
        ->where('subscription.firstBillingDate', fn ($value) => str_starts_with((string) $value, '2026-01-15'))
    );
});
