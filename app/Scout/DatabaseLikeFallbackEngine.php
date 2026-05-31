<?php

declare(strict_types=1);

namespace App\Scout;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\DatabaseEngine;

class DatabaseLikeFallbackEngine extends DatabaseEngine
{
    /**
     * @param  Builder<Model>  $builder
     * @return array<int|string, string>
     */
    protected function getFullTextColumns(Builder $builder): array
    {
        if ($builder->modelConnectionType() === 'sqlite') {
            return [];
        }

        return parent::getFullTextColumns($builder);
    }
}
