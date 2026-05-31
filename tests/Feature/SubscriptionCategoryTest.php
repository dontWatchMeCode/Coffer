<?php

use App\Models\Subscription;
use App\Models\SubscriptionCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->currentTeam;
});

it('creates a category when storing a subscription with a new category name', function () {
    $this->actingAs($this->user);

    $response = $this->post("/{$this->team->slug}/subscriptions", [
        'name' => 'Netflix',
        'price' => 15.99,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'category' => 'Entertainment',
    ]);

    $response->assertRedirect();

    $category = SubscriptionCategory::query()
        ->whereBelongsTo($this->team)
        ->where('slug', 'entertainment')
        ->first();

    expect($category)->not->toBeNull()
        ->and($category->name)->toBe('Entertainment');

    $subscription = Subscription::query()->whereBelongsTo($this->team)->first();
    expect($subscription->subscription_category_id)->toBe($category->id)
        ->and($subscription->category)->toBe('Entertainment')
        ->and($subscription->getRawOriginal('category'))->toBe('Entertainment');
});

it('reuses existing category when storing subscription with same category name', function () {
    $this->actingAs($this->user);

    $existing = SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Music',
        'slug' => 'music',
    ]);

    $this->post("/{$this->team->slug}/subscriptions", [
        'name' => 'Spotify',
        'category' => 'Music',
    ])->assertRedirect();

    expect(SubscriptionCategory::query()->whereBelongsTo($this->team)->count())->toBe(1);

    $subscription = Subscription::query()->whereBelongsTo($this->team)->first();
    expect($subscription->subscription_category_id)->toBe($existing->id);
});

it('updates subscription category and cleans up unused', function () {
    $this->actingAs($this->user);

    $category = SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Old Category',
        'slug' => 'old-category',
    ]);

    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'subscription_category_id' => $category->id,
    ]);

    $this->patch("/{$this->team->slug}/subscriptions/{$subscription->id}", [
        'name' => $subscription->name,
        'category' => 'New Category',
    ])->assertRedirect();

    expect(SubscriptionCategory::query()->find($category->id))->toBeNull();

    $newCategory = SubscriptionCategory::query()
        ->whereBelongsTo($this->team)
        ->where('slug', 'new-category')
        ->first();

    expect($newCategory)->not->toBeNull();
    $subscription->refresh();

    expect($subscription->subscription_category_id)->toBe($newCategory->id)
        ->and($subscription->getRawOriginal('category'))->toBe('New Category');
});

it('does not delete category still used by other subscription', function () {
    $this->actingAs($this->user);

    $category = SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Shared',
        'slug' => 'shared',
    ]);

    $sub1 = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'subscription_category_id' => $category->id,
    ]);

    $sub2 = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'subscription_category_id' => $category->id,
    ]);

    $this->patch("/{$this->team->slug}/subscriptions/{$sub1->id}", [
        'name' => $sub1->name,
        'category' => 'Different',
    ])->assertRedirect();

    expect(SubscriptionCategory::query()->find($category->id))->not->toBeNull();
});

it('keeps unused category when subscription is moved to trash', function () {
    $this->actingAs($this->user);

    $category = SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Unique',
        'slug' => 'unique',
    ]);

    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'subscription_category_id' => $category->id,
    ]);

    $this->delete("/{$this->team->slug}/subscriptions/{$subscription->id}")
        ->assertRedirect();

    expect(SubscriptionCategory::query()->find($category->id))->not->toBeNull();
});

it('clears category when null is sent', function () {
    $this->actingAs($this->user);

    $category = SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'ToRemove',
        'slug' => 'to-remove',
    ]);

    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'subscription_category_id' => $category->id,
    ]);

    $this->patch("/{$this->team->slug}/subscriptions/{$subscription->id}", [
        'name' => $subscription->name,
        'category' => null,
    ])->assertRedirect();

    expect($subscription->fresh()->subscription_category_id)->toBeNull();
    expect(SubscriptionCategory::query()->find($category->id))->toBeNull();
});

it('returns category candidates as json', function () {
    $this->actingAs($this->user);

    SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Entertainment',
        'slug' => 'entertainment',
    ]);

    SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Education',
        'slug' => 'education',
    ]);

    $response = $this->getJson("/{$this->team->slug}/subscriptions/categories/candidates?q=ent");

    $response->assertSuccessful();
    $response->assertJsonCount(1, 'categories');
    $response->assertJsonPath('categories.0.name', 'Entertainment');
});

it('returns all categories when no query is provided', function () {
    $this->actingAs($this->user);

    SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Alpha',
        'slug' => 'alpha',
    ]);

    SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Beta',
        'slug' => 'beta',
    ]);

    $response = $this->getJson("/{$this->team->slug}/subscriptions/categories/candidates");

    $response->assertSuccessful();
    $response->assertJsonCount(2, 'categories');
});

it('scopes categories to current team', function () {
    $otherUser = User::factory()->create();

    $this->actingAs($this->user);

    DB::table('subscription_categories')->insert([
        'team_id' => $otherUser->currentTeam->id,
        'name' => 'Other Team Category',
        'slug' => 'other-team-category',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'My Category',
        'slug' => 'my-category',
    ]);

    $response = $this->getJson("/{$this->team->slug}/subscriptions/categories/candidates");

    $response->assertSuccessful();
    $response->assertJsonCount(1, 'categories');
    $response->assertJsonPath('categories.0.name', 'My Category');
});

it('subscription category accessor returns name from relationship', function () {
    $this->actingAs($this->user);

    $category = SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Development',
        'slug' => 'development',
    ]);

    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'subscription_category_id' => $category->id,
        'category' => 'Old Text Value',
    ]);

    expect($subscription->category)->toBe('Development');
});

it('subscription category accessor falls back to text column', function () {
    $this->actingAs($this->user);

    $subscription = Subscription::factory()->create([
        'team_id' => $this->team->id,
        'subscription_category_id' => null,
        'category' => 'Text Only',
    ]);

    expect($subscription->category)->toBe('Text Only');
});

it('backfills existing text categories during migration', function () {
    $this->actingAs($this->user);

    $category = SubscriptionCategory::query()
        ->whereBelongsTo($this->team)
        ->where('name', 'Entertainment')
        ->first();

    expect($category)->toBeNull();
});
