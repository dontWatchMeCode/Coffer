<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscriptions;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use Illuminate\Foundation\Http\FormRequest;

class SaveSubscriptionRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        return $this->isTeamMember();
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
            'price' => $sometimes
                ? ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999.99']
                : ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => $sometimes
                ? ['sometimes', 'nullable', 'string', 'max:3']
                : ['nullable', 'string', 'max:3'],
            'billing_cycle' => $sometimes
                ? ['sometimes', 'nullable', 'string', 'in:weekly,monthly,yearly']
                : ['nullable', 'string', 'in:weekly,monthly,yearly'],
            'next_billing_date' => $sometimes
                ? ['sometimes', 'nullable', 'date']
                : ['nullable', 'date'],
            'url' => $sometimes
                ? ['sometimes', 'nullable', 'string', 'url', 'max:2048']
                : ['nullable', 'string', 'url', 'max:2048'],
            'description' => $sometimes
                ? ['sometimes', 'nullable', 'string', 'max:500']
                : ['nullable', 'string', 'max:500'],
            'notes' => $sometimes
                ? ['sometimes', 'nullable', 'string']
                : ['nullable', 'string'],
            'is_active' => $sometimes
                ? ['sometimes', 'boolean']
                : ['nullable', 'boolean'],
            'category' => $sometimes
                ? ['sometimes', 'nullable', 'string', 'max:100']
                : ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);
        }
    }
}
