<?php

namespace App\Console\Commands;

use App\Enums\TeamRole;
use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Laravel\Pulse\Facades\Pulse;

#[Signature('app:generate-test-records')]
#[Description('Generate 1,000 test records for Tasks, Calendar, Contacts, Bookmarks, Notes, and Collections')]
class GenerateTestRecords extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Pulse::ignore(fn (): null => $this->generateRecords());
    }

    protected function generateRecords(): null
    {
        $this->info('Clearing existing test data...');

        Task::withoutGlobalScopes()->delete();
        CalendarEvent::withoutGlobalScopes()->delete();
        Contact::withoutGlobalScopes()->delete();
        Bookmark::withoutGlobalScopes()->delete();
        Note::withoutGlobalScopes()->delete();
        RecordCollection::withoutGlobalScopes()->delete();
        Project::withoutGlobalScopes()->delete();
        Team::query()->forceDelete();

        $this->info('Creating test user (test@test.test / test@test.test)...');

        $testUser = User::firstOrCreate(
            ['email' => 'test@test.test'],
            [
                'name' => 'Test User',
                'password' => Hash::make('test@test.test'),
            ]
        );

        if ($testUser->wasRecentlyCreated) {
            $testUser->email_verified_at = now();
            $testUser->save();
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

        $this->info('Generating 1,000 Tasks...');
        for ($i = 0; $i < 10; $i++) {
            Task::factory()
                ->count(100)
                ->recycle($projects)
                ->state(fn (): array => ['created_by' => $users->random()->id])
                ->create();
        }

        $this->info('Generating 1,000 Calendar Events...');
        for ($i = 0; $i < 10; $i++) {
            CalendarEvent::factory()->count(100)->recycle($teams)->create();
        }

        $this->info('Generating 1,000 Contacts...');
        for ($i = 0; $i < 10; $i++) {
            Contact::factory()->count(100)->recycle($teams)->create();
        }

        $this->info('Generating 1,000 Bookmarks...');
        for ($i = 0; $i < 10; $i++) {
            Bookmark::factory()->count(100)->recycle($teams)->create();
        }

        $this->info('Generating 1,000 Notes...');
        for ($i = 0; $i < 10; $i++) {
            Note::factory()->count(100)->recycle($teams)->create();
        }

        $this->info('Generating 1,000 Collections...');
        for ($i = 0; $i < 10; $i++) {
            RecordCollection::factory()->count(100)->recycle($teams)->create();
        }

        $this->info('Done! Generated 6,000 test records.');

        return null;
    }
}
