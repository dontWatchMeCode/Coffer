<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Models\Task;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class DeleteTaskRequest extends FormRequest
{
    use AuthorizesTeamResource;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->isTeamMember()) {
            return false;
        }

        $taskId = $this->route('task');
        $team = $this->currentTeam();

        return filled($taskId) && $team instanceof Team && Task::query()
            ->whereBelongsTo($team)
            ->whereKey($taskId)
            ->exists();
    }
}
