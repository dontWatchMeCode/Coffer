<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Str;

trait ParsesSearchPrefixes
{
    /**
     * Parse a search query for a single-letter type prefix.
     *
     * @param  array<string, string>  $prefixMap
     * @return array{0: string, 1: list<string>, 2: string|null}
     */
    protected function parseSearchPrefix(string $query, array $prefixMap): array
    {
        if ($query === '') {
            return ['', [], null];
        }

        $prefix = null;

        if (strlen($query) >= 2 && strpos($query, ':') === 1 && ctype_alpha($query[0])) {
            $key = strtolower($query[0]);

            if (isset($prefixMap[$key])) {
                $prefix = $prefixMap[$key];
                $query = ltrim(substr($query, 2));
            }
        }

        $tagSlug = null;
        $textWords = [];

        foreach (explode(' ', $query) as $word) {
            if ($word !== '' && str_starts_with($word, '#') && strlen($word) > 1) {
                $slug = Str::slug(substr($word, 1));

                if ($slug !== '') {
                    $tagSlug ??= $slug;

                    continue;
                }
            }

            if ($word !== '') {
                $textWords[] = $word;
            }
        }

        $query = implode(' ', $textWords);

        return [$query, $prefix !== null ? [$prefix] : array_values($prefixMap), $tagSlug];
    }
}
