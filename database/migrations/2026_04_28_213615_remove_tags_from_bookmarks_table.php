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
                CREATE TABLE "bookmarks_new" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "title" text not null,
                    "url" text not null,
                    "description" text,
                    "notes" text,
                    "is_archived" integer not null default '0',
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade
                ) STRICT;
                CREATE INDEX "bookmarks_new_team_id_index" ON "bookmarks_new" ("team_id");
                CREATE INDEX "bookmarks_new_title_index" ON "bookmarks_new" ("title");
            SQL);

            DB::unprepared(<<<'SQL'
                INSERT INTO "bookmarks_new" ("id", "team_id", "title", "url", "description", "notes", "is_archived", "created_at", "updated_at")
                SELECT "id", "team_id", "title", "url", "description", "notes", "is_archived", "created_at", "updated_at"
                FROM "bookmarks";
            SQL);

            DB::unprepared('DROP TABLE "bookmarks";');
            DB::unprepared('ALTER TABLE "bookmarks_new" RENAME TO "bookmarks";');

            return;
        }

        Schema::table('bookmarks', function (Blueprint $table): void {
            $table->dropColumn('tags');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TABLE "bookmarks_new" (
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
                CREATE INDEX "bookmarks_new_team_id_index" ON "bookmarks_new" ("team_id");
                CREATE INDEX "bookmarks_new_title_index" ON "bookmarks_new" ("title");
            SQL);

            DB::unprepared(<<<'SQL'
                INSERT INTO "bookmarks_new" ("id", "team_id", "title", "url", "description", "tags", "notes", "is_archived", "created_at", "updated_at")
                SELECT "id", "team_id", "title", "url", "description", NULL, "notes", "is_archived", "created_at", "updated_at"
                FROM "bookmarks";
            SQL);

            DB::unprepared('DROP TABLE "bookmarks";');
            DB::unprepared('ALTER TABLE "bookmarks_new" RENAME TO "bookmarks";');

            return;
        }

        Schema::table('bookmarks', function (Blueprint $table): void {
            $table->json('tags')->nullable()->after('description');
        });
    }
};
