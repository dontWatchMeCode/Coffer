<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\Filterable;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use Database\Factories\FileItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['team_id', 'title', 'description', 'disk', 'path', 'original_name', 'mime_type', 'size', 'width', 'height'])]
class FileItem extends Model implements LinkableRecord
{
    use BelongsToTeam;
    use Filterable;

    /** @use HasFactory<FileItemFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use LogsActivity;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (self $fileItem): void {
            if (! $fileItem->isForceDeleting()) {
                return;
            }

            Storage::disk($fileItem->disk)->delete($fileItem->path);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('files')
            ->logOnly(['title', 'description', 'original_name', 'mime_type', 'size', 'width', 'height'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }
}
