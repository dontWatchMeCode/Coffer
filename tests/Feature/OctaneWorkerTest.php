<?php

use App\Http\Middleware\AuthenticateMcpToken;
use App\Http\Middleware\SetTeamUrlDefaults;
use App\Models\McpToken;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Laravel\Octane\ApplicationFactory;
use Laravel\Octane\Contracts\Client;
use Laravel\Octane\CurrentApplication;
use Laravel\Octane\OctaneResponse;
use Laravel\Octane\RequestContext;
use Laravel\Octane\Worker;

beforeEach(function () {
    $database = $this->app->make('db')->connection();
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('error')->andReturnUsing(function (Throwable $exception): never {
        throw $exception;
    });

    $this->worker = new Worker(new ApplicationFactory(base_path()), $client);
    $this->worker->boot();
    $workerApp = $this->worker->application();

    // Keep RefreshDatabase's transaction while booting the real HTTP application.
    $workerApp->make('db')->extend($database->getDriverName(), fn () => $database);
    expect($workerApp->make('db')->connection())->toBe($database);
    $workerApp->instance(Vite::class, $this->app->make(Vite::class));
    $workerApp->make('config')->set('csp.enabled', true);
    $workerApp->make('config')->set('csp.enabled_while_hot_reloading', true);

    $router = $workerApp->make('router');
    $router->get('/_worker/state', fn () => response()->json([
        'user_id' => request()->user()?->id,
        'token_bound' => app()->bound(McpToken::class),
        'context_team_id' => Context::get('current_team_id'),
        'defaults' => URL::getDefaultParameters(),
    ]))->middleware('web');

    $router->get('/_worker/token', fn () => response()->json([
        'user_id' => request()->user()?->id,
        'team_id' => app(McpToken::class)->team_id,
        'context_team_id' => Context::get('current_team_id'),
        'task_ids' => Task::query()->pluck('id')->all(),
        'defaults' => URL::getDefaultParameters(),
    ]))->middleware([AuthenticateMcpToken::class, SetTeamUrlDefaults::class]);

    $router->get('/_worker/failure', function (): never {
        throw new RuntimeException('Worker isolation test failure');
    })->middleware([AuthenticateMcpToken::class, SetTeamUrlDefaults::class]);

    CurrentApplication::set($this->app);
});

afterEach(function () {
    $this->worker->terminate();
    CurrentApplication::set($this->app);
    Model::setConnectionResolver($this->app->make('db'));
    Model::setEventDispatcher($this->app->make('events'));
});

function workerRequest(Worker $worker, Request $request): TestResponse
{
    $application = app();
    $response = null;
    $worker->application()->make(Client::class)
        ->shouldReceive('respond')->once()
        ->andReturnUsing(function (RequestContext $context, OctaneResponse $result) use (&$response): void {
            $response = TestResponse::fromBaseResponse($result->response);
        });

    try {
        $worker->handle($request, new RequestContext);
    } finally {
        CurrentApplication::set($application);
    }

    expect($response)->toBeInstanceOf(TestResponse::class);

    return $response;
}

test('one worker isolates token users teams and query scopes then rejects invalid tokens with 401', function () {
    $first = McpToken::factory()->create();
    $second = McpToken::factory()->create();
    $firstTask = Task::factory()->for(Project::factory()->state(['team_id' => $first->team_id]))
        ->create(['created_by' => $first->user_id]);
    $secondTask = Task::factory()->for(Project::factory()->state(['team_id' => $second->team_id]))
        ->create(['created_by' => $second->user_id]);

    foreach ([[$first, $firstTask], [$second, $secondTask], [$first, $firstTask]] as [$token, $task]) {
        workerRequest($this->worker, Request::create('/_worker/token', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token->token,
        ]))->assertOk()
            ->assertJsonPath('user_id', $token->user_id)
            ->assertJsonPath('team_id', $token->team_id)
            ->assertJsonPath('context_team_id', $token->team_id)
            ->assertJsonPath('task_ids', [$task->id])
            ->assertJsonPath('defaults.team', $token->team->slug);
    }

    workerRequest($this->worker, Request::create('/_worker/token', server: [
        'HTTP_AUTHORIZATION' => 'Bearer invalid',
    ]))->assertUnauthorized()->assertSee('Invalid token.');

    workerRequest($this->worker, Request::create('/_worker/token'))
        ->assertUnauthorized()->assertSee('Authentication required.');

    workerRequest($this->worker, Request::create('/_worker/state'))
        ->assertOk()->assertExactJson([
            'user_id' => null,
            'token_bound' => false,
            'context_team_id' => null,
            'defaults' => ['current_team' => null, 'team' => null],
        ]);
});

test('one worker clears token authentication and team context after an exception', function () {
    $token = McpToken::factory()->create();

    workerRequest($this->worker, Request::create('/_worker/failure', server: [
        'HTTP_AUTHORIZATION' => 'Bearer '.$token->token,
        'HTTP_ACCEPT' => 'application/json',
    ]))->assertInternalServerError();

    workerRequest($this->worker, Request::create('/_worker/state'))
        ->assertOk()->assertJsonPath('user_id', null)
        ->assertJsonPath('token_bound', false)
        ->assertJsonPath('context_team_id', null)
        ->assertJsonPath('defaults.team', null);
});

test('one worker isolates browser sessions and Inertia user props between users and guests', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $sessions = [];

    foreach ([$first, $second] as $user) {
        $login = workerRequest($this->worker, Request::create('/login', 'POST', [
            'email' => $user->email,
            'password' => 'password',
        ]));
        $login->assertRedirect();
        $sessions[] = collect($login->headers->getCookies())
            ->mapWithKeys(fn ($cookie): array => [$cookie->getName() => $cookie->getValue()])->all();
    }

    foreach ([[$first, $sessions[0]], [$second, $sessions[1]], [$first, $sessions[0]]] as [$user, $cookies]) {
        workerRequest($this->worker, Request::create('/settings/appearance', cookies: $cookies))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user.id', $user->id)
            ->where('currentTeam.id', $user->current_team_id));
    }

    workerRequest($this->worker, Request::create('/_worker/state'))
        ->assertOk()->assertJsonPath('user_id', null)->assertJsonPath('defaults.team', null);

    workerRequest($this->worker, Request::create('/settings/appearance'))
        ->assertRedirect('http://localhost/login');
});

test('one worker resolves appearance cookies and generates a fresh matching CSP nonce for every page', function () {
    $nonces = [];

    foreach ([['appearance' => 'dark', 'theme' => 'nord'], ['appearance' => 'light', 'theme' => 'sage'], []] as $cookies) {
        $response = workerRequest($this->worker, Request::create('/login', cookies: $cookies));
        $response->assertOk()->assertSee('data-theme="'.($cookies['theme'] ?? 'daybreak').'"', false);
        $response->assertHeader('Content-Security-Policy');

        preg_match("/'nonce-([^']+)'/", $response->headers->get('Content-Security-Policy'), $matches);
        expect($matches)->toHaveKey(1);
        $response->assertSee('nonce="'.$matches[1].'"', false);
        $nonces[] = $matches[1];
    }

    expect(array_unique($nonces))->toHaveCount(3);
});
