<?php

use App\Models\TaskComment;
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
        DB::table('task_comments')
            ->whereNotNull('body')
            ->orderBy('id')
            ->each(function (object $comment): void {
                $exists = DB::table('rte_blocks')
                    ->where('blockable_type', TaskComment::class)
                    ->where('blockable_id', $comment->id)
                    ->exists();

                if ($exists) {
                    return;
                }

                DB::table('rte_blocks')->insert([
                    'blockable_type' => TaskComment::class,
                    'blockable_id' => $comment->id,
                    'type' => 'text',
                    'position' => 0,
                    'payload' => json_encode(['content' => $comment->body]),
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ]);
            });

        Schema::table('task_comments', function (Blueprint $table): void {
            $table->text('body')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('task_comments')
            ->whereNull('body')
            ->orderBy('id')
            ->each(function (object $comment): void {
                $block = DB::table('rte_blocks')
                    ->where('blockable_type', TaskComment::class)
                    ->where('blockable_id', $comment->id)
                    ->where('type', 'text')
                    ->orderBy('position')
                    ->first();

                DB::table('task_comments')
                    ->where('id', $comment->id)
                    ->update([
                        'body' => filled($block?->payload)
                            ? (json_decode((string) $block->payload, true)['content'] ?? '')
                            : '',
                    ]);
            });

        Schema::table('task_comments', function (Blueprint $table): void {
            $table->text('body')->nullable(false)->change();
        });
    }
};
