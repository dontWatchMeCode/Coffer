<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\HandleAppearance;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Best-effort baseline for appearance view data.
     *
     * HandleAppearance overwrites these on every web/MCP request; the defaults
     * below only cover cold workers and renders that bypass that middleware
     * (e.g. early 404s). Queued mail runs on separate queue processes with
     * their own boot baseline.
     */
    public function boot(): void
    {
        View::share('appearance', 'system');
        View::share('theme', HandleAppearance::lightIds()[0]);
        View::share('themeLightIds', HandleAppearance::lightIds());
        View::share('themeDarkIds', HandleAppearance::darkIds());
    }
}
