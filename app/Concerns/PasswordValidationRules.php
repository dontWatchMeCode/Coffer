<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Confirmed;
use Illuminate\Validation\Rules\CurrentPassword;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\StringRule;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return list<Confirmed|Password|StringRule|string>
     */
    protected function passwordRules(): array
    {
        return [Rule::required(), Rule::string(), Password::default(), Rule::confirmed()];
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return list<CurrentPassword|StringRule|string>
     */
    protected function currentPasswordRules(): array
    {
        return [Rule::required(), Rule::string(), Rule::currentPassword()];
    }
}
