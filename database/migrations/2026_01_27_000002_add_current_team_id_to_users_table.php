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
            Schema::disableForeignKeyConstraints();

            DB::unprepared(<<<'SQL'
                CREATE TABLE "users_new" (
                    "id" integer primary key autoincrement not null,
                    "name" text not null,
                    "email" text not null,
                    "email_verified_at" text,
                    "password" text not null,
                    "remember_token" text,
                    "created_at" text,
                    "updated_at" text,
                    "two_factor_secret" text,
                    "two_factor_recovery_codes" text,
                    "two_factor_confirmed_at" text,
                    "current_team_id" integer,
                    foreign key("current_team_id") references "teams"("id") on delete set null
                ) STRICT;
                INSERT INTO "users_new" (
                    "id", "name", "email", "email_verified_at", "password", "remember_token",
                    "created_at", "updated_at", "two_factor_secret", "two_factor_recovery_codes", "two_factor_confirmed_at"
                )
                SELECT
                    "id", "name", "email", "email_verified_at", "password", "remember_token",
                    "created_at", "updated_at", "two_factor_secret", "two_factor_recovery_codes", "two_factor_confirmed_at"
                FROM "users";
                DROP TABLE "users";
                ALTER TABLE "users_new" RENAME TO "users";
                CREATE UNIQUE INDEX "users_email_unique" ON "users" ("email");
            SQL);

            Schema::enableForeignKeyConstraints();

            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('current_team_id')
                ->nullable()
                ->after('password')
                ->constrained('teams')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            DB::unprepared(<<<'SQL'
                CREATE TABLE "users_new" (
                    "id" integer primary key autoincrement not null,
                    "name" text not null,
                    "email" text not null,
                    "email_verified_at" text,
                    "password" text not null,
                    "remember_token" text,
                    "created_at" text,
                    "updated_at" text,
                    "two_factor_secret" text,
                    "two_factor_recovery_codes" text,
                    "two_factor_confirmed_at" text
                ) STRICT;
                INSERT INTO "users_new" (
                    "id", "name", "email", "email_verified_at", "password", "remember_token",
                    "created_at", "updated_at", "two_factor_secret", "two_factor_recovery_codes", "two_factor_confirmed_at"
                )
                SELECT
                    "id", "name", "email", "email_verified_at", "password", "remember_token",
                    "created_at", "updated_at", "two_factor_secret", "two_factor_recovery_codes", "two_factor_confirmed_at"
                FROM "users";
                DROP TABLE "users";
                ALTER TABLE "users_new" RENAME TO "users";
                CREATE UNIQUE INDEX "users_email_unique" ON "users" ("email");
            SQL);

            Schema::enableForeignKeyConstraints();

            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_team_id');
        });
    }
};
