<?php

declare(strict_types=1);

namespace App\Providers;

use App\Database\StrictSQLiteConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Connection::resolverFor('sqlite', fn (mixed $connection, string $database, string $prefix, array $config): StrictSQLiteConnection => new StrictSQLiteConnection($connection, $database, $prefix, $config));

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }
}
