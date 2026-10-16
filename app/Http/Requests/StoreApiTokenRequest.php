<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\McpToken;
use App\Services\RecordTypeRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $abilityRules = [];

        foreach (RecordTypeRegistry::mcpResources() as $resource) {
            $abilityRules['abilities.'.$resource] = [Rule::required(), Rule::string(), Rule::in(McpToken::PERMISSION_LEVELS)];
        }

        return [
            'name' => [Rule::required(), Rule::string(), 'max:100'],
            'expires_at' => [Rule::nullable(), Rule::date()->after('today')],
            'abilities' => [Rule::required(), Rule::array()],
            ...$abilityRules,
            'abilities.task_projects' => [Rule::required(), Rule::array()],
            'abilities.task_projects.mode' => [Rule::required(), Rule::string(), Rule::in(['all', 'only'])],
            'abilities.task_projects.ids' => [Rule::array()],
            'abilities.task_projects.ids.*' => ['integer'],
        ];
    }
}
