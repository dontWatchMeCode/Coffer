<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use Database\Factories\RecordCollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;

#[Fillable(['team_id', 'title', 'description'])]
class RecordCollection extends Model implements LinkableRecord
{
    use BelongsToTeam;

    /** @use HasFactory<RecordCollectionFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use Searchable;
    use SoftDeletes;

    /**
     * @return array<string, mixed>
     */
    #[SearchUsingFullText(['title', 'description'])]
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'team_id' => (int) $this->team_id,
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}
