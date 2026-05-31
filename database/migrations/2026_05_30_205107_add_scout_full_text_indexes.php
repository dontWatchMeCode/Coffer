<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array{name: string, columns: list<string>}>
     */
    private array $indexes = [
        'tasks' => ['name' => 'tasks_scout_fulltext', 'columns' => ['title', 'description']],
        'subscriptions' => ['name' => 'subscriptions_scout_fulltext', 'columns' => ['name', 'category', 'description']],
        'notes' => ['name' => 'notes_scout_fulltext', 'columns' => ['title']],
        'record_collections' => ['name' => 'record_collections_scout_fulltext', 'columns' => ['title', 'description']],
        'contacts' => ['name' => 'contacts_scout_fulltext', 'columns' => ['name', 'address', 'additional_info']],
        'calendar_events' => ['name' => 'calendar_events_scout_fulltext', 'columns' => ['title', 'description']],
        'log_entries' => ['name' => 'log_entries_scout_fulltext', 'columns' => ['body', 'category']],
        'bookmarks' => ['name' => 'bookmarks_scout_fulltext', 'columns' => ['title', 'description', 'url']],
        'projects' => ['name' => 'projects_scout_fulltext', 'columns' => ['name', 'description']],
        'tags' => ['name' => 'tags_scout_fulltext', 'columns' => ['name', 'slug']],
        'subscription_categories' => ['name' => 'subscription_categories_scout_fulltext', 'columns' => ['name', 'slug']],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! $this->supportsFullTextIndexes()) {
            return;
        }

        foreach ($this->indexes as $table => $index) {
            Schema::table($table, function (Blueprint $table) use ($index): void {
                $table->fullText($index['columns'], $index['name']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->supportsFullTextIndexes()) {
            return;
        }

        foreach ($this->indexes as $table => $index) {
            Schema::table($table, function (Blueprint $table) use ($index): void {
                $table->dropFullText($index['name']);
            });
        }
    }

    private function supportsFullTextIndexes(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'pgsql'], true);
    }
};
