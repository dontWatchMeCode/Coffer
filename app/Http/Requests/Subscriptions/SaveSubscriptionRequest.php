<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscriptions;

use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveSubscriptionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rulesFor('subscription', $this->isMethod('patch'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('first_billing_date', 'before:next_billing_date', fn ($input): bool => $input->first_billing_date !== null
            && $input->next_billing_date !== null);
    }
}
