<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use Illuminate\Foundation\Http\FormRequest;

class DeleteFileRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        return $this->isTeamMember();
    }
}
