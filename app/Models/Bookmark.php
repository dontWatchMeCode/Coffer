<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use Database\Factories\BookmarkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['team_id', 'title', 'url', 'description', 'notes', 'is_archived'])]
class Bookmark extends Model implements LinkableRecord
{
    use BelongsToTeam;

    /** @use HasFactory<BookmarkFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('bookmarks')
            ->logOnly(['title', 'url', 'description', 'notes', 'is_archived'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
        ];
    }
}
