<?php

use App\Console\Commands\GenerateTestRecords;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    Storage::fake('local');
});

test('generates records with tags, links, team, creator, and subscription coverage', function () {
    artisan('app:generate-test-records', ['--count' => 101])->assertSuccessful();

    expect(Task::withoutGlobalScopes()->count())->toBe(101);
    expect(CalendarEvent::withoutGlobalScopes()->count())->toBe(101);
    expect(Contact::withoutGlobalScopes()->count())->toBe(101);
    expect(Bookmark::withoutGlobalScopes()->count())->toBe(101);
    expect(Note::withoutGlobalScopes()->count())->toBe(101);
    expect(RecordCollection::withoutGlobalScopes()->count())->toBe(101);
    expect(Subscription::withoutGlobalScopes()->count())->toBe(101);
    expect(LogEntry::withoutGlobalScopes()->count())->toBe(101);
    expect(FileItem::withoutGlobalScopes()->count())->toBe(101);
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

    $fileTeams = Team::where('is_personal', false)->orderBy('id')->get();
    $fileCounts = $fileTeams->map(fn (Team $team): int => FileItem::withoutGlobalScopes()->where('team_id', $team->id)->count())->all();

    expect(array_sum($fileCounts))->toBe(101);
    expect($fileCounts[0])->toBe(11);
    expect(array_slice($fileCounts, 1))->each->toBe(10);

    $sampleFiles = FileItem::withoutGlobalScopes()->limit(5)->get();

    expect($sampleFiles)->toHaveCount(5);

    foreach ($sampleFiles as $file) {
        expect(Storage::disk('local')->exists($file->path))->toBeTrue();
        expect($file->size)->toBeGreaterThan(0);
        expect($file->width)->not->toBeNull();
        expect($file->height)->not->toBeNull();
        expect($file->mime_type)->toBeIn(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    $task = Task::withoutGlobalScopes()->firstOrFail();

    expect(DB::table('activity_log')->where('subject_type', $task->getMorphClass())->where('subject_id', $task->getKey())->count())
        ->toBeGreaterThanOrEqual(19);
    expect(DB::table('activity_log')->count())->toBeGreaterThan(0);

    artisan('app:generate-test-records', ['--count' => 101])->assertSuccessful();

    expect(Task::withoutGlobalScopes()->count())->toBe(101);
    expect(CalendarEvent::withoutGlobalScopes()->count())->toBe(101);
    expect(Contact::withoutGlobalScopes()->count())->toBe(101);
    expect(Bookmark::withoutGlobalScopes()->count())->toBe(101);
    expect(Note::withoutGlobalScopes()->count())->toBe(101);
    expect(RecordCollection::withoutGlobalScopes()->count())->toBe(101);
    expect(Subscription::withoutGlobalScopes()->count())->toBe(101);
    expect(LogEntry::withoutGlobalScopes()->count())->toBe(101);
    expect(FileItem::withoutGlobalScopes()->count())->toBe(101);
    expect(Project::withoutGlobalScopes()->count())->toBe(50);
    expect(Tag::count())->toBe(200);
    expect(Team::where('is_personal', false)->count())->toBe(10);
    expect(Team::count())->toBe($teamCount);
    expect(User::count())->toBe(51);
    expect(User::where('email', 'test@test.test')->count())->toBe(1);
});

test('defaults count option to 1000', function () {
    $command = new GenerateTestRecords;

    expect((string) $command->getDefinition()->getOption('count')->getDefault())->toBe('1000');
});

test('generates exact requested count below batch size', function () {
    artisan('app:generate-test-records', ['--count' => 11])->assertSuccessful();

    expect(Task::withoutGlobalScopes()->count())->toBe(11);
    expect(CalendarEvent::withoutGlobalScopes()->count())->toBe(11);
    expect(Contact::withoutGlobalScopes()->count())->toBe(11);
    expect(Bookmark::withoutGlobalScopes()->count())->toBe(11);
    expect(Note::withoutGlobalScopes()->count())->toBe(11);
    expect(RecordCollection::withoutGlobalScopes()->count())->toBe(11);
    expect(Subscription::withoutGlobalScopes()->count())->toBe(11);
    expect(LogEntry::withoutGlobalScopes()->count())->toBe(11);
    expect(FileItem::withoutGlobalScopes()->count())->toBe(11);
    expect(Project::withoutGlobalScopes()->count())->toBe(50);
    expect(Tag::count())->toBe(200);
});

test('rejects invalid count before deleting data', function (string $invalid) {
    Team::factory()->create();
    Storage::disk('local')->put('files/sentinel.txt', 'sentinel');

    $teamCount = Team::count();

    artisan('app:generate-test-records', ['--count' => $invalid])->assertFailed();

    expect(Team::count())->toBe($teamCount);
    expect(User::where('email', 'test@test.test')->count())->toBe(0);
    expect(Storage::disk('local')->exists('files/sentinel.txt'))->toBeTrue();
})->with([
    'zero' => '0',
    'negative' => '-5',
    'non-numeric' => 'abc',
    'fraction' => '1.5',
]);
