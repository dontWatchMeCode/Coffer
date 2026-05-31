<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\Filterable;
use App\Concerns\HasRecordLinks;
use App\Concerns\HasRecordTags;
use App\Contracts\LinkableRecord;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['team_id', 'name', 'price', 'currency', 'billing_cycle', 'next_billing_date', 'url', 'description', 'notes', 'is_active'])]
class Subscription extends Model implements LinkableRecord
{
    use BelongsToTeam;
    use Filterable;

    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    use HasRecordLinks;
    use HasRecordTags;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('subscriptions')
            ->logOnly(['name', 'price', 'currency', 'billing_cycle', 'next_billing_date', 'url', 'description', 'notes', 'is_active', 'subscription_category_id'])
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

    protected static function booted(): void
    {
        static::deleted(function (Subscription $subscription): void {
            if (! $subscription->isForceDeleting()) {
                return;
            }

            if ($subscription->subscription_category_id) {
                SubscriptionCategory::deleteUnused($subscription->team_id);
            }
        });
    }

    /**
     * @return BelongsTo<SubscriptionCategory, $this>
     */
    public function subscriptionCategory(): BelongsTo
    {
        return $this->belongsTo(SubscriptionCategory::class);
    }

    /**
     * @return Attribute<?string, null>
     */
    protected function category(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                $category = $this->subscriptionCategory;

                return $category !== null ? $category->name : $value;
            },
        );
    }
}
