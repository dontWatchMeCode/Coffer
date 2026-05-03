<?php

declare(strict_types=1);

namespace App\Http\Requests\Contacts;

use App\Http\Requests\Tasks\AuthorizesTeamResource;
use App\Models\Contact;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class SaveContactRequest extends FormRequest
{
    use AuthorizesTeamResource;

    protected function prepareForValidation(): void
    {
        $entries = [];

        foreach (['phone_numbers', 'email_addresses', 'links'] as $field) {
            if ($this->has($field)) {
                $entries[$field] = $this->filledEntries($field);
            }
        }

        $this->merge($entries);
    }

    public function authorize(): bool
    {
        if (! $this->isTeamMember()) {
            return false;
        }

        if (! $this->isMethod('patch')) {
            return true;
        }

        $contactId = $this->route('contact');
        $team = $this->currentTeam();

        return filled($contactId) && $team instanceof Team && Contact::query()
            ->whereBelongsTo($team)
            ->whereKey($contactId)
            ->exists();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $sometimes = $this->isMethod('patch');

        return [
            'name' => $sometimes
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'phone_numbers' => $sometimes
                ? ['sometimes', 'nullable', 'array', 'max:20']
                : ['nullable', 'array', 'max:20'],
            'phone_numbers.*.label' => ['nullable', 'string', 'max:100'],
            'phone_numbers.*.value' => ['required', 'string', 'max:255'],
            'email_addresses' => $sometimes
                ? ['sometimes', 'nullable', 'array', 'max:20']
                : ['nullable', 'array', 'max:20'],
            'email_addresses.*.label' => ['nullable', 'string', 'max:100'],
            'email_addresses.*.value' => ['required', 'email', 'max:255'],
            'links' => $sometimes
                ? ['sometimes', 'nullable', 'array', 'max:20']
                : ['nullable', 'array', 'max:20'],
            'links.*.label' => ['nullable', 'string', 'max:100'],
            'links.*.value' => ['required', 'url', 'max:2048'],
            'address' => $sometimes
                ? ['sometimes', 'nullable', 'string']
                : ['nullable', 'string'],
            'additional_info' => $sometimes
                ? ['sometimes', 'nullable', 'string']
                : ['nullable', 'string'],
        ];
    }

    /**
     * @return list<array{label: string|null, value: non-empty-string}>
     */
    private function filledEntries(string $field): array
    {
        $result = [];

        foreach ((array) $this->input($field, []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $value = trim((string) ($entry['value'] ?? ''));

            if ($value === '') {
                continue;
            }

            $label = isset($entry['label']) && trim((string) $entry['label']) !== ''
                ? trim((string) $entry['label'])
                : null;

            $result[] = ['label' => $label, 'value' => $value];
        }

        return $result;
    }
}
