<?php

use App\Enums\InsightsTimeRange;
use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-07-31 12:00:00'));
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
        'title' => 'Seven day boundary',
        'date' => $today->addDays(7)->toDateString(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Thirty day boundary',
        'date' => $today->addDays(30)->toDateString(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Far future',
        'date' => $today->addDays(60)->toDateString(),
    ]);

    $this->actingAs($this->user)
        ->get(route('team.calendar.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.kpis.thisMonth', 1)
            ->where('insights.kpis.next7Days', 3)
            ->where('insights.kpis.next30Days', 4));
});

it('excludes the first date outside each kpi range', function () {
    $today = CarbonImmutable::today();

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'date' => $today->startOfMonth()->addMonth()->toDateString(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'date' => $today->addDays(8)->toDateString(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'date' => $today->addDays(31)->toDateString(),
    ]);

    $this->actingAs($this->user)
        ->get(route('team.calendar.insights', ['current_team' => $this->team]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.kpis.thisMonth', 0)
            ->where('insights.kpis.next7Days', 1)
            ->where('insights.kpis.next30Days', 2));
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
        'date' => now()->toDateString(),
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
            ->where('insights.eventsPerMonth.2.count', 3));
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
