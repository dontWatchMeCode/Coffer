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
                CREATE TABLE "tasks" (
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
                CREATE INDEX "tasks_team_id_index" ON "tasks" ("team_id");
                CREATE INDEX "tasks_project_id_index" ON "tasks" ("project_id");
                CREATE INDEX "tasks_assigned_to_index" ON "tasks" ("assigned_to");
                CREATE INDEX "tasks_status_index" ON "tasks" ("status");
                CREATE INDEX "tasks_project_id_status_position_index" ON "tasks" ("project_id", "status", "position");
            SQL);

            return;
        }

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('planned');
            $table->unsignedInteger('progress')->default(0);
            $table->integer('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('team_id');
            $table->index('project_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index(['project_id', 'status', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
