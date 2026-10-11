<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme ?? 'daybreak' }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';
                const theme = '{{ $theme ?? "daybreak" }}';
                const lightThemes = @json($themeLightIds ?? ['daybreak']);
                const darkThemes = @json($themeDarkIds ?? ['dracula']);

                let isDark = appearance === 'dark';

                if (appearance === 'system') {
                    isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                }

                const root = document.documentElement;
                root.classList.toggle('dark', isDark);

                const pool = isDark ? darkThemes : lightThemes;
                root.dataset.theme = pool.includes(theme) ? theme : pool[0];
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #f4f3f8;
            }

            html[data-theme='parchment'] {
                background-color: #f5f0e6;
            }

            html[data-theme='glacier'] {
                background-color: #eef3f6;
            }

            html.dark {
                background-color: #282a36;
            }

            html.dark[data-theme='espresso'] {
                background-color: #201914;
            }

            html.dark[data-theme='abyss'] {
                background-color: #0e1620;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=ibm-plex-mono:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
