<?php

use App\Models\Project;
use App\Models\Team;
use App\Services\McpRecordValidator;
use Illuminate\Support\Facades\Validator;

it('returns fields for each record type', function (string $type, array $expectedFields) {
    $fields = McpRecordValidator::fieldsFor($type);

    expect($fields)->toBe($expectedFields);
})->with([
    'task' => ['task', ['project_id', 'assigned_to', 'title', 'description', 'status', 'progress', 'position', 'due_at']],
    'calendar_event' => ['calendar_event', ['title', 'description', 'date', 'time']],
    'contact' => ['contact', ['name', 'phone_numbers', 'email_addresses', 'links', 'address', 'additional_info']],
    'bookmark' => ['bookmark', ['title', 'url', 'description', 'notes']],
    'subscription' => ['subscription', ['name', 'price', 'currency', 'billing_cycle', 'next_billing_date', 'url', 'description', 'notes', 'is_active', 'category']],
    'note' => ['note', ['title', 'body', 'format', 'drawing_data']],
    'collection' => ['collection', ['title', 'description']],
    'log_entry' => ['log_entry', ['body', 'category']],
]);

it('returns empty fields for unknown type', function () {
    expect(McpRecordValidator::fieldsFor('unknown'))->toBe([]);
});

it('returns required fields for each record type', function (string $type, array $expected) {
    expect(McpRecordValidator::requiredFieldsFor($type))->toBe($expected);
})->with([
    'task' => ['task', ['project_id', 'title', 'status']],
    'calendar_event' => ['calendar_event', ['title', 'date']],
    'contact' => ['contact', ['name']],
    'bookmark' => ['bookmark', ['title', 'url']],
    'subscription' => ['subscription', ['name']],
    'note' => ['note', ['title']],
    'collection' => ['collection', ['title']],
    'log_entry' => ['log_entry', ['body']],
]);

it('returns empty required fields for unknown type', function () {
    expect(McpRecordValidator::requiredFieldsFor('unknown'))->toBe([]);
});

it('returns field notes for note type', function () {
    $notes = McpRecordValidator::fieldNotesFor('note');

    expect($notes)->toHaveKey('format')
        ->and($notes)->toHaveKey('body')
        ->and($notes)->toHaveKey('drawing_data')
        ->and($notes['format'])->toContain('text')
        ->and($notes['format'])->toContain('excalidraw');
});

it('returns empty field notes for non-note types', function (string $type) {
    expect(McpRecordValidator::fieldNotesFor($type))->toBe([]);
})->with(['task', 'bookmark', 'contact', 'calendar_event', 'subscription', 'collection', 'log_entry', 'unknown']);

it('returns custom messages for note type', function () {
    $messages = McpRecordValidator::messagesFor('note');

    expect($messages)->toHaveKey('format.in')
        ->and($messages['format.in'])->toContain('text')
        ->and($messages['format.in'])->toContain('excalidraw');
});

it('returns empty messages for non-note types', function (string $type) {
    expect(McpRecordValidator::messagesFor($type))->toBe([]);
})->with(['task', 'bookmark', 'contact', 'calendar_event', 'subscription', 'collection', 'log_entry', 'unknown']);

it('requires mandatory fields on create for task', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('task', false, $team);

    $validator = Validator::make([], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain('project_id', 'title', 'status');
});

it('does not require all fields on update for task', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('task', true, $team);

    $validator = Validator::make(['title' => 'Updated'], $rules);

    expect($validator->fails())->toBeFalse();
});

it('validates bookmark url format on create', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('bookmark', false, $team);

    $validator = Validator::make(['title' => 'Test', 'url' => 'not-a-url'], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('url'))->toBeTrue();
});

it('validates calendar event date format on create', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('calendar_event', false, $team);

    $validator = Validator::make(['title' => 'Event', 'date' => 'not-a-date'], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('date'))->toBeTrue();
});

it('validates calendar event time format on create', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('calendar_event', false, $team);

    $validator = Validator::make(['title' => 'Event', 'date' => '2026-05-08', 'time' => '25:00'], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('time'))->toBeTrue();
});

it('validates contact email format on create', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('contact', false, $team);

    $validator = Validator::make([
        'name' => 'John',
        'email_addresses' => [['value' => 'not-an-email', 'label' => 'work']],
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email_addresses.0.value'))->toBeTrue();
});

it('validates contact link url format on create', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('contact', false, $team);

    $validator = Validator::make([
        'name' => 'John',
        'links' => [['value' => 'not-a-url', 'label' => 'website']],
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('links.0.value'))->toBeTrue();
});

it('validates note format must be text or excalidraw', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('note', false, $team);
    $messages = McpRecordValidator::messagesFor('note');

    $validator = Validator::make([
        'title' => 'Test',
        'format' => 'markdown',
    ], $rules, $messages);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->get('format'))->toContain('The selected format is invalid. Use "text" for Markdown-backed rich text notes or "excalidraw" for drawing notes.');
});

it('validates subscription billing cycle values', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('subscription', false, $team);

    $validator = Validator::make([
        'name' => 'Test Sub',
        'billing_cycle' => 'daily',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('billing_cycle'))->toBeTrue();
});

it('accepts valid subscription billing cycles', function (string $cycle) {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('subscription', false, $team);

    $validator = Validator::make([
        'name' => 'Test Sub',
        'billing_cycle' => $cycle,
    ], $rules);

    expect($validator->fails())->toBeFalse();
})->with(['weekly', 'monthly', 'yearly']);

it('validates subscription price range', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('subscription', false, $team);

    $validator = Validator::make([
        'name' => 'Test Sub',
        'price' => -5,
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('price'))->toBeTrue();
});

it('validates log entry body max length', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('log_entry', false, $team);

    $validator = Validator::make([
        'body' => str_repeat('a', 5001),
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('body'))->toBeTrue();
});

it('returns empty rules for unknown type', function () {
    $team = Team::factory()->make();

    expect(McpRecordValidator::rulesFor('unknown', false, $team))->toBe([]);
});

it('validates task progress is between 0 and 100', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('task', false, $team);

    $validator = Validator::make([
        'project_id' => 1,
        'title' => 'Test',
        'status' => 'planned',
        'progress' => 150,
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('progress'))->toBeTrue();
});

it('validates bookmark url max length', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('bookmark', false, $team);

    $validator = Validator::make([
        'title' => 'Test',
        'url' => 'https://example.com/'.str_repeat('a', 2100),
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('url'))->toBeTrue();
});

it('allows optional fields to be omitted on create for collection', function () {
    $team = Team::factory()->make();
    $rules = McpRecordValidator::rulesFor('collection', false, $team);

    $validator = Validator::make(['title' => 'My Collection'], $rules);

    expect($validator->fails())->toBeFalse();
});

it('accepts valid create data for each record type', function (string $type, array $data) {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);

    if ($type === 'task') {
        $data['project_id'] = $project->id;
    }

    $rules = McpRecordValidator::rulesFor($type, false, $team);

    $validator = Validator::make($data, $rules);

    expect($validator->fails())->toBeFalse();
})->with([
    'task' => ['task', ['title' => 'Task', 'status' => 'planned']],
    'calendar_event' => ['calendar_event', ['title' => 'Event', 'date' => '2026-05-08']],
    'contact' => ['contact', ['name' => 'John Doe']],
    'bookmark' => ['bookmark', ['title' => 'Link', 'url' => 'https://example.com']],
    'subscription' => ['subscription', ['name' => 'Netflix']],
    'note' => ['note', ['title' => 'My Note']],
    'collection' => ['collection', ['title' => 'My Collection']],
    'log_entry' => ['log_entry', ['body' => 'Log entry text']],
]);
