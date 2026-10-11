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

test('appearance page renders stored theme for matching mode pair', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withUnencryptedCookie('appearance', 'dark')
        ->withUnencryptedCookie('theme', 'espresso')
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('data-theme="espresso"', false);
});

test('appearance page coerces dark theme under light appearance', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withUnencryptedCookie('appearance', 'light')
        ->withUnencryptedCookie('theme', 'espresso')
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('data-theme="daybreak"', false);
});

test('appearance page coerces light theme under dark appearance', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withUnencryptedCookie('appearance', 'dark')
        ->withUnencryptedCookie('theme', 'parchment')
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('data-theme="dracula"', false);
});

test('appearance page passes cross-mode theme through under system appearance', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withUnencryptedCookie('appearance', 'system')
        ->withUnencryptedCookie('theme', 'espresso')
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('data-theme="espresso"', false);
});

test('appearance page falls back to default theme for unknown cookie', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withUnencryptedCookie('theme', 'not-a-theme')
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('data-theme="daybreak"', false);
});
