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
                CREATE TABLE "record_links" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "left_type" text not null,
                    "left_id" integer not null,
                    "right_type" text not null,
                    "right_id" integer not null,
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade,
                    unique("team_id", "left_type", "left_id", "right_type", "right_id")
                ) STRICT;
                CREATE INDEX "record_links_left_index" ON "record_links" ("left_type", "left_id");
                CREATE INDEX "record_links_right_index" ON "record_links" ("right_type", "right_id");
            SQL);

            return;
        }

        Schema::create('record_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('left_type');
            $table->unsignedBigInteger('left_id');
            $table->string('right_type');
            $table->unsignedBigInteger('right_id');
            $table->timestamps();

            $table->unique(['team_id', 'left_type', 'left_id', 'right_type', 'right_id'], 'record_links_pair_unique');
            $table->index(['left_type', 'left_id']);
            $table->index(['right_type', 'right_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_links');
    }
};
