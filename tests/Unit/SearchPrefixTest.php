<?php

use App\Concerns\EscapesLikeWildcards;
use App\Concerns\ParsesSearchPrefixes;

class SearchPrefixTester
{
    use ParsesSearchPrefixes;

    /**
     * @param  array<string, string>  $prefixMap
     * @return array{0: string, 1: list<string>}
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
    ['t: hello', ['hello', ['tasks']]],
    ['c: world', ['world', ['contacts']]],
    ['hello world', ['hello world', ['tasks', 'contacts']]],
    ['', ['', []]],
    ['x: hello', ['x: hello', ['tasks', 'contacts']]],
    ['T: Hello', ['Hello', ['tasks']]],
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
