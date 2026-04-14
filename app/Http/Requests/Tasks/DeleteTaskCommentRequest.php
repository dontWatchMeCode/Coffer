<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class DeleteTaskCommentRequest extends FormRequest
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
        $taskId = $this->route('task');

        return filled($commentId) && filled($taskId) && $this->isCommentOwner((int) $commentId, (int) $taskId);
    }
}
