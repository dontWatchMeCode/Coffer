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
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Resource as McpResource;
use Laravel\Mcp\Server\Tool;

#[Name('Records Server')]
#[Version('1.0.0')]
#[Instructions('CRUD current-team tasks, calendar events, contacts, bookmarks, notes, and collections. Supports generic linked records and tags.')]
class RecordsServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    #[\Override]
    protected array $tools = [
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
