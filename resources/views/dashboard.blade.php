<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gov-900 dark:text-gray-100">
                    {{ __('dashboard.title') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('dashboard.subtitle') }}</p>
            </div>
            <div class="hidden sm:flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300 border border-gov-200 dark:border-gov-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse"></span>
                    {{ __('status.processing') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Welcome Banner -->
            <div class="relative overflow-hidden bg-gradient-to-br from-gov-600 via-gov-700 to-gov-900 rounded-2xl shadow-lg mb-8 sm:mb-10">
                <!-- Decorative Pattern -->
                <div class="absolute inset-0 opacity-[0.03]">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, white 1px, transparent 1px); background-size: 30px 30px;"></div>
                </div>
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-blue-400/10 rounded-full translate-y-1/2 -translate-x-1/2 blur-3xl"></div>

                <div class="relative px-6 sm:px-8 py-8 sm:py-10">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-12 h-12 bg-white/15 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl sm:text-2xl font-bold text-white">{{ __('dashboard.welcome', ['name' => Auth::user()->name]) }}</h3>
                                    <p class="text-blue-200 text-sm">{{ __('dashboard.subtitle') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-gov-700 rounded-xl font-semibold text-sm hover:bg-blue-50 transition-all duration-200 shadow-sm hover:shadow-md active:scale-[0.98]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ __('services.title') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-8 sm:mb-10">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 p-4 sm:p-6 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 service-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gov-50 dark:bg-gov-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gov-600 dark:text-gov-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-medium bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            +5
                        </span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-gov-900 dark:text-gray-100 mb-0.5">12</p>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ __('stats.services') }}</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 p-4 sm:p-6 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 service-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-medium bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            +12%
                        </span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-gov-900 dark:text-gray-100 mb-0.5">{{\App\Models\User::count()}}</p>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ __('stats.users') }}</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 p-4 sm:p-6 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 service-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-gov-900 dark:text-gray-100 mb-0.5">1,247</p>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ __('stats.completed') }}</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 p-4 sm:p-6 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 service-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-amber-50 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-gov-900 dark:text-gray-100 mb-0.5">96%</p>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ __('stats.satisfaction') }}</p>
                </div>
            </div>

            <!-- Services Grid -->
            <div class="mb-8 sm:mb-10">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-gov-900 dark:text-gray-100">{{ __('services.title') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('services.subtitle') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                    <!-- Population & Civil Status -->
                    <a href="#" class="group relative bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 p-4 sm:p-5 transition-all duration-300 service-card">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gov-50 dark:bg-gov-900/30 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-gov-600 dark:text-gov-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gov-900 dark:text-gray-100 text-sm sm:text-base">{{ __('services.population') }}</h4>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ __('services.population.desc') }}</p>
                                <span class="inline-flex items-center text-xs font-medium text-gov-600 dark:text-gov-400 mt-2 group-hover:gap-2 transition-all duration-200">
                                    {{ __('action.start') }}
                                    <svg class="w-3.5 h-3.5 ml-1 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Identity Card -->
                    <a href="#" class="group relative bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 p-4 sm:p-5 transition-all duration-300 service-card">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gov-900 dark:text-gray-100 text-sm sm:text-base">{{ __('services.identity') }}</h4>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ __('services.identity.desc') }}</p>
                                <span class="inline-flex items-center text-xs font-medium text-gov-600 dark:text-gov-400 mt-2 group-hover:gap-2 transition-all duration-200">
                                    {{ __('action.start') }}
                                    <svg class="w-3.5 h-3.5 ml-1 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Passport -->
                    <a href="#" class="group relative bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 p-4 sm:p-5 transition-all duration-300 service-card">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gov-900 dark:text-gray-100 text-sm sm:text-base">{{ __('services.passport') }}</h4>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ __('services.passport.desc') }}</p>
                                <span class="inline-flex items-center text-xs font-medium text-gov-600 dark:text-gov-400 mt-2 group-hover:gap-2 transition-all duration-200">
                                    {{ __('action.start') }}
                                    <svg class="w-3.5 h-3.5 ml-1 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Tax -->
                    <a href="#" class="group relative bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 p-4 sm:p-5 transition-all duration-300 service-card">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-amber-50 dark:bg-amber-900/30 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gov-900 dark:text-gray-100 text-sm sm:text-base">{{ __('services.tax') }}</h4>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ __('services.tax.desc') }}</p>
                                <span class="inline-flex items-center text-xs font-medium text-gov-600 dark:text-gov-400 mt-2 group-hover:gap-2 transition-all duration-200">
                                    {{ __('action.start') }}
                                    <svg class="w-3.5 h-3.5 ml-1 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Social Security -->
                    <a href="#" class="group relative bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 p-4 sm:p-5 transition-all duration-300 service-card">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-rose-50 dark:bg-rose-900/30 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gov-900 dark:text-gray-100 text-sm sm:text-base">{{ __('services.social') }}</h4>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ __('services.social.desc') }}</p>
                                <span class="inline-flex items-center text-xs font-medium text-gov-600 dark:text-gov-400 mt-2 group-hover:gap-2 transition-all duration-200">
                                    {{ __('action.start') }}
                                    <svg class="w-3.5 h-3.5 ml-1 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Justice -->
                    <a href="#" class="group relative bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 p-4 sm:p-5 transition-all duration-300 service-card">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-purple-50 dark:bg-purple-900/30 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gov-900 dark:text-gray-100 text-sm sm:text-base">{{ __('services.justice') }}</h4>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ __('services.justice.desc') }}</p>
                                <span class="inline-flex items-center text-xs font-medium text-gov-600 dark:text-gov-400 mt-2 group-hover:gap-2 transition-all duration-200">
                                    {{ __('action.start') }}
                                    <svg class="w-3.5 h-3.5 ml-1 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- More Services Row -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mt-3">
                    <a href="#" class="flex items-center gap-3 p-3 sm:p-4 bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 service-card">
                        <div class="w-10 h-10 bg-teal-50 dark:bg-teal-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('services.land') }}</span>
                    </a>
                    <a href="#" class="flex items-center gap-3 p-3 sm:p-4 bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 service-card">
                        <div class="w-10 h-10 bg-orange-50 dark:bg-orange-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z"/>
                            </svg>
                        </div>
                        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('services.education') }}</span>
                    </a>
                    <a href="#" class="flex items-center gap-3 p-3 sm:p-4 bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 service-card">
                        <div class="w-10 h-10 bg-cyan-50 dark:bg-cyan-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('services.transport') }}</span>
                    </a>
                    <a href="#" class="flex items-center gap-3 p-3 sm:p-4 bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 service-card">
                        <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('services.documents') }}</span>
                    </a>
                </div>
            </div>

            <!-- Recent Activity & Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Recent Activity -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 sm:p-6 border-b border-sand-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base sm:text-lg font-bold text-gov-900 dark:text-gray-100 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ __('services.status') }}
                            </h3>
                            <a href="#" class="text-xs sm:text-sm font-medium text-gov-600 dark:text-gov-400 hover:text-gov-700 dark:hover:text-gov-300 transition-colors">
                                {{ __('action.view') }} {{ __('action.more') }}
                            </a>
                        </div>
                    </div>
                    <div class="divide-y divide-sand-100 dark:divide-gray-700">
                        <div class="flex items-start gap-3 sm:gap-4 p-4 sm:p-5 hover:bg-sand-50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 bg-gov-50 dark:bg-gov-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gov-600 dark:text-gov-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('services.identity') }}</p>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('services.identity.desc') }}</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                                {{ __('status.completed') }}
                            </span>
                        </div>
                        <div class="flex items-start gap-3 sm:gap-4 p-4 sm:p-5 hover:bg-sand-50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 bg-amber-50 dark:bg-amber-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('services.passport') }}</p>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('services.passport.desc') }}</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                {{ __('status.processing') }}
                            </span>
                        </div>
                        <div class="flex items-start gap-3 sm:gap-4 p-4 sm:p-5 hover:bg-sand-50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 bg-red-50 dark:bg-red-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('services.tax') }}</p>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('services.tax.desc') }}</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800">
                                {{ __('status.rejected') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-sand-200 dark:border-gray-700 p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-bold text-gov-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('action.more') }}
                    </h3>
                    <div class="space-y-3">
                        <a href="#" class="flex items-center gap-3 p-3 rounded-xl bg-sand-50 dark:bg-gray-700/50 hover:bg-gov-50 dark:hover:bg-gov-900/20 transition-colors group">
                            <div class="w-10 h-10 bg-gov-50 dark:bg-gov-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-gov-600 dark:text-gov-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('services.appointments') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('services.appointments.desc') }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-gov-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        <a href="#" class="flex items-center gap-3 p-3 rounded-xl bg-sand-50 dark:bg-gray-700/50 hover:bg-gov-50 dark:hover:bg-gov-900/20 transition-colors group">
                            <div class="w-10 h-10 bg-gov-50 dark:bg-gov-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-gov-600 dark:text-gov-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('services.documents') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('services.documents.desc') }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-gov-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        <a href="#" class="flex items-center gap-3 p-3 rounded-xl bg-sand-50 dark:bg-gray-700/50 hover:bg-gov-50 dark:hover:bg-gov-900/20 transition-colors group">
                            <div class="w-10 h-10 bg-gov-50 dark:bg-gov-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-gov-600 dark:text-gov-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('services.status') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('services.status.desc') }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-gov-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center py-6 border-t border-sand-200 dark:border-gray-700">
                <div class="flex flex-wrap justify-center gap-4 mb-3">
                    <a href="#" class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 hover:text-gov-600 dark:hover:text-gov-400 transition-colors">{{ __('footer.about') }}</a>
                    <a href="#" class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 hover:text-gov-600 dark:hover:text-gov-400 transition-colors">{{ __('footer.contact') }}</a>
                    <a href="#" class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 hover:text-gov-600 dark:hover:text-gov-400 transition-colors">{{ __('footer.help') }}</a>
                    <a href="#" class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 hover:text-gov-600 dark:hover:text-gov-400 transition-colors">{{ __('footer.privacy') }}</a>
                    <a href="#" class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 hover:text-gov-600 dark:hover:text-gov-400 transition-colors">{{ __('footer.terms') }}</a>
                </div>
                <p class="text-xs sm:text-sm text-gray-400 dark:text-gray-500">
                    &copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('footer.rights') }}.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
