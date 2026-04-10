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
                CREATE TABLE "jobs" (
                    "id" integer primary key autoincrement not null,
                    "queue" text not null,
                    "payload" text not null,
                    "attempts" integer not null,
                    "reserved_at" integer,
                    "available_at" integer not null,
                    "created_at" integer not null
                ) STRICT;
                CREATE INDEX "jobs_queue_index" ON "jobs" ("queue");
                CREATE TABLE "job_batches" (
                    "id" text not null,
                    "name" text not null,
                    "total_jobs" integer not null,
                    "pending_jobs" integer not null,
                    "failed_jobs" integer not null,
                    "failed_job_ids" text not null,
                    "options" text,
                    "cancelled_at" integer,
                    "created_at" integer not null,
                    "finished_at" integer,
                    primary key ("id")
                ) STRICT;
                CREATE TABLE "failed_jobs" (
                    "id" integer primary key autoincrement not null,
                    "uuid" text not null,
                    "connection" text not null,
                    "queue" text not null,
                    "payload" text not null,
                    "exception" text not null,
                    "failed_at" text not null default CURRENT_TIMESTAMP
                ) STRICT;
                CREATE UNIQUE INDEX "failed_jobs_uuid_unique" ON "failed_jobs" ("uuid");
            SQL);

            return;
        }

        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
