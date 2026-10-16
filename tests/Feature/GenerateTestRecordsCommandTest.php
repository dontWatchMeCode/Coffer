<?php

use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\FileItem;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    Storage::fake('local');
});

test('generates records with tags, links, team, creator, and subscription coverage', function () {
    artisan('app:generate-test-records')->assertSuccessful();

    expect(Task::withoutGlobalScopes()->count())->toBe(1000);
    expect(CalendarEvent::withoutGlobalScopes()->count())->toBe(1000);
    expect(Contact::withoutGlobalScopes()->count())->toBe(1000);
    expect(Bookmark::withoutGlobalScopes()->count())->toBe(1000);
    expect(Note::withoutGlobalScopes()->count())->toBe(1000);
    expect(RecordCollection::withoutGlobalScopes()->count())->toBe(1000);
    expect(Subscription::withoutGlobalScopes()->count())->toBe(1000);
    expect(LogEntry::withoutGlobalScopes()->count())->toBe(1000);
    expect(FileItem::withoutGlobalScopes()->count())->toBe(1000);
    expect(Project::withoutGlobalScopes()->count())->toBe(50);

    expect(Tag::count())->toBe(200);

    $taggedCount = 0;
    foreach ([Task::class, Bookmark::class, Contact::class, Note::class] as $type) {
        $taggedCount += $type::withoutGlobalScopes()->has('recordTags')->count();
    }

    expect($taggedCount)->toBeGreaterThan(0);

    $types = [
        Bookmark::class,
        CalendarEvent::class,
        Contact::class,
        FileItem::class,
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
                $ids = [];

                if ($link->left_type === $type) {
                    $ids[] = (int) $link->left_id;
                }

                if ($link->right_type === $type) {
                    $ids[] = (int) $link->right_id;
                }

                return $ids;
            })
            ->unique()
            ->all();

        $missing = array_values(array_diff($recordIds, $linkedIds));

        expect($missing, 'Records '.$type.' without links: '.implode(',', array_slice($missing, 0, 20)))->toBeEmpty();
    }

    $user = User::where('email', 'test@test.test')->first();

    expect($user)->not->toBeNull();
    expect(Hash::check('test@test.test', $user->password))->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->current_team_id)->not->toBeNull();
    expect($user->currentTeam)->not->toBeNull();
    expect($user->belongsToTeam($user->currentTeam))->toBeTrue();
    expect($user->teams()->count())->toBeGreaterThan(0);

    $users = User::where('email', '!=', 'test@test.test')->get();

    expect($users)->toHaveCount(50);

    foreach ($users as $generatedUser) {
        expect($generatedUser->current_team_id)->not->toBeNull();
        expect($generatedUser->currentTeam)->not->toBeNull();
        expect($generatedUser->belongsToTeam($generatedUser->currentTeam))->toBeTrue();
    }

    expect(Team::where('is_personal', false)->count())->toBe(10);

    $teamCount = Team::count();

    $creators = Task::withoutGlobalScopes()
        ->distinct()
        ->pluck('created_by')
        ->filter()
        ->unique()
        ->count();

    expect($creators)->toBeGreaterThan(1);

    $subscriptions = Subscription::withoutGlobalScopes()->get(['is_active', 'first_billing_date']);

    $firstBillingMonths = $subscriptions
        ->pluck('first_billing_date')
        ->filter()
        ->map(fn ($date): string => $date->format('Y-m'))
        ->unique();

    expect($subscriptions->where('is_active', false)->count())->toBeGreaterThan(0);
    expect($subscriptions->where('is_active', true)->count())->toBeGreaterThan(0);
    expect($firstBillingMonths->count())->toBeGreaterThan(1);

    artisan('app:generate-test-records')->assertSuccessful();

    expect(Task::withoutGlobalScopes()->count())->toBe(1000);
    expect(CalendarEvent::withoutGlobalScopes()->count())->toBe(1000);
    expect(Contact::withoutGlobalScopes()->count())->toBe(1000);
    expect(Bookmark::withoutGlobalScopes()->count())->toBe(1000);
    expect(Note::withoutGlobalScopes()->count())->toBe(1000);
    expect(RecordCollection::withoutGlobalScopes()->count())->toBe(1000);
    expect(Subscription::withoutGlobalScopes()->count())->toBe(1000);
    expect(LogEntry::withoutGlobalScopes()->count())->toBe(1000);
    expect(FileItem::withoutGlobalScopes()->count())->toBe(1000);
    expect(Project::withoutGlobalScopes()->count())->toBe(50);
    expect(Tag::count())->toBe(200);
    expect(Team::where('is_personal', false)->count())->toBe(10);
    expect(Team::count())->toBe($teamCount);
    expect(User::count())->toBe(51);
    expect(User::where('email', 'test@test.test')->count())->toBe(1);
});
