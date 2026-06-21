<?php

use App\Enums\InsightsTimeRange;
use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->currentTeam;
});

it('redirects guests to the login page', function () {
    $this->get(route('team.calendar.insights', ['current_team' => $this->team]))
        ->assertRedirect(route('login'));
});

it('renders the calendar insights for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('team.calendar.insights', ['current_team' => $this->team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/Insights')
            ->has('insights.kpis')
            ->has('insights.eventsPerMonth')
            ->has('insights.upcoming')
            ->where('range', InsightsTimeRange::Last3Months->value)
            ->has('rangeOptions'));
});

it('aggregates this month, next 7 days, and next 30 days counts', function () {
    $today = CarbonImmutable::today();
    $nextMonthStart = $today->copy()->startOfMonth()->addMonth();

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Today event',
        'date' => $today->toDateString(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Within week',
        'date' => $today->addDays(5)->toDateString(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Next month start',
        'date' => $nextMonthStart->toDateString(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Far future',
        'date' => $today->addDays(60)->toDateString(),
    ]);

    $expectedThisMonth = collect([
        $today,
        $today->addDays(5),
        $nextMonthStart,
        $today->addDays(60),
    ])->filter(fn (CarbonImmutable $date): bool => $date->between($today->startOfMonth(), $today->endOfMonth()))->count();

    $expectedNext7 = collect([$today, $today->addDays(5), $nextMonthStart])
        ->filter(fn (CarbonImmutable $date): bool => $date->between($today, $today->addDays(7)))->count();

    $expectedNext30 = collect([$today, $today->addDays(5), $nextMonthStart, $today->addDays(60)])
        ->filter(fn (CarbonImmutable $date): bool => $date->between($today, $today->addDays(30)))->count();

    $this->actingAs($this->user)
        ->get(route('team.calendar.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.kpis.thisMonth', $expectedThisMonth)
            ->where('insights.kpis.next7Days', $expectedNext7)
            ->where('insights.kpis.next30Days', $expectedNext30));
});

it('scopes events to the current team', function () {
    $otherUser = User::factory()->create();

    CalendarEvent::factory()->create([
        'team_id' => $otherUser->currentTeam->id,
        'date' => now()->toDateString(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($this->user)
        ->get(route('team.calendar.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.kpis.thisMonth', 1)
            ->where('insights.kpis.next7Days', 1)
            ->where('insights.kpis.next30Days', 1));
});

it('groups events per month within the range', function () {
    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'date' => now()->startOfMonth()->toDateString(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'date' => now()->startOfMonth()->toDateString(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'date' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
    ]);

    $this->actingAs($this->user)
        ->get(route('team.calendar.insights', ['current_team' => $this->team, 'range' => InsightsTimeRange::Last3Months->value]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('insights.eventsPerMonth', 3)
            ->where('insights.eventsPerMonth.2.count', 2));
});

it('lists upcoming events sorted by date', function () {
    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Far',
        'date' => now()->addDays(20)->toDateString(),
        'time' => '09:00',
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Near',
        'date' => now()->addDay()->toDateString(),
        'time' => '10:00',
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Past',
        'date' => now()->subDay()->toDateString(),
    ]);

    $this->actingAs($this->user)
        ->get(route('team.calendar.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('insights.upcoming', 2)
            ->where('insights.upcoming.0.title', 'Near')
            ->where('insights.upcoming.1.title', 'Far'));
});

it('returns 404 when the calendar feature is disabled', function () {
    $this->team->forceFill(['feature_settings' => ['calendar' => false]])->save();

    $this->actingAs($this->user)
        ->get(route('team.calendar.insights', ['current_team' => $this->team]))
        ->assertNotFound();
});
