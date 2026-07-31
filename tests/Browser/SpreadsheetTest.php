<?php

use App\Models\SpreadsheetWorkbook;
use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\Webpage;

function waitForSpreadsheetSave(Webpage|AwaitableWebpage $page): void
{
    $deadline = microtime(true) + 5;

    while (microtime(true) < $deadline) {
        $saveFinished = $page->script(<<<'JS'
            (() => {
                const button = Array.from(document.querySelectorAll('button'))
                    .find((item) => item.textContent?.includes('Save changes'));

                return button?.textContent?.includes('Save changes') === true;
            })()
        JS);

        if ($saveFinished === true) {
            return;
        }

        usleep(100_000);
    }

    expect(false)->toBeTrue('Expected spreadsheet save to finish.');
}

it('enables save while editing a cell and persists the value', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $spreadsheet = SpreadsheetWorkbook::factory()->create([
        'team_id' => $team->id,
        'title' => 'Editable spreadsheet',
    ]);
    $columnId = $spreadsheet->snapshot['columns'][0]['id'];

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/spreadsheets/'.$spreadsheet->id)
        ->assertSee('Editable spreadsheet')
        ->assertNoJavaScriptErrors()
        ->fill('[aria-label="Name, row 1"]', 'Saved value');

    expect($page->script(
        "Array.from(document.querySelectorAll('button')).find((button) => button.textContent?.includes('Save changes'))?.disabled",
    ))->toBeFalse();

    $page->script(<<<'JS'
        (() => {
            const originalSend = XMLHttpRequest.prototype.send;

            XMLHttpRequest.prototype.send = function (...args) {
                XMLHttpRequest.prototype.send = originalSend;
                setTimeout(() => originalSend.apply(this, args), 250);
            };
        })()
    JS);

    $page->click('Save changes')
        ->assertSee('Saving...')
        ->fill('[aria-label="Name, row 1"]', 'Edited during save')
        ->assertNoJavaScriptErrors();
    waitForSpreadsheetSave($page);

    expect($page->script(
        "Array.from(document.querySelectorAll('button')).find((button) => button.textContent?.includes('Save changes'))?.disabled",
    ))->toBeFalse();

    $page->click('Save changes');
    waitForSpreadsheetSave($page);

    expect($spreadsheet->refresh()->snapshot['rows'][0]['cells'][$columnId] ?? null)
        ->toBe('Edited during save');
});

it('confirms before deleting a spreadsheet row', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $spreadsheet = SpreadsheetWorkbook::factory()->create([
        'team_id' => $team->id,
        'title' => 'Row deletion spreadsheet',
    ]);

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/spreadsheets/'.$spreadsheet->id)
        ->click('[aria-label="Delete row 1"]')
        ->assertSee('Delete row');

    $page->script(<<<'JS'
        document.elementFromPoint(10, 10)?.dispatchEvent(
            new PointerEvent('pointerdown', { bubbles: true, composed: true })
        )
    JS);

    $page
        ->assertDontSee('Delete row')
        ->click('[aria-label="Delete row 1"]')
        ->click('[data-testid="confirm-spreadsheet-grid-delete"]')
        ->assertNoJavaScriptErrors();

    expect($page->script(
        'document.querySelector(\'[aria-label="Delete row 1"]\') === null',
    ))->toBeTrue();
});

it('uses the shadcn checkbox and persists its value', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $spreadsheet = SpreadsheetWorkbook::factory()->create([
        'team_id' => $team->id,
        'title' => 'Checkbox spreadsheet',
    ]);
    $columnId = $spreadsheet->snapshot['columns'][2]['id'];

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/spreadsheets/'.$spreadsheet->id)
        ->click('[aria-label="Complete, row 1"]')
        ->hover('Save changes');

    expect($page->script(
        'getComputedStyle(document.querySelector(\'[data-testid="spreadsheet-row-number-1"]\')).opacity',
    ))->toBe('1');

    $page->click('Save changes')->assertNoJavaScriptErrors();

    waitForSpreadsheetSave($page);

    expect($spreadsheet->refresh()->snapshot['rows'][0]['cells'][$columnId] ?? null)
        ->toBeTrue();
});

it('uses the shadcn select and persists its value', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $spreadsheet = SpreadsheetWorkbook::factory()->create([
        'team_id' => $team->id,
        'title' => 'Select spreadsheet',
    ]);
    $columnId = $spreadsheet->snapshot['columns'][1]['id'];

    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/spreadsheets/'.$spreadsheet->id)
        ->click('[aria-label="Status, row 1"]')
        ->click('Done')
        ->click('Save changes')
        ->assertNoJavaScriptErrors();

    waitForSpreadsheetSave($page);

    expect($spreadsheet->refresh()->snapshot['rows'][0]['cells'][$columnId] ?? null)
        ->toBe('Done');

    $page->click('[aria-label="Status, row 1"]')
        ->click('None')
        ->click('Save changes')
        ->assertNoJavaScriptErrors();

    waitForSpreadsheetSave($page);

    expect($spreadsheet->refresh()->snapshot['rows'][0]['cells'])
        ->toHaveKey($columnId, null);
});
