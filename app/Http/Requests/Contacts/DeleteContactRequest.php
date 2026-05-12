<?php

declare(strict_types=1);

namespace App\Http\Requests\Contacts;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Models\Contact;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class DeleteContactRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        if (! $this->isTeamMember()) {
            return false;
        }

        $contactId = $this->route('contact');
        $team = $this->currentTeam();

        return filled($contactId) && $team instanceof Team && Contact::query()
            ->whereBelongsTo($team)
            ->whereKey($contactId)
            ->exists();
    }
}
