<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Context;
use Laravel\Scout\Builder as ScoutBuilder;

class ScoutRecordSearch
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  class-string<TModel>  $modelClass
     * @return Builder<TModel>
     */
    public static function constrain(Builder $query, string $modelClass, Team $team, string $search, bool $onlyTrashed = false): Builder
    {
        $ids = self::keys($modelClass, $team, $search, $onlyTrashed);

        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereKey($ids);
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return list<int>
     */
    public static function keys(string $modelClass, Team $team, string $search, bool $onlyTrashed = false): array
    {
        /** @var ScoutBuilder<Model> $builder */
        $builder = app(ScoutBuilder::class, [
            'model' => new $modelClass,
            'query' => $search,
            'callback' => null,
            'softDelete' => in_array(SoftDeletes::class, class_uses_recursive($modelClass), true) && config('scout.soft_delete', false),
        ])->where('team_id', (int) $team->id);

        if ($onlyTrashed) {
            $builder->onlyTrashed();
        }

        return Context::scope(
            fn (): array => $builder->keys()
                ->map(fn (mixed $id): int => (int) $id)
                ->values()
                ->all(),
            data: ['current_team_id' => (int) $team->id],
        );
    }
}
