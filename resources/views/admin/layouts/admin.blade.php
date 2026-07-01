<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#1E40AF">

        <title>@yield('title', 'Administration') - {{ setting('app_name', __('app.name')) }}</title>

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
    <body class="font-sans antialiased bg-sand-50 dark:bg-gray-900">
        <div class="min-h-screen">
            <!-- Admin Top Bar -->
            <header class="bg-white dark:bg-gray-800 border-b border-sand-200 dark:border-gray-700 shadow-sm sticky top-0 z-40 no-print">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
                                <div class="w-8 h-8 bg-gradient-to-br from-gov-600 to-gov-800 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-md transition-all group-hover:scale-105">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                                    </svg>
                                </div>
                                <span class="text-lg font-semibold text-gov-900 dark:text-gray-100 hidden sm:block">{{ __('nav.admin') }}</span>
                            </a>
                        </div>

                        <div class="flex items-center gap-1 sm:gap-2">
                            <!-- Theme Toggle -->
                            <div x-data="themeToggle()" class="relative">
                                <button @click="toggle" type="button"
                                    class="relative p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-all duration-200 touch-target focus:outline-none focus:ring-2 focus:ring-gov-500"
                                    :aria-label="isDark ? '{{ __('action.theme_light') }}' : '{{ __('action.theme_dark') }}'">
                                    <!-- Sun (show in light mode) -->
                                    <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <!-- Moon (show in dark mode) -->
                                    <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                    </svg>
                                </button>
                            </div>

                            <x-language-switcher variant="minimal" />

                            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gov-600 dark:text-gray-400 dark:hover:text-gov-400 transition-colors touch-target px-2 py-2">
                                <span class="hidden sm:inline">{{ __('action.back') }}</span>
                                <svg class="w-5 h-5 sm:hidden inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                            </a>

                            <x-dropdown align="{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" width="48">
                                <x-slot name="trigger">
                                    <button class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gov-600 dark:hover:text-gov-400 transition-colors touch-target px-2 py-2">
                                        <div class="w-8 h-8 bg-gov-100 dark:bg-gov-900/50 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-semibold text-gov-600 dark:text-gov-400">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                        </div>
                                        <span class="hidden sm:block ms-2">{{ Auth::user()->name }}</span>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile.edit')">
                                        {{ __('nav.profile') }}
                                    </x-dropdown-link>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')"
                                                onclick="event.preventDefault(); this.closest('form').submit();">
                                            {{ __('nav.logout') }}
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>
                </div>
            </header>

            <div class="flex">
                <!-- Admin Sidebar -->
                <aside class="w-64 bg-white dark:bg-gray-800 border-r border-sand-200 dark:border-gray-700 min-h-[calc(100vh-4rem)] hidden lg:block flex-shrink-0">
                    <nav class="p-4 space-y-1">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            {{ __('nav.dashboard') }}
                        </a>

                        <div class="pt-4 pb-2">
                            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('nav.services') }}</p>
                        </div>

                        <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                            </svg>
                            {{ __('admin.users') }}
                        </a>

                        <a href="{{ route('admin.roles.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.roles.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            {{ __('admin.roles') }}
                        </a>

                        <a href="{{ route('admin.permissions.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.permissions.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            {{ __('admin.permissions') }}
                        </a>

                        <div class="pt-4 pb-2">
                            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('admin.logs') }}</p>
                        </div>

                        <a href="{{ route('admin.activity-logs.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.activity-logs.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            {{ __('admin.logs') }}
                        </a>

                        <div class="pt-4 pb-2">
                            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('admin.configuration') }}</p>
                        </div>

                        <a href="{{ route('admin.settings.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.settings.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ __('admin.settings') }}
                        </a>
                    </nav>
                </aside>

                <!-- Mobile Sidebar Toggle -->
                <div x-data="{ open: false }" class="lg:hidden">
                    <button @click="open = !open" class="fixed bottom-4 {{ app()->getLocale() === 'ar' ? 'left-4' : 'right-4' }} z-50 w-12 h-12 bg-gov-600 text-white rounded-full shadow-lg flex items-center justify-center hover:bg-gov-700 transition-all hover:scale-105 active:scale-95 touch-target">
                        <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false" class="fixed inset-0 z-40 lg:hidden" style="display: none;">
                        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
                        <div class="fixed inset-y-0 {{ app()->getLocale() === 'ar' ? 'right-0' : 'left-0' }} w-64 bg-white dark:bg-gray-800 shadow-xl">
                            <nav class="p-4 pt-8 space-y-1">
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    {{ __('nav.dashboard') }}
                                </a>
                                <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                                    {{ __('admin.users') }}
                                </a>
                                <a href="{{ route('admin.roles.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.roles.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    {{ __('admin.roles') }}
                                </a>
                                <a href="{{ route('admin.permissions.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.permissions.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    {{ __('admin.permissions') }}
                                </a>
                                <a href="{{ route('admin.activity-logs.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.activity-logs.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    {{ __('admin.logs') }}
                                </a>
                                <a href="{{ route('admin.settings.index') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.settings.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-gray-700 dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ __('admin.settings') }}
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8 min-w-0">
                    @if (session('success'))
                        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-medium text-green-800 dark:text-green-300">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-medium text-red-800 dark:text-red-300">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
