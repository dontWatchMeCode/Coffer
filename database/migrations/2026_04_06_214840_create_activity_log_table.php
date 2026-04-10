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
                CREATE TABLE "activity_log" (
                    "id" integer primary key autoincrement not null,
                    "log_name" text,
                    "description" text not null,
                    "subject_type" text,
                    "subject_id" integer,
                    "event" text,
                    "causer_type" text,
                    "causer_id" integer,
                    "attribute_changes" text,
                    "properties" text,
                    "created_at" text,
                    "updated_at" text
                ) STRICT;
                CREATE INDEX "activity_log_log_name_index" ON "activity_log" ("log_name");
                CREATE INDEX "subject" ON "activity_log" ("subject_type", "subject_id");
                CREATE INDEX "causer" ON "activity_log" ("causer_type", "causer_id");
            SQL);

            return;
        }

        Schema::create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }
};
