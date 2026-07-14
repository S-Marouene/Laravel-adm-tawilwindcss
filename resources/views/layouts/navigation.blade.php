<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-sand-200 dark:border-gray-700 sticky top-0 z-50 no-print">
    <div style="margin-left: calc(-50vw + 50%); margin-right: calc(-50vw + 50%); width: 100vw;" class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <!-- Logo -->
            <div class="shrink-0 flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 sm:gap-3 group">
                    <!-- Tunisian Services Logo -->
                    <div class="w-9 h-9 bg-gradient-to-br from-gov-600 to-gov-800 rounded-xl flex items-center justify-center shadow-sm group-hover:shadow-md transition-all duration-200 group-hover:scale-105">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-bold text-gov-900 dark:text-gray-100 leading-tight">{{ __('app.name') }}</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight">{{ __('app.tagline') }}</p>
                    </div>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden sm:flex sm:items-center sm:gap-1">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('nav.dashboard') }}
                </x-nav-link>

                @auth
                    @if(Auth::user()->is_admin || Auth::user()->hasRole('admin'))
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            {{ __('nav.admin') }}
                        </x-nav-link>
                    @endif
                @endauth
            </div>

            <!-- Right Section -->
            <div class="flex items-center gap-1 sm:gap-2">
                <!-- Theme Toggle -->
                <div x-data="themeToggle()" class="relative">
                    <button @click="toggle" type="button"
                        class="relative p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-all duration-200 touch-target focus:outline-none focus:ring-2 focus:ring-gov-500"
                        :aria-label="isDark ? '{{ __('action.theme_light') }}' : '{{ __('action.theme_dark') }}'">
                        <!-- Sun (light mode) -->
                        <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <!-- Moon (dark mode) -->
                        <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                </div>

                <!-- Language Switcher -->
                <x-language-switcher variant="minimal" />

                @auth
                    <!-- Desktop User Menu -->
                    <div class="hidden sm:flex sm:items-center sm:gap-2">
                        <x-dropdown align="{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" width="48">
                            <x-slot name="trigger">
                                <button class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gov-600 dark:hover:text-gov-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-200 touch-target">
                                    <div class="w-8 h-8 bg-gov-100 dark:bg-gov-900/50 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-semibold text-gov-600 dark:text-gov-400">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                    </div>
                                    <span class="hidden lg:block">{{ Auth::user()->name }}</span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ __('nav.profile') }}
                                    </div>
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                            {{ __('nav.logout') }}
                                        </div>
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>

                    <!-- Mobile Hamburger -->
                    <button @click="open = !open" class="sm:hidden inline-flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gov-500 touch-target transition-all duration-200">
                        <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @else
                    <!-- Guest Actions -->
                    <div class="hidden sm:flex items-center gap-2">
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gov-600 dark:hover:text-gov-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-200">
                            {{ __('nav.login') }}
                        </a>
                        <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 rounded-lg shadow-sm hover:shadow transition-all duration-200">
                            {{ __('nav.register') }}
                        </a>
                    </div>
                    <a href="{{ route('login') }}" class="sm:hidden p-2 text-gray-500 hover:text-gov-600 touch-target">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="sm:hidden border-t border-sand-200 dark:border-gray-700 bg-white dark:bg-gray-800" style="display: none;">
        <div class="px-4 py-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    {{ __('nav.dashboard') }}
                </div>
            </x-responsive-nav-link>

            @auth
                @if(Auth::user()->is_admin || Auth::user()->hasRole('admin'))
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ __('nav.admin') }}
                        </div>
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        @auth
            <!-- Mobile User Section -->
            <div class="border-t border-sand-200 dark:border-gray-700 px-4 py-3">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-gov-100 dark:bg-gov-900/50 rounded-full flex items-center justify-center">
                        <span class="text-base font-semibold text-gov-600 dark:text-gov-400">{{ substr(Auth::user()->name, 0, 1) }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            {{ __('nav.profile') }}
                        </div>
                    </x-responsive-nav-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                {{ __('nav.logout') }}
                            </div>
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>
