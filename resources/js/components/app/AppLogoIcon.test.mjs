import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { compile, createSSRApp } from 'vue';
import { parse } from 'vue/compiler-sfc';
import { renderToString } from 'vue/server-renderer';

const { descriptor } = parse(
    readFileSync(new URL('./AppLogoIcon.vue', import.meta.url), 'utf8'),
);

const Logo = {
    inheritAttrs: false,
    props: ['className'],
    render: compile(descriptor.template.content, { prefixIdentifiers: true }),
};

test('open desk logo retains its geometry and inherits theme colors', async () => {
    const html = await renderToString(
        createSSRApp(Logo, {
            class: 'size-9 fill-current text-primary',
            'aria-label': 'Workspace',
        }),
    );

    assert.match(html, /viewBox="0 0 64 64"/);
    assert.match(html, /class="size-9 fill-current text-primary"/);
    assert.match(html, /aria-label="Workspace"/);
    assert.match(html, /d="M38 10H10v44h44V26"/);
    assert.match(html, /d="M21 23h12v12H21z" fill="currentColor"/);
    assert.match(html, /d="M21 44h22M44 10h10v10H44z"/);
    assert.equal((html.match(/fill="none"/g) ?? []).length, 2);
    assert.equal((html.match(/stroke="currentColor"/g) ?? []).length, 2);
    assert.doesNotMatch(html, /#[\da-f]{3,8}\b|\bcolor=/i);
});

test('logo preserves the explicit className prop', async () => {
    const html = await renderToString(
        createSSRApp(Logo, { className: 'custom-logo' }),
    );

    assert.match(html, /class="custom-logo"/);
});

test('favicon matches the app logo and supports light and dark appearances', () => {
    const favicon = readFileSync(
        new URL('../../../../public/favicon.svg', import.meta.url),
        'utf8',
    );
    const paths = (source) =>
        [...source.matchAll(/<path\b([^>]+)\/>/g)].map((match) =>
            Object.fromEntries(
                [...match[1].matchAll(/([\w-]+)="([^"]*)"/g)].map(
                    ([, name, value]) => [name, value],
                ),
            ),
        );

    assert.deepEqual(paths(favicon), paths(descriptor.template.content));
    assert.match(favicon, /viewBox="0 0 64 64"/);
    assert.match(favicon, /prefers-color-scheme: dark/);
    assert.match(favicon, /color: #7043aa/);
    assert.match(favicon, /color: #bd93f9/);
});
