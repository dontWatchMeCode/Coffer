import assert from 'node:assert/strict';
import test from 'node:test';
import { titleFromFileName } from './files.ts';

test('titleFromFileName removes the last extension', () => {
    assert.equal(titleFromFileName('reaction.png'), 'reaction');
    assert.equal(
        titleFromFileName('launch.moodboard.final.webp'),
        'launch.moodboard.final',
    );
});

test('titleFromFileName keeps names without a normal extension', () => {
    assert.equal(titleFromFileName('README'), 'README');
    assert.equal(titleFromFileName('.profile'), '.profile');
});
