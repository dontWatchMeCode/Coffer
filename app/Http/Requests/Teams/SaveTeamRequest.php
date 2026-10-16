<?php

declare(strict_types=1);

namespace App\Http\Requests\Teams;

use App\Enums\TeamFeature;
use App\Models\Team;
use App\Rules\TeamName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTeamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [Rule::required(), Rule::string(), 'max:255', new TeamName],
            'default_task_status_options' => [Rule::nullable(), Rule::array()],
            'default_task_status_options.*.value' => [Rule::required(), Rule::string(), Rule::regex('/^[a-z0-9_]+$/'), Rule::distinct()],
            'default_task_status_options.*.label' => [Rule::required(), Rule::string(), 'max:40'],
            'feature_settings' => [Rule::nullable(), Rule::array()],
            ...collect(TeamFeature::values())
                ->mapWithKeys(fn (string $feature): array => ['feature_settings.'.$feature => [Rule::required(), Rule::boolean()]])
                ->all(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('default_task_status_options_present') && ! $this->has('default_task_status_options')) {
            $this->merge([
                'default_task_status_options' => [],
            ]);
        }

        $team = $this->route('team');
        $featureSettings = $team instanceof Team ? $team->featureSettings() : TeamFeature::defaults();

        foreach ($this->input('feature_settings', []) as $feature => $enabled) {
            if (array_key_exists((string) $feature, $featureSettings)) {
                $featureSettings[$feature] = filter_var($enabled, FILTER_VALIDATE_BOOL);
            }
        }

        $this->merge(['feature_settings' => $featureSettings]);
    }
}
