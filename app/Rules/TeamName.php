<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Routing\Route as RouteElement;
use Illuminate\Support\Facades\Route;
use Illuminate\Translation\PotentiallyTranslatedString;

class TeamName implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = strtolower(trim((string) $value));

        if (in_array($name, $this->reservedNames(), true)) {
            $fail('This team name is reserved and cannot be used.');
        }
    }

    /**
     * Get a list of all reserved names.
     *
     * Reserved names are derived from the application's registered route
     * prefixes so a team slug can never collide with a real top-level route,
     * plus a small fixed list of names reserved for application infrastructure.
     *
     * @return array<int, string>
     */
    protected function reservedNames(): array
    {
        return once(fn () => collect($this->routesPrefixes())
            ->merge($this->fixedReservedNames())
            ->unique()
            ->sort()
            ->values()
            ->toArray());
    }

    /**
     * Fixed reserved names that are not derived from routes but should never be
     * usable as team slugs because they are reserved for application use.
     *
     * @return array<int, string>
     */
    protected function fixedReservedNames(): array
    {
        return ['api', 'mcp'];
    }

    /**
     * Get a list of reserved names from the application's route prefixes.
     *
     * @return array<int, string>
     */
    protected function routesPrefixes(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->map(fn (RouteElement $route) => $route->uri)
            ->map(fn (string $uri): string => explode('/', $uri)[0])
            ->reject(fn (string $uri): bool => str_contains($uri, '{'))
            ->filter(fn (string $uri): bool => $uri !== '')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }
}
