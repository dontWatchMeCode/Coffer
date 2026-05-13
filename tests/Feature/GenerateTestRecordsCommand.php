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

use function Pest\Laravel\artisan;

test('generates all record types including subscriptions and log entries', function () {
    artisan('app:generate-test-records')->assertSuccessful();

    expect(Task::withoutGlobalScopes()->count())->toBe(1000);
    expect(CalendarEvent::withoutGlobalScopes()->count())->toBe(1000);
    expect(Contact::withoutGlobalScopes()->count())->toBe(1000);
    expect(Bookmark::withoutGlobalScopes()->count())->toBe(1000);
    expect(Note::withoutGlobalScopes()->count())->toBe(1000);
    expect(RecordCollection::withoutGlobalScopes()->count())->toBe(1000);
    expect(Subscription::withoutGlobalScopes()->count())->toBe(1000);
    expect(LogEntry::withoutGlobalScopes()->count())->toBe(1000);
});

test('creates tags and attaches them to records', function () {
    artisan('app:generate-test-records')->assertSuccessful();

    expect(Tag::count())->toBeGreaterThan(0);

    $taggedCount = 0;
    foreach ([Task::class, Bookmark::class, Contact::class, Note::class] as $type) {
        $taggedCount += $type::withoutGlobalScopes()->has('recordTags')->count();
    }

    expect($taggedCount)->toBeGreaterThan(0);
});

test('every record has at least one linked record', function () {
    artisan('app:generate-test-records')->assertSuccessful();

    $types = [
        Bookmark::class,
        CalendarEvent::class,
        Contact::class,
        Note::class,
        Project::class,
        RecordCollection::class,
        Subscription::class,
        Task::class,
    ];

    foreach ($types as $type) {
        $recordIds = $type::withoutGlobalScopes()->pluck('id')->all();

        $linkedIds = RecordLink::query()
            ->where(function ($q) use ($type): void {
                $q->where('left_type', $type)
                    ->orWhere('right_type', $type);
            })
            ->get()
            ->flatMap(function (RecordLink $link) use ($type): array {
                if ($link->left_type === $type) {
                    return [(int) $link->left_id];
                }

                return [(int) $link->right_id];
            })
            ->unique()
            ->flip()
            ->all();

        foreach ($recordIds as $id) {
            expect(isset($linkedIds[$id]))->toBeTrue("Record {$type}:{$id} has no linked records");
        }
    }
});

test('creates test user with expected credentials and active team', function () {
    artisan('app:generate-test-records')->assertSuccessful();

    $user = User::where('email', 'test@test.test')->first();

    expect($user)->not->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->current_team_id)->not->toBeNull();
    expect($user->currentTeam)->not->toBeNull();
});

test('creates users with active teams and memberships', function () {
    artisan('app:generate-test-records')->assertSuccessful();

    $users = User::where('email', '!=', 'test@test.test')->get();

    expect($users)->toHaveCount(50);
    expect(Project::withoutGlobalScopes()->count())->toBe(50);

    foreach ($users as $user) {
        expect($user->current_team_id)->not->toBeNull();
    }

    $nonPersonalTeams = Team::where('is_personal', false)->count();
    expect($nonPersonalTeams)->toBe(10);
});

test('tasks have a random user as creator', function () {
    artisan('app:generate-test-records')->assertSuccessful();

    $creators = Task::withoutGlobalScopes()
        ->distinct()
        ->pluck('created_by')
        ->filter()
        ->unique()
        ->count();

    expect($creators)->toBeGreaterThan(1);
});

test('can be run idempotently without errors', function () {
    artisan('app:generate-test-records')->assertSuccessful();
    artisan('app:generate-test-records')->assertSuccessful();

    expect(Task::withoutGlobalScopes()->count())->toBe(1000);
    expect(User::where('email', 'test@test.test')->count())->toBe(1);
});
