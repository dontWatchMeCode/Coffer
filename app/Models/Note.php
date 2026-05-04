<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string, mixed>|null $drawing_data
 */
#[Fillable(['team_id', 'title', 'body', 'format', 'drawing_data'])]
class Note extends Model implements LinkableRecord
{
    use BelongsToTeam;

    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'drawing_data' => 'array',
        ];
    }
}
