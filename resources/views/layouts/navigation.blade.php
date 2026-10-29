{{-- =====================================================================
     EN-TÊTE OPÉRATIONNEL PORTAIL CITOYEN — bandeau état + barre marque
     + navigation institutionnelle (pills, active claire) + menu mobile
     Accessible à tous les états : invité, usager connecté, admin
     ===================================================================== --}}
@php
    $guestNav = [
        ['label' => __('nav.home'), 'href' => url('/'), 'active' => request()->is('/'), 'icon' => 'home'],
        ['label' => __('nav.my_requests'), 'href' => route('mes-demandes'), 'active' => request()->routeIs('mes-demandes'), 'icon' => 'folder_open'],
        ['label' => __('nav.services'), 'href' => url('/#services'), 'active' => false, 'icon' => 'domain_verification'],
    ];
@endphp

<div x-data="{ open: false, brandOpen: false }">
    {{-- 1. BANDEAU OFFICIEL --}}
    @include('partials.super-band')

    {{-- 2. BARRE DE MARQUE INSTITUTIONNELLE --}}
    <header class="bg-surface-container-lowest border-b border-surface-dim sticky top-0 z-40 shadow-sm">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
            {{-- Logo et identité de la marque (BOUWABA — langue active uniquement) --}}
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <a href="{{ url('/') }}" class="flex items-center gap-3 sm:gap-4 group min-w-0" aria-label="{{ __('app.name') }}">
                    <span class="shrink-0 flex items-center">
                        <img src="/images/logo.png" alt="Emblème de la Protection Civile — دائرة الحماية المدنية" class="w-11 h-11 sm:w-13 sm:h-13 object-contain">
                    </span>
                    <span class="flex flex-col min-w-0">
                        <span class="text-headline-sm font-black font-heading text-primary tracking-tight leading-none truncate">
                            {{ __('app.brand') }}
                        </span>
                        <span class="text-[11px] text-secondary mt-1 hidden sm:inline leading-snug">
                            <span class="block max-w-md truncate">{{ __('app.brand_sub') }}</span>
                        </span>
                    </span>
                </a>
            </div>

            {{-- Actions utilisateur --}}
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                {{-- Toggle thème --}}
                <div x-data="themeToggle()" class="relative">
                    <button @click="toggle" type="button" class="relative inline-flex cursor-pointer items-center justify-center w-10 h-10 rounded text-secondary hover:text-primary hover:bg-surface-container transition-colors touch-target"
                        :aria-label="isDark ? '{{ __('action.theme_light') }}' : '{{ __('action.theme_dark') }}'">
                        <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                </div>
                <div class="h-7 w-px bg-surface-dim" aria-hidden="true"></div>
                <x-language-switcher variant="minimal" />

                @auth
                    {{-- Profil connecté + dropdown --}}
                    <div class="relative">
                        <x-dropdown align="{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" width="52">
                            <x-slot name="trigger">
                                <button type="button" class="flex cursor-pointer items-center gap-2.5 px-2.5 py-1.5 rounded bg-surface-container hover:bg-surface-container-high transition-colors border border-surface-dim">
                                    <span class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center border border-primary/20 shrink-0">
                                        <span class="material-symbols-outlined filled text-lg text-on-primary" aria-hidden="true">account_circle</span>
                                    </span>
                                    <span class="hidden lg:flex flex-col text-start leading-tight">
                                        <span class="text-body-sm font-bold text-on-surface truncate max-w-[10rem]">{{ Auth::user()->name }}</span>
                                        <span class="text-[10px] text-secondary">{{ Auth::user()->email }}</span>
                                    </span>
                                    <span class="material-symbols-outlined text-secondary text-sm" aria-hidden="true">expand_more</span>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="py-1">
                                    <x-dropdown-link :href="route('profile.edit')" class="!text-[0.8125rem] !p-[0.65rem]">
                                        <span class="material-symbols-outlined text-base me-2 opacity-70" aria-hidden="true">account_circle</span>
                                        {{ __('nav.profile') }}
                                    </x-dropdown-link>

                                    @if(Auth::user()->is_admin || Auth::user()->hasRole('admin'))
                                        <x-dropdown-link :href="route('admin.dashboard')" class="!text-[0.8125rem] !p-[0.65rem]">
                                            <span class="material-symbols-outlined text-base me-2 opacity-70" aria-hidden="true">shield</span>
                                            {{ __('nav.admin') }}
                                        </x-dropdown-link>
                                    @endif

                                    <div class="border-t border-surface-container-high my-1"></div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')"
                                                onclick="event.preventDefault(); this.closest('form').submit();"
                                                class="!text-red-700 dark:!text-red-400 !text-[0.8125rem] !p-[0.65rem]">
                                            <span class="material-symbols-outlined text-base me-2 opacity-70" aria-hidden="true">logout</span>
                                            {{ __('nav.logout') }}
                                        </x-dropdown-link>
                                    </form>
                                </div>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @else
                    {{-- Invité : liens connexion / inscription --}}
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-surface-container-lowest border border-surface-dim text-on-surface text-body-sm font-semibold rounded hover:bg-surface-container hover:border-surface-container-highest transition-colors">
                        <span class="material-symbols-outlined text-[16px] text-secondary" aria-hidden="true">login</span>
                        {{ __('nav.login') }}
                    </a>
                    <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center gap-1.5 ui-btn-primary px-3.5 py-2.5 text-body-sm hover:shadow-md">
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">person_add</span>
                        {{ __('nav.register') }}
                    </a>
                @endauth

                {{-- Bouton menu mobile — visible pour tous les états (< sm) --}}
                <button @click="open = !open" type="button" class="sm:hidden inline-flex cursor-pointer items-center justify-center w-10 h-10 rounded text-secondary hover:text-primary hover:bg-surface-container touch-target transition-colors"
                    :aria-expanded="open ? 'true' : 'false'" aria-label="{{ app()->getLocale() === 'ar' ? 'القائمة' : 'Menu' }}">
                    <svg x-show="!open" class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="open" class="w-6 h-6 hidden" stroke="currentColor" fill="none" viewBox="0 0 24 24" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    {{-- 3. NAVIGATION INSTITUTIONNELLE (pills équilibrées, cible tactile 44px) --}}
    <nav class="bg-surface-container-lowest border-b border-surface-dim no-print hidden sm:block" aria-label="{{ __('nav.home') }}">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between gap-4">
            <ul class="flex items-center gap-1 overflow-x-auto py-1.5 -mx-1 px-1">
                @foreach($guestNav as $item)
                    <li>
                        <a href="{{ $item['href'] }}"
                           class="h-10 inline-flex items-center gap-2 px-3.5 rounded text-body-sm font-semibold whitespace-nowrap transition-colors duration-150 {{ $item['active'] ? 'bg-primary/10 text-primary' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' }}"
                           @if($item['active']) aria-current="page" @endif>
                            <span class="material-symbols-outlined text-[17px]" aria-hidden="true">{{ $item['icon'] }}</span>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
                @auth
                    @if(Auth::user()->is_admin || Auth::user()->hasRole('admin'))
                        <li>
                            <a href="{{ route('admin.dashboard') }}"
                               class="h-10 inline-flex items-center gap-2 px-3.5 rounded text-body-sm font-semibold whitespace-nowrap transition-colors duration-150 {{ request()->routeIs('admin.*') ? 'bg-primary/10 text-primary' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' }}"
                               @if(request()->routeIs('admin.*')) aria-current="page" @endif>
                                <span class="material-symbols-outlined text-[17px]" aria-hidden="true">shield</span>
                                <span>{{ __('nav.admin') }}</span>
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>
            <div class="hidden lg:flex items-center gap-2 text-body-sm text-secondary shrink-0 ps-4 border-s border-surface-dim self-center">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-600 animate-pulse" aria-hidden="true"></span>
                <span class="font-semibold text-on-surface-variant">{{ __('home.kicker') }}</span>
            </div>
        </div>
    </nav>

    {{-- 4. MENU MOBILE (invités + usagers connectés) --}}
    <div x-show="open" @click.away="open = false" @keydown.escape.window="open = false"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
        class="sm:hidden border-b border-surface-dim bg-surface-container-lowest overflow-y-auto max-h-[70vh] shadow-lg" style="display: none;">
        <nav class="px-3 py-3 space-y-1" aria-label="{{ app()->getLocale() === 'ar' ? 'القائمة الرئيسية' : 'Menu principal' }}">
            @foreach($guestNav as $item)
                <a href="{{ $item['href'] }}" @click="open=false"
                   class="flex items-center gap-3 px-3 py-3 min-h-[44px] text-body-md font-semibold rounded transition-colors {{ $item['active'] ? 'bg-primary/10 text-primary' : 'text-on-surface hover:bg-surface-container' }}">
                    <span class="material-symbols-outlined text-xl" aria-hidden="true">{{ $item['icon'] }}</span>
                    {{ $item['label'] }}
                </a>
            @endforeach
            @auth
                @if(Auth::user()->is_admin || Auth::user()->hasRole('admin'))
                    <a href="{{ route('admin.dashboard') }}" @click="open=false" class="flex items-center gap-3 px-3 py-3 min-h-[44px] text-body-md font-semibold rounded transition-colors {{ request()->routeIs('admin.*') ? 'bg-primary/10 text-primary' : 'text-on-surface hover:bg-surface-container' }}">
                        <span class="material-symbols-outlined text-xl" aria-hidden="true">shield</span>
                        {{ __('nav.admin') }}
                    </a>
                @endif
                <a href="{{ route('profile.edit') }}" @click="open=false" class="flex items-center gap-3 px-3 py-3 min-h-[44px] text-body-md font-semibold rounded transition-colors text-on-surface hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl" aria-hidden="true">account_circle</span>
                    {{ __('nav.profile') }}
                </a>
                <div class="border-t border-surface-container-high my-1.5" role="separator"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-start flex items-center gap-3 px-3 py-3 min-h-[44px] text-body-md font-semibold rounded text-error hover:bg-error-container/60 transition-colors">
                        <span class="material-symbols-outlined text-xl" aria-hidden="true">logout</span>
                        {{ __('nav.logout') }}
                    </button>
                </form>
            @else
                <div class="border-t border-surface-container-high my-1.5" role="separator"></div>
                <a href="{{ route('login') }}" @click="open=false" class="flex items-center gap-3 px-3 py-3 min-h-[44px] text-body-md font-semibold rounded transition-colors text-on-surface hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl" aria-hidden="true">login</span>
                    {{ __('nav.login') }}
                </a>
                <a href="{{ route('register') }}" @click="open=false" class="flex items-center gap-3 px-3 py-3 min-h-[44px] text-body-md font-semibold rounded text-primary hover:bg-primary/10 transition-colors">
                    <span class="material-symbols-outlined text-xl" aria-hidden="true">person_add</span>
                    {{ __('nav.register') }}
                </a>
            @endauth
        </nav>
    </div>
</div>
