<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\Rules\StringRule;
use Illuminate\Validation\Rules\Unique;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, list<Email|StringRule|Unique|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return list<StringRule|string>
     */
    protected function nameRules(): array
    {
        return [Rule::required(), Rule::string(), 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return list<Email|StringRule|Unique|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            Rule::required(),
            Rule::string(),
            Rule::email(),
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
