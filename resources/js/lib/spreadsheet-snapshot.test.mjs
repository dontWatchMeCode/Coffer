import assert from 'node:assert/strict';
import test from 'node:test';
import { reactive } from 'vue';
import {
    cloneSpreadsheetSnapshot,
    withSpreadsheetCell,
} from './spreadsheet-snapshot.ts';

test('cloneSpreadsheetSnapshot clones Vue proxies into editable plain data', () => {
    const snapshot = reactive({
        version: 1,
        columns: [
            {
                id: 'name',
                name: 'Name',
                type: 'text',
                width: 180,
                hidden: false,
                options: [],
            },
        ],
        rows: [{ id: '1', cells: { name: 'Original' } }],
    });

    const clone = cloneSpreadsheetSnapshot(snapshot);
    clone.rows[0].cells.name = 'Changed';

    assert.equal(snapshot.rows[0].cells.name, 'Original');
    assert.equal(clone.rows[0].cells.name, 'Changed');
});

test('withSpreadsheetCell returns an updated snapshot without mutating its source', () => {
    const snapshot = reactive({
        version: 1,
        columns: [
            {
                id: 'name',
                name: 'Name',
                type: 'text',
                width: 180,
                hidden: false,
                options: [],
            },
        ],
        rows: [{ id: '1', cells: { name: 'Original' } }],
    });

    const updated = withSpreadsheetCell(snapshot, '1', 'name', 'Changed');

    assert.equal(snapshot.rows[0].cells.name, 'Original');
    assert.equal(updated.rows[0].cells.name, 'Changed');
    assert.equal(
        withSpreadsheetCell(snapshot, 'missing', 'name', 'Ignored'),
        snapshot,
    );
});
