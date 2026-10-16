import assert from 'node:assert/strict';
import test from 'node:test';
import {
    formatStoryboardTime,
    parseStoryboardVtt,
    storyboardCueForRatio,
    storyboardTileStyle,
} from './video-storyboard.ts';

const vtt = `WEBVTT

00:00:00.000 --> 00:00:05.000
/team/acme/files/1/storyboard.jpg?v=abc#xywh=0,0,160,90

00:00:05.000 --> 00:00:10.000
/team/acme/files/1/storyboard.jpg?v=abc#xywh=160,0,160,90

00:00:10.000 --> 00:00:15.000
/team/acme/files/1/storyboard.jpg?v=abc#xywh=320,0,160,90

00:00:15.000 --> 00:00:20.000
/team/acme/files/1/storyboard.jpg?v=abc#xywh=480,0,160,90
`;

test('parseStoryboardVtt extracts cues, sprite size, and duration', () => {
    const storyboard = parseStoryboardVtt(vtt);

    assert.ok(storyboard);
    assert.equal(
        storyboard.imageUrl,
        '/team/acme/files/1/storyboard.jpg?v=abc',
    );
    assert.equal(storyboard.duration, 20);
    assert.equal(storyboard.spriteWidth, 640);
    assert.equal(storyboard.spriteHeight, 90);
    assert.deepEqual(storyboard.cues[3], {
        end: 20,
        h: 90,
        start: 15,
        w: 160,
        x: 480,
        y: 0,
    });
});

test('parseStoryboardVtt rejects payloads without usable cues', () => {
    assert.equal(parseStoryboardVtt('WEBVTT\n'), null);
    assert.equal(parseStoryboardVtt('not a vtt file'), null);
    assert.equal(
        parseStoryboardVtt(
            'WEBVTT\n\n00:00:00.000 --> 00:00:05.000\nhttps://example.test/sprite.jpg\n',
        ),
        null,
    );
    assert.equal(
        parseStoryboardVtt(
            'WEBVTT\n\n:: --> 00:00:05.000\nhttps://example.test/sprite.jpg#xywh=0,0,160,90\n',
        ),
        null,
    );
    assert.equal(
        parseStoryboardVtt(
            'WEBVTT\n\n00:00:00.000 --> 00:00:05.000\nhttps://example.test/sprite.jpg#xywh=0,0,,90\n',
        ),
        null,
    );
});

test('storyboardCueForRatio maps cursor position to the covered tile', () => {
    const storyboard = parseStoryboardVtt(vtt);

    assert.ok(storyboard);
    assert.equal(storyboardCueForRatio(storyboard, 0)?.index, 0);
    assert.equal(storyboardCueForRatio(storyboard, 0.3)?.index, 1);
    assert.equal(storyboardCueForRatio(storyboard, 0.9)?.index, 3);
    assert.equal(storyboardCueForRatio(storyboard, 1)?.index, 3);
    assert.equal(storyboardCueForRatio(storyboard, -0.5)?.index, 0);
});

test('storyboardTileStyle centers the cover-fit tile', () => {
    const storyboard = parseStoryboardVtt(vtt);

    assert.ok(storyboard);
    // 4:3 card with a 16:9 second tile: cover scale overflows horizontally.
    const style = storyboardTileStyle(storyboard, storyboard.cues[1], 320, 240);

    assert.deepEqual(style, {
        backgroundPosition: '-480px -0px',
        backgroundSize: '1706.6666666666665px 240px',
    });
    assert.equal(
        storyboardTileStyle(storyboard, storyboard.cues[0], 0, 240),
        null,
    );
});

test('formatStoryboardTime matches player timestamp conventions', () => {
    assert.equal(formatStoryboardTime(0), '0:00');
    assert.equal(formatStoryboardTime(7.4), '0:07');
    assert.equal(formatStoryboardTime(75), '1:15');
    assert.equal(formatStoryboardTime(3723), '1:02:03');
});
