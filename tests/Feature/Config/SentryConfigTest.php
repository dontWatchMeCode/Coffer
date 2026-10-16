<?php

use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Support\Env;

test('sentry dsn resolves with laravel dsn precedence over fallback', function (array $variables, ?string $expected) {
    $property = new ReflectionProperty(Env::class, 'repository');
    $original = $property->getValue(null);

    $repository = RepositoryBuilder::createWithNoAdapters()->addAdapter(ArrayAdapter::class)->make();
    foreach ($variables as $key => $value) {
        $repository->set($key, $value);
    }
    $property->setValue(null, $repository);

    try {
        $config = require config_path('sentry.php');
    } finally {
        $property->setValue(null, $original);
    }

    expect($config['dsn'])->toBe($expected);
})->with([
    'disabled by default' => [[], null],
    'legacy fallback' => [['SENTRY_DSN' => 'https://fallback@example.com/1'], 'https://fallback@example.com/1'],
    'laravel dsn wins' => [[
        'SENTRY_DSN' => 'https://fallback@example.com/1',
        'SENTRY_LARAVEL_DSN' => 'https://primary@example.com/2',
    ], 'https://primary@example.com/2'],
]);

test('sentry sample rates resolve from the environment', function (array $variables, mixed $traces, mixed $profiles) {
    $property = new ReflectionProperty(Env::class, 'repository');
    $original = $property->getValue(null);

    $repository = RepositoryBuilder::createWithNoAdapters()->addAdapter(ArrayAdapter::class)->make();
    foreach ($variables as $key => $value) {
        $repository->set($key, $value);
    }
    $property->setValue(null, $repository);

    try {
        $config = require config_path('sentry.php');
    } finally {
        $property->setValue(null, $original);
    }

    expect($config['traces_sample_rate'])->toBe($traces)
        ->and($config['profiles_sample_rate'])->toBe($profiles);
})->with([
    'disabled by default' => [[], null, null],
    'custom rates' => [[
        'SENTRY_TRACES_SAMPLE_RATE' => '0.25',
        'SENTRY_PROFILES_SAMPLE_RATE' => '0.5',
    ], 0.25, 0.5],
]);
