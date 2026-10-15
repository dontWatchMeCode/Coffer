import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import * as Vue from 'vue';
import { parse } from 'vue/compiler-sfc';
import { renderToString } from 'vue/server-renderer';
import * as search from '../../lib/search.ts';
import { evaluateScript } from '../../lib/vue-test-helpers.mjs';

const { descriptor } = parse(
    readFileSync(new URL('./SearchOverlay.vue', import.meta.url), 'utf8'),
);
const render = Vue.compile(descriptor.template.content, {
    prefixIdentifiers: true,
});
const stub = {
    setup:
        (_, { slots }) =>
        () =>
            Vue.h('div', slots.default?.()),
};
const searchModule = evaluateScript(
    readFileSync(
        new URL('../../composables/useTeamSearch.ts', import.meta.url),
        'utf8',
    ),
    {
        vue: Vue,
        '@/lib/search': search,
        '@/routes/team': {
            default: {
                search: Object.assign(
                    { url: () => '/search' },
                    { page: { url: () => '/search/page' } },
                ),
            },
        },
    },
);

async function renderOverlay(loading, hasResults) {
    return renderToString(
        Vue.createSSRApp({
            components: Object.fromEntries(
                [
                    'Dialog',
                    'DialogContent',
                    'DialogTitle',
                    'DialogDescription',
                    'Input',
                    'Search',
                    'LoaderCircle',
                    'SearchPrefixTooltip',
                    'Button',
                ].map((name) => [name, stub]),
            ),
            setup: () => ({
                open: true,
                loading,
                hasResults,
                query: 'query',
                selectedIndex: 0,
                SEARCH_CATEGORIES: [
                    { key: 'tasks', label: 'Tasks', icon: stub },
                ],
                results: {
                    tasks: hasResults
                        ? [{ id: 1, title: 'Existing result', url: '/task/1' }]
                        : [],
                },
                allResults: hasResults ? [{}] : [],
                onKeyDown() {},
                navigateTo() {},
                goToSearchPage() {},
                getResultIndex: () => 0,
            }),
            render,
        }),
    );
}

test('overlay retains results while loading and anchors its top edge', async () => {
    const html = await renderOverlay(true, true);
    assert.match(html, /Existing result/);
    assert.match(html, /aria-busy="true"/);
    assert.match(html, /top-\[10svh\]/);
    assert.match(html, /translate-y-0/);
    assert.doesNotMatch(html, /No results found/);
});

test('overlay does not flash no-results before a search finishes', async () => {
    const html = await renderOverlay(true, false);
    assert.match(html, /Searching…/);
    assert.doesNotMatch(html, /No results found/);
    assert.match(await renderOverlay(false, false), /No results found/);
});

function setupOverlay(t) {
    const requests = [];
    t.mock.method(
        globalThis,
        'fetch',
        (_url, { signal }) =>
            new Promise((resolve, reject) => {
                requests.push({
                    signal,
                    resolve: (title) =>
                        resolve({
                            ok: true,
                            json: async () => ({
                                ...search.createEmptySearchResults(),
                                tasks: [{ id: 1, title, url: '/task/1' }],
                            }),
                        }),
                    reject,
                });
            }),
    );
    let timer;
    t.mock.method(globalThis, 'setTimeout', (callback) => {
        timer = callback;

        return 1;
    });
    t.mock.method(globalThis, 'clearTimeout', () => {
        timer = null;
    });
    const open = Vue.ref(true);
    const page = Vue.reactive({ props: { currentTeam: { slug: 'team' } } });
    let unmount;
    const scope = Vue.effectScope();
    const state = scope.run(() =>
        evaluateScript(
            descriptor.scriptSetup.content,
            {
                vue: {
                    ...Vue,
                    onMounted() {},
                    onUnmounted: (callback) => {
                        unmount = callback;
                    },
                },
                '@inertiajs/vue3': { usePage: () => page },
                '@/composables/useTeamSearch': searchModule,
                '@/lib/search': search,
            },
            {},
            '{ query, loading, results, selectedIndex }',
            open,
        ),
    );
    const previousDocument = Object.getOwnPropertyDescriptor(
        globalThis,
        'document',
    );
    Object.defineProperty(globalThis, 'document', {
        configurable: true,
        value: { removeEventListener() {} },
    });
    t.after(() => {
        unmount();
        scope.stop();

        if (previousDocument) {
            Object.defineProperty(globalThis, 'document', previousDocument);
        } else {
            delete globalThis.document;
        }
    });

    return {
        state,
        open,
        page,
        requests,
        unmount: () => unmount(),
        runTimer: () => {
            const callback = timer;
            timer = null;
            callback?.();
        },
        hasTimer: () => timer !== null,
    };
}

test('overlay aborts immediately during debounce and ignores stale completions', async (t) => {
    const { state, requests, runTimer } = setupOverlay(t);
    state.query.value = 'first';
    runTimer();
    state.query.value = 'second';
    assert.equal(requests[0].signal.aborted, true);
    requests[0].resolve('Stale');
    await new Promise(setImmediate);
    assert.equal(state.loading.value, true);
    assert.equal(state.results.value.tasks.length, 0);
    runTimer();
    requests[1].resolve('Latest');
    await new Promise(setImmediate);
    assert.equal(state.results.value.tasks[0].title, 'Latest');
    assert.equal(state.loading.value, false);
});

test('closing the overlay cancels both debounce and in-flight work', async (t) => {
    const { state, open, requests, runTimer, hasTimer } = setupOverlay(t);
    state.query.value = 'pending';
    open.value = false;
    assert.equal(hasTimer(), false);
    runTimer();
    assert.equal(requests.length, 0);
    open.value = true;
    state.query.value = 'fetch';
    runTimer();
    open.value = false;
    assert.equal(requests[0].signal.aborted, true);
    requests[0].reject(new DOMException('Aborted', 'AbortError'));
    await new Promise(setImmediate);
    assert.equal(state.results.value.tasks.length, 0);
    assert.equal(state.loading.value, false);
});

test('clearing query and switching teams cannot resurrect old results', async (t) => {
    const { state, page, requests, runTimer } = setupOverlay(t);
    state.query.value = 'first';
    runTimer();
    state.query.value = '';
    assert.equal(requests[0].signal.aborted, true);
    requests[0].resolve('Stale clear');
    await new Promise(setImmediate);
    assert.equal(state.results.value.tasks.length, 0);
    state.query.value = 'next';
    runTimer();
    page.props.currentTeam = null;
    assert.equal(requests[1].signal.aborted, true);
    requests[1].resolve('Stale team');
    await new Promise(setImmediate);
    assert.equal(state.results.value.tasks.length, 0);
    assert.equal(state.loading.value, false);
});

test('unmount cancels work and newly received results reset keyboard selection', async (t) => {
    const { state, requests, runTimer, unmount } = setupOverlay(t);
    state.query.value = 'first';
    runTimer();
    state.selectedIndex.value = 9;
    requests[0].resolve('Result');
    await new Promise(setImmediate);
    assert.equal(state.selectedIndex.value, 0);
    state.query.value = 'second';
    runTimer();
    unmount();
    assert.equal(requests[1].signal.aborted, true);
    requests[1].resolve('Late');
    await new Promise(setImmediate);
    assert.equal(state.results.value.tasks[0].title, 'Result');
});

test('switching between teams clears previous-team results before debounce', async (t) => {
    const { state, page, requests, runTimer } = setupOverlay(t);
    state.query.value = 'query';
    runTimer();
    requests[0].resolve('Previous team');
    await new Promise(setImmediate);
    assert.equal(state.results.value.tasks[0].title, 'Previous team');

    page.props.currentTeam = { slug: 'other-team' };
    assert.equal(state.results.value.tasks.length, 0);
    assert.equal(state.loading.value, true);
    runTimer();
    requests[1].resolve('Current team');
    await new Promise(setImmediate);
    assert.equal(state.results.value.tasks[0].title, 'Current team');
    assert.equal(state.loading.value, false);
});
