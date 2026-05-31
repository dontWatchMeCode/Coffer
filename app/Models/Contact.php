<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property list<array{label: string|null, value: string}>|null $phone_numbers
 * @property list<array{label: string|null, value: string}>|null $email_addresses
 * @property list<array{label: string|null, value: string}>|null $links
 */
#[Fillable(['team_id', 'name', 'phone_numbers', 'email_addresses', 'links', 'address', 'additional_info'])]
class Contact extends Model implements LinkableRecord
{
    use BelongsToTeam;

    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use LogsActivity;
    use Searchable;
    use SoftDeletes;

    /**
     * @return array<string, mixed>
     */
    #[SearchUsingFullText(['name', 'address', 'additional_info'])]
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'team_id' => (int) $this->team_id,
            'name' => $this->name,
            'address' => $this->address,
            'additional_info' => $this->additional_info,
            'phone_numbers' => $this->searchableJson($this->phone_numbers),
            'email_addresses' => $this->searchableJson($this->email_addresses),
            'links' => $this->searchableJson($this->links),
        ];
    }

    /**
     * @param  list<array{label: string|null, value: string}>|null  $values
     */
    private function searchableJson(?array $values): string
    {
        return collect($values ?? [])->pluck('value')->filter()->implode(' ');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contacts')
            ->logOnly(['name', 'phone_numbers', 'email_addresses', 'links', 'address', 'additional_info'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'phone_numbers' => 'array',
            'email_addresses' => 'array',
            'links' => 'array',
        ];
    }
}
