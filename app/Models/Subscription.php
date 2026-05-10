<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['team_id', 'name', 'price', 'currency', 'billing_cycle', 'next_billing_date', 'url', 'description', 'notes', 'is_active', 'category'])]
class Subscription extends Model implements LinkableRecord
{
    use BelongsToTeam;

    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('subscriptions')
            ->logOnly(['name', 'price', 'currency', 'billing_cycle', 'next_billing_date', 'url', 'description', 'notes', 'is_active', 'category'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'next_billing_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
