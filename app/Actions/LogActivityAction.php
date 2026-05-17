<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\McpToken;
use App\Services\ActivitySignificance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Actions\LogActivityAction as BaseLogActivityAction;

class LogActivityAction extends BaseLogActivityAction
{
    public function execute(Model $activity, string $description): Model
    {
        $token = app()->bound(McpToken::class) ? app(McpToken::class) : null;

        if ($token instanceof McpToken && $token->name !== '') {
            if ($activity->getAttribute('causer_id') === null) {
                $user = $token->user;

                if ($user !== null) {
                    $activity->setAttribute('causer_type', $user->getMorphClass());
                    $activity->setAttribute('causer_id', $user->getKey());
                }
            }

            $properties = $activity->getAttribute('properties');

            if ($properties instanceof Collection) {
                $activity->setAttribute('properties', $properties->put('mcp_token_name', $token->name));
            }
        }

        return parent::execute($activity, $description);
    }

    protected function save(Model $activity): void
    {
        $attributeChanges = $activity->getAttribute('attribute_changes');
        $properties = $activity->getAttribute('properties');

        $attributeChanges = $attributeChanges instanceof Collection ? $attributeChanges->toArray() : $attributeChanges;
        $properties = $properties instanceof Collection ? $properties->toArray() : $properties;

        if (! ActivitySignificance::isSignificant(
            is_array($attributeChanges) ? $attributeChanges : null,
            is_array($properties) ? $properties : null,
        )) {
            return;
        }

        parent::save($activity);
    }
}
