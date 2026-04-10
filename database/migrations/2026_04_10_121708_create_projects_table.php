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
                CREATE TABLE "projects" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "name" text not null,
                    "description" text,
                    "archived" integer not null default '0',
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade
                ) STRICT;
                CREATE UNIQUE INDEX "projects_team_id_name_unique" ON "projects" ("team_id", "name");
                CREATE INDEX "projects_team_id_archived_index" ON "projects" ("team_id", "archived");
            SQL);

            return;
        }

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('archived')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'name']);
            $table->index(['team_id', 'archived']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
