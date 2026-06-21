<?php

use App\Enums\InsightsTimeRange;
use App\Models\Subscription;
use App\Models\SubscriptionCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->currentTeam;
});

it('redirects guests to the login page', function () {
    $this->get(route('team.subscriptions.insights', ['current_team' => $this->team]))
        ->assertRedirect(route('login'));
});

it('renders the subscriptions insights for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('team.subscriptions.insights', ['current_team' => $this->team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('subscriptions/Insights')
            ->has('insights.kpis')
            ->has('insights.spendTrend')
            ->has('insights.categoryBreakdown')
            ->where('range', InsightsTimeRange::Last3Months->value)
            ->has('rangeOptions'));
});

it('aggregates monthly spend, active count, and upcoming renewals', function () {
    $category = SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Entertainment',
        'slug' => 'entertainment',
    ]);

    Subscription::factory()->create([
        'team_id' => $this->team->id,
        'subscription_category_id' => $category->id,
        'name' => 'Netflix',
        'price' => 15,
        'billing_cycle' => 'monthly',
        'next_billing_date' => now()->addDays(10)->toDateString(),
        'is_active' => true,
    ]);

    Subscription::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Yearly Cloud',
        'price' => 120,
        'billing_cycle' => 'yearly',
        'next_billing_date' => now()->addDays(40)->toDateString(),
        'is_active' => true,
    ]);

    Subscription::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Inactive',
        'price' => 50,
        'billing_cycle' => 'monthly',
        'is_active' => false,
    ]);

    $this->actingAs($this->user)
        ->get(route('team.subscriptions.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.kpis.activeCount', 2)
            ->where('insights.kpis.monthlySpend', '25.00')
            ->where('insights.kpis.upcomingRenewals', 1));
});

it('scopes subscriptions to the current team', function () {
    $otherUser = User::factory()->create();
    $otherTeam = $otherUser->currentTeam;

    Subscription::factory()->create([
        'team_id' => $otherTeam->id,
        'price' => 99,
        'billing_cycle' => 'monthly',
        'is_active' => true,
    ]);

    Subscription::factory()->create([
        'team_id' => $this->team->id,
        'price' => 12,
        'billing_cycle' => 'monthly',
        'is_active' => true,
    ]);

    $this->actingAs($this->user)
        ->get(route('team.subscriptions.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.kpis.activeCount', 1)
            ->where('insights.kpis.monthlySpend', '12.00'));
});

it('groups category breakdown by subscription category name', function () {
    $entertainment = SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Entertainment',
        'slug' => 'entertainment',
    ]);

    $productivity = SubscriptionCategory::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Productivity',
        'slug' => 'productivity',
    ]);

    Subscription::factory()->create([
        'team_id' => $this->team->id,
        'subscription_category_id' => $entertainment->id,
        'price' => 20,
        'billing_cycle' => 'monthly',
        'is_active' => true,
    ]);

    Subscription::factory()->create([
        'team_id' => $this->team->id,
        'subscription_category_id' => $productivity->id,
        'price' => 10,
        'billing_cycle' => 'monthly',
        'is_active' => true,
    ]);

    Subscription::factory()->create([
        'team_id' => $this->team->id,
        'subscription_category_id' => null,
        'price' => 5,
        'billing_cycle' => 'monthly',
        'is_active' => true,
    ]);

    $this->actingAs($this->user)
        ->get(route('team.subscriptions.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.categoryBreakdown.0.category', 'Entertainment')
            ->where('insights.categoryBreakdown.0.spend', 20)
            ->where('insights.categoryBreakdown.1.category', 'Productivity')
            ->where('insights.categoryBreakdown.1.spend', 10)
            ->where('insights.categoryBreakdown.2.category', 'Uncategorized')
            ->where('insights.categoryBreakdown.2.spend', 5));
});

it('honors the range query parameter for spend trend', function () {
    Subscription::factory()->create([
        'team_id' => $this->team->id,
        'price' => 50,
        'billing_cycle' => 'monthly',
        'next_billing_date' => now()->startOfYear()->addMonths(2)->toDateString(),
        'is_active' => true,
    ]);

    Subscription::factory()->create([
        'team_id' => $this->team->id,
        'price' => 30,
        'billing_cycle' => 'monthly',
        'next_billing_date' => now()->startOfYear()->toDateString(),
        'is_active' => true,
    ]);

    $this->actingAs($this->user)
        ->get(route('team.subscriptions.insights', ['current_team' => $this->team, 'range' => InsightsTimeRange::ThisYear->value]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('range', InsightsTimeRange::ThisYear->value)
            ->has('insights.spendTrend', 12)
            ->where('insights.spendTrend.0.spend', 80)
            ->where('insights.spendTrend.11.spend', 80));
});

it('normalizes weekly and yearly billing cycles to monthly', function () {
    Subscription::factory()->create([
        'team_id' => $this->team->id,
        'price' => 12,
        'billing_cycle' => 'weekly',
        'is_active' => true,
    ]);

    Subscription::factory()->create([
        'team_id' => $this->team->id,
        'price' => 120,
        'billing_cycle' => 'yearly',
        'is_active' => true,
    ]);

    $expectedWeekly = round(12 * 52 / 12, 2);
    $expectedYearly = round(120 / 12, 2);
    $expectedTotal = number_format($expectedWeekly + $expectedYearly, 2, '.', '');

    $this->actingAs($this->user)
        ->get(route('team.subscriptions.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.kpis.monthlySpend', $expectedTotal));
});

it('returns 404 when the subscriptions feature is disabled', function () {
    $this->team->forceFill(['feature_settings' => ['subscriptions' => false]])->save();

    $this->actingAs($this->user)
        ->get(route('team.subscriptions.insights', ['current_team' => $this->team]))
        ->assertNotFound();
});
