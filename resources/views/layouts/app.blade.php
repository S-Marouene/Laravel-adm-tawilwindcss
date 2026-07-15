<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#1E40AF">
        <meta name="description" content="{{ __('app.description') }}">

        <!-- Favicon -->
        @php
            $favicon = setting('app_favicon') ? Storage::url(setting('app_favicon')) . '?v=' . date('Ymd') : '/favicon.ico';
            $icon192 = setting('app_icon') ? Storage::url(setting('app_icon')) . '?v=' . date('Ymd') : '/favicon.ico';
        @endphp
        <link rel="icon" type="image/x-icon" href="{{ $favicon }}">
        <link rel="apple-touch-icon" href="{{ $icon192 }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ $icon192 }}">

        <title>@yield('title', setting('app_name', __('app.name'))) - {{ setting('app_name', config('app.name', __('app.name'))) }}</title>

        <!-- Fonts: Inter for French, Noto Sans Arabic for Arabic -->
        @if(app()->getLocale() === 'ar')
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Noto+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
        @else
            <link rel="preconnect" href="https://fonts.bunny.net">
            <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet">
        @endif

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Theme: restore saved preference before page renders (prevent FOUC) -->
        <script>
            (function() {
                var theme = localStorage.getItem('theme');
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    // Default is light mode
                }
                // Disable transitions temporarily to avoid flash animation
                document.documentElement.classList.add('disable-transitions');
                // Remove after first paint so user interactions get smooth transitions
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        document.documentElement.classList.remove('disable-transitions');
                    });
                });
            })();
        </script>
    </head>
    <body class="font-sans antialiased bg-sand-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        <!-- Skip Link for Accessibility -->
        <a href="#main-content" class="skip-link no-print">
            {{ app()->getLocale() === 'fr' ? 'Aller au contenu principal' : 'الانتقال إلى المحتوى الرئيسي' }}
        </a>

        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white/85 dark:bg-gray-800/85 border-b border-sand-200/80 dark:border-gray-700/80 shadow-sm backdrop-blur-xl">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main id="main-content">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
