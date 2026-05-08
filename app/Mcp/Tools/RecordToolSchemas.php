<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\McpRecordResolver;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

class RecordToolSchemas
{
    /**
     * @return array<string, mixed>
     */
    public static function pair(JsonSchema $schema): array
    {
        return [
            'source_type' => self::type($schema)->required(),
            'source_id' => $schema->integer()->required(),
            'target_type' => self::type($schema)->required(),
            'target_id' => $schema->integer()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function tags(JsonSchema $schema): array
    {
        return [
            'type' => self::type($schema)->required(),
            'id' => $schema->integer()->required(),
            'tags' => $schema->array()->items($schema->string())->description('Tag names to add or remove.')->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function typeAndId(JsonSchema $schema): array
    {
        return [
            'type' => self::type($schema)->required(),
            'id' => $schema->integer()->required(),
        ];
    }

    public static function type(JsonSchema $schema): Type
    {
        return $schema->string()->enum(McpRecordResolver::RECORD_TYPES);
    }
}
