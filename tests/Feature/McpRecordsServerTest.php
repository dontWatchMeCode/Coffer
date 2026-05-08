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
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\RecordLink;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;

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
