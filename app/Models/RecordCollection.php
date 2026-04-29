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

#[Fillable(['team_id', 'title', 'description'])]
class RecordCollection extends Model implements LinkableRecord
{
    use BelongsToTeam;

    /** @use HasFactory<RecordCollectionFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
}
