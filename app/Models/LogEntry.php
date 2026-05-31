<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\LogEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;

#[Fillable(['team_id', 'body', 'category'])]
class LogEntry extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<LogEntryFactory> */
    use HasFactory;

    use Searchable;
    use SoftDeletes;

    /**
     * @return array<string, mixed>
     */
    #[SearchUsingFullText(['body', 'category'])]
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'team_id' => (int) $this->team_id,
            'body' => $this->body,
            'category' => $this->category,
        ];
    }
}
