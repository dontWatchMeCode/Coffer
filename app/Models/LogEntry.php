<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\LogEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'body', 'category'])]
class LogEntry extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<LogEntryFactory> */
    use HasFactory;
}
