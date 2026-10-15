import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import * as Vue from 'vue';
import { parse } from 'vue/compiler-sfc';
import { renderToString } from 'vue/server-renderer';
import { evaluateScript } from '../../../lib/vue-test-helpers.mjs';

const { descriptor } = parse(
    readFileSync(new URL('./FilePreview.vue', import.meta.url), 'utf8'),
);
const render = Vue.compile(descriptor.template.content, {
    prefixIdentifiers: true,
});

function fileItem(previewKind, extra = {}) {
    return {
        id: 1,
        title: 'Private file',
        previewKind,
        previewUrl: '/team/files/1/inline',
        thumbnailUrl:
            previewKind === 'video' ? '/team/files/1/thumbnail' : null,
        downloadUrl: '/team/files/1/download',
        ...extra,
    };
}

function renderPreview(file) {
    return renderToString(
        Vue.createSSRApp({
            setup: () => ({
                file,
                ...evaluateScript(
                    descriptor.scriptSetup.content,
                    { vue: Vue },
                    { file },
                    '{ videoFailed }',
                ),
            }),
            render,
        }),
    );
}

test('videos use a private native player without autoplay and include a download fallback', async () => {
    const html = await renderPreview(fileItem('video'));
    assert.match(html, /<video[^>]*src="\/team\/files\/1\/inline"/);
    assert.match(html, /controls/);
    assert.match(html, /preload="metadata"/);
    assert.doesNotMatch(html, /autoplay/);
    assert.match(html, /Download video/);
});

test('PDF documents do not embed an unsandboxed browser viewer', async () => {
    const html = await renderPreview(
        fileItem(null, { previewUrl: null, mimeType: 'application/pdf' }),
    );
    assert.match(html, /Download it to open locally/);
    assert.doesNotMatch(html, /<iframe|<object|<embed/);
});

test('plain text is escaped rather than interpreted as HTML and explains truncation', async () => {
    const html = await renderPreview(
        fileItem('text', {
            previewUrl: null,
            textPreview: {
                content: '<script>alert(1)</script>',
                truncated: true,
            },
        }),
    );
    assert.match(html, /&lt;script&gt;/);
    assert.doesNotMatch(html, /<script>/);
    assert.match(html, /first 64 KB/);
});

test('Office files and missing previews render a download-only fallback', async () => {
    for (const kind of [null, 'image', 'video', 'text']) {
        const html = await renderPreview(fileItem(kind, { previewUrl: null }));
        assert.match(html, /Download it to open locally/);
        assert.doesNotMatch(html, /<video|<iframe|<img/);
    }
});

const { descriptor: board } = parse(
    readFileSync(new URL('./FileMasonryBoard.vue', import.meta.url), 'utf8'),
);

function boardState(props) {
    return evaluateScript(
        board.scriptSetup.content,
        {
            vue: { ...Vue, onMounted() {}, onUnmounted() {} },
            '@tanstack/vue-virtual': {
                useWindowVirtualizer: (options) =>
                    Vue.computed(() => ({
                        getVirtualItems: () =>
                            Array.from(
                                { length: options.value.count },
                                (_, index) => ({
                                    key: index,
                                    index,
                                    lane: 0,
                                    start: index * 300,
                                }),
                            ),
                        getTotalSize: () => options.value.count * 300,
                    })),
            },
        },
        props,
        '{ virtualItems, totalSize, itemStyle, imageAspectRatio, measureItem, previewImageUrl, failedPreviews }',
    );
}

test('failed thumbnail images reactively fall back and reset when files refresh', async (t) => {
    const props = Vue.reactive({ files: [fileItem('video')] });
    const scope = Vue.effectScope();
    t.after(() => scope.stop());
    const state = scope.run(() => boardState(props));
    let imageUrl;
    scope.run(() =>
        Vue.watchEffect(() => {
            imageUrl = state.previewImageUrl(props.files[0]);
        }),
    );
    assert.equal(imageUrl, '/team/files/1/thumbnail');
    state.failedPreviews.value.add(1);
    await Vue.nextTick();
    assert.equal(imageUrl, null);

    props.files = [
        fileItem('video', { thumbnailUrl: '/team/files/1/thumbnail?v=2' }),
    ];
    await Vue.nextTick();
    assert.equal(imageUrl, '/team/files/1/thumbnail?v=2');
});

test('the virtual board uses image thumbnails for videos without loading video players', async () => {
    const files = ['image', 'video', 'text', null].map((kind, index) =>
        fileItem(kind, {
            id: index + 1,
            title: `File ${index}`,
            originalName: `file-${index}`,
            size: 1024,
        }),
    );
    const stub = {
        setup:
            (_, { slots }) =>
            () =>
                Vue.h('div', slots.default?.()),
    };
    const html = await renderToString(
        Vue.createSSRApp({
            components: {
                Button: stub,
                FileKindIcon: stub,
                EmptyState: stub,
                Download: stub,
                Trash2: stub,
                File: stub,
            },
            setup: () => ({
                files,
                searchQuery: '',
                formatBytes: () => '1 KB',
                navigateToFile() {},
                openDeleteDialog() {},
                openCreateDialog() {},
                ...boardState({ files }),
            }),
            render: Vue.compile(board.template.content, {
                prefixIdentifiers: true,
            }),
        }),
    );

    for (const index of [0, 1, 2, 3]) {
        assert.match(html, new RegExp(`File ${index}`));
    }

    assert.equal((html.match(/<img /g) ?? []).length, 2);
    assert.match(html, /src="\/team\/files\/1\/thumbnail"/);
    assert.doesNotMatch(html, /<video|<iframe/);
});
