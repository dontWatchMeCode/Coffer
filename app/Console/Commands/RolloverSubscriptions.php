<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Advance next_billing_date for active subscriptions that have passed their billing date.')]
#[Signature('subscriptions:rollover')]
class RolloverSubscriptions extends Command
{
    public function handle(): int
    {
        $count = 0;
        $cutoff = now()->startOfDay();

        Subscription::query()
            ->withoutGlobalScope('current_team')
            ->where('is_active', true)
            ->whereNotNull('next_billing_date')
            ->where('next_billing_date', '<=', $cutoff)
            ->chunkById(500, function ($subscriptions) use (&$count): void {
                foreach ($subscriptions as $subscription) {
                    $subscription->withoutEvents(function () use ($subscription): void {
                        $nextDate = $subscription->getAttribute('next_billing_date');

                        $subscription->update([
                            'next_billing_date' => $nextDate instanceof CarbonImmutable
                                ? $this->advanceDate($nextDate, $subscription->billing_cycle)->toDateString()
                                : null,
                        ]);
                    });

                    $count++;
                }
            });

        if ($count === 0) {
            $this->info('No subscriptions ready for rollover.');
        } else {
            $this->info(sprintf('Rolled over %d subscription(s).', $count));
        }

        return self::SUCCESS;
    }

    private function advanceDate(CarbonImmutable $date, ?string $cycle): CarbonImmutable
    {
        return match ($cycle) {
            'weekly' => $date->addWeek(),
            'yearly' => $date->addYear(),
            default => $date->addMonth(),
        };
    }
}
