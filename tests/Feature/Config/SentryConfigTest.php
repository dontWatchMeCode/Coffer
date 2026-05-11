<?php

test('sentry is disabled unless a dsn is configured', function () {
    expect(config('sentry.dsn'))->toBeNull()
        ->and(config('sentry.traces_sample_rate'))->toBeNull()
        ->and(config('sentry.profiles_sample_rate'))->toBeNull();
});
