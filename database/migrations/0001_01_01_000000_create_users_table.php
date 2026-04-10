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
                CREATE TABLE "users" (
                    "id" integer primary key autoincrement not null,
                    "name" text not null,
                    "email" text not null,
                    "email_verified_at" text,
                    "password" text not null,
                    "remember_token" text,
                    "created_at" text,
                    "updated_at" text
                ) STRICT;
                CREATE UNIQUE INDEX "users_email_unique" ON "users" ("email");
                CREATE TABLE "password_reset_tokens" (
                    "email" text not null,
                    "token" text not null,
                    "created_at" text,
                    primary key ("email")
                ) STRICT;
                CREATE TABLE "sessions" (
                    "id" text not null,
                    "user_id" integer,
                    "ip_address" text,
                    "user_agent" text,
                    "payload" text not null,
                    "last_activity" integer not null,
                    primary key ("id")
                ) STRICT;
                CREATE INDEX "sessions_user_id_index" ON "sessions" ("user_id");
                CREATE INDEX "sessions_last_activity_index" ON "sessions" ("last_activity");
            SQL);

            return;
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
