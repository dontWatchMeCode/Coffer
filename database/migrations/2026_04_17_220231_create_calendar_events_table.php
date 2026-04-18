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
                CREATE TABLE "calendar_events" (
                    "id" integer primary key autoincrement not null,
                    "team_id" integer not null,
                    "title" text not null,
                    "description" text,
                    "date" text not null,
                    "created_at" text,
                    "updated_at" text,
                    foreign key("team_id") references "teams"("id") on delete cascade
                ) STRICT;
                CREATE INDEX "calendar_events_team_id_index" ON "calendar_events" ("team_id");
                CREATE INDEX "calendar_events_date_index" ON "calendar_events" ("date");
            SQL);

            return;
        }

        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('date');
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
