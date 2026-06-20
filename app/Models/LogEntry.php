<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\Filterable;
use Database\Factories\LogEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['team_id', 'body', 'category'])]
class LogEntry extends Model
{
    use BelongsToTeam;
    use Filterable;

    /** @use HasFactory<LogEntryFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('log_entries')
            ->logOnly(['body', 'category'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
