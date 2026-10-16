<?php

test('inline appearance assets use the nonce permitted by the production content security policy', function () {
    config(['app.debug' => false, 'csp.enabled' => true, 'csp.nonce_enabled' => true]);

    $response = $this->get(route('home'));

    $response->assertOk()->assertHeader('Content-Security-Policy');

    $nonce = app('csp-nonce');

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("'nonce-{$nonce}'");

    expect($response->getContent())
        ->toMatch('/<script\s+nonce="'.preg_quote($nonce, '/').'"\s*>/')
        ->toMatch('/<style\s+nonce="'.preg_quote($nonce, '/').'"\s*>/');

    $response->assertDontSee('fonts.bunny.net', false);
});

test('the app still renders when content security policy nonces are disabled', function () {
    config(['app.debug' => false, 'csp.enabled' => true, 'csp.nonce_enabled' => false]);
    app()->offsetUnset('csp-nonce');

    $this->get(route('home'))
        ->assertOk()
        ->assertHeader('Content-Security-Policy')
        ->assertDontSee('nonce=', false);
});
