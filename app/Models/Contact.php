<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\Filterable;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    use Filterable;

    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use LogsActivity;

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
