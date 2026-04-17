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
                CREATE TABLE "tasks_new" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "project_id" integer not null,
                    "assigned_to" integer,
                    "created_by" integer,
                    "title" text not null,
                    "description" text,
                    "status" text not null default 'planned',
                    "progress" integer not null default '0',
                    "position" integer not null default '0',
                    "due_at" text,
                    "completed_at" text,
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade,
                    foreign key("project_id") references "projects"("id") on delete cascade,
                    foreign key("assigned_to") references "users"("id") on delete set null,
                    foreign key("created_by") references "users"("id") on delete set null
                ) STRICT;
                INSERT INTO "tasks_new" ("id", "team_id", "project_id", "assigned_to", "created_by", "title", "description", "status", "progress", "position", "due_at", "completed_at", "created_at", "updated_at") SELECT "id", "team_id", "project_id", "assigned_to", "created_by", "title", "description", "status", "progress", "position", NULL, "completed_at", "created_at", "updated_at" FROM "tasks";
                DROP TABLE "tasks";
                ALTER TABLE "tasks_new" RENAME TO "tasks";
                CREATE INDEX "tasks_team_id_index" ON "tasks" ("team_id");
                CREATE INDEX "tasks_project_id_index" ON "tasks" ("project_id");
                CREATE INDEX "tasks_assigned_to_index" ON "tasks" ("assigned_to");
                CREATE INDEX "tasks_status_index" ON "tasks" ("status");
                CREATE INDEX "tasks_project_id_status_position_index" ON "tasks" ("project_id", "status", "position");
            SQL);

            return;
        }

        Schema::table('tasks', function (Blueprint $table): void {
            $table->timestamp('due_at')->nullable()->after('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TABLE "tasks_new" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "project_id" integer not null,
                    "assigned_to" integer,
                    "created_by" integer,
                    "title" text not null,
                    "description" text,
                    "status" text not null default 'planned',
                    "progress" integer not null default '0',
                    "position" integer not null default '0',
                    "completed_at" text,
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade,
                    foreign key("project_id") references "projects"("id") on delete cascade,
                    foreign key("assigned_to") references "users"("id") on delete set null,
                    foreign key("created_by") references "users"("id") on delete set null
                ) STRICT;
                INSERT INTO "tasks_new" ("id", "team_id", "project_id", "assigned_to", "created_by", "title", "description", "status", "progress", "position", "completed_at", "created_at", "updated_at") SELECT "id", "team_id", "project_id", "assigned_to", "created_by", "title", "description", "status", "progress", "position", "completed_at", "created_at", "updated_at" FROM "tasks";
                DROP TABLE "tasks";
                ALTER TABLE "tasks_new" RENAME TO "tasks";
                CREATE INDEX "tasks_team_id_index" ON "tasks" ("team_id");
                CREATE INDEX "tasks_project_id_index" ON "tasks" ("project_id");
                CREATE INDEX "tasks_assigned_to_index" ON "tasks" ("assigned_to");
                CREATE INDEX "tasks_status_index" ON "tasks" ("status");
                CREATE INDEX "tasks_project_id_status_position_index" ON "tasks" ("project_id", "status", "position");
            SQL);

            return;
        }

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('due_at');
        });
    }
};
