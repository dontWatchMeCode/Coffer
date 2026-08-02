<?php

use App\Http\Middleware\EnsureTeamMembership;
use App\Models\Bookmark;
use App\Policies\BookmarkPolicy;
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

it('discovers conventional policies automatically', function () {
    expect(Gate::getPolicyFor(Bookmark::class))->toBeInstanceOf(BookmarkPolicy::class);
});
