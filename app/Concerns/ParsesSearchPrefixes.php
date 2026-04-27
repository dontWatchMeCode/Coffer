<?php

declare(strict_types=1);

namespace App\Concerns;

trait ParsesSearchPrefixes
{
    /**
     * Parse a search query for a single-letter type prefix.
     *
     * @param  array<string, string>  $prefixMap
     * @return array{0: string, 1: list<string>}
     */
    protected function parseSearchPrefix(string $query, array $prefixMap): array
    {
        if ($query === '') {
            return ['', []];
        }

        $prefix = null;

        if (preg_match('/^([a-z]):\s*(.*)$/i', $query, $matches)) {
            $key = strtolower($matches[1]);

            if (isset($prefixMap[$key])) {
                $prefix = $prefixMap[$key];
                $query = $matches[2];
            }
        }

        return [$query, $prefix !== null ? [$prefix] : array_values($prefixMap)];
    }
}
