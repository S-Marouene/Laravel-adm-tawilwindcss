{{-- ============================================================
     BASE DES PAGES D'ERREUR ONPC (design/landing.html)
     Chrome institutionnel condensé + panneau d'erreur centré.
     ============================================================ --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#9e001f">
    <meta name="description" content="{{ __('app.description') }}">

    <title>@yield('title', __('app.name')) — {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-styles')
</head>
<body class="font-sans antialiased bg-background text-on-surface min-h-screen flex flex-col selection:bg-primary selection:text-on-primary">
    @include('partials.super-band')

    <!-- Brand header -->
    <header class="bg-surface-container-lowest border-b border-surface-dim">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img src="/images/logo.png" alt="" class="w-9 h-9 object-contain shrink-0">
                <span class="min-w-0 leading-tight">
                    <span class="block text-title-section text-on-surface truncate">{{ setting('app_name', __('app.name')) }}</span>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-secondary">{{ __('app.tagline') }}</span>
                </span>
            </a>
        </div>
    </header>
    <div class="h-[2px] bg-primary" aria-hidden="true"></div>

    <!-- Error panel -->
    <main id="main-content" class="flex-grow flex items-center justify-center px-4 py-16 sm:py-24">
        <div class="w-full max-w-2xl relative overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-sm">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-primary-container" aria-hidden="true"></div>
            <div class="p-8 md:p-12 text-center">
                <p class="text-display-hero-mobile font-black text-primary/15 leading-none select-none" aria-hidden="true">{{ $code ?? '500' }}</p>
                <div class="mx-auto -mt-14 sm:-mt-16 relative">
                    <span class="w-14 h-14 mx-auto mb-4 rounded bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined filled text-3xl" aria-hidden="true">@yield('icon', 'error')</span>
                    </span>
                </div>
                <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">
                    @yield('heading')
                </h1>
                <p class="text-body-md text-secondary mt-3 max-w-md mx-auto leading-relaxed">
                    @yield('message')
                </p>
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ url('/') }}" class="ui-btn-primary inline-flex items-center gap-2 px-5 py-3 text-body-sm">
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">home</span>
                        {{ app()->getLocale() === 'ar' ? 'العودة إلى الرئيسية' : 'Retour à l’accueil' }}
                    </a>
                    <a href="javascript:history.back()" class="ui-soft-button inline-flex items-center gap-2 px-5 py-3 text-body-sm">
                        <span class="material-symbols-outlined text-[18px] rtl:rotate-180" aria-hidden="true">arrow_back</span>
                        {{ app()->getLocale() === 'ar' ? 'الصفحة السابقة' : 'Page précédente' }}
                    </a>
                </div>
                <p class="mt-8 pt-6 border-t border-surface-dim text-xs text-secondary">
                    {{ app()->getLocale() === 'ar' ? 'إذا استمرت المشكلة، تواصوا مع قيادة الحماية المدنية.' : 'Si le problème persiste, contactez la Direction Générale de la Protection Civile.' }}
                </p>
            </div>
        </div>
    </main>

    @include('partials.footer')
</body>
</html>
