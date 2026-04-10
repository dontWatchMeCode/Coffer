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
                CREATE TABLE "teams" (
                    "id" integer primary key autoincrement not null,
                    "name" text not null,
                    "slug" text not null,
                    "is_personal" integer not null default '0',
                    "created_at" text,
                    "updated_at" text,
                    "deleted_at" text
                ) STRICT;
                CREATE UNIQUE INDEX "teams_slug_unique" ON "teams" ("slug");
                CREATE TABLE "team_members" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "user_id" integer not null,
                    "role" text not null,
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade,
                    foreign key("user_id") references "users"("id") on delete cascade
                ) STRICT;
                CREATE UNIQUE INDEX "team_members_team_id_user_id_unique" ON "team_members" ("team_id", "user_id");
                CREATE TABLE "team_invitations" (
                    "id" integer primary key autoincrement not null,
                    "code" text not null,
                    "team_id" integer not null,
                    "email" text not null,
                    "role" text not null,
                    "invited_by" integer not null,
                    "expires_at" text,
                    "accepted_at" text,
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade,
                    foreign key("invited_by") references "users"("id") on delete cascade
                ) STRICT;
                CREATE UNIQUE INDEX "team_invitations_code_unique" ON "team_invitations" ("code");
            SQL);

            return;
        }

        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_personal')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });

        Schema::create('team_invitations', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role');
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }
};
