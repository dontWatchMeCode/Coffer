import assert from 'node:assert/strict';
import test from 'node:test';
import { spreadsheetToCsv } from './spreadsheet-csv.ts';

test('spreadsheetToCsv exports headers, typed values, and escaped text', () => {
    const columns = [
        {
            id: 'name',
            name: 'Name',
            type: 'text',
            width: 180,
            hidden: false,
            options: [],
        },
        {
            id: 'done',
            name: 'Done',
            type: 'checkbox',
            width: 100,
            hidden: false,
            options: [],
        },
    ];
    const rows = [
        { id: '1', cells: { name: 'Quoted, "value"', done: true } },
        { id: '2', cells: { name: 'Plain', done: false } },
    ];

    assert.equal(
        spreadsheetToCsv(columns, rows),
        'Name,Done\r\n"Quoted, ""value""",Yes\r\nPlain,No',
    );
});

test('spreadsheetToCsv neutralizes formula-leading text', () => {
    const columns = [
        {
            id: 'value',
            name: 'Value',
            type: 'text',
            width: 180,
            hidden: false,
            options: [],
        },
    ];
    const rows = [
        { id: '1', cells: { value: '=HYPERLINK("https://example.com")' } },
        { id: '2', cells: { value: '@SUM(1,2)' } },
        { id: '3', cells: { value: -42 } },
    ];

    assert.equal(
        spreadsheetToCsv(columns, rows),
        'Value\r\n"\'=HYPERLINK(""https://example.com"")"\r\n"\'@SUM(1,2)"\r\n-42',
    );
});
