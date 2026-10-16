<?php

declare(strict_types=1);

namespace App\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait HasActivityLog
{
    use LogsActivity;

    abstract protected static function activityLogName(): string;

    /** @return list<string> */
    abstract protected static function activityLogAttributes(): array;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(static::activityLogName())
            ->logOnly(static::activityLogAttributes())
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
