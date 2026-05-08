<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LinkableRecord;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class McpRecordPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function forModel(?Model $model, Team $team, bool $includeRelations = false): array
    {
        if (! $model instanceof Model) {
            return [];
        }

        $type = McpRecordResolver::typeForClass($model::class);
        $payload = [
            'id' => (int) $model->getKey(),
            'type' => $type,
            'title' => RecordLinkHelper::titleForModel($model),
            'preview' => RecordLinkHelper::previewForModel($model),
            'url' => RecordLinkHelper::urlForModel($model, $team),
            'data' => Arr::only($model->toArray(), McpRecordValidator::fieldsFor($type)),
            'created_at' => $model->getAttribute('created_at')?->toISOString(),
            'updated_at' => $model->getAttribute('updated_at')?->toISOString(),
        ];

        if ($includeRelations && $model instanceof LinkableRecord) {
            $payload['tags'] = $model->formattedRecordTags();
            $payload['related'] = $model->formattedLinkedRecords($team);
        }

        return $payload;
    }
}
