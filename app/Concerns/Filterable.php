<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
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

        $like = sprintf('%%%s%%', addcslashes($search, '%_\\'));

        $query->where(function (Builder $query) use ($columns, $like): void {
            foreach ($columns as $index => $column) {
                $index === 0
                    ? $query->where($column, 'like', $like)
                    : $query->orWhere($column, 'like', $like);
            }
        });
    }
}
