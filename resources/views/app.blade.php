<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="neutral">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name') }}</title>
        <script>
            (() => {
                const root = document.documentElement;
                let name = 'neutral';
                let mode = 'light';

                try {
                    name = window.localStorage.getItem('theme.name') || name;
                    mode = window.localStorage.getItem('theme.mode') || mode;
                } catch {
                    // Ignore storage access failures and keep defaults.
                }

                root.dataset.theme = name;
                root.classList.toggle('dark', mode === 'dark');
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="min-h-screen bg-background font-sans antialiased">
        @inertia
    </body>
</html>
