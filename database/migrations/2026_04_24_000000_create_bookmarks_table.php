<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TABLE "bookmarks" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "title" text not null,
                    "url" text not null,
                    "description" text,
                    "tags" text,
                    "notes" text,
                    "is_archived" integer not null default '0',
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade
                ) STRICT;
                CREATE INDEX "bookmarks_team_id_index" ON "bookmarks" ("team_id");
                CREATE INDEX "bookmarks_title_index" ON "bookmarks" ("title");
            SQL);

            return;
        }

        Schema::create('bookmarks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('url');
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
