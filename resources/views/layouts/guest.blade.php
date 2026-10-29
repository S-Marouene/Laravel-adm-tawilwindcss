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

        <!-- Fonts (Roboto latin ; Janna LT arabe auto-hébergée via app.css) -->
        @if(app()->getLocale() === 'ar')
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100..900&display=swap" rel="stylesheet">
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
    <body class="font-sans antialiased text-on-surface bg-background min-h-screen flex flex-col">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <!-- Left/Brand Panel — vitrine institutionnelle bleu nuit (garde le contraste
                 en mode sombre : le contenu s'inverse automatiquement via les tokens) -->
            <div class="hidden lg:flex lg:w-[46%] xl:w-1/2 relative flex-col overflow-hidden bg-inverse-surface">
                <div class="auth-brand" aria-hidden="true"></div>
                <div class="relative z-10 flex flex-col flex-1 w-full max-w-xl mx-auto justify-center px-8 xl:px-12 py-10 min-h-0 overflow-y-auto" style="color: var(--color-inverse-on-surface)">

                    <!-- Identité (logo dans une pastille verre dépoli) -->
                    <div class="flex items-center gap-3.5 shrink-0 mb-8">
                        <a href="{{ url('/') }}" class="w-13 h-13 shrink-0 rounded-2xl flex items-center justify-center bg-inverse-on-surface/10 ring-1 ring-inverse-on-surface/15 backdrop-blur-sm" aria-label="{{ __('app.name') }}">
                            <img src="/images/logo.png" alt="Emblème de la Protection Civile — دائرة الحماية المدنية" class="w-10.5 h-10.5 object-contain">
                        </a>
                        <div class="min-w-0">
                            <p class="text-lg font-black tracking-tight leading-tight truncate">{{ __('app.brand') }}</p>
                            <p class="text-[11px] font-semibold opacity-70 leading-snug truncate">{{ __('app.brand_sub') }}</p>
                        </div>
                    </div>

                    <!-- Déclaration -->
                    <div class="shrink-0">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-inverse-on-surface/20 bg-inverse-on-surface/5 px-3 py-1.5 text-label-caps uppercase opacity-90">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" aria-hidden="true"></span>
                            {{ __('home.kicker') }}
                        </span>
                        <h1 class="mt-4 text-[26px] xl:text-[30px] font-black tracking-tight leading-[1.22] max-w-md">
                            {{ __('app.tagline') }}
                        </h1>
                        <p class="mt-2.5 text-body-lg opacity-75 leading-relaxed max-w-md">
                            {{ __('app.description') }}
                        </p>
                    </div>

                    <!-- Services phares disponibles après connexion -->
                    <div class="mt-7 space-y-2.5 w-full max-w-md shrink-0">
                        @foreach ([['prevention_certificate', 'verified_user'], ['volunteering', 'volunteer_activism'], ['training', 'school']] as [$svc, $icon])
                            <div class="group flex items-center gap-3 rounded-xl border border-inverse-on-surface/12 bg-inverse-on-surface/5 px-3.5 py-3 transition-colors hover:bg-inverse-on-surface/10 hover:border-inverse-on-surface/25">
                                <span class="w-9 h-9 shrink-0 rounded-lg bg-inverse-primary/15 flex items-center justify-center" aria-hidden="true">
                                    <span class="material-symbols-outlined filled text-[18px]" style="color: var(--color-inverse-primary)">{{ $icon }}</span>
                                </span>
                                <span class="text-body-sm font-semibold leading-snug min-w-0">{{ __('services.' . $svc) }}</span>
                                <span class="ms-auto inline-flex items-center gap-1.5 text-[11px] font-semibold opacity-65 shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" aria-hidden="true"></span>
                                    {{ __('services.status_available') }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Gages de confiance (rangée compacte) -->
                    <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2 text-[12.5px] font-semibold opacity-70 shrink-0">
                        @foreach (['trust_official' => 'verified', 'trust_online' => 'devices', 'trust_tracking' => 'timeline'] as $trust => $icon)
                            <span class="inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined filled text-[15px]" style="color: var(--color-inverse-primary)" aria-hidden="true">{{ $icon }}</span>
                                {{ __("home.$trust") }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Bandeau numéros d'urgence (tous cliquables) -->
                <div class="relative z-10 shrink-0 bg-inverse-on-surface/5 border-t border-inverse-on-surface/12 backdrop-blur-sm py-3.5" style="color: var(--color-inverse-on-surface)">
                    <div class="max-w-xl mx-auto px-8 xl:px-12 flex items-center justify-between gap-4 min-w-0">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="material-symbols-outlined filled text-lg shrink-0" style="color: var(--color-inverse-primary)" aria-hidden="true">emergency</span>
                            <div class="min-w-0 leading-tight">
                                <p class="text-body-sm font-bold truncate">{{ __('home.emergency_title') }}</p>
                                <p class="text-[11px] opacity-65 truncate">{{ __('home.emergency_subtitle') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0" aria-label="{{ __('home.emergency_title') }} : 198, 190, 197">
                            <a href="tel:198" class="num-ltr rounded-lg px-2.5 py-1.5 leading-none hover:bg-inverse-on-surface/10 transition-colors">
                                <span class="block text-base font-black tabular-nums" style="color: var(--color-inverse-primary)">198</span>
                                <span class="block text-[10px] opacity-70 mt-0.5">{{ __('home.emergency_civil') }}</span>
                            </a>
                            <a href="tel:190" class="num-ltr rounded-lg px-2.5 py-1.5 leading-none hover:bg-inverse-on-surface/10 transition-colors">
                                <span class="block text-base font-black tabular-nums" style="color: var(--color-inverse-primary)">190</span>
                                <span class="block text-[10px] opacity-70 mt-0.5">{{ __('home.emergency_samu') }}</span>
                            </a>
                            <a href="tel:197" class="num-ltr hidden xl:block rounded-lg px-2.5 py-1.5 leading-none hover:bg-inverse-on-surface/10 transition-colors">
                                <span class="block text-base font-black tabular-nums" style="color: var(--color-inverse-primary)">197</span>
                                <span class="block text-[10px] opacity-70 mt-0.5">{{ __('home.emergency_police') }}</span>
                            </a>
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
                <div class="lg:hidden flex flex-col items-center mb-6 mt-10 px-6 text-center">
                    <a href="{{ url('/') }}" class="block mb-3">
                        <img src="/images/logo.png" alt="" class="w-13 h-13 object-contain">
                    </a>
                    <h1 class="text-headline-md text-primary font-black tracking-tight">{{ __('app.brand') }}</h1>
                    <p class="text-body-sm text-secondary mt-1.5 max-w-xs">{{ __('app.brand_sub') }}</p>
                </div>

                <!-- Auth Card -->
                <div class="w-full max-w-md">
                    <div class="auth-card bg-surface-container-lowest border border-surface-dim rounded-2xl shadow-lg shadow-surface-dim/30 p-6 sm:p-8">
                        <!-- Page Header -->
                        <div class="mb-6 pb-5 border-b border-surface-dim">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="h-px w-6 bg-primary" aria-hidden="true"></span>
                                <span class="text-label-caps text-primary uppercase">{{ __('home.kicker') }}</span>
                            </div>
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
