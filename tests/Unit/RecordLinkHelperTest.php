<?php

use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\RteBlock;
use App\Models\Task;
use App\Services\RecordLinkHelper;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

uses(TestCase::class);

it('extracts titles from models', function (string $modelClass, string $attribute, string $value) {
    $model = new $modelClass;
    $model->setAttribute($attribute, $value);

    expect(RecordLinkHelper::titleForModel($model))->toBe($value);
})->with([
    [Task::class, 'title', 'Task Title'],
    [Project::class, 'name', 'Project Name'],
    [CalendarEvent::class, 'title', 'Event Title'],
    [Contact::class, 'name', 'Contact Name'],
    [Bookmark::class, 'title', 'Bookmark Title'],
    [Note::class, 'title', 'Note Title'],
    [RecordCollection::class, 'title', 'Collection Title'],
]);

it('falls back to key when title attribute is missing', function (string $modelClass) {
    $model = new $modelClass;
    $model->id = 42;

    expect(RecordLinkHelper::titleForModel($model))->toBe('42');
})->with([
    Task::class,
    Project::class,
    CalendarEvent::class,
    Contact::class,
    Bookmark::class,
    Note::class,
    RecordCollection::class,
]);

it('extracts previews from models', function (string $modelClass, string $attribute, mixed $value, ?string $expected) {
    $model = new $modelClass;
    $model->setAttribute($attribute, $value);

    expect(RecordLinkHelper::previewForModel($model))->toBe($expected);
})->with([
    [Task::class, 'description', 'Task desc', 'Task desc'],
    [Project::class, 'description', 'Project desc', 'Project desc'],
    [CalendarEvent::class, 'description', 'Event desc', 'Event desc'],
    [Contact::class, 'additional_info', 'Extra info', 'Extra info'],
    [Bookmark::class, 'description', 'Bookmark desc', 'Bookmark desc'],
    [RecordCollection::class, 'description', 'Collection desc', 'Collection desc'],
    [Task::class, 'description', null, null],
]);

it('extracts note preview from text blocks', function () {
    $note = new Note;
    $block = new RteBlock([
        'type' => 'text',
        'position' => 0,
        'payload' => ['content' => '<p>Note body</p>'],
    ]);
    $block->id = 1;
    $note->setRelation('blocks', new Collection([$block]));

    expect(RecordLinkHelper::previewForModel($note))->toBe('Note body');
});

it('strips tags and limits note preview from blocks', function () {
    $note = new Note;
    $block = new RteBlock([
        'type' => 'text',
        'position' => 0,
        'payload' => ['content' => '<p>'.str_repeat('a', 200).'</p>'],
    ]);
    $block->id = 1;
    $note->setRelation('blocks', new Collection([$block]));

    $preview = RecordLinkHelper::previewForModel($note);

    expect($preview)
        ->not->toContain('<')
        ->toHaveLength(183);
});
