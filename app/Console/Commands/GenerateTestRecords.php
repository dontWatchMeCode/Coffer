<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TeamRole;
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
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Telescope;

#[Signature('app:generate-test-records {--count=1000 : Records to generate per record type}')]
#[Description('Generate test records for all model types with tags, links, and log entries')]
class GenerateTestRecords extends Command
{
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
        FileItem::class,
        Note::class,
        Project::class,
        RecordCollection::class,
        Subscription::class,
        Task::class,
    ];

    public function handle(): int
    {
        return Pulse::ignore(function (): int {
            Telescope::stopRecording();

            $result = $this->generateRecords();

            if (app()->bound(EntriesRepository::class)) {
                Telescope::startRecording();
            }

            return $result;
        });
    }

    protected function generateRecords(): int
    {
        $count = filter_var(
            is_string($raw = $this->option('count')) ? trim($raw) : $raw,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($count === false) {
            $this->error('Invalid --count value. Provide a positive integer.');

            return self::FAILURE;
        }

        $this->info('Clearing existing test data...');

        RecordLink::query()->delete();
        DB::table('taggables')->delete();
        Tag::query()->delete();
        LogEntry::withoutGlobalScopes()->delete();
        FileItem::withoutGlobalScopes()->forceDelete();
        Storage::disk('local')->deleteDirectory('files');
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

        $batchSizes = $this->batchSizes($count);
        $label = number_format($count);

        $this->info(sprintf('Generating %s Tasks...', $label));
        foreach ($batchSizes as $batchSize) {
            Task::factory()
                ->count($batchSize)
                ->recycle($projects)
                ->state(fn (): array => ['created_by' => $allUsers->random()->id])
                ->create();
        }

        $this->info(sprintf('Generating %s Calendar Events...', $label));
        foreach ($batchSizes as $batchSize) {
            CalendarEvent::factory()->count($batchSize)->recycle($teams)->create();
        }

        $this->info(sprintf('Generating %s Contacts...', $label));
        foreach ($batchSizes as $batchSize) {
            Contact::factory()->count($batchSize)->recycle($teams)->create();
        }

        $this->info(sprintf('Generating %s Bookmarks...', $label));
        foreach ($batchSizes as $batchSize) {
            Bookmark::factory()->count($batchSize)->recycle($teams)->create();
        }

        $this->info(sprintf('Generating %s Notes...', $label));
        foreach ($batchSizes as $batchSize) {
            Note::factory()->count($batchSize)->recycle($teams)->create();
        }

        $this->info(sprintf('Generating %s Collections...', $label));
        foreach ($batchSizes as $batchSize) {
            RecordCollection::factory()->count($batchSize)->recycle($teams)->create();
        }

        $this->info(sprintf('Generating %s Subscriptions...', $label));
        foreach ($batchSizes as $batchSize) {
            Subscription::factory()
                ->count($batchSize)
                ->recycle($teams)
                ->state(fn (): array => self::subscriptionState())
                ->create();
        }

        $this->info(sprintf('Generating %s Log Entries...', $label));
        foreach ($batchSizes as $batchSize) {
            LogEntry::factory()->count($batchSize)->recycle($teams)->create();
        }

        $this->createFiles($teams, $count);

        $this->createTags($teams);
        $this->createRecordLinks($teams);
        $this->createActivityHistory($teams, $allUsers);

        $this->info('Done! Generated test records with tags, links, and log entries.');

        return self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    protected function batchSizes(int $count): array
    {
        $sizes = [];

        for ($remaining = $count; $remaining > 0; $remaining -= self::BATCH_SIZE) {
            $sizes[] = min(self::BATCH_SIZE, $remaining);
        }

        return $sizes;
    }

    /**
     * @return array{is_active: bool, first_billing_date: string|null, next_billing_date: string|null}
     */
    protected static function subscriptionState(): array
    {
        $isActive = fake()->boolean(70);
        $nextBillingDate = CarbonImmutable::instance(fake()->dateTimeBetween(
            $isActive ? 'now' : '-3 months',
            $isActive ? '+1 month' : 'now',
        ));

        return [
            'is_active' => $isActive,
            'first_billing_date' => fake()->boolean(90)
                ? $nextBillingDate->subMonths(random_int(1, 12))->subDays(random_int(0, 27))->toDateString()
                : null,
            'next_billing_date' => fake()->boolean($isActive ? 100 : 70)
                ? $nextBillingDate->toDateString()
                : null,
        ];
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, User>  $allUsers
     */
    protected function createActivityHistory($teams, $allUsers): void
    {
        $this->info('Generating activity history (20 entries per record)...');

        $causerType = $allUsers->first()?->getMorphClass() ?? '';
        $userIds = $allUsers->pluck('id')->all();

        foreach ($teams as $team) {
            foreach (self::RECORD_TYPES as $type) {
                $model = new $type;
                $morphClass = $model->getMorphClass();
                $logName = class_basename($type);

                $recordIds = $type::withoutGlobalScopes()
                    ->where('team_id', $team->id)
                    ->pluck('id')
                    ->all();

                if ($recordIds === []) {
                    continue;
                }

                $inserts = [];
                $baseTime = now()->subMinutes(5);

                foreach ($recordIds as $recordId) {
                    for ($i = 0; $i < 19; $i++) {
                        $causerId = $userIds[array_rand($userIds)];

                        $inserts[] = [
                            'log_name' => $logName,
                            'description' => 'Updated title',
                            'subject_type' => $morphClass,
                            'subject_id' => $recordId,
                            'event' => 'updated',
                            'causer_type' => $causerType,
                            'causer_id' => $causerId,
                            'attribute_changes' => json_encode([
                                'attributes' => ['title' => fake()->sentence(3)],
                                'old' => ['title' => fake()->sentence(3)],
                            ]),
                            'properties' => json_encode([]),
                            'created_at' => $baseTime->copy()->addMinutes($i),
                            'updated_at' => $baseTime->copy()->addMinutes($i),
                        ];
                    }
                }

                foreach (array_chunk($inserts, 500) as $chunk) {
                    DB::table('activity_log')->insert($chunk);
                }
            }
        }
    }

    /**
     * @param  Collection<int, Team>  $teams
     */
    protected function createFiles($teams, int $count): void
    {
        $this->info('Generating '.number_format($count).' Files with placeholder images...');

        $disk = Storage::disk('local');
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $mimeByExtension = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        $teamsCount = $teams->count();
        $perTeam = (int) floor($count / $teamsCount);
        $remainder = $count % $teamsCount;
        $progress = $this->output->createProgressBar($count);
        $progress->start();

        $teamIndex = 0;

        foreach ($teams as $team) {
            $teamDirectory = 'files/'.$team->id;
            $disk->makeDirectory($teamDirectory);

            $countForTeam = $perTeam + ($teamIndex < $remainder ? 1 : 0);
            $batchesForTeam = (int) ceil($countForTeam / self::BATCH_SIZE);
            $createdForTeam = 0;

            for ($i = 0; $i < $batchesForTeam; $i++) {
                $now = now()->toDateTimeString();

                $inserts = [];

                $batchCount = min(self::BATCH_SIZE, $countForTeam - $createdForTeam);

                for ($j = 0; $j < $batchCount; $j++) {
                    $extension = $extensions[array_rand($extensions)];
                    $width = random_int(320, 2400);
                    $height = random_int(240, 1800);
                    $red = random_int(0, 255);
                    $green = random_int(0, 255);
                    $blue = random_int(0, 255);

                    $image = imagecreatetruecolor($width, $height);

                    if ($image === false) {
                        $progress->advance();

                        continue;
                    }

                    $color = imagecolorallocate($image, $red, $green, $blue);

                    if ($color === false) {
                        $progress->advance();

                        continue;
                    }

                    imagefill($image, 0, 0, $color);

                    ob_start();
                    match ($extension) {
                        'png' => imagepng($image, quality: 9),
                        'gif' => imagegif($image),
                        'webp' => imagewebp($image, quality: 75),
                        default => imagejpeg($image, quality: 75),
                    };
                    $bytes = ob_get_clean();

                    if ($bytes === false) {
                        $progress->advance();

                        continue;
                    }

                    $uuid = fake()->uuid();
                    $path = $teamDirectory.'/'.$uuid.'.'.$extension;
                    $disk->put($path, $bytes);

                    $inserts[] = [
                        'team_id' => $team->id,
                        'title' => fake()->sentence(3),
                        'description' => fake()->optional(0.5)->sentence(),
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => fake()->word().'.'.$extension,
                        'mime_type' => $mimeByExtension[$extension],
                        'size' => strlen($bytes),
                        'width' => $width,
                        'height' => $height,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $progress->advance();
                }

                foreach (array_chunk($inserts, 100) as $chunk) {
                    FileItem::withoutGlobalScopes()->insert($chunk);
                }

                $createdForTeam += $batchCount;
            }

            $teamIndex++;
        }

        $progress->finish();
        $this->newLine();
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

                if ($otherTypes === []) {
                    continue;
                }

                foreach ($leftIds as $leftId) {
                    $rightType = $otherTypes[array_rand($otherTypes)];

                    if (! isset($idsByType[$rightType])) {
                        continue;
                    }

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
