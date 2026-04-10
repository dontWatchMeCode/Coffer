<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        return config('telescope.storage.database.connection');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());

        if (DB::connection($this->getConnection())->getDriverName() === 'sqlite') {
            DB::connection($this->getConnection())->unprepared(<<<'SQL'
                CREATE TABLE "telescope_entries" (
                    "sequence" integer primary key autoincrement not null,
                    "uuid" text not null,
                    "batch_id" text not null,
                    "family_hash" text,
                    "should_display_on_index" integer not null default '1',
                    "type" text not null,
                    "content" text not null,
                    "created_at" text
                ) STRICT;
                CREATE UNIQUE INDEX "telescope_entries_uuid_unique" ON "telescope_entries" ("uuid");
                CREATE INDEX "telescope_entries_batch_id_index" ON "telescope_entries" ("batch_id");
                CREATE INDEX "telescope_entries_family_hash_index" ON "telescope_entries" ("family_hash");
                CREATE INDEX "telescope_entries_created_at_index" ON "telescope_entries" ("created_at");
                CREATE INDEX "telescope_entries_type_should_display_on_index_index" ON "telescope_entries" ("type", "should_display_on_index");
                CREATE TABLE "telescope_entries_tags" (
                    "entry_uuid" text not null,
                    "tag" text not null,
                    foreign key("entry_uuid") references "telescope_entries"("uuid") on delete cascade,
                    primary key ("entry_uuid", "tag")
                ) STRICT;
                CREATE INDEX "telescope_entries_tags_tag_index" ON "telescope_entries_tags" ("tag");
                CREATE TABLE "telescope_monitoring" (
                    "tag" text not null,
                    primary key ("tag")
                ) STRICT;
            SQL);

            return;
        }

        $schema->create('telescope_entries', function (Blueprint $table): void {
            $table->bigIncrements('sequence');
            $table->uuid('uuid');
            $table->uuid('batch_id');
            $table->string('family_hash')->nullable();
            $table->boolean('should_display_on_index')->default(true);
            $table->string('type', 20);
            $table->longText('content');
            $table->dateTime('created_at')->nullable();

            $table->unique('uuid');
            $table->index('batch_id');
            $table->index('family_hash');
            $table->index('created_at');
            $table->index(['type', 'should_display_on_index']);
        });

        $schema->create('telescope_entries_tags', function (Blueprint $table): void {
            $table->uuid('entry_uuid');
            $table->string('tag');

            $table->primary(['entry_uuid', 'tag']);
            $table->index('tag');

            $table->foreign('entry_uuid')
                ->references('uuid')
                ->on('telescope_entries')
                ->cascadeOnDelete();
        });

        $schema->create('telescope_monitoring', function (Blueprint $table): void {
            $table->string('tag')->primary();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection($this->getConnection());

        $schema->dropIfExists('telescope_entries_tags');
        $schema->dropIfExists('telescope_entries');
        $schema->dropIfExists('telescope_monitoring');
    }
};
