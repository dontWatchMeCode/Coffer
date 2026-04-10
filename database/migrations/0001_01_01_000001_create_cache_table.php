<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TABLE "cache" (
                    "key" text not null,
                    "value" text not null,
                    "expiration" integer not null,
                    primary key ("key")
                ) STRICT;
                CREATE INDEX "cache_expiration_index" ON "cache" ("expiration");
                CREATE TABLE "cache_locks" (
                    "key" text not null,
                    "owner" text not null,
                    "expiration" integer not null,
                    primary key ("key")
                ) STRICT;
                CREATE INDEX "cache_locks_expiration_index" ON "cache_locks" ("expiration");
            SQL);

            return;
        }

        Schema::create('cache', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
