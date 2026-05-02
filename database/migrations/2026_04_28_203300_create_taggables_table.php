<?php

use App\Models\Bookmark;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->string('taggable_type');
            $table->unsignedBigInteger('taggable_id');
            $table->timestamps();

            $table->unique(['tag_id', 'taggable_type', 'taggable_id']);
            $table->index(['taggable_type', 'taggable_id']);
        });

        $this->backfillBookmarkTags();
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }

    private function backfillBookmarkTags(): void
    {
        $now = now();

        DB::table('bookmarks')
            ->whereNotNull('tags')
            ->orderBy('id')
            ->each(function (object $bookmark) use ($now): void {
                $tags = json_decode((string) $bookmark->tags, true);

                if (! is_array($tags)) {
                    return;
                }

                foreach ($tags as $name) {
                    if (! is_string($name)) {
                        continue;
                    }

                    $name = trim($name);
                    $slug = Str::slug($name);
                    if ($name === '') {
                        continue;
                    }

                    if ($slug === '') {
                        continue;
                    }

                    $tagId = DB::table('tags')->where([
                        'team_id' => $bookmark->team_id,
                        'slug' => $slug,
                    ])->value('id');

                    if ($tagId === null) {
                        $tagId = DB::table('tags')->insertGetId([
                            'team_id' => $bookmark->team_id,
                            'name' => $name,
                            'slug' => $slug,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    DB::table('taggables')->insertOrIgnore([
                        'tag_id' => $tagId,
                        'taggable_type' => Bookmark::class,
                        'taggable_id' => $bookmark->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }
};
