<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#1E40AF">

        <!-- Favicon -->
        @php
            $favicon = setting('app_favicon') ? Storage::url(setting('app_favicon')) . '?v=' . date('Ymd') : '/favicon.ico';
            $icon192 = setting('app_icon') ? Storage::url(setting('app_icon')) . '?v=' . date('Ymd') : '/images/logo-192.png';
        @endphp
        <link rel="icon" type="image/x-icon" href="{{ $favicon }}">
        <link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ $icon192 }}">

        <title>@yield('title', 'Administration') - {{ setting('app_name', __('app.name')) }}</title>

        @if(app()->getLocale() !== 'ar')
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
    <body class="font-sans antialiased bg-surface-container-low dark:bg-surface-dim text-on-surface overflow-x-hidden">
        <div class="min-h-screen flex w-full overflow-x-hidden flex-col lg:flex-row" x-data="sidebarManager()">
            <!-- Bandeau officiel (full width) -->
            <div class="w-full">
                @include('partials.super-band')
            </div>
            <div class="flex w-full flex-1 min-h-0">
            <!-- Admin Sidebar (full height from top of page) -->
            <aside class="hidden lg:block ynex-sidebar border-r min-h-screen flex-shrink-0 transition-all duration-300 ease-in-out overflow-hidden" :class="[sidebarWidth, { 'sidebar-collapsed': collapsed }]">
                <!-- Sidebar Logo -->
                <div x-show="!collapsed" class="px-4 pt-4 pb-2">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                        <img src="/images/logo.png" alt="" class="w-8 h-8 object-contain flex-shrink-0">
                        <span x-show="!collapsed" class="leading-tight">
                            <span class="block text-[11px] font-label-caps uppercase tracking-wider text-secondary">{{ app()->getLocale() === 'ar' ? 'الداخلية' : "Ministère Int." }}</span>
                            <span class="block text-sm font-extrabold text-primary uppercase tracking-tight">{{ setting('app_name', __('app.name')) }}</span>
                        </span>
                    </a>
                </div>
                <div x-show="collapsed" class="px-3 pt-4 pb-2 flex justify-center">
                    <img src="/images/logo.png" alt="" class="w-8 h-8 object-contain">
                </div>
                <div class="mx-3 mt-2 mb-1 bg-inverse-surface text-inverse-on-surface rounded p-2 text-center" x-show="!collapsed">
                    <span class="block text-[10px] font-bold uppercase tracking-widest text-inverse-primary">Salle des Opérations</span>
                    <span class="block text-[10px] text-surface-dim">{{ auth()->user()?->is_admin ? 'Console Administrateur' : 'Poste de Travail' }}</span>
                </div>

                <!-- Sidebar Navigation -->
                <nav class="p-3 space-y-0.5 ynex-sidebar-scroll" style="height: calc(100vh - 5rem); overflow-y: auto;">
                    <a href="{{ route('admin.dashboard') }}" data-tooltip="{{ __('nav.dashboard') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span x-show="!collapsed" class="truncate">{{ __('nav.dashboard') }}</span>
                    </a>

                    <div class="pt-4 pb-1" x-show="!collapsed">
                        <p class="px-3 sidebar-divider text-xs font-semibold uppercase tracking-widest">{{ __('nav.services') }}</p>
                    </div>

                    <a href="{{ route('admin.users.index') }}" data-tooltip="{{ __('admin.users') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                        </svg>
                        <span x-show="!collapsed" class="truncate">{{ __('admin.users') }}</span>
                    </a>

                    <a href="{{ route('admin.roles.index') }}" data-tooltip="{{ __('admin.roles') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span x-show="!collapsed" class="truncate">{{ __('admin.roles') }}</span>
                    </a>

                    <a href="{{ route('admin.permissions.index') }}" data-tooltip="{{ __('admin.permissions') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <span x-show="!collapsed" class="truncate">{{ __('admin.permissions') }}</span>
                    </a>

                    <!-- Advanced UI (Collapsible Submenu) -->
                    <div class="pt-4 pb-1" x-show="!collapsed">
                        <p class="px-3 sidebar-divider text-xs font-semibold uppercase tracking-widest">{{ __('nav.advanced_ui') }}</p>
                    </div>

                    <div x-data="{ advancedOpen: {{ request()->routeIs('admin.advanced-ui.*') ? 'true' : 'false' }} }" class="space-y-0.5">
                        <button @click="advancedOpen = !advancedOpen; if(collapsed) collapsed = false" data-tooltip="{{ __('nav.advanced_ui') }}" class="sidebar-nav-link flex items-center justify-between w-full gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.advanced-ui.*') ? 'active' : '' }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                                <span x-show="!collapsed" class="truncate">{{ __('nav.advanced_ui') }}</span>
                            </div>
                            <svg x-show="!collapsed" class="w-4 h-4 flex-shrink-0 text-outline transition-transform duration-200" :class="{ 'rotate-180': advancedOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="advancedOpen || collapsed" class="space-y-0.5 {{ app()->getLocale() === 'ar' ? 'pr-4' : 'pl-4' }}">
                            <a href="{{ route('admin.advanced-ui.stepper') }}" data-tooltip="{{ __('nav.stepper') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.advanced-ui.stepper') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/>
                                </svg>
                                <span x-show="!collapsed" class="truncate">{{ __('nav.stepper') }}</span>
                            </a>
                            <a href="{{ route('admin.advanced-ui.modals') }}" data-tooltip="{{ __('nav.modals') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.advanced-ui.modals') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M3 10v11h18V10"/>
                                </svg>
                                <span x-show="!collapsed" class="truncate">{{ __('nav.modals') }}</span>
                            </a>
                            <a href="{{ route('admin.advanced-ui.accordions') }}" data-tooltip="{{ __('nav.accordions') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.advanced-ui.accordions') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                                <span x-show="!collapsed" class="truncate">{{ __('nav.accordions') }}</span>
                            </a>
                            <a href="{{ route('admin.advanced-ui.tabs') }}" data-tooltip="{{ __('nav.tabs') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.advanced-ui.tabs') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                                </svg>
                                <span x-show="!collapsed" class="truncate">{{ __('nav.tabs') }}</span>
                            </a>
                            <a href="{{ route('admin.advanced-ui.carousel') }}" data-tooltip="{{ __('nav.carousel') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.advanced-ui.carousel') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                                <span x-show="!collapsed" class="truncate">{{ __('nav.carousel') }}</span>
                            </a>
                            <a href="{{ route('admin.advanced-ui.tooltips') }}" data-tooltip="{{ __('nav.tooltips') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.advanced-ui.tooltips') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span x-show="!collapsed" class="truncate">{{ __('nav.tooltips') }}</span>
                            </a>
                            <a href="{{ route('admin.advanced-ui.dropdowns') }}" data-tooltip="{{ __('nav.dropdowns') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.advanced-ui.dropdowns') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                                <span x-show="!collapsed" class="truncate">{{ __('nav.dropdowns') }}</span>
                            </a>
                            <a href="{{ route('admin.advanced-ui.sweet-alerts') }}" data-tooltip="{{ __('nav.sweet_alerts') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.advanced-ui.sweet-alerts') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span x-show="!collapsed" class="truncate">{{ __('nav.sweet_alerts') }}</span>
                            </a>
                        </div>
                    </div>

                    <div class="pt-4 pb-1" x-show="!collapsed">
                        <p class="px-3 sidebar-divider text-xs font-semibold uppercase tracking-widest">{{ __('admin.ref_data') }}</p>
                    </div>

                    {{-- Prevention Dossiers --}}
                    @can('view prevention-dossiers')
                    <a href="{{ route('admin.prevention-dossiers.index') }}" data-tooltip="Prevention Dossiers" class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.prevention-dossiers.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span x-show="!collapsed" class="truncate">Prevention Dossiers</span>
                    </a>
                    @endcan

                    {{-- Rescue & Relief Certificates --}}
                    @can('view rescue-certificates')
                    <a href="{{ route('admin.rescue-certificates.index') }}" data-tooltip="Rescue Certificates" class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.rescue-certificates.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1h-1m1 1h1M4 20h16a1 1 0 001-1V9a1 1 0 00-1-1h-4.586l-2.707-2.707A1 1 0 0012.414 5H4a1 1 0 00-1 1v13a1 1 0 001 1z"/>
                        </svg>
                        <span x-show="!collapsed" class="truncate">Rescue Certificates</span>
                    </a>
                    @endcan

                    {{-- Preventive Presence --}}
                    @can('view preventive-presence')
                    <a href="{{ route('admin.preventive-presence.index') }}" data-tooltip="Preventive Presence" class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.preventive-presence.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span x-show="!collapsed" class="truncate">Preventive Presence</span>
                    </a>
                    @endcan

                    {{-- Reference data: collapsible submenu --}}
                    <div x-data="{ refOpen: {{ request()->routeIs('admin.governorates.*', 'admin.delegations.*', 'admin.building-types.*', 'admin.building-activities.*', 'admin.document-types.*') ? 'true' : 'false' }} }" class="space-y-0.5">
                        <button @click="refOpen = !refOpen; if(collapsed) collapsed = false" data-tooltip="{{ __('admin.ref_data') }}" class="sidebar-nav-link flex items-center justify-between w-full gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.governorates.*', 'admin.delegations.*', 'admin.building-types.*', 'admin.building-activities.*', 'admin.document-types.*') ? 'active' : '' }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                </svg>
                                <span x-show="!collapsed" class="truncate">{{ __('admin.ref_data') }}</span>
                            </div>
                            <svg x-show="!collapsed" class="w-4 h-4 flex-shrink-0 text-outline transition-transform duration-200" :class="{ 'rotate-180': refOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="refOpen || collapsed" class="space-y-0.5 {{ app()->getLocale() === 'ar' ? 'pr-4' : 'pl-4' }}">
                            <a href="{{ route('admin.governorates.index') }}" data-tooltip="{{ __('admin.governorates') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.governorates.*') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span x-show="!collapsed" class="truncate">{{ __('admin.governorates') }}</span>
                            </a>
                            <a href="{{ route('admin.delegations.index') }}" data-tooltip="{{ __('admin.delegations') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.delegations.*') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                <span x-show="!collapsed" class="truncate">{{ __('admin.delegations') }}</span>
                            </a>
                            <a href="{{ route('admin.building-types.index') }}" data-tooltip="{{ __('admin.building_types') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.building-types.*') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <span x-show="!collapsed" class="truncate">{{ __('admin.building_types') }}</span>
                            </a>
                            <a href="{{ route('admin.building-activities.index') }}" data-tooltip="{{ __('admin.building_activities') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.building-activities.*') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <span x-show="!collapsed" class="truncate">{{ __('admin.building_activities') }}</span>
                            </a>
                            <a href="{{ route('admin.document-types.index') }}" data-tooltip="{{ __('admin.document_types') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.document-types.*') ? 'active' : '' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <span x-show="!collapsed" class="truncate">{{ __('admin.document_types') }}</span>
                            </a>
                        </div>
                    </div>

                    <div class="pt-4 pb-1" x-show="!collapsed">
                        <p class="px-3 sidebar-divider text-xs font-semibold uppercase tracking-widest">{{ __('admin.logs') }}</p>
                    </div>

                    <a href="{{ route('admin.activity-logs.index') }}" data-tooltip="{{ __('admin.logs') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span x-show="!collapsed" class="truncate">{{ __('admin.logs') }}</span>
                    </a>

                    <div class="pt-4 pb-1" x-show="!collapsed">
                        <p class="px-3 sidebar-divider text-xs font-semibold uppercase tracking-widest">{{ __('admin.configuration') }}</p>
                    </div>

                    <a href="{{ route('admin.theme-settings.index') }}" data-tooltip="{{ __('admin.theme_settings') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.theme-settings.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                        <span x-show="!collapsed" class="truncate">{{ __('admin.theme_settings') }}</span>
                    </a>

                    <a href="{{ route('admin.settings.index') }}" data-tooltip="{{ __('admin.settings') }}" class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span x-show="!collapsed" class="truncate">{{ __('admin.settings') }}</span>
                    </a>
                </nav>
            </aside>

            <!-- Right Content Area: Topbar + Main Content -->
            <div class="flex-1 min-w-0 flex flex-col">
                <!-- Admin Top Bar (Ynex Design) -->
                <header class="app-header flex-shrink-0">
                    <nav class="main-header" aria-label="Global">
                        <div class="main-header-container">

                            <!-- Left Section -->
                            <div class="header-content-left">
                            <!-- Sidebar Toggle (desktop collapse) -->
                                <div class="header-element md:px-[0.325rem] !items-center hidden lg:inline-flex">
                                    <button @click="toggle()" type="button" aria-label="Toggle sidebar" class="relative flex cursor-pointer items-center justify-center w-9 h-9 rounded-lg text-secondary dark:text-gray-400 hover:text-gov-600 dark:hover:text-gov-400 hover:bg-surface-container dark:hover:bg-gray-700/60 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gov-500/30 group">
                                        <span class="animated-arrow" :class="{ 'active': collapsed }">
                                            <span></span>
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Right Section -->
                            <div class="header-content-right admin-header-actions" dir="ltr">
                                <!-- Home Icon -->
                                <div class="header-element admin-header-secondary py-[1rem] md:px-[0.65rem] px-2">
                                    <a href="{{ route('dashboard') }}" class="inline-flex cursor-pointer flex-shrink-0 justify-center items-center w-9 h-9 rounded-lg text-secondary dark:text-gray-400 hover:text-gov-600 dark:hover:text-gov-400 hover:bg-surface-container dark:hover:bg-gray-700/60 transition-all duration-200" aria-label="{{ __('nav.dashboard') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                    </a>
                                </div>
                                <!-- Search -->
                                <div class="header-element admin-header-secondary py-[1rem] md:px-[0.65rem] px-2" x-data="searchModal()">
                                    <button @click="open = true" type="button" class="inline-flex cursor-pointer flex-shrink-0 justify-center items-center w-9 h-9 rounded-lg text-secondary dark:text-gray-400 hover:text-gov-600 dark:hover:text-gov-400 hover:bg-surface-container dark:hover:bg-gray-700/60 transition-all duration-200">
                                        <i class="bx bx-search-alt-2 header-link-icon"></i>
                                    </button>

                                    <!-- Search Modal -->
                                    <template x-teleport="body">
                                        <div x-show="open" @click="open = false" class="search-modal-overlay" style="display: none;">
                                            <div class="search-modal-content" @click.outside="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                                                <div class="flex items-center px-4">
                                                    <i class="bx bx-search text-outline text-xl"></i>
                                                    <input type="text" x-model="query" @input="search" placeholder="{{ app()->getLocale() === 'ar' ? 'بحث...' : 'Rechercher...' }}" class="search-modal-input">
                                                    <button @click="open = false" type="button" class="inline-flex cursor-pointer items-center justify-center w-9 h-9 rounded-lg text-outline hover:text-secondary dark:hover:text-gray-200 hover:bg-surface-container dark:hover:bg-gray-700/60">
                                                        <i class="bx bx-x text-xl"></i>
                                                    </button>
                                                </div>
                                                <div class="search-modal-results" x-show="query.length > 0" style="display: none;">
                                                    <template x-for="result in results" :key="result.title">
                                                        <a :href="result.url" class="search-result-item" @click="open = false">
                                                            <i :class="result.icon + ' text-outline text-lg'"></i>
                                                            <span x-text="result.title"></span>
                                                        </a>
                                                    </template>
                                                    <div x-show="results.length === 0" class="px-5 py-8 text-center text-outline text-sm">
                                                        <i class="bx bx-search-alt text-2xl mb-2 block"></i>
                                                        <span>{{ app()->getLocale() === 'ar' ? 'لا توجد نتائج' : 'Aucun résultat trouvé' }}</span>
                                                    </div>
                                                </div>
                                                <div class="search-modal-results" x-show="query.length === 0" style="display: none;">
                                                    <div class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-outline">
                                                        {{ app()->getLocale() === 'ar' ? 'عمليات البحث الأخيرة' : 'Recherches récentes' }}
                                                    </div>
                                                    <template x-for="(item, index) in recentSearches" :key="index">
                                                        <div class="search-result-item">
                                                            <i class="bx bx-time text-outline"></i>
                                                            <span x-text="item" class="text-secondary dark:text-gray-400"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Language Switcher (Flag only) -->
                                <div class="header-element py-[1rem] md:px-[0.65rem] px-2">
                                    <x-language-switcher variant="flag" />
                                </div>

                                <!-- Theme Toggle -->
                                <div class="header-element admin-header-theme py-[1rem] md:px-[0.65rem] px-2" x-data="themeToggle()">
                                    <button @click="toggle" type="button" class="inline-flex cursor-pointer flex-shrink-0 justify-center items-center w-9 h-9 rounded-lg text-secondary dark:text-gray-400 hover:text-gov-600 dark:hover:text-gov-400 hover:bg-surface-container dark:hover:bg-gray-700/60 transition-all duration-200" :aria-label="isDark ? '{{ __('action.theme_light') }}' : '{{ __('action.theme_dark') }}'">
                                        <i x-show="!isDark" class="bx bx-moon header-link-icon"></i>
                                        <i x-show="isDark" class="bx bx-sun header-link-icon" style="display: none;"></i>
                                    </button>
                                </div>



                                <!-- Notifications Dropdown -->
                                <div class="header-element admin-header-secondary py-[1rem] md:px-[0.65rem] px-2" x-data="notificationPanel()">
                                    <div class="hs-dropdown ti-dropdown relative flex items-center" x-data="{ dropOpen: false }" @click.outside="dropOpen = false">
                                        <button @click.stop="dropOpen = !dropOpen" type="button" class="relative inline-flex cursor-pointer flex-shrink-0 justify-center items-center w-9 h-9 rounded-lg text-secondary dark:text-gray-400 hover:text-gov-600 dark:hover:text-gov-400 hover:bg-surface-container dark:hover:bg-gray-700/60 transition-all duration-200" :aria-expanded="dropOpen" aria-haspopup="true">
                                            <i class="bx bx-bell header-link-icon"></i>
                                            <span class="header-badge" x-text="unreadCount" x-show="unreadCount > 0" style="display: none;">0</span>
                                            <span class="flex absolute h-5 w-5 -top-[0.25rem] end-0 -me-[0.6rem]" x-show="unreadCount > 0" style="display: none;">
                                                <span class="animate-ping-slow absolute inline-flex h-full w-full rounded-full bg-gov-400 opacity-75"></span>
                                            </span>
                                        </button>

                                        <div x-show="dropOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 -translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 -translate-y-1" class="main-header-dropdown admin-notification-dropdown z-[60]" style="display: none;">
                                            <div class="ti-dropdown-header flex justify-between items-center !m-0 !p-4">
                                                <span class="text-base font-semibold text-on-surface dark:text-gray-200">{{ app()->getLocale() === 'ar' ? 'الإشعارات' : 'Notifications' }}</span>
                                                <button @click="markAllRead" type="button" class="cursor-pointer text-xs font-semibold text-gov-600 dark:text-gov-400 hover:underline">
                                                    {{ app()->getLocale() === 'ar' ? 'تحديد الكل كمقروء' : 'Tout marquer lu' }}
                                                </button>
                                            </div>
                                            <div class="dropdown-divider border-t border-surface-container-high dark:border-gray-700"></div>
                                            <div class="max-h-[60vh] overflow-y-auto sm:max-h-80">
                                                <template x-if="notifications.length === 0">
                                                    <div class="p-8 text-center">
                                                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-surface-container dark:bg-gray-700 text-outline mb-3">
                                                            <i class="bx bx-bell-off text-2xl"></i>
                                                        </span>
                                                        <p class="text-sm text-secondary dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'لا توجد إشعارات جديدة' : 'Aucune nouvelle notification' }}</p>
                                                    </div>
                                                </template>
                                                <template x-for="(notification, index) in notifications" :key="index">
                                                    <div class="notification-item">
                                                        <div class="notification-icon" :class="{
                                                            'bg-primary/10 text-gov-600 dark:text-gov-400': notification.type === 'info',
                                                            'bg-success/10 text-green-600 dark:text-green-400': notification.type === 'success',
                                                            'bg-warning/10 text-amber-600 dark:text-amber-400': notification.type === 'warning',
                                                            'bg-danger/10 text-red-600 dark:text-red-400': notification.type === 'error'
                                                        }">
                                                            <i :class="{
                                                                'bx bx-info-circle': notification.type === 'info',
                                                                'bx bx-check-circle': notification.type === 'success',
                                                                'bx bx-error': notification.type === 'error',
                                                                'bx bx-error-circle': notification.type === 'warning'
                                                            }"></i>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium text-on-surface dark:text-gray-200 truncate" x-text="notification.title"></p>
                                                            <p class="text-xs text-secondary dark:text-gray-400 truncate" x-text="notification.message"></p>
                                                            <p class="text-xs text-outline mt-0.5" x-text="notification.time"></p>
                                                        </div>
                                                        <button @click="removeNotification(index)" type="button" class="inline-flex cursor-pointer flex-shrink-0 items-center justify-center w-8 h-8 rounded-lg text-gray-300 dark:text-gray-600 hover:text-secondary dark:hover:text-outline hover:bg-surface-container dark:hover:bg-gray-700/60">
                                                            <i class="bx bx-x text-lg"></i>
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="p-3 border-t border-surface-container-high dark:border-gray-700">
                                                <a href="{{ route('admin.activity-logs.index') }}" class="block w-full text-center py-2 text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 dark:bg-gov-500 dark:hover:bg-gov-600 rounded-lg transition-colors">
                                                    {{ app()->getLocale() === 'ar' ? 'عرض الكل' : 'Voir tout' }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fullscreen Toggle -->
                                <div class="header-element admin-header-secondary py-[1rem] md:px-[0.65rem] px-2 hidden sm:block" x-data="fullscreenToggle()">
                                    <button @click="toggle" type="button" class="inline-flex cursor-pointer flex-shrink-0 justify-center items-center w-9 h-9 rounded-lg text-secondary dark:text-gray-400 hover:text-gov-600 dark:hover:text-gov-400 hover:bg-surface-container dark:hover:bg-gray-700/60 transition-all duration-200" aria-label="{{ app()->getLocale() === 'ar' ? 'ملء الشاشة' : 'Plein écran' }}">
                                        <i class="bx bx-fullscreen header-link-icon" x-show="!isFullscreen"></i>
                                        <i class="bx bx-exit-fullscreen header-link-icon" x-show="isFullscreen" style="display: none;"></i>
                                    </button>
                                </div>

                                <!-- User Profile -->
                                <div class="header-element admin-header-profile md:px-[0.65rem] px-2" x-data="{ profileOpen: false }" @click.outside="profileOpen = false">
                                    <div class="relative">
                                        <button @click.stop="profileOpen = !profileOpen" type="button" class="flex cursor-pointer items-center gap-2 !p-0 flex-shrink-0 rounded-full transition-all text-xs align-middle !border-0">
                                            <div class="w-8 h-8 bg-gradient-to-br from-gov-500 to-gov-700 rounded-full flex items-center justify-center shadow-sm">
                                                <span class="text-sm font-semibold text-white">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                            </div>
                                            <div class="hidden md:block text-start">
                                                <p class="font-semibold mb-0 leading-none text-on-surface dark:text-gray-300 text-sm">{{ Auth::user()->name }}</p>
                                                <span class="opacity-70 font-normal text-secondary dark:text-gray-400 block text-[0.6875rem]">{{ Auth::user()->is_admin ? 'Administrator' : 'User' }}</span>
                                            </div>
                                            <i class="bx bx-chevron-down text-outline text-sm hidden md:block"></i>
                                        </button>

                                        <div x-show="profileOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="main-header-dropdown absolute end-0 mt-2 w-[11rem] z-50" style="display: none;">
                                            <div class="py-1">
                                                <a href="{{ route('profile.edit') }}" class="ti-dropdown-item w-full !text-[0.8125rem] !p-[0.65rem] !flex">
                                                    <i class="bx bx-user-circle text-lg me-2 opacity-70"></i>
                                                    {{ __('nav.profile') }}
                                                </a>
                                                <a href="{{ route('admin.settings.index') }}" class="ti-dropdown-item w-full !text-[0.8125rem] !p-[0.65rem] !flex">
                                                    <i class="bx bx-slider text-lg me-2 opacity-70"></i>
                                                    {{ __('admin.settings') }}
                                                </a>
                                                <div class="border-t border-surface-container-high dark:border-gray-700 my-1"></div>
                                                <form method="POST" action="{{ route('logout') }}">
                                                    @csrf
                                                    <button type="submit" class="w-full cursor-pointer ti-dropdown-item !text-[0.8125rem] !p-[0.65rem] !inline-flex !text-red-600 dark:!text-red-400">
                                                        <i class="bx bx-log-out text-lg me-2 opacity-70"></i>
                                                        {{ __('nav.logout') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mobile Hamburger -->
                                <div class="header-element admin-header-menu md:px-[0.65rem] px-2 sm:hidden">
                                    <button @click="toggleMobile()" type="button" aria-label="Toggle navigation" class="inline-flex cursor-pointer items-center justify-center p-2 rounded-lg text-secondary hover:text-on-surface hover:bg-surface-container dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gov-500 touch-target transition-all duration-200">
                                        <svg x-show="!mobileOpen" class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                        <svg x-show="mobileOpen" class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                            </div>
                        </div>
                    </nav>
                </header>

                <!-- Mobile Menu -->
                <div x-show="mobileOpen" @click.away="closeMobile()" @keydown.escape.window="closeMobile()" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="sm:hidden border-t border-surface-dim bg-surface-container-lowest" style="display: none;">
                    <div class="px-4 py-3 space-y-1">
                                <a href="{{ route('admin.dashboard') }}" @click="closeMobile()" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-on-surface dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    {{ __('nav.dashboard') }}
                                </a>
                                <a href="{{ route('admin.users.index') }}" @click="closeMobile()" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-on-surface dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                                    {{ __('admin.users') }}
                                </a>
                                <a href="{{ route('admin.roles.index') }}" @click="closeMobile()" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.roles.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-on-surface dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    {{ __('admin.roles') }}
                                </a>
                                <a href="{{ route('admin.permissions.index') }}" @click="closeMobile()" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.permissions.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-on-surface dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    {{ __('admin.permissions') }}
                                </a>

                                <!-- Mobile Advanced UI Submenu -->
                                <div x-data="{ advOpen: {{ request()->routeIs('admin.advanced-ui.*') ? 'true' : 'false' }} }">
                                    <button @click="advOpen = !advOpen" class="flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.advanced-ui.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-on-surface dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                            {{ __('nav.advanced_ui') }}
                                        </div>
                                        <svg class="w-4 h-4 text-outline transition-transform duration-200" :class="{ 'rotate-180': advOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="advOpen" class="{{ app()->getLocale() === 'ar' ? 'pr-4' : 'pl-4' }} space-y-0.5 mt-0.5">
                                        <a href="{{ route('admin.advanced-ui.stepper') }}" @click="closeMobile()" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.advanced-ui.stepper') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-secondary dark:text-gray-400 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                            <svg class="w-4 h-4 me-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/></svg>
                                            {{ __('nav.stepper') }}
                                        </a>
                                        <a href="{{ route('admin.advanced-ui.modals') }}" @click="closeMobile()" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.advanced-ui.modals') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-secondary dark:text-gray-400 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                            <svg class="w-4 h-4 me-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M3 10v11h18V10"/></svg>
                                            {{ __('nav.modals') }}
                                        </a>
                                        <a href="{{ route('admin.advanced-ui.accordions') }}" @click="closeMobile()" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.advanced-ui.accordions') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-secondary dark:text-gray-400 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                            <svg class="w-4 h-4 me-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            {{ __('nav.accordions') }}
                                        </a>
                                        <a href="{{ route('admin.advanced-ui.tabs') }}" @click="closeMobile()" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.advanced-ui.tabs') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-secondary dark:text-gray-400 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                            <svg class="w-4 h-4 me-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                                            {{ __('nav.tabs') }}
                                        </a>
                                        <a href="{{ route('admin.advanced-ui.carousel') }}" @click="closeMobile()" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.advanced-ui.carousel') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-secondary dark:text-gray-400 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                            <svg class="w-4 h-4 me-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                            {{ __('nav.carousel') }}
                                        </a>
                                        <a href="{{ route('admin.advanced-ui.tooltips') }}" @click="closeMobile()" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.advanced-ui.tooltips') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-secondary dark:text-gray-400 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                            <svg class="w-4 h-4 me-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ __('nav.tooltips') }}
                                        </a>
                                        <a href="{{ route('admin.advanced-ui.dropdowns') }}" @click="closeMobile()" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.advanced-ui.dropdowns') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-secondary dark:text-gray-400 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                            <svg class="w-4 h-4 me-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            {{ __('nav.dropdowns') }}
                                        </a>
                                        <a href="{{ route('admin.advanced-ui.sweet-alerts') }}" @click="closeMobile()" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.advanced-ui.sweet-alerts') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-secondary dark:text-gray-400 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                            <svg class="w-4 h-4 me-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ __('nav.sweet_alerts') }}
                                        </a>
                                    </div>
                                </div>

                                <a href="{{ route('admin.activity-logs.index') }}" @click="closeMobile()" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.activity-logs.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-on-surface dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    {{ __('admin.logs') }}
                                </a>
                                <a href="{{ route('admin.theme-settings.index') }}" @click="closeMobile()" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.theme-settings.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-on-surface dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                                    {{ __('admin.theme_settings') }}
                                </a>
                                <a href="{{ route('admin.settings.index') }}" @click="closeMobile()" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('admin.settings.*') ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300' : 'text-on-surface dark:text-gray-300 hover:bg-sand-50 dark:hover:bg-gray-700/50' }}">
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ __('admin.settings') }}
                                </a>
                    </div>
                    <div class="border-t border-surface-dim dark:border-gray-700 px-4 py-3">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-gov-100 dark:bg-gov-900/50 rounded-full flex items-center justify-center">
                                <span class="text-base font-semibold text-gov-600 dark:text-gov-400">{{ substr(Auth::user()->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-on-surface dark:text-gray-100">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-secondary dark:text-gray-400">{{ Auth::user()->email }}</p>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <a href="{{ route('profile.edit') }}" @click="closeMobile()" class="block w-full cursor-pointer ps-3 pe-4 py-2.5 rounded-xl border-l-4 border-transparent text-start text-base font-medium text-secondary dark:text-gray-300 hover:text-gov-700 dark:hover:text-gov-300 hover:bg-sand-50 dark:hover:bg-gray-700/50 hover:border-gov-200 dark:hover:border-gov-700 focus:outline-none focus:ring-2 focus:ring-gov-500/30 transition-all duration-200 ease-in-out">
                                <div class="flex items-center gap-3">
                                    <i class="bx bx-user-circle text-xl text-outline"></i>
                                    {{ __('nav.profile') }}
                                </div>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full cursor-pointer ps-3 pe-4 py-2.5 rounded-xl border-l-4 border-transparent text-start text-base font-medium text-secondary dark:text-gray-300 hover:text-gov-700 dark:hover:text-gov-300 hover:bg-sand-50 dark:hover:bg-gray-700/50 hover:border-gov-200 dark:hover:border-gov-700 focus:outline-none focus:ring-2 focus:ring-gov-500/30 transition-all duration-200 ease-in-out">
                                    <div class="flex items-center gap-3">
                                        <i class="bx bx-log-out text-xl text-outline"></i>
                                        {{ __('nav.logout') }}
                                    </div>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8 min-w-0">
                    @if (session('success'))
                        <div class="mb-6 p-3.5 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded shadow-sm">
                            <div class="flex items-center gap-2 text-body-sm">
                                <span class="material-symbols-outlined text-emerald-600 text-xl" aria-hidden="true">check_circle</span>
                                <span class="font-medium text-emerald-800 dark:text-emerald-300">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 p-3.5 bg-error-container/70 dark:bg-error-container/20 border border-error/40 rounded shadow-sm">
                            <div class="flex items-center gap-2 text-body-sm">
                                <span class="material-symbols-outlined text-error text-xl" aria-hidden="true">error</span>
                                <span class="font-medium text-on-error-container dark:text-error-container">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
