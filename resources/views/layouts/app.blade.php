@php
    $userThemePref = auth()->check() ? (auth()->user()->theme_preference ?? 'system') : 'system';
    if (!in_array($userThemePref, ['light', 'dark', 'system'])) {
        $userThemePref = 'system';
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="light"
      data-theme-preference="{{ $userThemePref }}"
      data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BuinSeguro CAD') }}</title>

        <!-- Script Anti-Destello (FOUC) — Buin Seguro CAD Design System v1.0 -->
        <script>
            (() => {
                try {
                    const stored = localStorage.getItem('cad-theme');
                    const serverPref = document.documentElement.dataset.themePreference;
                    const preference = ['light', 'dark', 'system'].includes(stored)
                        ? stored
                        : (['light', 'dark', 'system'].includes(serverPref) ? serverPref : 'system');
                    const isDark = preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
                    document.documentElement.dataset.themePreference = preference;
                } catch (_) {
                    document.documentElement.dataset.theme = 'light';
                }
            })();
        </script>

        <!-- Fonts & Icons (Inter + JetBrains Mono + FontAwesome) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places,visualization"></script>
    </head>
    <body class="font-sans antialiased bg-ui-app text-ui-fg selection:bg-brand-cobalt selection:text-brand-white">
        <div class="min-h-screen bg-ui-app text-ui-fg flex flex-col">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-ui-card border-b border-ui-border shadow-xs">
                    <div class="max-w-[1600px] mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>
        <livewire:incoming-call-popup />
    </body>
</html>
