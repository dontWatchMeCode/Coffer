<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\Filterable;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use Database\Factories\SpreadsheetWorkbookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property array<string, mixed> $snapshot
 */
#[Fillable(['team_id', 'title', 'snapshot'])]
class SpreadsheetWorkbook extends Model implements LinkableRecord
{
    use BelongsToTeam;
    use Filterable;

    /** @use HasFactory<SpreadsheetWorkbookFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('spreadsheets')
            ->logOnly(['title'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return array{version: int, columns: list<array{id: string, name: string, type: string, width: int, hidden: bool, options: list<string>}>, rows: list<array{id: string, cells: array<string, mixed>}>}
     */
    public static function defaultSnapshot(): array
    {
        $nameId = str()->uuid()->toString();
        $statusId = str()->uuid()->toString();
        $completeId = str()->uuid()->toString();

        return [
            'version' => 1,
            'columns' => [
                ['id' => $nameId, 'name' => 'Name', 'type' => 'text', 'width' => 240, 'hidden' => false, 'options' => []],
                ['id' => $statusId, 'name' => 'Status', 'type' => 'select', 'width' => 160, 'hidden' => false, 'options' => ['Not started', 'In progress', 'Done']],
                ['id' => $completeId, 'name' => 'Complete', 'type' => 'checkbox', 'width' => 110, 'hidden' => false, 'options' => []],
            ],
            'rows' => [
                ['id' => str()->uuid()->toString(), 'cells' => []],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }
}
