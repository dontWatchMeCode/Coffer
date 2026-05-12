<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveTaskCommentRequest extends FormRequest
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

        $commentId = $this->route('comment');

        if (filled($commentId)) {
            return $this->isCommentOwner((int) $commentId, (int) $this->route('task'));
        }

        $taskId = $this->route('task');
        $team = $this->currentTeam();

        return filled($taskId) && $team instanceof Team && Task::query()
            ->whereBelongsTo($team)
            ->whereKey($taskId)
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
        ];
    }
}
