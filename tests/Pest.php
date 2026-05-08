<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\Webpage;
use Pest\Browser\Playwright\Playwright;
use Tests\TestCase;

pest()->beforeEach(function () {
    Playwright::setTimeout(3_000);
})->in('Browser');

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extends(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

pest()->group('browser')->in('Browser');

pest()->beforeEach(function () {
    $this->withoutVite();
})->in('Feature');

pest()->beforeEach(function () {
    $hot = __DIR__.'/../public/hot';
    $backup = $hot.'.backup';

    // Self-healing: restore orphaned backup from a previous crashed run
    if (file_exists($backup)) {
        rename($backup, $hot);
    }

    if (file_exists($hot)) {
        rename($hot, $backup);
    }
})->afterEach(function () {
    $hot = __DIR__.'/../public/hot';
    $backup = $hot.'.backup';

    if (file_exists($backup)) {
        rename($backup, $hot);
    }
})->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function waitForBrowserText(Webpage|AwaitableWebpage $page, string $text, int|float $seconds = 5): Webpage|AwaitableWebpage
{
    $deadline = microtime(true) + $seconds;
    $encodedText = json_encode($text, JSON_THROW_ON_ERROR);

    while (microtime(true) < $deadline) {
        if ($page->script("document.body.innerText.includes({$encodedText})") === true) {
            return $page;
        }

        usleep(100_000);
    }

    expect(false)->toBeTrue("Expected to see text [{$text}] within {$seconds} seconds.");

    return $page;
}

function waitForBrowserPath(Webpage|AwaitableWebpage $page, string $path, int|float $seconds = 5): Webpage|AwaitableWebpage
{
    $deadline = microtime(true) + $seconds;

    while (microtime(true) < $deadline) {
        if (parse_url($page->url(), PHP_URL_PATH) === $path) {
            return $page;
        }

        usleep(100_000);
    }

    expect(parse_url($page->url(), PHP_URL_PATH))->toBe($path);

    return $page;
}
