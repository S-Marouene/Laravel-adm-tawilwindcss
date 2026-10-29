<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#9e001f">
        <meta name="description" content="{{ __('app.description') }}">

        <!-- Favicon -->
        @php
            // Fallback locaux : emblème ONPC fourni dans design/ (logo.ico + logo.png)
            $favicon = setting('app_favicon') ? Storage::url(setting('app_favicon')) . '?v=' . date('Ymd') : '/favicon.ico';
        @endphp
        <link rel="icon" type="image/x-icon" href="{{ $favicon }}">
        <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32.png">
        <link rel="icon" type="image/png" sizes="64x64" href="/images/favicon-64.png">
        <link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/images/logo-192.png">
        <link rel="icon" type="image/png" sizes="512x512" href="/images/logo-512.png">

        <title>@yield('title', setting('app_name', __('app.name'))) - {{ setting('app_name', config('app.name', __('app.name'))) }}</title>

        <!-- Fonts: Public Sans (Janna LT arabe auto-hébergée via app.css) -->
        @if(app()->getLocale() === 'ar')
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
        @endif

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-styles')

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
    <body class="font-sans antialiased bg-background text-on-surface min-h-screen flex flex-col selection:bg-primary selection:text-on-primary">
        <!-- Skip Link for Accessibility -->
        <a href="#main-content" class="skip-link no-print">
            {{ app()->getLocale() === 'ar' ? 'الانتقال إلى المحتوى الرئيسي' : 'Aller au contenu principal' }}
        </a>

        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')
            <div class="h-[2px] bg-primary" aria-hidden="true"></div>

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-surface-container-low border-b border-surface-dim">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main id="main-content" class="flex-1">
                {{ $slot }}
            </main>

            @include('partials.footer')
        </div>
    </body>
</html>
