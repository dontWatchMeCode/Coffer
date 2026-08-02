<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class SaveCalendarEventRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rulesFor('calendar_event', $this->isMethod('patch'), requiredWhenPresent: [
            'title' => false,
            'date' => false,
        ]);
    }
}
