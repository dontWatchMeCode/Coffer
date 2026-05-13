<?php

use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\LogEntry;
use App\Models\Note;
use App\Models\Project;
use App\Models\RecordCollection;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\User;

it('bookmark show page renders copy as markdown button', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $bookmark = Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Test Bookmark',
        'url' => 'https://example.com',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/bookmarks/'.$bookmark->id)
        ->assertSee('Copy as Markdown')
        ->assertNoJavaScriptErrors();
});

it('contact show page renders copy as markdown button', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Jane Doe',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/contacts/'.$contact->id)
        ->assertSee('Copy as Markdown')
        ->assertNoJavaScriptErrors();
});

it('note show page renders copy as markdown button', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()->create([
        'team_id' => $team->id,
        'title' => 'Test Note',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/notes/'.$note->id)
        ->assertSee('Copy as Markdown')
        ->assertNoJavaScriptErrors();
});

it('subscription show page renders copy as markdown button', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $subscription = Subscription::factory()->create([
        'team_id' => $team->id,
        'name' => 'Netflix',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/subscriptions/'.$subscription->id)
        ->assertSee('Copy as Markdown')
        ->assertNoJavaScriptErrors();
});

it('collection show page renders copy as markdown button', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $collection = RecordCollection::factory()->create([
        'team_id' => $team->id,
        'title' => 'Test Collection',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/collections/'.$collection->id)
        ->assertSee('Copy as Markdown')
        ->assertNoJavaScriptErrors();
});

it('calendar event edit page renders copy as markdown button', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Team Meeting',
        'date' => now()->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/calendar/events/'.$event->id.'/edit')
        ->assertSee('Copy as Markdown')
        ->assertNoJavaScriptErrors();
});

it('task edit page renders copy as markdown button', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'title' => 'Test Task',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/tasks/'.$project->id.'/'.$task->id.'/edit')
        ->assertSee('Copy as Markdown')
        ->assertNoJavaScriptErrors();
});

it('log page renders without errors', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    LogEntry::factory()->create([
        'team_id' => $team->id,
        'body' => 'Test log entry',
    ]);

    $this->actingAs($user);

    visit('/'.$team->slug.'/log')
        ->assertSee('Test log entry')
        ->assertNoJavaScriptErrors();
});
