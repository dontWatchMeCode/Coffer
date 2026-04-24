import assert from 'node:assert/strict';
import test from 'node:test';
import { renderStoredRichTextAsHtml } from './storage.ts';

test('renderStoredRichTextAsHtml preserves empty paragraphs in readonly mode', () => {
    const markdown = 'asdasdasd\n\n\n\nasd asdasd\n\nasdasd';

    assert.equal(
        renderStoredRichTextAsHtml(markdown),
        '<p>asdasdasd</p>\n<br>\n<p>asd asdasd</p>\n<p>asdasd</p>\n',
    );
});

test('renderStoredRichTextAsHtml keeps normal paragraph breaks without empty paragraphs', () => {
    const markdown = 'asdasdasd\n\nasd asdasd\n\nasdasd';

    assert.equal(
        renderStoredRichTextAsHtml(markdown),
        '<p>asdasdasd</p>\n<p>asd asdasd</p>\n<p>asdasd</p>\n',
    );
});

test('renderStoredRichTextAsHtml does not create empty paragraphs from extra markdown spacing', () => {
    const markdown = 'asdasdasd\n\n\nasd asdasd\n\nasdasd';

    assert.equal(
        renderStoredRichTextAsHtml(markdown),
        '<p>asdasdasd</p>\n<p>asd asdasd</p>\n<p>asdasd</p>\n',
    );
});
