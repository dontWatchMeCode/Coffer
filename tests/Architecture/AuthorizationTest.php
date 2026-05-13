<?php

use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\LogEntry;
use App\Models\McpToken;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\RecordLink;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

arch('base controller uses AuthorizesRequests')
    ->expect('App\Http\Controllers\Controller')
    ->toUse(AuthorizesRequests::class);

$crudControllers = [
    'App\Http\Controllers\Bookmarks\BookmarkController',
    'App\Http\Controllers\Contacts\ContactController',
    'App\Http\Controllers\Notes\NoteController',
    'App\Http\Controllers\Collections\CollectionController',
    'App\Http\Controllers\Subscriptions\SubscriptionController',
    'App\Http\Controllers\Log\LogEntryController',
    'App\Http\Controllers\Tasks\TaskController',
    'App\Http\Controllers\Calendar\CalendarEventController',
    'App\Http\Controllers\Tasks\ProjectController',
    'App\Http\Controllers\Tasks\TaskCommentController',
    'App\Http\Controllers\ApiTokens\ApiTokenController',
];

it('crud controllers call authorize', function (string $controller) {
    $ref = new ReflectionClass($controller);
    $source = file_get_contents($ref->getFileName());

    expect(str_contains($source, 'authorize('))->toBeTrue("{$controller} must call authorize()");
})->with($crudControllers);

it('registers policies for all models with policies', function (string $model) {
    $policyClass = str_replace('App\Models', 'App\Policies', $model).'Policy';

    expect(class_exists($policyClass))->toBeTrue("Policy [{$policyClass}] for model [{$model}] does not exist.");
})->with([
    Bookmark::class,
    CalendarEvent::class,
    Contact::class,
    LogEntry::class,
    McpToken::class,
    Note::class,
    Project::class,
    RecordCollection::class,
    RecordLink::class,
    Subscription::class,
    Tag::class,
    Task::class,
    TaskComment::class,
    Team::class,
]);
