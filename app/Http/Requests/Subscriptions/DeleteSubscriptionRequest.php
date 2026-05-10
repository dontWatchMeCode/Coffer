<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscriptions;

use App\Http\Requests\Tasks\AuthorizesTeamResource;
use App\Models\Subscription;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class DeleteSubscriptionRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        if (! $this->isTeamMember()) {
            return false;
        }

        $subscriptionId = $this->route('subscription');
        $team = $this->currentTeam();

        return filled($subscriptionId) && $team instanceof Team && Subscription::query()
            ->whereBelongsTo($team)
            ->whereKey($subscriptionId)
            ->exists();
    }
}
