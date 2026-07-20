@extends('admin.layouts.admin')

@section('title', __('nav.tooltips'))

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-rose-700 text-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('nav.tooltips') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Tooltip components with various placements, themes, and trigger options.</p>
        </div>
    </div>

    <!-- Direction Tooltips -->
    <div class="ynex-card p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Tooltip Directions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-lg mx-auto py-8">
            <div class="flex justify-center">
                <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                    <button class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gov-50 dark:hover:bg-gov-900/30 hover:text-gov-600 dark:hover:text-gov-400 transition-all cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    </button>
                    <div x-show="show" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1.5 text-xs font-medium text-white bg-gray-900 dark:bg-gray-700 rounded-lg shadow-lg whitespace-nowrap" style="display: none;">Tooltip on Top</div>
                    <span class="block text-center text-xs text-gray-500 dark:text-gray-400 mt-2">Top</span>
                </div>
            </div>
            <div class="flex justify-center">
                <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                    <button class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gov-50 dark:hover:bg-gov-900/30 hover:text-gov-600 dark:hover:text-gov-400 transition-all cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    </button>
                    <div x-show="show" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="absolute top-full left-1/2 -translate-x-1/2 mt-2 px-3 py-1.5 text-xs font-medium text-white bg-gray-900 dark:bg-gray-700 rounded-lg shadow-lg whitespace-nowrap" style="display: none;">Tooltip on Bottom</div>
                    <span class="block text-center text-xs text-gray-500 dark:text-gray-400 mt-2">Bottom</span>
                </div>
            </div>
            <div class="flex justify-center">
                <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                    <button class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gov-50 dark:hover:bg-gov-900/30 hover:text-gov-600 dark:hover:text-gov-400 transition-all cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </button>
                    <div x-show="show" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 translate-x-1" x-transition:enter-end="opacity-100 translate-x-0" x-cloak class="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-3 py-1.5 text-xs font-medium text-white bg-gray-900 dark:bg-gray-700 rounded-lg shadow-lg whitespace-nowrap" style="display: none;">Tooltip on Left</div>
                    <span class="block text-center text-xs text-gray-500 dark:text-gray-400 mt-2">Left</span>
                </div>
            </div>
            <div class="flex justify-center">
                <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                    <button class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gov-50 dark:hover:bg-gov-900/30 hover:text-gov-600 dark:hover:text-gov-400 transition-all cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                    <div x-show="show" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-x-1" x-transition:enter-end="opacity-100 translate-x-0" x-cloak class="absolute left-full top-1/2 -translate-y-1/2 ml-2 px-3 py-1.5 text-xs font-medium text-white bg-gray-900 dark:bg-gray-700 rounded-lg shadow-lg whitespace-nowrap" style="display: none;">Tooltip on Right</div>
                    <span class="block text-center text-xs text-gray-500 dark:text-gray-400 mt-2">Right</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Color Variants -->
    <div class="ynex-card p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Color Variants</h3>
        <div class="flex flex-wrap justify-center gap-6 py-8">
            <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                <button class="px-5 py-2.5 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all cursor-pointer">Dark</button>
                <div x-show="show" x-transition x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1.5 text-xs font-medium text-white bg-gray-900 dark:bg-gray-700 rounded-lg shadow-lg whitespace-nowrap" style="display: none;">Default dark tooltip</div>
            </div>
            <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                <button class="px-5 py-2.5 rounded-lg text-sm font-medium bg-gov-50 dark:bg-gov-900/40 text-gov-700 dark:text-gov-300 hover:bg-gov-100 dark:hover:bg-gov-900/60 transition-all cursor-pointer">Primary</button>
                <div x-show="show" x-transition x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1.5 text-xs font-medium text-white bg-gov-600 rounded-lg shadow-lg whitespace-nowrap" style="display: none;">Primary colored tooltip</div>
            </div>
            <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                <button class="px-5 py-2.5 rounded-lg text-sm font-medium bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition-all cursor-pointer">Success</button>
                <div x-show="show" x-transition x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 rounded-lg shadow-lg whitespace-nowrap" style="display: none;">Success colored tooltip</div>
            </div>
            <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                <button class="px-5 py-2.5 rounded-lg text-sm font-medium bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/60 transition-all cursor-pointer">Warning</button>
                <div x-show="show" x-transition x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1.5 text-xs font-medium text-white bg-amber-600 rounded-lg shadow-lg whitespace-nowrap" style="display: none;">Warning colored tooltip</div>
            </div>
            <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                <button class="px-5 py-2.5 rounded-lg text-sm font-medium bg-red-50 dark:bg-red-900/40 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/60 transition-all cursor-pointer">Danger</button>
                <div x-show="show" x-transition x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg shadow-lg whitespace-nowrap" style="display: none;">Danger colored tooltip</div>
            </div>
        </div>
    </div>

    <!-- Interactive Tooltips with Rich Content -->
    <div class="ynex-card p-6">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Rich Tooltips</h3>
        <div class="flex flex-wrap justify-center gap-8 py-8">
            <!-- Tooltip with icon + text -->
            <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                <button class="w-16 h-16 rounded-2xl bg-gradient-to-br from-gov-500 to-gov-700 text-white shadow-lg shadow-gov-500/20 flex items-center justify-center hover:scale-105 transition-transform cursor-pointer">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </button>
                <div x-show="show" x-transition x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-3 px-4 py-2.5 bg-white dark:bg-gray-700 rounded-xl shadow-xl border border-gray-100 dark:border-gray-600 whitespace-nowrap z-50" style="display: none;">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gov-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8"/></svg>
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">Send email to team</span>
                    </div>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Click to compose a new message</p>
                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-white dark:bg-gray-700 border-r border-b border-gray-100 dark:border-gray-600 rotate-45"></div>
                </div>
            </div>

            <!-- Image tooltip -->
            <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                <button class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-lg shadow-emerald-500/20 flex items-center justify-center hover:scale-105 transition-transform cursor-pointer">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
                <div x-show="show" x-transition x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-3 p-2 bg-white dark:bg-gray-700 rounded-xl shadow-xl border border-gray-100 dark:border-gray-600 z-50" style="display: none;">
                    <div class="w-32 h-20 rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white text-xs font-medium">Preview Image</div>
                    <p class="text-[10px] text-gray-500 mt-1 text-center">Screenshot preview</p>
                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-white dark:bg-gray-700 border-r border-b border-gray-100 dark:border-gray-600 rotate-45"></div>
                </div>
            </div>

            <!-- Stats tooltip -->
            <div x-data="{show: false}" @mouseenter="show = true" @mouseleave="show = false" class="relative">
                <button class="w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-700 text-white shadow-lg shadow-amber-500/20 flex items-center justify-center hover:scale-105 transition-transform cursor-pointer">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </button>
                <div x-show="show" x-transition x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-3 px-4 py-3 bg-white dark:bg-gray-700 rounded-xl shadow-xl border border-gray-100 dark:border-gray-600 z-50" style="display: none;">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center"><p class="text-lg font-bold text-gov-600">2.4k</p><p class="text-[10px] text-gray-500">Users</p></div>
                        <div class="text-center"><p class="text-lg font-bold text-emerald-600">85%</p><p class="text-[10px] text-gray-500">Growth</p></div>
                    </div>
                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-white dark:bg-gray-700 border-r border-b border-gray-100 dark:border-gray-600 rotate-45"></div>
                </div>
            </div>

            <!-- Click tooltip (toggled on click) -->
            <div x-data="{show: false}" class="relative">
                <button @click="show = !show" @click.outside="show = false" type="button" class="w-16 h-16 rounded-2xl bg-gradient-to-br from-rose-500 to-rose-700 text-white shadow-lg shadow-rose-500/20 flex items-center justify-center hover:scale-105 transition-transform cursor-pointer">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                </button>
                <div x-show="show" x-transition x-cloak class="absolute bottom-full left-1/2 -translate-x-1/2 mb-3 px-3 py-2 bg-white dark:bg-gray-700 rounded-xl shadow-xl border border-gray-100 dark:border-gray-600 z-50" style="display: none;">
                    <p class="text-xs text-gray-700 dark:text-gray-200 whitespace-nowrap">🔔 Click-triggered tooltip</p>
                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-white dark:bg-gray-700 border-r border-b border-gray-100 dark:border-gray-600 rotate-45"></div>
                </div>
                <span class="block text-center text-xs text-gray-500 dark:text-gray-400 mt-2">Click me</span>
            </div>
        </div>
    </div>
</div>
@endsection
