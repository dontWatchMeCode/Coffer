<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

trait EscapesLikeWildcards
{
    /**
     * @var array<string, literal-string>
     */
    private const LIKE_SQL = [
        'additional_info' => '"additional_info" like ? escape ?',
        'address' => '"address" like ? escape ?',
        'body' => '"body" like ? escape ?',
        'category' => '"category" like ? escape ?',
        'description' => '"description" like ? escape ?',
        'email_addresses' => '"email_addresses" like ? escape ?',
        'links' => '"links" like ? escape ?',
        'name' => '"name" like ? escape ?',
        'original_name' => '"original_name" like ? escape ?',
        'phone_numbers' => '"phone_numbers" like ? escape ?',
        'slug' => '"slug" like ? escape ?',
        'title' => '"title" like ? escape ?',
        'url' => '"url" like ? escape ?',
    ];

    protected function likePattern(string $value): string
    {
        return sprintf('%%%s%%', addcslashes($value, '%_\\'));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function whereLikeEscaped(Builder $query, string $column, string $like, string $boolean = 'and'): Builder
    {
        return $query->whereRaw($this->whereLikeEscapedSql($column), [$like, '\\'], $boolean);
    }

    /**
     * @return literal-string
     */
    private function whereLikeEscapedSql(string $column): string
    {
        if (! array_key_exists($column, self::LIKE_SQL)) {
            throw new InvalidArgumentException(sprintf('Unsupported LIKE column [%s].', $column));
        }

        return self::LIKE_SQL[$column];
    }
}
