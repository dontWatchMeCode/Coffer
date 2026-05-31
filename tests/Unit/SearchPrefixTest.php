<?php

use App\Concerns\EscapesLikeWildcards;
use App\Concerns\ParsesSearchPrefixes;

class SearchPrefixTester
{
    use ParsesSearchPrefixes;

    /**
     * @param  array<string, string>  $prefixMap
     * @return array{0: string, 1: list<string>, 2: string|null}
     */
    public function test(string $query, array $prefixMap): array
    {
        return $this->parseSearchPrefix($query, $prefixMap);
    }
}

class LikePatternTester
{
    use EscapesLikeWildcards;

    public function test(string $value): string
    {
        return $this->likePattern($value);
    }
}

it('parses prefixed search queries', function (string $query, array $expected) {
    $prefixMap = ['t' => 'tasks', 'c' => 'contacts'];
    $result = (new SearchPrefixTester)->test($query, $prefixMap);

    expect($result)->toBe($expected);
})->with([
    ['t: hello', ['hello', ['tasks'], null]],
    ['c: world', ['world', ['contacts'], null]],
    ['hello world', ['hello world', ['tasks', 'contacts'], null]],
    ['', ['', [], null]],
    ['x: hello', ['x: hello', ['tasks', 'contacts'], null]],
    ['T: Hello', ['Hello', ['tasks'], null]],
]);

it('escapes like wildcards', function (string $input, string $expected) {
    $result = (new LikePatternTester)->test($input);

    expect($result)->toBe($expected);
})->with([
    ['hello', '%hello%'],
    ['he%lo', '%he\\%lo%'],
    ['he_lo', '%he\\_lo%'],
    ['he\\lo', '%he\\\\lo%'],
]);

it('parses tag from search query', function (string $query, array $expected) {
    $prefixMap = ['t' => 'tasks', 'c' => 'contacts'];
    $result = (new SearchPrefixTester)->test($query, $prefixMap);

    expect($result)->toBe($expected);
})->with([
    'tag only' => ['hello #urgent', ['hello', ['tasks', 'contacts'], 'urgent']],
    'tag at start' => ['#work project', ['project', ['tasks', 'contacts'], 'work']],
    'tag with prefix' => ['t: fix #bug', ['fix', ['tasks'], 'bug']],
    'tag only no text' => ['#research', ['', ['tasks', 'contacts'], 'research']],
    'no tag' => ['hello world', ['hello world', ['tasks', 'contacts'], null]],
    'tag with hyphen' => ['#my-tag stuff', ['stuff', ['tasks', 'contacts'], 'my-tag']],
    'multiple tags strips all' => ['#work #urgent deploy', ['deploy', ['tasks', 'contacts'], 'work']],
    'inline hash not treated as tag' => ['hello#world', ['hello#world', ['tasks', 'contacts'], null]],
    'extra whitespace normalized' => ['hello  #tag  world', ['hello world', ['tasks', 'contacts'], 'tag']],
]);
