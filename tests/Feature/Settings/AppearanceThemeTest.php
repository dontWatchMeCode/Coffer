<?php

use App\Models\User;

test('appearance page renders default theme without cookies', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('data-theme="daybreak"', false);
});

test('appearance page renders stored theme for matching mode pair', function (string $theme, string $mode, string $background) {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withUnencryptedCookie('appearance', $mode)
        ->withUnencryptedCookie('theme', $theme)
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('data-theme="'.$theme.'"', false);
    $selector = 'html'.($mode === 'dark' ? '.dark' : '')."[data-theme='".$theme."']";
    expect($response->getContent())->toMatch('/'.preg_quote($selector, '/').'\s*\{\s*background-color:\s*'.preg_quote($background, '/').';/');
})->with([
    ['espresso', 'dark', '#201914'],
    ['nord', 'dark', '#2e3440'],
    ['rose-pine', 'dark', '#191724'],
    ['gruvbox', 'dark', '#282828'],
    ['sage', 'light', '#f0f3ed'],
    ['rose-pine-dawn', 'light', '#faf4ed'],
    ['porcelain', 'light', '#f5f5f4'],
]);

test('appearance page coerces dark theme under light appearance', function (string $theme) {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withUnencryptedCookie('appearance', 'light')
        ->withUnencryptedCookie('theme', $theme)
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('data-theme="daybreak"', false);
})->with(['espresso', 'nord', 'rose-pine', 'gruvbox']);

test('appearance page coerces light theme under dark appearance', function (string $theme) {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withUnencryptedCookie('appearance', 'dark')
        ->withUnencryptedCookie('theme', $theme)
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('data-theme="dracula"', false);
})->with(['parchment', 'sage', 'rose-pine-dawn', 'porcelain']);

test('appearance page passes cross-mode theme through under system appearance', function (string $theme) {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withUnencryptedCookie('appearance', 'system')
        ->withUnencryptedCookie('theme', $theme)
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('data-theme="'.$theme.'"', false);
})->with(['espresso', 'nord', 'rose-pine', 'gruvbox', 'sage', 'rose-pine-dawn', 'porcelain']);

test('appearance page falls back to default theme for unknown cookie', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withUnencryptedCookie('theme', 'not-a-theme')
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('data-theme="daybreak"', false);
});
