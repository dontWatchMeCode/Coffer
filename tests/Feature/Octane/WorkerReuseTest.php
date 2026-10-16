<?php

use App\Http\Middleware\AuthenticateMcpToken;
use App\Models\McpToken;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

test('sequential requests in one process do not leak team url defaults or inertia auth', function () {
    $userA = User::factory()->create();
    $teamA = $userA->currentTeam;
    $userB = User::factory()->create();
    $teamB = $userB->currentTeam;

    $projectA = Project::factory()->create(['team_id' => $teamA->id]);
    Task::factory()->create([
        'project_id' => $projectA->id,
        'team_id' => $teamA->id,
        'created_by' => $userA->id,
        'title' => 'Team A private task',
        'due_at' => now(),
    ]);

    $projectB = Project::factory()->create(['team_id' => $teamB->id]);
    Task::factory()->create([
        'project_id' => $projectB->id,
        'team_id' => $teamB->id,
        'created_by' => $userB->id,
        'title' => 'Team B private task',
        'due_at' => now(),
    ]);

    // First request in this process: user A, team A context.
    $this->actingAs($userA)->get(route('team.dashboard', $teamA))->assertOk();

    // Second request in the SAME process/app instance: user B.
    $this->actingAs($userB)->get(route('team.dashboard', $teamB))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.user.id', $userB->id)
            ->where('currentTeam.slug', $teamB->slug)
            ->has('dashboard.today.tasks', 1)
            ->where('dashboard.today.tasks.0.title', 'Team B private task'));

    expect(route('team.dashboard'))->toBe(route('team.dashboard', ['current_team' => $teamB->slug]))
        ->and(route('dashboard'))->not->toContain($teamA->slug);
});

test('guest request after an authenticated request sees no leaked team context', function () {
    $userA = User::factory()->create();
    $teamA = $userA->currentTeam;

    $this->actingAs($userA)->get(route('team.dashboard', $teamA))->assertOk();

    // Drop the in-process auth state so the next call is a guest request
    // handled by the same app instance (simulating worker reuse).
    $this->app['auth']->forgetGuards();

    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', null)
            ->where('currentTeam', null)
            ->has('teams', 0));

    expect(URL::getDefaultParameters())
        ->toMatchArray(['current_team' => null, 'team' => null]);
});

test('mcp-authed request does not leak guard user into the next web request', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    [, $plainTextToken] = McpToken::createToken($user, $team, 'Worker reuse probe', [
        'notes' => 'read',
    ]);

    $this->withToken($plainTextToken)
        ->postJson(route('mcp.records'), [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ])
        ->assertSuccessful();

    // Same process, no session: a leaked SessionGuard user would 200 here.
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('appearance is resolved for each render even outside the web middleware', function () {
    Route::get('/worker-appearance', fn () => Inertia::render('Welcome'));

    $this->withUnencryptedCookies(['appearance' => 'dark', 'theme' => 'espresso'])
        ->get(route('home'))
        ->assertOk()
        ->assertSee('data-theme="espresso"', false);

    $this->call('GET', '/worker-appearance')
        ->assertOk()
        ->assertSee('data-theme="daybreak"', false)
        ->assertDontSee('class="dark"', false);

    $this->call('GET', '/worker-appearance', cookies: ['appearance' => 'light', 'theme' => 'sage'])
        ->assertOk()
        ->assertSee('data-theme="sage"', false);
});

test('failed mcp requests clear token auth and team context before the next request', function () {
    $user = User::factory()->create();
    [, $plainTextToken] = McpToken::createToken($user, $user->currentTeam, 'Failed worker request', [
        'notes' => 'read',
    ]);

    Route::get('/worker-mcp-failure', fn () => abort(403))
        ->middleware(AuthenticateMcpToken::class);

    $this->withToken($plainTextToken)->get('/worker-mcp-failure')->assertForbidden();

    expect(app()->bound(McpToken::class))->toBeFalse();
    expect(Context::get('current_team_id'))->toBeNull();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
