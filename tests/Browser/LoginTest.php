<?php

use App\Models\User;

it('allows a user to log in from the browser', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $page = visit('/login');

    $page->assertSee('Email address')
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->press('Log in')
        ->assertPathIs('/dashboard')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs($user);
});
