<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\Filterable;
use App\Concerns\HasActivityLog;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use Database\Factories\BookmarkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['team_id', 'title', 'url', 'description', 'notes'])]
class Bookmark extends Model implements LinkableRecord
{
    use BelongsToTeam;
    use Filterable;
    use HasActivityLog;

    /** @use HasFactory<BookmarkFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use SoftDeletes;

    protected static function activityLogName(): string
    {
        return 'bookmarks';
    }

    /** @return list<string> */
    protected static function activityLogAttributes(): array
    {
        return ['title', 'url', 'description', 'notes'];
    }

    protected function casts(): array
    {
        return [];
    }
}
