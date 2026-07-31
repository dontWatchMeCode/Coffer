<?php

use App\Models\Bookmark;
use App\Models\SpreadsheetWorkbook;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

test('spreadsheet page is team scoped', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    SpreadsheetWorkbook::factory()->create([
        'team_id' => $team->id,
        'title' => 'Visible spreadsheet',
    ]);
    SpreadsheetWorkbook::factory()->create([
        'team_id' => $otherTeam->id,
        'title' => 'Hidden spreadsheet',
    ]);

    actingAs($user)
        ->get(route('team.spreadsheets.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('spreadsheets/Index')
            ->has('spreadsheets.data', 1)
            ->where('spreadsheets.data.0.title', 'Visible spreadsheet')
            ->where('spreadsheets.data.0.rowCount', 1)
            ->where('spreadsheets.data.0.columnCount', 3));
});

test('a spreadsheet can be created and opened', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->post(route('team.spreadsheets.store', ['current_team' => $team]), [
            'title' => 'Budget tracker',
        ])
        ->assertRedirect();

    $spreadsheet = SpreadsheetWorkbook::query()->whereBelongsTo($team)->firstOrFail();

    expect($spreadsheet->snapshot)
        ->toHaveKey('version', 1)
        ->toHaveKey('columns')
        ->toHaveKey('rows');

    actingAs($user)
        ->get(route('team.spreadsheets.show', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('spreadsheets/Index')
            ->where('spreadsheet.title', 'Budget tracker')
            ->has('spreadsheet.snapshot.columns', 3)
            ->has('recordLinks')
            ->has('recordTags')
            ->has('activityHistory'));
});

test('a spreadsheet title and workbook can be saved', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $spreadsheet = SpreadsheetWorkbook::factory()->create([
        'team_id' => $team->id,
        'title' => 'Original title',
    ]);
    $snapshot = SpreadsheetWorkbook::defaultSnapshot();
    $columnId = $snapshot['columns'][0]['id'];
    $snapshot['rows'][0]['cells'][$columnId] = 'Saved cell';

    actingAs($user)
        ->patch(route('team.spreadsheets.workbook.update', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet,
        ]), [
            'title' => 'Renamed spreadsheet',
            'snapshot' => $snapshot,
        ])
        ->assertRedirect();

    expect($spreadsheet->fresh())
        ->title->toBe('Renamed spreadsheet')
        ->snapshot->toMatchArray($snapshot);

    expect(Activity::query()
        ->where('subject_type', $spreadsheet->getMorphClass())
        ->where('subject_id', $spreadsheet->id)
        ->where('event', 'updated')
        ->exists())->toBeTrue();
});

test('spreadsheet workbook validation rejects invalid schemas', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $spreadsheet = SpreadsheetWorkbook::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->patch(route('team.spreadsheets.workbook.update', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet,
        ]), [
            'title' => 'Invalid spreadsheet',
            'snapshot' => [
                'version' => 1,
                'columns' => [],
                'rows' => [],
            ],
        ])
        ->assertSessionHasErrors(['snapshot.columns']);
});

test('spreadsheet workbook validation rejects cells for unknown columns', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $spreadsheet = SpreadsheetWorkbook::factory()->create(['team_id' => $team->id]);
    $snapshot = SpreadsheetWorkbook::defaultSnapshot();
    $snapshot['rows'][0]['cells']['unknown-column'] = 'Orphaned value';

    actingAs($user)
        ->patch(route('team.spreadsheets.workbook.update', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet,
        ]), [
            'title' => 'Invalid spreadsheet',
            'snapshot' => $snapshot,
        ])
        ->assertSessionHasErrors(['snapshot.rows.0.cells']);
});

test('spreadsheet workbook validation rejects oversized snapshots', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $spreadsheet = SpreadsheetWorkbook::factory()->create(['team_id' => $team->id]);
    $snapshot = SpreadsheetWorkbook::defaultSnapshot();
    $snapshot['columns'] = collect(range(1, 50))
        ->map(fn (int $index): array => [
            'id' => "column-{$index}",
            'name' => "Column {$index}",
            'type' => 'text',
            'width' => 180,
            'hidden' => false,
            'options' => [],
        ])
        ->all();
    $snapshot['rows'] = collect(range(1, 5))
        ->map(fn (int $rowIndex): array => [
            'id' => "row-{$rowIndex}",
            'cells' => collect(range(1, 50))
                ->mapWithKeys(fn (int $columnIndex): array => [
                    "column-{$columnIndex}" => str_repeat('x', 10_000),
                ])
                ->all(),
        ])
        ->all();

    actingAs($user)
        ->patch(route('team.spreadsheets.workbook.update', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet,
        ]), [
            'title' => 'Oversized spreadsheet',
            'snapshot' => $snapshot,
        ])
        ->assertSessionHasErrors(['snapshot']);
});

test('a spreadsheet from another team cannot be viewed or changed', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $spreadsheet = SpreadsheetWorkbook::factory()->create();

    actingAs($user)
        ->get(route('team.spreadsheets.show', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet,
        ]))
        ->assertNotFound();

    actingAs($user)
        ->patch(route('team.spreadsheets.workbook.update', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet,
        ]), [
            'title' => 'Changed',
            'snapshot' => SpreadsheetWorkbook::defaultSnapshot(),
        ])
        ->assertNotFound();

    actingAs($user)
        ->delete(route('team.spreadsheets.destroy', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet,
        ]))
        ->assertNotFound();

    $spreadsheet->delete();

    actingAs($user)
        ->patch(route('team.spreadsheets.restore', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet->id,
        ]))
        ->assertNotFound();

    actingAs($user)
        ->delete(route('team.spreadsheets.force-destroy', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet->id,
        ]))
        ->assertNotFound();
});

test('spreadsheets support tags links and global search', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $spreadsheet = SpreadsheetWorkbook::factory()->create([
        'team_id' => $team->id,
        'title' => 'Quarterly capacity',
    ]);
    $bookmark = Bookmark::factory()->create(['team_id' => $team->id]);

    actingAs($user)
        ->postJson(route('team.tags.store', ['current_team' => $team]), [
            'from_type' => 'spreadsheet',
            'from_id' => $spreadsheet->id,
            'name' => 'Planning',
        ])
        ->assertCreated();

    actingAs($user)
        ->postJson(route('team.links.store', ['current_team' => $team]), [
            'from_type' => 'spreadsheet',
            'from_id' => $spreadsheet->id,
            'to_type' => 'bookmark',
            'to_id' => $bookmark->id,
        ])
        ->assertCreated();

    actingAs($user)
        ->getJson(route('team.search', [
            'current_team' => $team,
            'q' => 'x: Quarterly',
        ]))
        ->assertOk()
        ->assertJsonPath('spreadsheets.0.title', 'Quarterly capacity');
});

test('spreadsheets can be trashed restored and permanently deleted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $spreadsheet = SpreadsheetWorkbook::factory()->create([
        'team_id' => $team->id,
        'title' => 'Old spreadsheet',
    ]);

    actingAs($user)
        ->delete(route('team.spreadsheets.destroy', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet,
        ]))
        ->assertRedirect(route('team.spreadsheets.index', ['current_team' => $team]));

    expect(SpreadsheetWorkbook::query()->find($spreadsheet->id))->toBeNull();

    actingAs($user)
        ->get(route('team.spreadsheets.trash', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('spreadsheets/Trash')
            ->where('spreadsheets.data.0.title', 'Old spreadsheet'));

    actingAs($user)
        ->patch(route('team.spreadsheets.restore', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet->id,
        ]))
        ->assertRedirect();

    expect($spreadsheet->fresh())->not->toBeNull();

    $spreadsheet->delete();

    actingAs($user)
        ->delete(route('team.spreadsheets.force-destroy', [
            'current_team' => $team,
            'spreadsheet' => $spreadsheet->id,
        ]))
        ->assertRedirect();

    expect(SpreadsheetWorkbook::withTrashed()->find($spreadsheet->id))->toBeNull();
});
