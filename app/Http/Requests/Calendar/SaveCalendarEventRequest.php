<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Http\Requests\Tasks\AuthorizesTeamResource;
use App\Models\CalendarEvent;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class SaveCalendarEventRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        if (! $this->isTeamMember()) {
            return false;
        }

        if (! $this->isMethod('patch')) {
            return true;
        }

        $eventId = $this->route('event');
        $team = $this->currentTeam();

        return filled($eventId) && $team instanceof Team && CalendarEvent::query()
            ->whereBelongsTo($team)
            ->whereKey($eventId)
            ->exists();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => $this->isMethod('patch') ? ['sometimes', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'description' => $this->isMethod('patch') ? ['sometimes', 'nullable', 'string'] : ['nullable', 'string'],
            'date' => $this->isMethod('patch') ? ['sometimes', 'date'] : ['required', 'date'],
        ];
    }
}
