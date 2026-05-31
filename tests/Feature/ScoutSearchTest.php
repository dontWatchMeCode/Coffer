<?php

use App\Models\Contact;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\Team;
use App\Services\ScoutRecordSearch;

test('scout search matches serialized contact fields', function () {
    $team = Team::factory()->create();

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Jane Person',
        'email_addresses' => [['label' => 'work', 'value' => 'jane@example.test']],
    ]);

    expect(ScoutRecordSearch::keys(Contact::class, $team, 'jane@example.test'))->toBe([$contact->id]);
});

test('scout search is constrained to team ownership', function () {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();

    Contact::factory()->create([
        'team_id' => $otherTeam->id,
        'name' => 'Private Person',
    ]);

    expect(ScoutRecordSearch::keys(Contact::class, $team, 'Private'))->toBe([]);
});

test('scout search can constrain soft deleted records', function () {
    $team = Team::factory()->create();

    $contact = Contact::factory()->create([
        'team_id' => $team->id,
        'name' => 'Archived Person',
    ]);

    $contact->delete();

    expect(ScoutRecordSearch::keys(Contact::class, $team, 'Archived'))->toBe([])
        ->and(ScoutRecordSearch::keys(Contact::class, $team, 'Archived', onlyTrashed: true))->toBe([$contact->id]);
});

test('tag candidates use scout searchable fields', function () {
    $team = Team::factory()->create();

    $tag = Tag::factory()->create([
        'team_id' => $team->id,
        'name' => 'Deep Work',
        'slug' => 'deep-work',
    ]);

    expect(ScoutRecordSearch::keys(Tag::class, $team, 'deep-work'))->toBe([$tag->id]);
});

test('subscription category column is searchable', function () {
    $team = Team::factory()->create();

    $subscription = Subscription::factory()->create([
        'team_id' => $team->id,
        'name' => 'Hosted Service',
        'category' => 'Operations',
    ]);

    expect(ScoutRecordSearch::keys(Subscription::class, $team, 'Operations'))->toBe([$subscription->id]);
});
