<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Contracts\LinkableRecord;
use App\Models\RecordLink;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;

trait ResolvesLinkableRecord
{
    protected function resolveModel(Team $currentTeam, string $type, int|string $id): ?Model
    {
        $class = RecordLink::linkableMap()[$type] ?? null;

        if ($class === null) {
            return null;
        }

        $model = $class::query()->whereBelongsTo($currentTeam)->find((int) $id);

        return $model instanceof LinkableRecord ? $model : null;
    }
}
