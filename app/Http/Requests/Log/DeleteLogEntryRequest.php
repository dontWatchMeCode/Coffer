<?php

declare(strict_types=1);

namespace App\Http\Requests\Log;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Models\LogEntry;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class DeleteLogEntryRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        if (! $this->isTeamMember()) {
            return false;
        }

        $entryId = $this->route('logEntry');
        $team = $this->currentTeam();

        return filled($entryId) && $team instanceof Team && LogEntry::query()
            ->whereBelongsTo($team)
            ->whereKey($entryId)
            ->exists();
    }
}
