<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

test('sqlite schema builder creates strict compatible columns', function () {
    Schema::dropIfExists('strict_schema_test');

    Schema::create('strict_schema_test', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->boolean('active')->default(false);
        $table->timestamp('seen_at')->nullable();
        $table->decimal('score', 8, 2)->default(0);
        $table->json('meta')->nullable();
    });

    $table = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'strict_schema_test'");

    expect($table->sql)
        ->toContain('"name" text not null')
        ->toContain('"active" integer not null default')
        ->toContain('"seen_at" text')
        ->toContain('"score" real not null default')
        ->toContain('"meta" text')
        ->toEndWith('STRICT');

    Schema::dropIfExists('strict_schema_test');
});

test('sqlite schema builder rebuilds altered tables in strict mode', function () {
    Schema::dropIfExists('strict_alter_test');

    Schema::create('strict_alter_test', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('obsolete')->nullable();
    });

    Schema::table('strict_alter_test', function (Blueprint $table): void {
        $table->dropColumn('obsolete');
    });

    $table = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'strict_alter_test'");

    expect($table->sql)
        ->toContain('"name" text not null')
        ->not->toContain('obsolete')
        ->toEndWith('STRICT');

    Schema::dropIfExists('strict_alter_test');
});
