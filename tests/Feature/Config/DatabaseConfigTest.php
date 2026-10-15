<?php

use Illuminate\Support\Facades\DB;

$skipUnlessSqlite = fn (): bool => DB::connection()->getDriverName() !== 'sqlite';

test('sqlite uses wal-oriented defaults', function () {
    expect(config('database.connections.sqlite'))->toMatchArray([
        'busy_timeout' => 5000,
        'journal_mode' => 'wal',
        'synchronous' => 'normal',
    ]);
})->skip($skipUnlessSqlite, 'SQLite configuration test only runs on SQLite.');

test('development setup resets the configured database through migrations', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($composer['scripts']['setup-dev'])->toBe([
        'Composer\\Config::disableProcessTimeout',
        '@php artisan migrate:fresh --force --no-interaction',
        '@php -d memory_limit=512M artisan app:generate-test-records',
    ]);
});
