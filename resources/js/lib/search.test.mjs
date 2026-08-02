import assert from 'node:assert/strict';
import test from 'node:test';
import {
    SEARCH_CATEGORY_KEYS,
    createEmptySearchResults,
    flattenSearchResults,
} from './search.ts';

test('createEmptySearchResults returns an empty array for every category', () => {
    const empty = createEmptySearchResults();

    for (const key of SEARCH_CATEGORY_KEYS) {
        assert.deepEqual(empty[key], []);
    }

    assert.equal(Object.keys(empty).length, SEARCH_CATEGORY_KEYS.length);
});

test('createEmptySearchResults returns a fresh object each call', () => {
    const a = createEmptySearchResults();
    const b = createEmptySearchResults();

    assert.notEqual(a, b);
    assert.notEqual(a.tasks, b.tasks);
    a.tasks.push({ id: 1, title: 'x', subtitle: null, url: '/' });
    assert.equal(b.tasks.length, 0);
});

test('flattenSearchResults preserves SEARCH_CATEGORY_KEYS ordering and tags items', () => {
    const results = createEmptySearchResults();

    results.tasks = [{ id: 1, title: 'Task', subtitle: null, url: '/tasks/1' }];
    results.files = [
        { id: 2, title: 'File', subtitle: 'pdf', url: '/files/2' },
    ];
    results.log_entries = [
        { id: 3, title: 'Log', subtitle: null, url: '/log/3' },
    ];

    const flat = flattenSearchResults(results);

    assert.equal(flat.length, 3);
    assert.deepEqual(
        flat.map((item) => item.category),
        ['tasks', 'files', 'log_entries'],
    );
    assert.equal(flat[1].title, 'File');
    assert.equal(flat[1].subtitle, 'pdf');
});

test('flattenSearchResults does not mutate the source results', () => {
    const results = createEmptySearchResults();

    results.contacts = [{ id: 1, title: 'C', subtitle: null, url: '/c/1' }];
    const flat = flattenSearchResults(results);

    flat[0].title = 'changed';
    assert.equal(results.contacts[0].title, 'C');
});
