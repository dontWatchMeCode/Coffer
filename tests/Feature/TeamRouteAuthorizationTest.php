<?php

use App\Http\Middleware\EnsureTeamMembership;
use App\Models\McpToken;
use App\Models\RecordLink;
use App\Models\Tag;
use App\Models\TaskComment;
use App\Models\Team;
use App\Services\RecordTypeRegistry;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

it('protects every current team route with membership middleware', function () {
    $teamRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (IlluminateRoute $route): bool => str_contains($route->uri(), '{current_team}'));

    expect($teamRoutes)->not->toBeEmpty();

    $teamRoutes->each(function (IlluminateRoute $route): void {
        expect($route->gatherMiddleware())
            ->toContain(EnsureTeamMembership::class);
    });
});

it('discovers conventional policies automatically', function (string $model) {
    $policyClass = str_replace('App\\Models', 'App\\Policies', $model).'Policy';

    expect(class_exists($policyClass))->toBeTrue()
        ->and(Gate::getPolicyFor($model))->toBeInstanceOf($policyClass);
})->with(array_values(array_unique([
    ...array_map(
        fn (array $definition): string => $definition['class'],
        RecordTypeRegistry::definitions(),
    ),
    McpToken::class,
    RecordLink::class,
    Tag::class,
    TaskComment::class,
    Team::class,
])));
