<?php

use App\Enums\TaskStatus;
use App\Mcp\Servers\RecordsServer;
use App\Mcp\Tools\CreateRecordTool;
use App\Mcp\Tools\DeleteRecordTool;
use App\Mcp\Tools\GetRecordTool;
use App\Mcp\Tools\SearchRecordsTool;
use App\Mcp\Tools\UpdateRecordTool;
use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\LogEntry;
use App\Models\McpToken;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    app()->forgetInstance(McpToken::class);
});

afterEach(function () {
    app()->forgetInstance(McpToken::class);
});

it('creates a subscription through the service', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'subscription',
        'data' => [
            'name' => 'Netflix',
            'price' => 15.99,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'category' => 'Entertainment',
        ],
    ])->assertOk()->assertSee('Netflix');

    $subscription = Subscription::query()->whereBelongsTo($team)->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->name)->toBe('Netflix')
        ->and((float) $subscription->price)->toBe(15.99)
        ->and($subscription->currency)->toBe('USD')
        ->and($subscription->billing_cycle)->toBe('monthly')
        ->and($subscription->category)->toBe('Entertainment');
});

it('updates a subscription through the service', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $subscription = Subscription::factory()->create(['team_id' => $team->id, 'name' => 'Old Name']);

    RecordsServer::actingAs($user)->tool(UpdateRecordTool::class, [
        'type' => 'subscription',
        'id' => $subscription->id,
        'data' => ['name' => 'New Name', 'price' => 9.99],
    ])->assertOk()->assertSee('New Name');

    expect($subscription->fresh()->name)->toBe('New Name')
        ->and((float) $subscription->fresh()->price)->toBe(9.99);
});

it('deletes a subscription through the service', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $subscription = Subscription::factory()->create(['team_id' => $team->id]);

    RecordsServer::actingAs($user)->tool(DeleteRecordTool::class, [
        'type' => 'subscription',
        'id' => $subscription->id,
    ])->assertOk()->assertSee('deleted');

    $this->assertSoftDeleted('subscriptions', ['id' => $subscription->id]);
});

it('rejects creating a subscription with invalid billing cycle', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'subscription',
        'data' => ['name' => 'Test', 'billing_cycle' => 'daily'],
    ])->assertHasErrors();
});

it('rejects creating a subscription with negative price', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'subscription',
        'data' => ['name' => 'Test', 'price' => -10],
    ])->assertHasErrors();
});

it('rejects creating a subscription when first billing date is after next billing date', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'subscription',
        'data' => [
            'name' => 'Test',
            'first_billing_date' => '2026-06-15',
            'next_billing_date' => '2026-01-15',
        ],
    ])->assertHasErrors();
});

it('accepts creating a subscription when first billing date is before next billing date', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'subscription',
        'data' => [
            'name' => 'Test',
            'first_billing_date' => '2026-01-15',
            'next_billing_date' => '2026-06-15',
        ],
    ])->assertOk();
});

it('rejects updating a subscription when first billing date is after next billing date', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $subscription = Subscription::factory()->create(['team_id' => $team->id]);

    RecordsServer::actingAs($user)->tool(UpdateRecordTool::class, [
        'type' => 'subscription',
        'id' => $subscription->id,
        'data' => [
            'first_billing_date' => '2026-06-15',
            'next_billing_date' => '2026-01-15',
        ],
    ])->assertHasErrors();
});

it('rejects creating a bookmark without a url', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'bookmark',
        'data' => ['title' => 'Missing URL'],
    ])->assertHasErrors();
});

it('rejects creating a bookmark with an invalid url', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'bookmark',
        'data' => ['title' => 'Bad URL', 'url' => 'not-valid'],
    ])->assertHasErrors();
});

it('rejects creating a calendar event without a date', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'calendar_event',
        'data' => ['title' => 'No Date'],
    ])->assertHasErrors();
});

it('rejects creating a contact without a name', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'contact',
        'data' => ['email_addresses' => [['value' => 'test@example.com']]],
    ])->assertHasErrors();
});

it('rejects creating a task without a project', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'task',
        'data' => ['title' => 'No Project', 'status' => TaskStatus::Planned->value],
    ])->assertHasErrors();
});

it('rejects creating a log entry without a body', function () {
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'log_entry',
        'data' => ['category' => 'general'],
    ])->assertHasErrors();
});

it('scopes record retrieval to the current team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $foreignNote = Note::factory()->create(['team_id' => $otherUser->currentTeam->id, 'title' => 'Foreign Note']);

    RecordsServer::actingAs($user)->tool(GetRecordTool::class, [
        'type' => 'note',
        'id' => $foreignNote->id,
    ])->assertHasErrors(['Record not found.']);
});

it('scopes record search to the current team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Note::factory()->create(['team_id' => $otherUser->currentTeam->id, 'title' => 'Foreign Search Note']);

    RecordsServer::actingAs($user)->tool(SearchRecordsTool::class, [
        'query' => 'Foreign Search',
        'type' => 'note',
    ])->assertOk()->assertDontSee('Foreign Search Note');
});

it('scopes record deletion to the current team', function () {
    $otherUser = User::factory()->create();
    $foreignNote = Note::factory()->create(['team_id' => $otherUser->currentTeam->id]);

    $user = User::factory()->create();
    $this->actingAs($user);

    RecordsServer::actingAs($user)->tool(DeleteRecordTool::class, [
        'type' => 'note',
        'id' => $foreignNote->id,
    ])->assertHasErrors(['Record not found.']);

    expect(DB::table('notes')->where('id', $foreignNote->id)->exists())->toBeTrue();
});

it('scopes record update to the current team', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $foreignNote = Note::factory()->create(['team_id' => $otherUser->currentTeam->id, 'title' => 'Original']);

    RecordsServer::actingAs($user)->tool(UpdateRecordTool::class, [
        'type' => 'note',
        'id' => $foreignNote->id,
        'data' => ['title' => 'Hijacked'],
    ])->assertHasErrors(['Record not found.']);

    expect($foreignNote->fresh()->title)->toBe('Original');
});

it('searches across all record types by default', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    Note::factory()->create(['team_id' => $team->id, 'title' => 'Shared Keyword Note']);
    Bookmark::factory()->create(['team_id' => $team->id, 'title' => 'Shared Keyword Bookmark']);

    RecordsServer::actingAs($user)->tool(SearchRecordsTool::class, [
        'query' => 'Shared Keyword',
    ])->assertOk()->assertSee('Shared Keyword Note')->assertSee('Shared Keyword Bookmark');
});

it('searches within a specific record type', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    Note::factory()->create(['team_id' => $team->id, 'title' => 'Type Filter Note']);
    Bookmark::factory()->create(['team_id' => $team->id, 'title' => 'Type Filter Bookmark']);

    RecordsServer::actingAs($user)->tool(SearchRecordsTool::class, [
        'query' => 'Type Filter',
        'type' => 'note',
    ])->assertOk()->assertSee('Type Filter Note')->assertDontSee('Type Filter Bookmark');
});

it('respects search limit', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    foreach (range(1, 5) as $i) {
        Note::factory()->create(['team_id' => $team->id, 'title' => "Limit Test Note {$i}"]);
    }

    RecordsServer::actingAs($user)->tool(SearchRecordsTool::class, [
        'query' => 'Limit Test',
        'type' => 'note',
        'limit' => 2,
    ])->assertOk()->assertSee('Limit Test Note 1')->assertSee('Limit Test Note 2')->assertDontSee('Limit Test Note 3');
});

it('creates all 8 record types through the service', function (string $type, array $data, string $class) {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    if ($type === 'task') {
        $data['project_id'] = $project->id;
    }

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => $type,
        'data' => $data,
    ])->assertOk();

    expect($class::query()->whereBelongsTo($team)->count())->toBe(1);
})->with([
    'task' => ['task', ['title' => 'Full CRUD task', 'status' => TaskStatus::Planned->value], Task::class],
    'calendar_event' => ['calendar_event', ['title' => 'Full CRUD event', 'date' => '2026-06-01'], CalendarEvent::class],
    'contact' => ['contact', ['name' => 'Full CRUD contact'], Contact::class],
    'bookmark' => ['bookmark', ['title' => 'Full CRUD bookmark', 'url' => 'https://example.com'], Bookmark::class],
    'subscription' => ['subscription', ['name' => 'Full CRUD sub'], Subscription::class],
    'note' => ['note', ['title' => 'Full CRUD note'], Note::class],
    'collection' => ['collection', ['title' => 'Full CRUD collection'], RecordCollection::class],
    'log_entry' => ['log_entry', ['body' => 'Full CRUD log entry'], LogEntry::class],
]);

it('deletes all 8 record types through the service', function (string $type, string $class) {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    $model = match ($type) {
        'task' => Task::factory()->create(['team_id' => $team->id, 'project_id' => $project->id]),
        'calendar_event' => CalendarEvent::factory()->create(['team_id' => $team->id]),
        'contact' => Contact::factory()->create(['team_id' => $team->id]),
        'bookmark' => Bookmark::factory()->create(['team_id' => $team->id]),
        'subscription' => Subscription::factory()->create(['team_id' => $team->id]),
        'note' => Note::factory()->create(['team_id' => $team->id]),
        'collection' => RecordCollection::factory()->create(['team_id' => $team->id]),
        'log_entry' => LogEntry::factory()->create(['team_id' => $team->id]),
    };

    RecordsServer::actingAs($user)->tool(DeleteRecordTool::class, [
        'type' => $type,
        'id' => $model->id,
    ])->assertOk()->assertSee('deleted');

    $this->assertSoftDeleted($model->getTable(), ['id' => $model->id]);
})->with([
    'task' => ['task', Task::class],
    'calendar_event' => ['calendar_event', CalendarEvent::class],
    'contact' => ['contact', Contact::class],
    'bookmark' => ['bookmark', Bookmark::class],
    'subscription' => ['subscription', Subscription::class],
    'note' => ['note', Note::class],
    'collection' => ['collection', RecordCollection::class],
    'log_entry' => ['log_entry', LogEntry::class],
]);
