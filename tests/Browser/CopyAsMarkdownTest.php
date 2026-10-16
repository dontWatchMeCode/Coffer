<?php

use App\Enums\TaskStatus;
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
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\Webpage;

function installClipboardCapture(Webpage|AwaitableWebpage $page): void
{
    $page->script(<<<'JS'
        (() => {
            window.__copiedMarkdown = null;
            Object.defineProperty(window.navigator, 'clipboard', {
                value: {
                    writeText: async (text) => {
                        window.__copiedMarkdown = text;
                    },
                },
                configurable: true,
            });
        })()
    JS);
}

it('bookmark show page copies markdown to the clipboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $bookmark = Bookmark::factory()->create([
        'team_id' => $team->id,
        'title' => 'Clipboard Bookmark',
        'url' => 'https://example.com/clip',
        'description' => null,
        'notes' => null,
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/bookmarks/'.$bookmark->id)
        ->assertSee('Copy as Markdown');

    installClipboardCapture($page);
    $page->click('Copy as Markdown');

    waitForBrowserText($page, 'Copied!');

    expect($page->script('window.__copiedMarkdown'))
        ->toBe("# Clipboard Bookmark\n**URL:** https://example.com/clip");
    $page->assertNoJavaScriptErrors();
});

it('contact show page copies markdown to the clipboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Clipboard Contact',
        'phone_numbers' => [['label' => 'Mobile', 'value' => '+1 555-0100']],
        'email_addresses' => [],
        'links' => [],
        'address' => null,
        'additional_info' => null,
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/contacts/'.$contact->id)
        ->assertSee('Copy as Markdown');

    installClipboardCapture($page);
    $page->click('Copy as Markdown');

    waitForBrowserText($page, 'Copied!');

    expect($page->script('window.__copiedMarkdown'))
        ->toBe("# Clipboard Contact\n## Phone Numbers\n- +1 555-0100 (Mobile)");
    $page->assertNoJavaScriptErrors();
});

it('note show page copies markdown to the clipboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $note = Note::factory()
        ->withTextBlock('Clipboard note body.')
        ->create([
            'team_id' => $team->id,
            'title' => 'Clipboard Note',
        ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/notes/'.$note->id)
        ->assertSee('Copy as Markdown');

    installClipboardCapture($page);
    $page->click('Copy as Markdown');

    waitForBrowserText($page, 'Copied!');

    expect($page->script('window.__copiedMarkdown'))
        ->toBe("# Clipboard Note\n\nClipboard note body.");
    $page->assertNoJavaScriptErrors();
});

it('subscription show page copies markdown to the clipboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $subscription = Subscription::factory()->create([
        'team_id' => $team->id,
        'name' => 'Clipboard Streaming',
        'price' => '9.99',
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'first_billing_date' => null,
        'next_billing_date' => null,
        'url' => null,
        'description' => 'Streams clipboard hits.',
        'notes' => null,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/subscriptions/'.$subscription->id)
        ->assertSee('Copy as Markdown');

    installClipboardCapture($page);
    $page->click('Copy as Markdown');

    waitForBrowserText($page, 'Copied!');

    $copied = $page->script('window.__copiedMarkdown');

    expect($copied)->toContain('# Clipboard Streaming')
        ->and($copied)->toContain('**Price:** $9.99 / monthly')
        ->and($copied)->toContain('Streams clipboard hits.');
    $page->assertNoJavaScriptErrors();
});

it('collection show page copies markdown to the clipboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $collection = RecordCollection::factory()->create([
        'team_id' => $team->id,
        'title' => 'Clipboard Collection',
        'description' => 'Clipboard collection blurb.',
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/collections/'.$collection->id)
        ->assertSee('Copy as Markdown');

    installClipboardCapture($page);
    $page->click('Copy as Markdown');

    waitForBrowserText($page, 'Copied!');

    expect($page->script('window.__copiedMarkdown'))
        ->toBe("# Clipboard Collection\nClipboard collection blurb.");
    $page->assertNoJavaScriptErrors();
});

it('calendar event edit page copies markdown to the clipboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Clipboard Sync',
        'description' => 'Quarterly clipboard review.',
        'date' => '2026-10-20',
        'time' => null,
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/calendar/events/'.$event->id.'/edit')
        ->assertSee('Copy as Markdown');

    installClipboardCapture($page);
    $page->click('Copy as Markdown');

    waitForBrowserText($page, 'Copied!');

    $copied = $page->script('window.__copiedMarkdown');

    expect($copied)->toContain('# Clipboard Sync')
        ->and($copied)->toContain('**Date:**')
        ->and($copied)->toContain('Quarterly clipboard review.');
    $page->assertNoJavaScriptErrors();
});

it('task edit page copies markdown to the clipboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Clipboard Project',
    ]);
    $task = Task::factory()->create([
        'team_id' => $team->id,
        'project_id' => $project->id,
        'title' => 'Clipboard Task',
        'description' => 'Ship the clipboard shim.',
        'status' => TaskStatus::Planned->value,
        'progress' => 0,
        'due_at' => null,
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/tasks/'.$project->id.'/'.$task->id.'/edit')
        ->assertSee('Copy as Markdown');

    installClipboardCapture($page);
    $page->click('Copy as Markdown');

    waitForBrowserText($page, 'Copied!');

    $copied = $page->script('window.__copiedMarkdown');

    expect($copied)->toContain('# Clipboard Task')
        ->and($copied)->toContain('**Project:** Clipboard Project')
        ->and($copied)->toContain('**Status:** planned')
        ->and($copied)->toContain('Ship the clipboard shim.');
    $page->assertNoJavaScriptErrors();
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
