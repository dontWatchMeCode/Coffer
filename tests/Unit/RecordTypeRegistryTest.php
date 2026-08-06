<?php

use App\Models\Bookmark;
use App\Models\LogEntry;
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
            'linkable' => true,
            'taggable' => true,
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

it('derives ordered MCP permission resources and labels', function () {
    expect(RecordTypeRegistry::mcpResourceLabels())->toBe([
        'tasks' => 'Tasks',
        'calendar' => 'Calendar',
        'contacts' => 'Contacts',
        'bookmarks' => 'Bookmarks',
        'subscriptions' => 'Subscriptions',
        'notes' => 'Notes',
        'collections' => 'Collections',
        'log_entries' => 'Log',
        'files' => 'Files',
    ])->and(RecordTypeRegistry::mcpResources())
        ->toBe(array_keys(RecordTypeRegistry::mcpResourceLabels()));
});

it('reports record capabilities per type', function () {
    expect(RecordTypeRegistry::isLinkable('task'))->toBeTrue()
        ->and(RecordTypeRegistry::isTaggable('task'))->toBeTrue()
        ->and(RecordTypeRegistry::isMcpExposed('task'))->toBeTrue()
        ->and(RecordTypeRegistry::isLinkable('log_entry'))->toBeFalse()
        ->and(RecordTypeRegistry::isTaggable('log_entry'))->toBeFalse()
        ->and(RecordTypeRegistry::isMcpExposed('log_entry'))->toBeTrue()
        ->and(RecordTypeRegistry::isLinkable('unknown_type'))->toBeFalse()
        ->and(RecordTypeRegistry::isTaggable('unknown_type'))->toBeFalse();
});

it('only exposes linkable records in the linkable map and prefix map', function () {
    $linkable = RecordTypeRegistry::linkableMap();

    expect($linkable)->toHaveKey('task')
        ->and($linkable)->toHaveKey('project')
        ->and($linkable)->toHaveKey('spreadsheet')
        ->and($linkable)->not->toHaveKey('log_entry')
        ->and($linkable['task'])->toBe(Task::class)
        ->and($linkable['bookmark'])->toBe(Bookmark::class);

    $prefixes = RecordTypeRegistry::linkablePrefixMap();

    expect($prefixes)->toHaveKey('t')
        ->and($prefixes)->toHaveKey('p')
        ->and($prefixes)->toHaveKey('x')
        ->and($prefixes)->not->toHaveKey('g')
        ->and($prefixes['t'])->toBe('task')
        ->and($prefixes['x'])->toBe('spreadsheet');
});

it('only exposes taggable records in the taggable map', function () {
    $taggable = RecordTypeRegistry::taggableMap();

    expect($taggable)->toHaveKey('task')
        ->and($taggable)->toHaveKey('note')
        ->and($taggable)->not->toHaveKey('log_entry')
        ->and($taggable['task'])->toBe(Task::class);
});

it('narrows MCP link and tag types to capable, exposed records', function () {
    expect(RecordTypeRegistry::mcpLinkableTypes())
        ->not->toContain('log_entry')
        ->toContain('task', 'note', 'file')
        ->and(RecordTypeRegistry::mcpTaggableTypes())
        ->not->toContain('log_entry')
        ->toContain('task', 'note', 'file')
        ->and(RecordTypeRegistry::mcpTypes())->toContain('log_entry');
});

it('resolves the log entry type alias even though it is not linkable', function () {
    expect(RecordTypeRegistry::typeForClass(LogEntry::class))->toBe('log_entry')
        ->and(RecordTypeRegistry::classFor('log_entry'))->toBe(LogEntry::class);
});
