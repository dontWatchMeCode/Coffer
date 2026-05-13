<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use Illuminate\Foundation\Http\FormRequest;

class DeleteCalendarEventRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        return $this->isTeamMember();
    }
}
