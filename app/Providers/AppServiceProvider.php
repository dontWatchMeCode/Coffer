<?php

declare(strict_types=1);

namespace App\Providers;

use App\Database\StrictSQLiteConnection;
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
use App\Policies\BookmarkPolicy;
use App\Policies\CalendarEventPolicy;
use App\Policies\ContactPolicy;
use App\Policies\LogEntryPolicy;
use App\Policies\McpTokenPolicy;
use App\Policies\NotePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\RecordCollectionPolicy;
use App\Policies\RecordLinkPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\TagPolicy;
use App\Policies\TaskCommentPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Connection::resolverFor('sqlite', fn (mixed $connection, string $database, string $prefix, array $config): StrictSQLiteConnection => new StrictSQLiteConnection($connection, $database, $prefix, $config));

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Bookmark::class, BookmarkPolicy::class);
        Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);
        Gate::policy(Contact::class, ContactPolicy::class);
        Gate::policy(LogEntry::class, LogEntryPolicy::class);
        Gate::policy(McpToken::class, McpTokenPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(RecordCollection::class, RecordCollectionPolicy::class);
        Gate::policy(RecordLink::class, RecordLinkPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskComment::class, TaskCommentPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
    }
}
