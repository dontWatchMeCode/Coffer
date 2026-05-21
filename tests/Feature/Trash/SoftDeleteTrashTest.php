<?php

use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\LogEntry;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\RecordLink;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;

test('records move to module trash and can be restored', function (string $type): void {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $record = trashRecord($type, $team);
    $config = trashConfig($type, $record, $team);

    actingAs($user)
        ->delete($config['destroy'])
        ->assertRedirect();

    assertSoftDeleted($record->getTable(), ['id' => $record->getKey()]);

    actingAs($user)
        ->get($config['index'])
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where($config['prop'].'.data', fn ($items): bool => collect($items)->contains('id', $record->getKey()) === false));

    actingAs($user)
        ->get($config['trash'])
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where($config['prop'].'.data', fn ($items): bool => collect($items)->contains('id', $record->getKey())));

    actingAs($user)
        ->patch($config['restore'])
        ->assertRedirect($config['trash']);

    expect($record->newQuery()->whereKey($record->getKey())->exists())->toBeTrue();
})->with('trash record types');

test('trashed records can be permanently deleted', function (string $type): void {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $record = trashRecord($type, $team);
    $config = trashConfig($type, $record, $team);

    $record->delete();

    actingAs($user)
        ->delete($config['forceDestroy'])
        ->assertRedirect($config['trash']);

    assertDatabaseMissing($record->getTable(), ['id' => $record->getKey()]);
})->with('trash record types');

test('soft deleting a linkable record preserves links and tags until permanent delete', function (): void {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $task = trashRecord('task', $team);
    $contact = trashRecord('contact', $team);
    $tag = Tag::factory()->create(['team_id' => $team->id]);

    RecordLink::create([
        'team_id' => $team->id,
        'left_type' => Task::class,
        'left_id' => $task->id,
        'right_type' => Contact::class,
        'right_id' => $contact->id,
    ]);

    $task->recordTags()->attach($tag->id);
    $task->delete();

    assertDatabaseHas('record_links', ['left_id' => $task->id]);
    assertDatabaseHas('taggables', ['taggable_id' => $task->id, 'taggable_type' => Task::class]);

    actingAs($user)->delete(trashConfig('task', $task, $team)['forceDestroy']);

    assertDatabaseMissing('record_links', ['left_id' => $task->id]);
    assertDatabaseMissing('taggables', ['taggable_id' => $task->id, 'taggable_type' => Task::class]);
});

test('soft deleting a note preserves blocks until permanent delete', function (): void {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $note = Note::factory()->create(['team_id' => $team->id]);

    $note->blocks()->create([
        'type' => 'text',
        'position' => 0,
        'payload' => ['content' => 'Keep me'],
    ]);

    $note->delete();

    assertDatabaseHas('rte_blocks', ['blockable_id' => $note->id, 'blockable_type' => Note::class]);

    actingAs($user)->delete(trashConfig('note', $note, $team)['forceDestroy']);

    assertDatabaseMissing('rte_blocks', ['blockable_id' => $note->id, 'blockable_type' => Note::class]);
});

dataset('trash record types', [
    'calendar' => ['calendar'],
    'contact' => ['contact'],
    'bookmark' => ['bookmark'],
    'subscription' => ['subscription'],
    'note' => ['note'],
    'log' => ['log'],
    'collection' => ['collection'],
    'task' => ['task'],
]);

function trashRecord(string $type, Team $team): Model
{
    return match ($type) {
        'calendar' => CalendarEvent::factory()->create(['team_id' => $team->id, 'title' => 'Trash Calendar Event']),
        'contact' => Contact::factory()->create(['team_id' => $team->id, 'name' => 'Trash Contact']),
        'bookmark' => Bookmark::factory()->create(['team_id' => $team->id, 'title' => 'Trash Bookmark']),
        'subscription' => Subscription::factory()->create(['team_id' => $team->id, 'name' => 'Trash Subscription']),
        'note' => Note::factory()->create(['team_id' => $team->id, 'title' => 'Trash Note']),
        'log' => LogEntry::factory()->create(['team_id' => $team->id, 'body' => 'Trash Log Entry']),
        'collection' => RecordCollection::factory()->create(['team_id' => $team->id, 'title' => 'Trash Collection']),
        'task' => Task::factory()->create([
            'team_id' => $team->id,
            'project_id' => Project::factory()->create(['team_id' => $team->id])->id,
            'title' => 'Trash Task',
        ]),
    };
}

/**
 * @return array{index: string, trash: string, destroy: string, restore: string, forceDestroy: string, afterDestroy: string, prop: string}
 */
function trashConfig(string $type, Model $record, Team $team): array
{
    return match ($type) {
        'calendar' => [
            'index' => route('team.calendar.index', ['current_team' => $team]),
            'trash' => route('team.calendar.trash', ['current_team' => $team]),
            'destroy' => route('team.calendar.events.destroy', ['current_team' => $team, 'event' => $record]),
            'restore' => route('team.calendar.events.restore', ['current_team' => $team, 'event' => $record->id]),
            'forceDestroy' => route('team.calendar.events.force-destroy', ['current_team' => $team, 'event' => $record->id]),
            'afterDestroy' => route('team.calendar.index', ['current_team' => $team]),
            'prop' => 'events',
        ],
        'contact' => moduleTrashConfig('contacts', 'contact', 'contacts', $record, $team),
        'bookmark' => moduleTrashConfig('bookmarks', 'bookmark', 'bookmarks', $record, $team),
        'subscription' => moduleTrashConfig('subscriptions', 'subscription', 'subscriptions', $record, $team),
        'note' => moduleTrashConfig('notes', 'note', 'notes', $record, $team),
        'collection' => moduleTrashConfig('collections', 'collection', 'collections', $record, $team),
        'log' => [
            'index' => route('team.log.index', ['current_team' => $team]),
            'trash' => route('team.log.trash', ['current_team' => $team]),
            'destroy' => route('team.log.destroy', ['current_team' => $team, 'logEntry' => $record]),
            'restore' => route('team.log.restore', ['current_team' => $team, 'logEntry' => $record->id]),
            'forceDestroy' => route('team.log.force-destroy', ['current_team' => $team, 'logEntry' => $record->id]),
            'afterDestroy' => route('team.log.index', ['current_team' => $team]),
            'prop' => 'entries',
        ],
        'task' => [
            'index' => route('team.tasks.show', ['current_team' => $team, 'project' => $record->project_id]),
            'trash' => route('team.tasks.trash', ['current_team' => $team, 'project' => $record->project_id]),
            'destroy' => route('team.tasks.destroy', ['current_team' => $team, 'task' => $record]),
            'restore' => route('team.tasks.restore', ['current_team' => $team, 'task' => $record->id]),
            'forceDestroy' => route('team.tasks.force-destroy', ['current_team' => $team, 'task' => $record->id]),
            'afterDestroy' => route('team.tasks.show', ['current_team' => $team, 'project' => $record->project_id]),
            'prop' => 'tasks',
        ],
    };
}

/**
 * @return array{index: string, trash: string, destroy: string, restore: string, forceDestroy: string, afterDestroy: string, prop: string}
 */
function moduleTrashConfig(string $module, string $parameter, string $prop, Model $record, Team $team): array
{
    return [
        'index' => route("team.{$module}.index", ['current_team' => $team]),
        'trash' => route("team.{$module}.trash", ['current_team' => $team]),
        'destroy' => route("team.{$module}.destroy", ['current_team' => $team, $parameter => $record]),
        'restore' => route("team.{$module}.restore", ['current_team' => $team, $parameter => $record->id]),
        'forceDestroy' => route("team.{$module}.force-destroy", ['current_team' => $team, $parameter => $record->id]),
        'afterDestroy' => route("team.{$module}.index", ['current_team' => $team]),
        'prop' => $prop,
    ];
}
