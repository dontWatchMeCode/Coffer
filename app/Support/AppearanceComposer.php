<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\View\View;

class AppearanceComposer
{
    /**
     * Single source of truth for theme ids and their modes.
     * Keep in sync with THEMES in resources/js/composables/useAppearance.ts
     * and the pre-paint script in resources/views/app.blade.php.
     * The first entry per mode is that mode's default.
     *
     * @var array<string, 'light'|'dark'>
     */
    private const array THEMES = [
        'daybreak' => 'light',
        'parchment' => 'light',
        'glacier' => 'light',
        'sage' => 'light',
        'rose-pine-dawn' => 'light',
        'porcelain' => 'light',
        'dracula' => 'dark',
        'nord' => 'dark',
        'rose-pine' => 'dark',
        'espresso' => 'dark',
        'abyss' => 'dark',
        'gruvbox' => 'dark',
    ];

    private const array APPEARANCES = ['light', 'dark', 'system'];

    /** @return list<string> */
    private static function lightIds(): array
    {
        return array_keys(array_filter(
            self::THEMES,
            fn (string $mode): bool => $mode === 'light',
        ));
    }

    /** @return list<string> */
    private static function darkIds(): array
    {
        return array_keys(array_filter(
            self::THEMES,
            fn (string $mode): bool => $mode === 'dark',
        ));
    }

    /**
     * Resolve appearance at render time without retaining request data on the worker.
     */
    public function compose(View $view): void
    {
        $request = request();
        $appearance = $request->cookie('appearance') ?? 'system';
        $appearance = in_array($appearance, self::APPEARANCES, true)
            ? $appearance
            : 'system';

        $theme = $request->cookie('theme') ?? 'daybreak';
        $theme = is_string($theme) && array_key_exists($theme, self::THEMES)
            ? $theme
            : 'daybreak';

        // Coerce cross-mode pairs for explicit appearances so the SSR
        // markup matches what the client resolves (system stays as-is
        // because the OS theme is unknown server-side).
        if ($appearance === 'light' && self::THEMES[$theme] === 'dark') {
            $theme = 'daybreak';
        } elseif ($appearance === 'dark' && self::THEMES[$theme] === 'light') {
            $theme = 'dracula';
        }

        $view->with([
            'appearance' => $appearance,
            'theme' => $theme,
            'themeLightIds' => self::lightIds(),
            'themeDarkIds' => self::darkIds(),
        ]);
    }
}
