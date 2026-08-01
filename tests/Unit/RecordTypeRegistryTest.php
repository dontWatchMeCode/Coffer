<?php

use App\Models\Project;
use App\Models\SpreadsheetWorkbook;
use App\Models\Task;
use App\Services\RecordTypeRegistry;

it('provides one definition for shared record metadata', function () {
    expect(RecordTypeRegistry::classFor('task'))->toBe(Task::class)
        ->and(RecordTypeRegistry::typeForClass(Task::class))->toBe('task')
        ->and(RecordTypeRegistry::definition('task'))
        ->toMatchArray([
            'prefix' => 't',
            'global' => 'tasks',
            'class' => Task::class,
            'mcp_resource' => 'tasks',
        ]);
});

it('derives MCP types from record definitions', function () {
    expect(RecordTypeRegistry::mcpTypes())
        ->toBe([
            'task',
            'calendar_event',
            'contact',
            'bookmark',
            'subscription',
            'note',
            'collection',
            'log_entry',
            'file',
        ])
        ->and(RecordTypeRegistry::mcpResourceFor('task'))->toBe('tasks')
        ->and(RecordTypeRegistry::mcpResourceFor('project'))->toBeNull()
        ->and(RecordTypeRegistry::mcpResourceFor('spreadsheet'))->toBeNull()
        ->and(RecordTypeRegistry::classFor('project'))->toBe(Project::class)
        ->and(RecordTypeRegistry::classFor('spreadsheet'))->toBe(SpreadsheetWorkbook::class);
});
