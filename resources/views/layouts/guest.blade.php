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

        <title>@yield('title', __('login.title')) - {{ __('app.name') }}</title>

        <!-- Fonts -->
        @if(app()->getLocale() === 'ar')
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
        @else
            <link rel="preconnect" href="https://fonts.bunny.net">
            <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet">
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            (function() {
                var theme = localStorage.getItem('theme');
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                }
                document.documentElement.classList.add('disable-transitions');
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        document.documentElement.classList.remove('disable-transitions');
                    });
                });
            })();
        </script>
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <!-- Left/Brand Panel - visible on desktop -->
            <div class="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-gov-600 via-gov-700 to-gov-900 overflow-hidden">
                <!-- Geometric pattern overlay -->
                <div class="absolute inset-0 opacity-[0.06]">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, white 1px, transparent 1px); background-size: 30px 30px;"></div>
                    <div class="absolute top-20 right-20 w-72 h-72 border border-white/10 rounded-full"></div>
                    <div class="absolute bottom-20 left-20 w-96 h-96 border border-white/10 rounded-full"></div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] border border-white/5 rounded-full"></div>
                    <!-- Abstract lines -->
                    <svg class="absolute bottom-0 left-0 w-full h-48 text-white/5" viewBox="0 0 1200 200" preserveAspectRatio="none">
                        <path d="M0,100 C300,0 600,200 900,100 C1000,50 1100,100 1200,80 L1200,200 L0,200 Z" fill="currentColor"/>
                    </svg>
                </div>

                <!-- Brand Content -->
                <div class="relative flex flex-col justify-center items-center w-full px-12 py-16">
                    <!-- Logo -->
                    <div class="mb-8">
                        <div class="w-20 h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl border border-white/10">
                            <svg class="w-12 h-12 text-white" viewBox="0 0 316 316" fill="currentColor">
                                <path d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 235.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 255.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125Z"/>
                            </svg>
                        </div>
                    </div>

                    <h1 class="text-3xl font-bold text-white text-center mb-3">{{ __('app.name') }}</h1>
                    <p class="text-base text-blue-200 text-center max-w-sm leading-relaxed">{{ __('app.description') }}</p>

                    <!-- Features list -->
                    <div class="mt-10 space-y-4 w-full max-w-sm">
                        <div class="flex items-center gap-3 text-white/80">
                            <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <span class="text-sm">{{ __('services.title') }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-white/80">
                            <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <span class="text-sm">{{ __('stats.satisfaction') }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-white/80">
                            <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                                </svg>
                            </div>
                            <span class="text-sm">{{ __('stats.users') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right/Auth Panel -->
            <div class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 py-8 bg-sand-50 dark:bg-gray-900 relative min-h-screen lg:min-h-0">
                <!-- Top bar with controls -->
                <div class="absolute top-4 right-4 sm:top-6 sm:right-6 flex items-center gap-2 z-10">
                    <x-language-switcher variant="minimal" />

                    <div x-data="themeToggle()" class="relative">
                        <button @click="toggle" type="button"
                            class="relative p-2.5 rounded-xl text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 hover:bg-white dark:hover:bg-gray-800/50 border border-transparent hover:border-gray-200 dark:hover:border-gray-700 transition-all duration-200 touch-target focus:outline-none focus:ring-2 focus:ring-gov-500"
                            :aria-label="isDark ? '{{ __('action.theme_light') }}' : '{{ __('action.theme_dark') }}'">
                            <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile brand (shown on small screens) -->
                <div class="lg:hidden flex flex-col items-center mb-6 mt-12">
                    <div class="w-14 h-14 bg-gradient-to-br from-gov-600 to-gov-800 rounded-2xl flex items-center justify-center shadow-lg mb-3">
                        <svg class="w-8 h-8 text-white" viewBox="0 0 316 316" fill="currentColor">
                            <path d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 245.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 255.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125Z"/>
                        </svg>
                    </div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('app.name') }}</h1>
                </div>

                <!-- Auth Card -->
                <div class="w-full max-w-md">
                    <div class="auth-card bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-sand-200 dark:border-gray-700 p-6 sm:p-8">
                        <!-- Page Header -->
                        <div class="mb-6">
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">
                                @yield('auth-title', __('login.title'))
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                @yield('auth-subtitle', __('login.subtitle'))
                            </p>
                        </div>

                        @yield('content')
                    </div>
                </div>

                <!-- Footer -->
                <p class="mt-6 text-xs text-gray-400 dark:text-gray-500 text-center">
                    &copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('footer.rights') }}
                </p>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
