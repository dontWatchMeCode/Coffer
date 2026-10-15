import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import ts from 'typescript';
import * as Vue from 'vue';
import { parse } from 'vue/compiler-sfc';
import { renderToString } from 'vue/server-renderer';
import * as search from '../../lib/search.ts';

const { descriptor } = parse(
    readFileSync(new URL('./Index.vue', import.meta.url), 'utf8'),
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

async function renderSearch({
    loading,
    hasResults = true,
    activeTag = '',
    activeType = '',
    searchQuery = 'updated query',
}) {
    return renderToString(
        Vue.createSSRApp({
            components: Object.fromEntries(
                [
                    'Head',
                    'PageHeader',
                    'Search',
                    'Input',
                    'LoaderCircle',
                    'SearchPrefixTooltip',
                    'Badge',
                    'X',
                    'Link',
                    'Separator',
                    'SearchFilterSidebar',
                ].map((name) => [name, stub]),
            ),
            setup: () => ({
                loading,
                hasResults,
                searchQuery,
                activeType,
                activeTag,
                activeTagName: 'Work',
                types: [],
                tags: [],
                SEARCH_CATEGORIES: [
                    { key: 'tasks', label: 'Tasks', icon: stub },
                ],
                localResults: {
                    tasks: hasResults
                        ? [{ id: 1, title: 'Existing result', url: '/task/1' }]
                        : [],
                },
                allResults: hasResults ? [{}] : [],
                iconForType: () => stub,
                onSidebarTypeChange() {},
                onSidebarTagChange() {},
                clearFilters() {},
            }),
            render,
        }),
    );
}

test('search keeps existing results rendered while typing or changing tags', async () => {
    for (const filters of [
        {},
        { activeTag: 'work' },
        { activeType: 'tasks' },
    ]) {
        const html = await renderSearch({ loading: true, ...filters });
        assert.match(html, /Existing result/);
        assert.match(html, /aria-busy="true"/);
        assert.doesNotMatch(html, /No results found/);
    }
});

test('search does not flash a no-results message before a request completes', async () => {
    for (const searchQuery of ['', 'updated query']) {
        const html = await renderSearch({
            loading: true,
            hasResults: false,
            searchQuery,
        });
        assert.match(html, /Searching…/);
        assert.doesNotMatch(html, /No results found/);
    }
});

test('search prompts for input when the query and filters are empty', async () => {
    const html = await renderSearch({
        loading: false,
        hasResults: false,
        searchQuery: '',
    });
    assert.match(html, /Type to search across your team/);
    assert.doesNotMatch(html, /No results found/);
});

test('search shows no results for empty-query filters after loading', async () => {
    for (const filters of [{ activeTag: 'work' }, { activeType: 'tasks' }]) {
        const html = await renderSearch({
            loading: false,
            hasResults: false,
            searchQuery: '',
            ...filters,
        });
        assert.match(html, /No results found/);
        assert.doesNotMatch(html, /Type to search across your team/);
    }
});

test('search shows the empty state after the request completes', async () => {
    const html = await renderSearch({ loading: false, hasResults: false });
    assert.match(html, /No results found/);
    assert.match(html, /aria-busy="false"/);
});

function evaluateScript(
    source,
    imports,
    props = {},
    returnExpression = 'exports',
) {
    const { outputText } = ts.transpileModule(source, {
        compilerOptions: { module: ts.ModuleKind.CommonJS },
    });

    return new Function(
        'require',
        'exports',
        'defineProps',
        'defineOptions',
        `${outputText}\nreturn ${returnExpression};`,
    )(
        (name) => {
            if (!(name in imports)) {
                throw new Error(`Unexpected import: ${name}`);
            }

            return imports[name];
        },
        {},
        () => props,
        () => {},
    );
}

const searchRequests = evaluateScript(
    readFileSync(
        new URL('../../composables/useTeamSearch.ts', import.meta.url),
        'utf8',
    ),
    { vue: Vue, '@/lib/search': search, '@/routes/team': {} },
);

function setupSearch(t) {
    const requests = [];
    const visits = [];
    let unmount;
    t.mock.method(
        globalThis,
        'fetch',
        (_url, { signal }) =>
            new Promise((resolve, reject) => {
                requests.push({
                    signal,
                    resolve: (results) =>
                        resolve({ ok: true, json: async () => results }),
                    reject,
                });
            }),
    );
    const props = Vue.reactive({
        query: 'initial',
        type: '',
        tag: '',
        results: search.createEmptySearchResults(),
        types: [],
        tags: [],
    });
    const scope = Vue.effectScope();
    const state = scope.run(() =>
        evaluateScript(
            descriptor.scriptSetup.content,
            {
                vue: {
                    ...Vue,
                    onUnmounted: (callback) => {
                        unmount = callback;
                    },
                },
                '@inertiajs/vue3': {
                    usePage: () => ({
                        props: { currentTeam: { slug: 'team' } },
                    }),
                    router: {
                        visit: (_url, options) => {
                            const visit = { options, cancelled: false };
                            visits.push(visit);
                            options.onCancelToken({
                                cancel: () => {
                                    visit.cancelled = true;
                                    options.onFinish();
                                },
                            });
                        },
                    },
                },
                '@/composables/useTeamSearch': {
                    requestSearchResults: searchRequests.requestSearchResults,
                    buildSearchJsonUrl: () => '/search',
                    buildSearchPageUrl: () => '/search/page',
                },
                '@/lib/search': search,
                'lucide-vue-next': { Search: stub },
            },
            props,
            '{ searchQuery, activeType, activeTag, loading, localResults, performSearch, onSidebarTagChange, onSidebarTypeChange }',
        ),
    );
    const previousWindow = Object.getOwnPropertyDescriptor(
        globalThis,
        'window',
    );
    Object.defineProperty(globalThis, 'window', {
        configurable: true,
        value: {
            location: { href: 'https://example.test/team/search/page' },
            history: { state: null, replaceState() {} },
        },
    });
    t.after(() => {
        unmount();
        scope.stop();

        if (previousWindow) {
            Object.defineProperty(globalThis, 'window', previousWindow);
        } else {
            delete globalThis.window;
        }
    });

    return { state, props, requests, visits, unmount: () => unmount() };
}

function taskResults(title) {
    return {
        ...search.createEmptySearchResults(),
        tasks: [{ id: 1, title, url: '/task/1' }],
    };
}

test('new filters abort old requests and only the latest response updates results', async (t) => {
    const { state, requests } = setupSearch(t);
    const first = state.performSearch();
    state.onSidebarTagChange('work');
    assert.equal(requests[0].signal.aborted, true);
    requests[1].resolve(taskResults('Latest'));
    await new Promise(setImmediate);
    requests[0].resolve(taskResults('Stale'));
    await first;
    assert.equal(state.localResults.value.tasks[0].title, 'Latest');
    assert.equal(state.loading.value, false);
});

test('typing aborts immediately during debounce without clearing results or loading', async (t) => {
    const { state, requests } = setupSearch(t);
    state.localResults.value = taskResults('Existing');
    const first = state.performSearch();
    state.searchQuery.value = 'new query';
    assert.equal(requests[0].signal.aborted, true);
    requests[0].reject(new DOMException('Aborted', 'AbortError'));
    await first;
    assert.equal(state.localResults.value.tasks[0].title, 'Existing');
    assert.equal(state.loading.value, true);
});

test('network and HTTP errors settle with empty results rather than rejecting', async (t) => {
    const { state, requests } = setupSearch(t);
    const first = state.performSearch();
    requests[0].reject(new TypeError('Network failed'));
    await first;
    assert.equal(state.loading.value, false);
    assert.deepEqual(
        state.localResults.value,
        search.createEmptySearchResults(),
    );

    t.mock.method(globalThis, 'fetch', async () => ({ ok: false }));
    await state.performSearch();
    assert.equal(state.loading.value, false);
    assert.deepEqual(
        state.localResults.value,
        search.createEmptySearchResults(),
    );
});

test('unmount aborts in-flight requests without replacing results', async (t) => {
    const { state, requests, unmount } = setupSearch(t);
    state.localResults.value = taskResults('Existing');
    const pending = state.performSearch();
    unmount();
    assert.equal(requests[0].signal.aborted, true);
    requests[0].resolve(taskResults('Late'));
    await pending;
    assert.equal(state.localResults.value.tasks[0].title, 'Existing');
});

test('new searches cancel type-only visits and ignore their completion', async (t) => {
    const { state, requests, visits } = setupSearch(t);
    state.searchQuery.value = '';
    state.onSidebarTypeChange('tasks');
    state.onSidebarTagChange('work');
    assert.equal(visits[0].cancelled, true);
    assert.equal(state.loading.value, true);
    visits[0].options.onSuccess({
        props: { results: taskResults('Stale visit') },
    });
    assert.deepEqual(
        state.localResults.value,
        search.createEmptySearchResults(),
    );
    requests[0].resolve(taskResults('Latest'));
    await new Promise(setImmediate);
    assert.equal(state.localResults.value.tasks[0].title, 'Latest');
    assert.equal(state.loading.value, false);
});

test('external Inertia results replace local results and invalidate pending fetches', async (t) => {
    const { state, props, requests } = setupSearch(t);
    const pending = state.performSearch();
    props.query = 'restored query';
    props.type = 'tasks';
    props.tag = 'work';
    props.results = taskResults('Restored page');
    await Vue.nextTick();
    assert.equal(requests[0].signal.aborted, true);
    assert.equal(state.localResults.value.tasks[0].title, 'Restored page');
    assert.equal(state.loading.value, false);
    assert.equal(state.searchQuery.value, 'restored query');
    assert.equal(state.activeType.value, 'tasks');
    assert.equal(state.activeTag.value, 'work');
    requests[0].resolve(taskResults('Late fetch'));
    await pending;
    assert.equal(state.localResults.value.tasks[0].title, 'Restored page');
});

test('type-only results are applied by their own guarded visit callback', async (t) => {
    const { state, props, visits } = setupSearch(t);
    state.searchQuery.value = '';
    state.onSidebarTypeChange('tasks');
    props.results = taskResults('Browse results');
    await Vue.nextTick();
    assert.equal(visits[0].cancelled, false);
    visits[0].options.onSuccess({ props });
    visits[0].options.onFinish();
    assert.equal(state.localResults.value.tasks[0].title, 'Browse results');
    assert.equal(state.loading.value, false);
});
