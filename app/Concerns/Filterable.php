<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    use EscapesLikeWildcards;

    /**
     * Apply a search query filter across the given columns.
     *
     * @param  Builder<self>  $query
     * @param  list<string>  $columns
     */
    public function scopeSearch(Builder $query, ?string $search, array $columns): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $like = $this->likePattern($search);

        $query->where(function (Builder $query) use ($columns, $like): void {
            foreach ($columns as $index => $column) {
                $index === 0
                    ? $this->whereLikeEscaped($query, $column, $like)
                    : $this->whereLikeEscaped($query, $column, $like, 'or');
            }
        });
    }
}
