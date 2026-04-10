<?php

use Illuminate\Support\Facades\DB;

test('sqlite uses wal-oriented defaults', function () {
    expect(config('database.connections.sqlite'))->toMatchArray([
        'busy_timeout' => 5000,
        'journal_mode' => 'wal',
        'synchronous' => 'normal',
    ]);
});

test('sqlite tables are rebuilt in strict mode', function () {
    $tables = collect(DB::select("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
        ->reject(fn (object $table): bool => $table->name === 'migrations')
        ->values();

    expect($tables)->not->toBeEmpty();

    $tables->each(function (object $table): void {
        expect($table->sql)
            ->toBeString()
            ->toEndWith('STRICT');
    });
});
