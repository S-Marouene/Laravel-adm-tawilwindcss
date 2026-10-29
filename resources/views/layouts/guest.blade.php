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
            $favicon = setting('app_favicon') ? Storage::url(setting('app_favicon')) . '?v=' . date('Ymd') : '/favicon.ico';
            $icon192 = setting('app_icon') ? Storage::url(setting('app_icon')) . '?v=' . date('Ymd') : '/images/logo-192.png';
        @endphp
        <link rel="icon" type="image/x-icon" href="{{ $favicon }}">
        <link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ $icon192 }}">

        <title>@yield('title', __('login.title')) - {{ __('app.name') }}</title>

        <!-- Fonts (Janna LT arabe auto-hébergée via app.css) -->
        @if(app()->getLocale() === 'ar')
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-styles')

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
    <body class="font-sans antialiased text-on-surface bg-background min-h-screen flex flex-col">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <!-- Left/Brand Panel - visible on desktop -->
            <div class="hidden lg:flex lg:w-1/2 relative bg-primary flex-col overflow-hidden">
                <div class="hero-panel absolute inset-0"></div>

                <!-- Official band overlay -->
                <div class="relative z-10 bg-inverse-surface text-inverse-on-surface text-body-sm border-b border-secondary/40">
                    <div class="px-8 xl:px-12 py-1.5 flex items-center gap-2 text-[11px] font-semibold tracking-wider">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-primary-container animate-alert-pulse"></span>
                        <span class="uppercase">RÉPUBLIQUE TUNISIENNE</span>
                        <span class="text-secondary-fixed opacity-60">|</span>
                        <span class="uppercase">Ministère de l'Intérieur</span>
                        <span class="font-arabic-crest text-sm tracking-normal text-surface-variant font-bold">الجمهورية التونسية - وزارة الداخلية</span>
                    </div>
                </div>

                <!-- Brand Content -->
                <div class="relative z-10 flex flex-col justify-center items-center flex-1 w-full px-12 py-12">
                    <img src="/images/logo.png" alt="Emblème de la Protection Civile — دائرة الحماية المدنية" class="w-20 h-20 object-contain mb-8 drop-shadow-lg">

                    <h1 class="text-headline-lg font-bold text-white uppercase tracking-tight text-center mb-2">{{ __('app.name') }}</h1>
                    <p class="font-arabic-crest text-2xl text-primary-fixed font-bold text-center mb-4">الديوان الوطني للحماية المدنية</p>
                    <p class="text-body-md text-white/85 text-center max-w-md leading-relaxed">{{ __('app.description') }}</p>

                    <!-- Features list -->
                    <div class="mt-10 space-y-3 w-full max-w-md">
                        <div class="flex items-center gap-3 text-white/85 text-body-sm">
                            <span class="w-9 h-9 bg-white/10 rounded flex items-center justify-center shrink-0 border border-white/10">
                                <span class="material-symbols-outlined text-lg" aria-hidden="true">domain_verification</span>
                            </span>
                            <span>{{ __('services.title') }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-white/85 text-body-sm">
                            <span class="w-9 h-9 bg-white/10 rounded flex items-center justify-center shrink-0 border border-white/10">
                                <span class="material-symbols-outlined text-lg" aria-hidden="true">verified_user</span>
                            </span>
                            <span>{{ __('stats.satisfaction') }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-white/85 text-body-sm">
                            <span class="w-9 h-9 bg-white/10 rounded flex items-center justify-center shrink-0 border border-white/10">
                                <span class="material-symbols-outlined text-lg" aria-hidden="true">group</span>
                            </span>
                            <span>{{ __('stats.users') }}</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Right/Auth Panel -->
            <div class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 py-10 bg-background relative min-h-screen lg:min-h-0">
                <!-- Top bar controls -->
                <div class="absolute top-4 end-4 sm:top-6 sm:end-6 flex items-center gap-2 z-10">
                    <x-language-switcher variant="minimal" />

                    <div x-data="themeToggle()" class="relative">
                        <button @click="toggle" type="button"
                            class="relative inline-flex cursor-pointer items-center justify-center w-10 h-10 rounded text-secondary hover:text-primary hover:bg-surface-container transition-colors touch-target"
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
                <div class="lg:hidden flex flex-col items-center mb-6 mt-10">
                    <a href="{{ url('/') }}" class="block mb-3">
                        <img src="/images/logo.png" alt="" class="w-13 h-13 object-contain">
                    </a>
                    <h1 class="text-headline-sm text-primary uppercase tracking-tight">{{ __('app.name') }}</h1>
                    <p class="text-[11px] text-secondary mt-1">{{ app()->getLocale() === 'ar' ? 'Office National de la Protection Civile' : 'الديوان الوطني للحماية المدنية' }}</p>
                </div>

                <!-- Auth Card -->
                <div class="w-full max-w-md">
                    <div class="auth-card bg-surface-container-lowest border border-surface-dim rounded shadow-sm p-6 sm:p-8">
                        <!-- Page Header -->
                        <div class="mb-6 pb-4 border-b border-surface-dim">
                            <span class="inline-block bg-primary text-on-primary text-label-caps font-label-caps px-2 py-0.5 rounded uppercase mb-2">
                                {{ app()->getLocale() === 'ar' ? 'ONPC' : 'Portail ONPC' }}
                            </span>
                            <h2 class="text-headline-md text-on-surface tracking-tight">
                                @yield('auth-title', __('login.title'))
                            </h2>
                            <p class="text-body-sm text-secondary mt-1">
                                @yield('auth-subtitle', __('login.subtitle'))
                            </p>
                        </div>

                        @yield('content')
                    </div>
                </div>

                <!-- Footer -->
                <p class="mt-6 text-xs text-secondary text-center max-w-md">
                    &copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('footer.rights') }}
                </p>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
