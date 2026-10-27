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

                    {{-- Service 1 - شهادة الوقاية --}}
                    <a href="{{ route('prevention-requests.index') }}" class="group relative section-surface rounded-2xl p-6 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 border border-gray-200 dark:border-gray-700 block">
                        <div class="metric-icon w-14 h-14 bg-gov-50 dark:bg-gov-900/30 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-gov-600 dark:text-gov-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2">{{ __('services.prevention_certificate') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.prevention_certificate.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gov-600 dark:text-gov-400 group-hover:underline">
                            {{ __('services.access_service') }} &rarr;
                        </span>
                    </a>

                    {{-- Service 2 - التطوع في خدمة الحماية المدنية --}}
                    <div class="group relative section-surface rounded-2xl p-6 transition-all duration-300 opacity-80 cursor-not-allowed border border-dashed border-gray-300 dark:border-gray-600">
                        <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                {{ __('services.under_construction') }}
                            </span>
                        </div>
                        <div class="metric-icon w-14 h-14 bg-red-50 dark:bg-red-900/30 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2 pr-16">{{ __('services.volunteering') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.volunteering.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gray-400 dark:text-gray-500">
                            {{ __('services.coming_soon') }}
                        </span>
                    </div>

                    {{-- Service 3 - خدمات التكوين والرسكلة بمقابل --}}
                    <div class="group relative section-surface rounded-2xl p-6 transition-all duration-300 opacity-80 cursor-not-allowed border border-dashed border-gray-300 dark:border-gray-600">
                        <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                {{ __('services.under_construction') }}
                            </span>
                        </div>
                        <div class="metric-icon w-14 h-14 bg-blue-50 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2 pr-16">{{ __('services.training') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.training.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gray-400 dark:text-gray-500">
                            {{ __('services.coming_soon') }}
                        </span>
                    </div>

                    {{-- Service 4 - الحضور الوقائي --}}
                    <a href="{{ route('preventive-presence.index') }}" class="group relative section-surface rounded-2xl p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                        <div class="metric-icon w-14 h-14 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2">{{ __('services.preventive_presence') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.preventive_presence.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gov-600 dark:text-gov-400 group-hover:underline">
                            {{ __('services.access_service') }} &rarr;
                        </span>
                    </a>

                    {{-- Service 5 - المصادقة على مخطط التدخل الداخلي --}}
                    <div class="group relative section-surface rounded-2xl p-6 transition-all duration-300 opacity-80 cursor-not-allowed border border-dashed border-gray-300 dark:border-gray-600">
                        <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                {{ __('services.under_construction') }}
                            </span>
                        </div>
                        <div class="metric-icon w-14 h-14 bg-amber-50 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2 pr-16">{{ __('services.internal_intervention_plan') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.internal_intervention_plan.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gray-400 dark:text-gray-500">
                            {{ __('services.coming_soon') }}
                        </span>
                    </div>

                    {{-- Service 6 - المصادقة على ملف السلامة - البنايات المعدة للاستقبال العموم --}}
                    <div class="group relative section-surface rounded-2xl p-6 transition-all duration-300 opacity-80 cursor-not-allowed border border-dashed border-gray-300 dark:border-gray-600">
                        <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                {{ __('services.under_construction') }}
                            </span>
                        </div>
                        <div class="metric-icon w-14 h-14 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2 pr-16">{{ __('services.safety_file_public') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.safety_file_public.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gray-400 dark:text-gray-500">
                            {{ __('services.coming_soon') }}
                        </span>
                    </div>

                    {{-- Service 7 - المصادقة على ملف السلامة - المؤسسات الخطرة --}}
                    <div class="group relative section-surface rounded-2xl p-6 transition-all duration-300 opacity-80 cursor-not-allowed border border-dashed border-gray-300 dark:border-gray-600">
                        <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                {{ __('services.under_construction') }}
                            </span>
                        </div>
                        <div class="metric-icon w-14 h-14 bg-orange-50 dark:bg-orange-900/30 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2 pr-16">{{ __('services.safety_file_hazardous') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.safety_file_hazardous.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gray-400 dark:text-gray-500">
                            {{ __('services.coming_soon') }}
                        </span>
                    </div>

                    {{-- Service 8 - الإعلام عن إنجاز أشغال لا تستوجب رخصة بناء --}}
                    <div class="group relative section-surface rounded-2xl p-6 transition-all duration-300 opacity-80 cursor-not-allowed border border-dashed border-gray-300 dark:border-gray-600">
                        <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                {{ __('services.under_construction') }}
                            </span>
                        </div>
                        <div class="metric-icon w-14 h-14 bg-yellow-50 dark:bg-yellow-900/30 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2 pr-16">{{ __('services.works_notification') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.works_notification.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gray-400 dark:text-gray-500">
                            {{ __('services.coming_soon') }}
                        </span>
                    </div>

                    {{-- Service 9 - شهادة نجدة وإنقاذ --}}
                    <div class="group relative section-surface rounded-2xl p-6 transition-all duration-300 opacity-80 cursor-not-allowed border border-dashed border-gray-300 dark:border-gray-600">
                        <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                {{ __('services.under_construction') }}
                            </span>
                        </div>
                        <div class="metric-icon w-14 h-14 bg-rose-50 dark:bg-rose-900/30 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2 pr-16">{{ __('services.rescue_certificate') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.rescue_certificate.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gray-400 dark:text-gray-500">
                            {{ __('services.coming_soon') }}
                        </span>
                    </div>

                    {{-- Service 10 - التدخلات والعمليات والخدمات الخاصة بمقابل --}}
                    <div class="group relative section-surface rounded-2xl p-6 transition-all duration-300 opacity-80 cursor-not-allowed border border-dashed border-gray-300 dark:border-gray-600">
                        <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                {{ __('services.under_construction') }}
                            </span>
                        </div>
                        <div class="metric-icon w-14 h-14 bg-purple-50 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gov-900 dark:text-gray-100 mb-2 pr-16">{{ __('services.special_interventions') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('services.special_interventions.desc') }}</p>
                        <span class="inline-flex items-center text-sm font-medium text-gray-400 dark:text-gray-500">
                            {{ __('services.coming_soon') }}
                        </span>
                    </div>

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
                            <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('services.prevention_certificate') }}</a></li>
                            <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('services.volunteering') }}</a></li>
                            <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('services.training') }}</a></li>
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
