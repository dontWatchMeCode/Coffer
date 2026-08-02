<?php

use App\Models\TaskComment;
use Illuminate\Support\Facades\DB;

test('legacy BlockNote text blocks are converted to markdown once', function () {
    $legacyContent = json_encode([
        [
            'type' => 'heading',
            'props' => ['level' => 2],
            'content' => [[
                'type' => 'text',
                'text' => 'Plan *phase*',
                'styles' => ['bold' => true],
            ]],
        ],
        [
            'type' => 'bulletListItem',
            'content' => 'First task',
        ],
        [
            'type' => 'numberedListItem',
            'content' => [[
                'type' => 'text',
                'text' => 'Second task',
                'styles' => ['italic' => true],
            ]],
        ],
        [
            'type' => 'checkListItem',
            'props' => ['checked' => true],
            'content' => [[
                'type' => 'text',
                'text' => 'Completed',
                'styles' => ['strike' => true],
            ]],
        ],
        [
            'type' => 'quote',
            'content' => 'Quoted text',
        ],
        [
            'type' => 'codeBlock',
            'props' => ['language' => 'php'],
            'content' => 'echo 1;',
        ],
        [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'See '],
                ['type' => 'link', 'href' => 'https://example.com', 'content' => [['type' => 'text', 'text' => 'docs']]],
                ['type' => 'text', 'text' => ' and '],
                ['type' => 'text', 'text' => 'code', 'styles' => ['code' => true]],
            ],
            'children' => [[
                'type' => 'bulletListItem',
                'content' => 'Nested task',
            ]],
        ],
    ], JSON_THROW_ON_ERROR);
    $jsonLikeMarkdown = '["text"]';
    $now = now();

    DB::table('rte_blocks')->insert([
        [
            'blockable_type' => TaskComment::class,
            'blockable_id' => 1001,
            'type' => 'text',
            'position' => 0,
            'payload' => json_encode(['content' => $legacyContent], JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'blockable_type' => TaskComment::class,
            'blockable_id' => 1002,
            'type' => 'text',
            'position' => 0,
            'payload' => json_encode(['content' => $jsonLikeMarkdown], JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    $migration = require database_path('migrations/2026_08_02_010535_convert_legacy_block_note_rte_blocks_to_markdown.php');
    $migration->up();

    $contents = DB::table('rte_blocks')
        ->whereIn('blockable_id', [1001, 1002])
        ->orderBy('blockable_id')
        ->pluck('payload')
        ->map(fn (string $payload): mixed => data_get(json_decode($payload, true, flags: JSON_THROW_ON_ERROR), 'content'))
        ->all();

    expect($contents)->toBe([
        "## **Plan \\*phase\\***\n\n- First task\n\n1. *Second task*\n\n- [x] ~~Completed~~\n\n> Quoted text\n\n```php\necho 1;\n```\n\nSee [docs](https://example.com) and `code`\n  - Nested task",
        $jsonLikeMarkdown,
    ]);
});
