<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\Filterable;
use App\Concerns\HasActivityLog;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use App\Services\VideoThumbnail;
use App\Support\FileTypes;
use Database\Factories\FileItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['team_id', 'title', 'description', 'disk', 'path', 'original_name', 'mime_type', 'size', 'width', 'height'])]
class FileItem extends Model implements LinkableRecord
{
    use BelongsToTeam;
    use Filterable;
    use HasActivityLog;

    /** @use HasFactory<FileItemFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (self $fileItem): void {
            if (! $fileItem->isForceDeleting()) {
                return;
            }

            if ($fileItem->disk !== null && $fileItem->path !== null) {
                Storage::disk($fileItem->disk)->delete([$fileItem->path, ...VideoThumbnail::pathsFor($fileItem->path)]);
            }
        });
    }

    protected static function activityLogName(): string
    {
        return 'files';
    }

    /** @return list<string> */
    protected static function activityLogAttributes(): array
    {
        return ['title', 'description', 'original_name', 'mime_type', 'size', 'width', 'height'];
    }

    public function previewKind(): ?string
    {
        return FileTypes::previewKind((string) $this->mime_type);
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
