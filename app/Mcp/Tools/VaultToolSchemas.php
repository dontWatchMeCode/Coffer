<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

class VaultToolSchemas
{
    /**
     * @return array<string, Type>
     */
    public static function publicKeys(JsonSchema $schema): array
    {
        return [
            'device_label' => $schema->string()->description('Human-readable device label.')->required(),
            'public_key' => $schema->string()->description('Base64url X25519 public key (32 bytes).')->required(),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public static function cursor(JsonSchema $schema): array
    {
        return [
            'cursor' => $schema->integer()->description('Opaque sync cursor; start at 0.')->default(0),
            'limit' => $schema->integer()->description('Maximum changes to return, 1-1000.')->default(200),
            'types' => $schema->array()->items($schema->string())->description('Optional record type filter for pulls.'),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public static function push(JsonSchema $schema): array
    {
        return [
            'records' => $schema->array()
                ->items(
                    $schema->object([
                        'type' => $schema->string()->required(),
                        'id' => $schema->integer()->required(),
                        'epoch_id' => $schema->integer()->required(),
                        'version' => $schema->integer()->required(),
                        'envelope' => $schema->object()->description('Opaque overwrite ciphertext envelope.')->required(),
                        'tombstone' => $schema->boolean(),
                        'task_project_id' => $schema->integer(),
                    ]),
                )
                ->description('Ciphertext record envelopes to upsert.')
                ->required(),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public static function putEnvelopes(JsonSchema $schema): array
    {
        return [
            'epoch_id' => $schema->integer()->required(),
            'envelopes' => $schema->array()
                ->items(
                    $schema->object([
                        'public_key' => $schema->string()->required(),
                        'envelope' => $schema->object()->description('Wrapped team key envelope.')->required(),
                    ]),
                )
                ->required(),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public static function filePut(JsonSchema $schema): array
    {
        return [
            'file_id' => $schema->integer()->required(),
            'epoch_id' => $schema->integer()->required(),
            'version' => $schema->integer()->required(),
            'metadata' => $schema->object()->description('Opaque encrypted file metadata envelope.')->required(),
            'content' => $schema->string()->description('Base64-encoded encrypted file ciphertext.')->required(),
            'chunk_count' => $schema->string()->description('Number of ciphertext chunks.')->default('1'),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public static function fileGet(JsonSchema $schema): array
    {
        return [
            'file_id' => $schema->integer()->required(),
        ];
    }
}
