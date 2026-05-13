<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\SubscriptionCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['team_id', 'name', 'slug'])]
class SubscriptionCategory extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<SubscriptionCategoryFactory> */
    use HasFactory;

    public static function slugFor(string $name): string
    {
        return Str::slug(trim($name));
    }

    public static function deleteUnused(?int $teamId = null): void
    {
        $query = static::query();

        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        }

        $query
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('subscriptions')
                ->whereColumn('subscriptions.subscription_category_id', 'subscription_categories.id'))
            ->delete();
    }

    /**
     * @return int<0, max>|null
     */
    public static function resolveIdForTeam(?string $categoryName, Team $team): ?int
    {
        if (blank($categoryName)) {
            return null;
        }

        $slug = static::slugFor($categoryName);

        $id = static::query()
            ->firstOrCreate(
                ['team_id' => $team->id, 'slug' => $slug],
                ['name' => trim($categoryName)],
            )->id;

        return $id > 0 ? $id : null;
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return array{id: int, name: string, slug: string}
     */
    public function toPayload(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}
