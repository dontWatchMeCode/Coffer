<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddRecordTagsTool;
use App\Mcp\Tools\AddTaskCommentTool;
use App\Mcp\Tools\CreateRecordTool;
use App\Mcp\Tools\DeleteRecordTool;
use App\Mcp\Tools\GetRecordTool;
use App\Mcp\Tools\GetRelatedRecordsTool;
use App\Mcp\Tools\LinkRecordsTool;
use App\Mcp\Tools\ListRecordTagsTool;
use App\Mcp\Tools\ListTaskCommentsTool;
use App\Mcp\Tools\RecordsSchemaTool;
use App\Mcp\Tools\RemoveRecordTagsTool;
use App\Mcp\Tools\SearchRecordsTool;
use App\Mcp\Tools\UnlinkRecordsTool;
use App\Mcp\Tools\UpdateRecordTool;
use App\Mcp\Tools\VaultFilesGetTool;
use App\Mcp\Tools\VaultFilesPutTool;
use App\Mcp\Tools\VaultKeysGetTool;
use App\Mcp\Tools\VaultKeysPutTool;
use App\Mcp\Tools\VaultPullTool;
use App\Mcp\Tools\VaultPushTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Resource as McpResource;
use Laravel\Mcp\Server\Tool;

#[Name('Records Server')]
#[Version('1.0.0')]
#[Instructions('For plaintext teams: CRUD current-team tasks, calendar events, contacts, bookmarks, subscriptions, notes, collections, log entries, and files. For E2EE teams: opaque ciphertext vault transport (vault.push, vault.pull, vault.keys.*, vault.files.*) only.')]
class RecordsServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    #[\Override]
    protected array $tools = [
        // Plaintext-team semantic tools (suppressed for E2EE teams).
        RecordsSchemaTool::class,
        SearchRecordsTool::class,
        GetRecordTool::class,
        CreateRecordTool::class,
        UpdateRecordTool::class,
        DeleteRecordTool::class,
        LinkRecordsTool::class,
        UnlinkRecordsTool::class,
        GetRelatedRecordsTool::class,
        AddRecordTagsTool::class,
        RemoveRecordTagsTool::class,
        ListRecordTagsTool::class,
        ListTaskCommentsTool::class,
        AddTaskCommentTool::class,
        // E2EE-team ciphertext transport (suppressed for plaintext teams).
        VaultKeysGetTool::class,
        VaultKeysPutTool::class,
        VaultPullTool::class,
        VaultPushTool::class,
        VaultFilesGetTool::class,
        VaultFilesPutTool::class,
    ];

    /**
     * @var array<int, class-string<McpResource>>
     */
    #[\Override]
    protected array $resources = [
        //
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    #[\Override]
    protected array $prompts = [
        //
    ];
}
