<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TeamRole;
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
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Telescope\Telescope;
use Spatie\Activitylog\Facades\Activity;

#[Signature('app:generate-test-records')]
#[Description('Generate test records for all model types with tags, links, and log entries')]
class GenerateTestRecords extends Command
{
    private const int PER_TYPE = 1000;

    private const int BATCH_SIZE = 100;

    private const int TAGS_PER_TEAM = 20;

    private const int TAGS_PER_RECORD_MIN = 0;

    private const int TAGS_PER_RECORD_MAX = 3;

    /**
     * @var array<int, string>
     */
    private const array RECORD_TYPES = [
        Bookmark::class,
        CalendarEvent::class,
        Contact::class,
        Note::class,
        Project::class,
        RecordCollection::class,
        Subscription::class,
        Task::class,
    ];

    public function handle(): void
    {
        Pulse::ignore(function (): null {
            $recordingWasEnabled = Telescope::isRecording();

            Telescope::stopRecording();
            Activity::disableLogging();

            $result = $this->generateRecords();

            if ($recordingWasEnabled) {
                Telescope::startRecording();
            }

            Activity::enableLogging();

            return $result;
        });
    }

    protected function generateRecords(): null
    {
        $this->info('Clearing existing test data...');

        RecordLink::query()->delete();
        DB::table('taggables')->delete();
        Tag::query()->delete();
        LogEntry::withoutGlobalScopes()->delete();
        Subscription::withoutGlobalScopes()->delete();
        Task::withoutGlobalScopes()->delete();
        CalendarEvent::withoutGlobalScopes()->delete();
        Contact::withoutGlobalScopes()->delete();
        Bookmark::withoutGlobalScopes()->delete();
        Note::withoutGlobalScopes()->delete();
        RecordCollection::withoutGlobalScopes()->delete();
        Project::withoutGlobalScopes()->delete();
        Team::query()->forceDelete();
        User::where('email', '!=', 'test@test.test')->forceDelete();

        $this->info('Creating test user (test@test.test / test@test.test)...');

        $testUser = User::where('email', 'test@test.test')->first();

        if ($testUser === null) {
            $testUser = User::create([
                'name' => 'Test User',
                'email' => 'test@test.test',
                'password' => Hash::make('test@test.test'),
            ]);

            $testUser->forceFill(['email_verified_at' => now()->toDateTimeString()])->save();
        }

        $this->info('Creating shared teams, projects, and users...');

        $teams = Team::factory()->count(10)->create();
        $projects = Project::factory()->count(50)->recycle($teams)->create();
        $users = User::factory()->count(50)->create();

        foreach ($teams as $team) {
            foreach ($users as $user) {
                if (! $user->belongsToTeam($team)) {
                    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
                }
            }

            if (! $testUser->belongsToTeam($team)) {
                $team->members()->attach($testUser, ['role' => TeamRole::Member->value]);
            }
        }

        $testUser->switchTeam($teams->firstOrFail());

        $allUsers = $users->concat([$testUser]);

        $batches = self::PER_TYPE / self::BATCH_SIZE;

        $this->info('Generating 1,000 Tasks...');
        for ($i = 0; $i < $batches; $i++) {
            Task::factory()
                ->count(self::BATCH_SIZE)
                ->recycle($projects)
                ->state(fn (): array => ['created_by' => $allUsers->random()->id])
                ->create();
        }

        $this->info('Generating 1,000 Calendar Events...');
        for ($i = 0; $i < $batches; $i++) {
            CalendarEvent::factory()->count(self::BATCH_SIZE)->recycle($teams)->create();
        }

        $this->info('Generating 1,000 Contacts...');
        for ($i = 0; $i < $batches; $i++) {
            Contact::factory()->count(self::BATCH_SIZE)->recycle($teams)->create();
        }

        $this->info('Generating 1,000 Bookmarks...');
        for ($i = 0; $i < $batches; $i++) {
            Bookmark::factory()->count(self::BATCH_SIZE)->recycle($teams)->create();
        }

        $this->info('Generating 1,000 Notes...');
        for ($i = 0; $i < $batches; $i++) {
            Note::factory()->count(self::BATCH_SIZE)->recycle($teams)->create();
        }

        $this->info('Generating 1,000 Collections...');
        for ($i = 0; $i < $batches; $i++) {
            RecordCollection::factory()->count(self::BATCH_SIZE)->recycle($teams)->create();
        }

        $this->info('Generating 1,000 Subscriptions...');
        for ($i = 0; $i < $batches; $i++) {
            Subscription::factory()->count(self::BATCH_SIZE)->recycle($teams)->create();
        }

        $this->info('Generating 1,000 Log Entries...');
        for ($i = 0; $i < $batches; $i++) {
            LogEntry::factory()->count(self::BATCH_SIZE)->recycle($teams)->create();
        }

        $this->createTags($teams);
        $this->createRecordLinks($teams);

        $this->info('Done! Generated test records with tags, links, and log entries.');

        return null;
    }

    /**
     * @param  Collection<int, Team>  $teams
     */
    protected function createTags($teams): void
    {
        $this->info('Generating tags per team...');

        $tagIndex = 0;

        foreach ($teams as $team) {
            $tags = collect();

            for ($j = 0; $j < self::TAGS_PER_TEAM; $j++) {
                $tags->push(Tag::create([
                    'team_id' => $team->id,
                    'name' => 'Tag '.$tagIndex,
                    'slug' => 'tag-'.$tagIndex,
                ]));

                $tagIndex++;
            }

            $tagIds = $tags->pluck('id')->all();

            foreach (self::RECORD_TYPES as $type) {
                $recordIds = $type::withoutGlobalScopes()
                    ->where('team_id', $team->id)
                    ->pluck('id')
                    ->all();

                $inserts = [];

                foreach ($recordIds as $recordId) {
                    $count = random_int(self::TAGS_PER_RECORD_MIN, self::TAGS_PER_RECORD_MAX);

                    if ($count > 0) {
                        $selected = array_rand(array_flip($tagIds), min($count, count($tagIds)));
                        $selected = is_array($selected) ? $selected : [$selected];

                        foreach ($selected as $tagId) {
                            $inserts[] = [
                                'taggable_type' => $type,
                                'taggable_id' => $recordId,
                                'tag_id' => $tagId,
                                'created_at' => now()->toDateTimeString(),
                                'updated_at' => now()->toDateTimeString(),
                            ];
                        }
                    }
                }

                if ($inserts !== []) {
                    DB::table('taggables')->insert($inserts);
                }
            }
        }
    }

    /**
     * @param  Collection<int, Team>  $teams
     */
    protected function createRecordLinks($teams): void
    {
        $this->info('Generating record links (one per record)...');

        $now = now()->toDateTimeString();

        foreach ($teams as $team) {
            $idsByType = [];

            foreach (self::RECORD_TYPES as $type) {
                $ids = $type::withoutGlobalScopes()
                    ->where('team_id', $team->id)
                    ->pluck('id')
                    ->all();

                if ($ids !== []) {
                    $idsByType[$type] = $ids;
                }
            }

            $typesWithRecords = array_keys($idsByType);

            if (count($typesWithRecords) < 2) {
                continue;
            }

            $inserts = [];

            foreach ($idsByType as $leftType => $leftIds) {
                $otherTypes = array_values(array_filter($typesWithRecords, fn (string $t): bool => $t !== $leftType));

                foreach ($leftIds as $leftId) {
                    $rightType = $otherTypes[array_rand($otherTypes)];
                    $rightIds = $idsByType[$rightType];
                    $rightId = $rightIds[array_rand($rightIds)];

                    $inserts[] = [
                        'team_id' => $team->id,
                        'left_type' => $leftType,
                        'left_id' => $leftId,
                        'right_type' => $rightType,
                        'right_id' => $rightId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($inserts, 500) as $chunk) {
                RecordLink::insert($chunk);
            }
        }
    }
}
