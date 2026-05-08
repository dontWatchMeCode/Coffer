<?php

use App\Enums\TaskStatus;
use App\Mcp\Servers\RecordsServer;
use App\Mcp\Tools\AddRecordTagsTool;
use App\Mcp\Tools\CreateRecordTool;
use App\Mcp\Tools\DeleteRecordTool;
use App\Mcp\Tools\GetRecordTool;
use App\Mcp\Tools\GetRelatedRecordsTool;
use App\Mcp\Tools\LinkRecordsTool;
use App\Mcp\Tools\ListRecordTagsTool;
use App\Mcp\Tools\RecordsSchemaTool;
use App\Mcp\Tools\RemoveRecordTagsTool;
use App\Mcp\Tools\SearchRecordsTool;
use App\Mcp\Tools\UnlinkRecordsTool;
use App\Mcp\Tools\UpdateRecordTool;
use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\McpToken;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\RecordLink;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    app()->forgetInstance(McpToken::class);
});

afterEach(function () {
    app()->forgetInstance(McpToken::class);
});

test('the records mcp server describes its supported schema', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(RecordsSchemaTool::class)
        ->assertOk()
        ->assertSee('calendar_event')
        ->assertSee('relationships')
        ->assertSee('tags');
});

test('records can be created for each supported type through mcp', function (string $type, string $expected, string $class) {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    $data = match ($type) {
        'task' => ['project_id' => $project->id, 'title' => $expected, 'status' => TaskStatus::Planned->value],
        'calendar_event' => ['title' => $expected, 'date' => '2026-05-08'],
        'contact' => ['name' => $expected],
        'bookmark' => ['title' => $expected, 'url' => 'https://example.com/'.$type],
        'note' => ['title' => $expected, 'body' => 'MCP body'],
        'collection' => ['title' => $expected, 'description' => 'MCP collection'],
    };

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => $type,
        'data' => $data,
    ])->assertOk()->assertSee($expected);

    expect($class::query()->whereBelongsTo($team)->count())->toBe(1);
})->with([
    ['task', 'MCP task', Task::class],
    ['calendar_event', 'MCP event', CalendarEvent::class],
    ['contact', 'MCP contact', Contact::class],
    ['bookmark', 'MCP bookmark', Bookmark::class],
    ['note', 'MCP note', Note::class],
    ['collection', 'MCP collection', RecordCollection::class],
]);

test('records can be searched read updated and deleted through mcp', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'note',
        'data' => ['title' => 'Alpha MCP Note', 'body' => 'Original body'],
    ])->assertOk()->assertSee('Alpha MCP Note');

    $note = Note::query()->where('title', 'Alpha MCP Note')->firstOrFail();

    RecordsServer::actingAs($user)->tool(SearchRecordsTool::class, [
        'query' => 'Alpha MCP',
        'type' => 'note',
    ])->assertOk()->assertSee('Alpha MCP Note');

    RecordsServer::actingAs($user)->tool(GetRecordTool::class, [
        'type' => 'note',
        'id' => $note->id,
    ])->assertOk()->assertSee('Original body');

    RecordsServer::actingAs($user)->tool(UpdateRecordTool::class, [
        'type' => 'note',
        'id' => $note->id,
        'data' => ['title' => 'Updated MCP Note'],
    ])->assertOk()->assertSee('Updated MCP Note');

    RecordsServer::actingAs($user)->tool(DeleteRecordTool::class, [
        'type' => 'note',
        'id' => $note->id,
    ])->assertOk()->assertSee('deleted');

    expect(Note::query()->whereKey($note->id)->exists())->toBeFalse();
});

test('linked records and tags can be managed through mcp', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id, 'title' => 'Linked note']);
    $contact = Contact::factory()->create(['team_id' => $team->id, 'name' => 'Linked contact']);

    RecordsServer::actingAs($user)->tool(AddRecordTagsTool::class, [
        'type' => 'note',
        'id' => $note->id,
        'tags' => ['Research', 'Urgent'],
    ])->assertOk()->assertSee('Research')->assertSee('Urgent');

    RecordsServer::actingAs($user)->tool(ListRecordTagsTool::class, [
        'type' => 'note',
        'id' => $note->id,
    ])->assertOk()->assertSee('Research');

    RecordsServer::actingAs($user)->tool(LinkRecordsTool::class, [
        'source_type' => 'note',
        'source_id' => $note->id,
        'target_type' => 'contact',
        'target_id' => $contact->id,
    ])->assertOk()->assertSee('Linked contact');

    RecordsServer::actingAs($user)->tool(GetRelatedRecordsTool::class, [
        'type' => 'note',
        'id' => $note->id,
    ])->assertOk()->assertSee('Linked contact');

    RecordsServer::actingAs($user)->tool(RemoveRecordTagsTool::class, [
        'type' => 'note',
        'id' => $note->id,
        'tags' => ['Research'],
    ])->assertOk();

    RecordsServer::actingAs($user)->tool(UnlinkRecordsTool::class, [
        'source_type' => 'note',
        'source_id' => $note->id,
        'target_type' => 'contact',
        'target_id' => $contact->id,
    ])->assertOk()->assertSee('unlinked');

    expect(RecordLink::query()->count())->toBe(0);
    expect($note->fresh()->recordTags()->pluck('slug')->all())->toBe(['urgent']);
    expect(Tag::query()->where('slug', 'research')->exists())->toBeFalse();
});

test('mcp record tools are scoped to the authenticated users current team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $foreignNote = Note::factory()->create(['team_id' => $otherUser->currentTeam->id]);

    RecordsServer::actingAs($user)->tool(GetRecordTool::class, [
        'type' => 'note',
        'id' => $foreignNote->id,
    ])->assertHasErrors(['Record not found.']);
});

test('self links are rejected through mcp', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['team_id' => $user->currentTeam->id]);

    RecordsServer::actingAs($user)->tool(LinkRecordsTool::class, [
        'source_type' => 'note',
        'source_id' => $note->id,
        'target_type' => 'note',
        'target_id' => $note->id,
    ])->assertHasErrors(['Cannot link a record to itself.']);
});

test('duplicate links are rejected through mcp', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);
    $contact = Contact::factory()->create(['team_id' => $team->id]);

    RecordsServer::actingAs($user)->tool(LinkRecordsTool::class, [
        'source_type' => 'note',
        'source_id' => $note->id,
        'target_type' => 'contact',
        'target_id' => $contact->id,
    ])->assertOk();

    RecordsServer::actingAs($user)->tool(LinkRecordsTool::class, [
        'source_type' => 'contact',
        'source_id' => $contact->id,
        'target_type' => 'note',
        'target_id' => $note->id,
    ])->assertHasErrors(['Link already exists.']);
});

test('the web mcp route is registered', function () {
    expect(route('mcp.records'))->toContain('/mcp/records');
});

test('mcp token read permissions allow reads and deny writes', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id, 'title' => 'Readable MCP Note']);

    $token = McpToken::factory()->create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'abilities' => [
            'collections' => 'none',
            'notes' => 'read',
            'bookmarks' => 'none',
            'contacts' => 'none',
            'calendar' => 'none',
            'tasks' => 'none',
            'task_projects' => ['mode' => 'all', 'ids' => []],
        ],
    ])->load('team');

    app()->instance(McpToken::class, $token);

    RecordsServer::actingAs($user)->tool(SearchRecordsTool::class, [
        'query' => 'Readable MCP',
    ])->assertOk()->assertSee('Readable MCP Note');

    RecordsServer::actingAs($user)->tool(GetRecordTool::class, [
        'type' => 'note',
        'id' => $note->id,
    ])->assertOk()->assertSee('Readable MCP Note');

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'note',
        'data' => ['title' => 'Denied', 'body' => 'Denied'],
    ])->assertHasErrors(['Permission denied.']);
});

test('mcp token none permissions hide and block record types', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    Bookmark::factory()->create(['team_id' => $team->id, 'title' => 'Hidden Bookmark']);

    $token = McpToken::factory()->create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'abilities' => [
            'collections' => 'none',
            'notes' => 'read',
            'bookmarks' => 'none',
            'contacts' => 'none',
            'calendar' => 'none',
            'tasks' => 'none',
            'task_projects' => ['mode' => 'all', 'ids' => []],
        ],
    ])->load('team');

    app()->instance(McpToken::class, $token);

    RecordsServer::actingAs($user)->tool(RecordsSchemaTool::class)
        ->assertOk()
        ->assertSee('note')
        ->assertHasNoErrors();

    RecordsServer::actingAs($user)->tool(SearchRecordsTool::class, [
        'query' => 'Hidden',
        'type' => 'bookmark',
    ])->assertHasErrors(['Permission denied.']);
});

test('mcp task project scope is enforced', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $allowedProject = Project::factory()->create(['team_id' => $team->id]);
    $blockedProject = Project::factory()->create(['team_id' => $team->id]);
    $blockedTask = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $blockedProject->id,
        'title' => 'Blocked Scoped Task',
    ]);

    $token = McpToken::factory()->create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'abilities' => [
            'collections' => 'none',
            'notes' => 'none',
            'bookmarks' => 'none',
            'contacts' => 'none',
            'calendar' => 'none',
            'tasks' => 'write',
            'task_projects' => ['mode' => 'only', 'ids' => [$allowedProject->id]],
        ],
    ])->load('team');

    app()->instance(McpToken::class, $token);

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'task',
        'data' => ['project_id' => $blockedProject->id, 'title' => 'Denied task', 'status' => TaskStatus::Planned->value],
    ])->assertHasErrors(['Permission denied.']);

    RecordsServer::actingAs($user)->tool(GetRecordTool::class, [
        'type' => 'task',
        'id' => $blockedTask->id,
    ])->assertHasErrors(['Permission denied.']);

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'task',
        'data' => ['project_id' => $allowedProject->id, 'title' => 'Allowed task', 'status' => TaskStatus::Planned->value],
    ])->assertOk()->assertSee('Allowed task');
});
