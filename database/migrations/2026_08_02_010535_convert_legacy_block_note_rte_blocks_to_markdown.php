<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rte_blocks')
            ->where('type', 'text')
            ->whereNotNull('payload')
            ->orderBy('id')
            ->chunkById(100, function (Collection $blocks): void {
                foreach ($blocks as $block) {
                    $payload = json_decode((string) $block->payload, true);
                    $content = is_array($payload) ? ($payload['content'] ?? null) : null;

                    if (! is_string($content)) {
                        continue;
                    }

                    $legacyBlocks = json_decode($content, true);

                    if (! $this->isBlockList($legacyBlocks)) {
                        continue;
                    }

                    $payload['content'] = $this->renderBlocks($legacyBlocks);

                    DB::table('rte_blocks')
                        ->where('id', $block->id)
                        ->update(['payload' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)]);
                }
            });
    }

    public function down(): void {}

    /**
     * @phpstan-assert-if-true list<array<string, mixed>> $value
     */
    private function isBlockList(mixed $value): bool
    {
        return is_array($value)
            && $value !== []
            && array_is_list($value)
            && array_all($value, fn (mixed $block): bool => is_array($block) && is_string($block['type'] ?? null));
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private function renderBlocks(array $blocks, int $depth = 0): string
    {
        $rendered = array_map(function (array $block) use ($depth): string {
            $content = $this->renderInlineContent($block['content'] ?? null);
            $children = $block['children'] ?? null;
            $nested = $this->isBlockList($children)
                ? "\n".$this->renderBlocks($children, $depth + 1)
                : '';
            $indent = str_repeat('  ', $depth);
            $props = is_array($block['props'] ?? null) ? $block['props'] : [];

            return match ($block['type']) {
                'heading' => str_repeat('#', (int) ($props['level'] ?? 2)).' '.$content,
                'bulletListItem' => $indent.'- '.$content.$nested,
                'numberedListItem' => $indent.'1. '.$content.$nested,
                'checkListItem' => sprintf('%s- [%s] %s%s', $indent, ($props['checked'] ?? false) ? 'x' : ' ', $content, $nested),
                'quote' => $indent.'> '.$content.$nested,
                'codeBlock' => sprintf("```%s\n%s\n```", $props['language'] ?? '', $content),
                default => rtrim($content.$nested),
            };
        }, $blocks);

        $markdown = implode("\n\n", array_filter($rendered));

        return $depth === 0 ? trim($markdown) : rtrim($markdown);
    }

    private function renderInlineContent(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (! is_array($content)) {
            return '';
        }

        return implode('', array_map(function (mixed $item): string {
            if (! is_array($item)) {
                return '';
            }

            if (($item['type'] ?? null) === 'link') {
                return sprintf('[%s](%s)', $this->renderInlineContent($item['content'] ?? null), $item['href'] ?? '');
            }

            $base = is_string($item['text'] ?? null)
                ? $item['text']
                : $this->renderInlineContent($item['content'] ?? null);

            if ($base === '') {
                return '';
            }

            $formatted = preg_replace('/([*_`~\[\]()])/', '\\\\$1', $base) ?? $base;
            $styles = is_array($item['styles'] ?? null) ? $item['styles'] : [];

            if ($styles['code'] ?? false) {
                $formatted = '`'.$formatted.'`';
            }

            if ($styles['bold'] ?? false) {
                $formatted = '**'.$formatted.'**';
            }

            if ($styles['italic'] ?? false) {
                $formatted = '*'.$formatted.'*';
            }

            if ($styles['strike'] ?? false) {
                return '~~'.$formatted.'~~';
            }

            return $formatted;
        }, $content));
    }
};
