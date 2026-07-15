<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#1E40AF">
        <meta name="description" content="{{ __('app.description') }}">

        <title>{{ __('app.name') }}</title>

        @if(app()->getLocale() === 'ar')
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Noto+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
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
    <body class="font-sans antialiased bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        <!-- Navigation -->
        <nav class="bg-white/90 dark:bg-gray-800/90 border-b border-sand-200/80 dark:border-gray-700/80 sticky top-0 z-50 no-print shadow-sm backdrop-blur-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <a href="/" class="flex items-center gap-3 group">
                        <div class="w-9 h-9 bg-gradient-to-br from-gov-600 via-gov-700 to-accent-600 rounded-xl flex items-center justify-center shadow-sm group-hover:shadow-md group-hover:scale-105 transition-all">
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gov-900 dark:text-gray-100 leading-tight">{{ __('app.name') }}</p>
                        </div>
                    </a>

                    <div class="flex items-center gap-1 sm:gap-2">
                        <!-- Theme Toggle -->
                        <div x-data="themeToggle()" class="relative">
                            <button @click="toggle" type="button"
                                class="relative inline-flex cursor-pointer items-center justify-center w-11 h-11 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-all duration-200 touch-target focus:outline-none focus:ring-2 focus:ring-gov-500"
                                :aria-label="isDark ? '{{ __('action.theme_light') }}' : '{{ __('action.theme_dark') }}'">
                                <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                </svg>
                            </button>
                        </div>

                        <x-language-switcher variant="minimal" />

                        @auth
                            <a href="{{ route('dashboard') }}" class="ui-btn-primary px-4 py-2 text-sm">
                                {{ __('nav.dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="hidden sm:inline-flex ui-soft-button px-4 py-2 text-sm">
                                {{ __('nav.login') }}
                            </a>
                            <a href="{{ route('register') }}" class="ui-btn-primary px-4 py-2 text-sm">
                                {{ __('nav.register') }}
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero-panel relative overflow-hidden">

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 lg:py-32">
                <div class="text-center max-w-3xl mx-auto">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 border border-white/20 text-white/80 text-xs sm:text-sm font-medium mb-6 sm:mb-8 backdrop-blur-sm">
                        <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                        {{ __('services.title') }}
                    </div>

                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight mb-4 sm:mb-6">
                        {{ __('app.tagline') }}
                    </h1>

                    <p class="text-base sm:text-lg text-blue-200 max-w-2xl mx-auto mb-8 sm:mb-10 leading-relaxed">
                        {{ __('app.description') }}
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="ui-soft-button w-full sm:w-auto px-8 py-3.5 text-sm sm:text-base text-center">
                                {{ __('nav.dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="ui-soft-button w-full sm:w-auto px-8 py-3.5 text-sm sm:text-base text-center">
                                {{ __('register.submit') }}
                            </a>
                            <a href="{{ route('login') }}" class="ui-ghost-button w-full sm:w-auto px-8 py-3.5 text-sm sm:text-base text-center">
                                {{ __('login.title') }}
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="py-12 sm:py-16 lg:py-20 bg-sand-50 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-14">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300 border border-gov-200 dark:border-gov-700 mb-4">
                        {{ __('services.title') }}
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-bold text-gov-900 dark:text-gray-100 mb-4">
                        {{ __('services.subtitle') }}
                    </h2>
                    <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400">
                        {{ __('stats.services') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    <!-- Service 1 -->
                    <a href="{{ route('login') }}" class="group section-surface rounded-2xl p-6 transition-all duration-300 service-card hover:border-gov-200 dark:hover:border-gov-700">
                        <div class="metric-icon w-14 h-14 bg-gov-50 dark:bg-gov-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-gov-600 dark:text-gov-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2">{{ __('services.population') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.population.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gov-600 dark:text-gov-400 group-hover:gap-2 transition-all">
                            {{ __('action.start') }}
                            <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <!-- Service 2 -->
                    <a href="{{ route('login') }}" class="group section-surface rounded-2xl p-6 transition-all duration-300 service-card hover:border-gov-200 dark:hover:border-gov-700">
                        <div class="metric-icon w-14 h-14 bg-blue-50 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2">{{ __('services.identity') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.identity.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gov-600 dark:text-gov-400 group-hover:gap-2 transition-all">
                            {{ __('action.start') }}
                            <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <!-- Service 3 -->
                    <a href="{{ route('login') }}" class="group section-surface rounded-2xl p-6 transition-all duration-300 service-card hover:border-gov-200 dark:hover:border-gov-700">
                        <div class="metric-icon w-14 h-14 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2">{{ __('services.passport') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.passport.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gov-600 dark:text-gov-400 group-hover:gap-2 transition-all">
                            {{ __('action.start') }}
                            <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <!-- Service 4 -->
                    <a href="{{ route('login') }}" class="group section-surface rounded-2xl p-6 transition-all duration-300 service-card hover:border-gov-200 dark:hover:border-gov-700">
                        <div class="metric-icon w-14 h-14 bg-amber-50 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2">{{ __('services.tax') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.tax.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gov-600 dark:text-gov-400 group-hover:gap-2 transition-all">
                            {{ __('action.start') }}
                            <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <!-- Service 5 -->
                    <a href="{{ route('login') }}" class="group section-surface rounded-2xl p-6 transition-all duration-300 service-card hover:border-gov-200 dark:hover:border-gov-700">
                        <div class="metric-icon w-14 h-14 bg-rose-50 dark:bg-rose-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2">{{ __('services.social') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.social.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gov-600 dark:text-gov-400 group-hover:gap-2 transition-all">
                            {{ __('action.start') }}
                            <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <!-- Service 6 -->
                    <a href="{{ route('login') }}" class="group section-surface rounded-2xl p-6 transition-all duration-300 service-card hover:border-gov-200 dark:hover:border-gov-700">
                        <div class="metric-icon w-14 h-14 bg-purple-50 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2">{{ __('services.justice') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.justice.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gov-600 dark:text-gov-400 group-hover:gap-2 transition-all">
                            {{ __('action.start') }}
                            <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-12 sm:py-16 bg-white/90 dark:bg-gray-800/90">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                    <div class="text-center">
                        <p class="text-3xl sm:text-4xl font-bold text-gov-600 dark:text-gov-400 mb-1">12+</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('stats.services') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl sm:text-4xl font-bold text-gov-600 dark:text-gov-400 mb-1">{{ \App\Models\User::count() }}+</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('stats.users') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl sm:text-4xl font-bold text-gov-600 dark:text-gov-400 mb-1">1,247</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('stats.completed') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl sm:text-4xl font-bold text-gov-600 dark:text-gov-400 mb-1">96%</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('stats.satisfaction') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="hero-panel relative overflow-hidden py-12 sm:py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-2xl sm:text-4xl font-bold text-white mb-4">
                    {{ __('app.tagline') }}
                </h2>
                <p class="text-base sm:text-lg text-blue-200 mb-8 max-w-2xl mx-auto">
                    {{ __('app.description') }}
                </p>
                @auth
                    <a href="{{ route('dashboard') }}" class="ui-soft-button px-8 py-3.5">
                        {{ __('nav.dashboard') }}
                    </a>
                @else
                    <a href="{{ route('register') }}" class="ui-soft-button px-8 py-3.5">
                        {{ __('register.submit') }}
                    </a>
                @endauth
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gov-900 dark:bg-gray-950 py-8 sm:py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8 mb-8">
                    <div class="col-span-2 md:col-span-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-white">{{ __('app.name') }}</span>
                        </div>
                        <p class="text-xs sm:text-sm text-gray-400 leading-relaxed">{{ __('app.description') }}</p>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">{{ __('footer.about') }}</h4>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('footer.about') }}</a></li>
                            <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('footer.contact') }}</a></li>
                            <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('footer.help') }}</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">{{ __('services.title') }}</h4>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('services.population') }}</a></li>
                            <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('services.identity') }}</a></li>
                            <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('services.tax') }}</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">{{ __('footer.terms') }}</h4>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('footer.privacy') }}</a></li>
                            <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('footer.terms') }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-white/10 pt-6 text-center">
                    <p class="text-xs sm:text-sm text-gray-500">
                        &copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('footer.rights') }}.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>
