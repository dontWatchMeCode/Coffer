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
                CREATE TABLE "record_collections" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "title" text not null,
                    "description" text,
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade
                ) STRICT;
                CREATE INDEX "record_collections_team_id_index" ON "record_collections" ("team_id");
                CREATE INDEX "record_collections_title_index" ON "record_collections" ("title");
            SQL);

            return;
        }

        Schema::create('record_collections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_collections');
    }
};
