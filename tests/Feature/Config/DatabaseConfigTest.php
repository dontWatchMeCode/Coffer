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
