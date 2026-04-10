<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Pulse\Support\PulseMigration;

return new class extends PulseMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! $this->shouldRun()) {
            return;
        }

        if ($this->driver() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TABLE "pulse_values" (
                    "id" integer primary key autoincrement not null,
                    "timestamp" integer not null,
                    "type" text not null,
                    "key" text not null,
                    "key_hash" text not null,
                    "value" text not null
                ) STRICT;
                CREATE INDEX "pulse_values_timestamp_index" ON "pulse_values" ("timestamp");
                CREATE INDEX "pulse_values_type_index" ON "pulse_values" ("type");
                CREATE UNIQUE INDEX "pulse_values_type_key_hash_unique" ON "pulse_values" ("type", "key_hash");
                CREATE TABLE "pulse_entries" (
                    "id" integer primary key autoincrement not null,
                    "timestamp" integer not null,
                    "type" text not null,
                    "key" text not null,
                    "key_hash" text not null,
                    "value" integer
                ) STRICT;
                CREATE INDEX "pulse_entries_timestamp_index" ON "pulse_entries" ("timestamp");
                CREATE INDEX "pulse_entries_type_index" ON "pulse_entries" ("type");
                CREATE INDEX "pulse_entries_key_hash_index" ON "pulse_entries" ("key_hash");
                CREATE INDEX "pulse_entries_timestamp_type_key_hash_value_index" ON "pulse_entries" ("timestamp", "type", "key_hash", "value");
                CREATE TABLE "pulse_aggregates" (
                    "id" integer primary key autoincrement not null,
                    "bucket" integer not null,
                    "period" integer not null,
                    "type" text not null,
                    "key" text not null,
                    "key_hash" text not null,
                    "aggregate" text not null,
                    "value" real not null,
                    "count" integer
                ) STRICT;
                CREATE UNIQUE INDEX "pulse_aggregates_bucket_period_type_aggregate_key_hash_unique" ON "pulse_aggregates" ("bucket", "period", "type", "aggregate", "key_hash");
                CREATE INDEX "pulse_aggregates_period_bucket_index" ON "pulse_aggregates" ("period", "bucket");
                CREATE INDEX "pulse_aggregates_type_index" ON "pulse_aggregates" ("type");
                CREATE INDEX "pulse_aggregates_period_type_aggregate_bucket_index" ON "pulse_aggregates" ("period", "type", "aggregate", "bucket");
            SQL);

            return;
        }

        Schema::create('pulse_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('timestamp');
            $table->string('type');
            $table->mediumText('key');
            match ($this->driver()) {
                'mariadb', 'mysql' => $table->char('key_hash', 16)->charset('binary')->virtualAs('unhex(md5(`key`))'),
                'pgsql' => $table->uuid('key_hash')->storedAs('md5("key")::uuid'),
                'sqlite' => $table->string('key_hash'),
            };
            $table->mediumText('value');

            $table->index('timestamp'); // For trimming...
            $table->index('type'); // For fast lookups and purging...
            $table->unique(['type', 'key_hash']); // For data integrity and upserts...
        });

        Schema::create('pulse_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('timestamp');
            $table->string('type');
            $table->mediumText('key');
            match ($this->driver()) {
                'mariadb', 'mysql' => $table->char('key_hash', 16)->charset('binary')->virtualAs('unhex(md5(`key`))'),
                'pgsql' => $table->uuid('key_hash')->storedAs('md5("key")::uuid'),
                'sqlite' => $table->string('key_hash'),
            };
            $table->bigInteger('value')->nullable();

            $table->index('timestamp'); // For trimming...
            $table->index('type'); // For purging...
            $table->index('key_hash'); // For mapping...
            $table->index(['timestamp', 'type', 'key_hash', 'value']); // For aggregate queries...
        });

        Schema::create('pulse_aggregates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('bucket');
            $table->unsignedMediumInteger('period');
            $table->string('type');
            $table->mediumText('key');
            match ($this->driver()) {
                'mariadb', 'mysql' => $table->char('key_hash', 16)->charset('binary')->virtualAs('unhex(md5(`key`))'),
                'pgsql' => $table->uuid('key_hash')->storedAs('md5("key")::uuid'),
                'sqlite' => $table->string('key_hash'),
            };
            $table->string('aggregate');
            $table->decimal('value', 20, 2);
            $table->unsignedInteger('count')->nullable();

            $table->unique(['bucket', 'period', 'type', 'aggregate', 'key_hash']); // Force "on duplicate update"...
            $table->index(['period', 'bucket']); // For trimming...
            $table->index('type'); // For purging...
            $table->index(['period', 'type', 'aggregate', 'bucket']); // For aggregate queries...
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pulse_values');
        Schema::dropIfExists('pulse_entries');
        Schema::dropIfExists('pulse_aggregates');
    }
};
