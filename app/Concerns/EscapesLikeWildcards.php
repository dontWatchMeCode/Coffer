<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait EscapesLikeWildcards
{
    protected function likePattern(string $value): string
    {
        return sprintf('%%%s%%', addcslashes($value, '%_\\'));
    }

    protected function whereLikeEscaped(Builder $query, string $column, string $like, string $boolean = 'and'): Builder
    {
        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);

        return $query->whereRaw($wrappedColumn.' like ? escape ?', [$like, '\\'], $boolean);
    }
}
