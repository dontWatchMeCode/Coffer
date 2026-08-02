<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\RecordTypeRegistry;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

class RecordToolSchemas
{
    /**
     * @param  list<string>|null  $types
     * @return array<string, mixed>
     */
    public static function pair(JsonSchema $schema, ?array $types = null): array
    {
        return [
            'source_type' => self::type($schema, $types)->required(),
            'source_id' => $schema->integer()->required(),
            'target_type' => self::type($schema, $types)->required(),
            'target_id' => $schema->integer()->required(),
        ];
    }

    /**
     * @param  list<string>|null  $types
     * @return array<string, mixed>
     */
    public static function tags(JsonSchema $schema, ?array $types = null): array
    {
        return [
            'type' => self::type($schema, $types)->required(),
            'id' => $schema->integer()->required(),
            'tags' => $schema->array()->items($schema->string())->description('Tag names to add or remove.')->required(),
        ];
    }

    /**
     * @param  list<string>|null  $types
     * @return array<string, mixed>
     */
    public static function typeAndId(JsonSchema $schema, ?array $types = null): array
    {
        return [
            'type' => self::type($schema, $types)->required(),
            'id' => $schema->integer()->required(),
        ];
    }

    /**
     * @param  list<string>|null  $types
     */
    public static function type(JsonSchema $schema, ?array $types = null): Type
    {
        return $schema->string()->enum($types ?? RecordTypeRegistry::mcpTypes());
    }
}
