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
                CREATE TABLE "task_comments" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "task_id" integer not null,
                    "user_id" integer not null,
                    "body" text not null,
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade,
                    foreign key("task_id") references "tasks"("id") on delete cascade,
                    foreign key("user_id") references "users"("id")
                ) STRICT;
                CREATE INDEX "task_comments_team_id_index" ON "task_comments" ("team_id");
                CREATE INDEX "task_comments_task_id_index" ON "task_comments" ("task_id");
                CREATE INDEX "task_comments_user_id_index" ON "task_comments" ("user_id");
            SQL);

            return;
        }

        Schema::create('task_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index('team_id');
            $table->index('task_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};
