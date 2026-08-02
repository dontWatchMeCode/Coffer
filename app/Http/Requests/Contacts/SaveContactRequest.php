<?php

declare(strict_types=1);

namespace App\Http\Requests\Contacts;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Validation\DomainRecordValidation;
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
        return $this->isTeamMember();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rulesFor('contact', $this->isMethod('patch'));
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
