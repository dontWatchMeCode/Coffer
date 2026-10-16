<?php

use Inertia\Testing\AssertableInertia as Assert;

test('home page renders welcome component', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome'),
        );
});
