<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasRecordLinks;
use App\Contracts\LinkableRecord;
use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'title', 'description', 'date'])]
class CalendarEvent extends Model implements LinkableRecord
{
    use BelongsToTeam;

    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    use HasRecordLinks;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
