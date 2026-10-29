<x-app-layout>
    {{-- ================================================================
         PAGE D'ACCUEIL — Portail Citoyen ONPC (design/landing.html)
         Héros clair institutionnel + numéros d'urgence + 3 étapes
         + services (disponibles / à venir) + statistiques + bande CTA
         ================================================================ --}}

    <!-- ===========================================================
         HERO — clair, sobre : le rouge reste un accent de marque
         =========================================================== -->
    <section class="hero-light relative overflow-hidden no-print">
        {{-- Emblème décoratif en filigrane --}}
        <img src="/images/logo.png" alt="" aria-hidden="true"
             class="pointer-events-none absolute -top-10 -end-24 w-[26rem] max-w-none opacity-[0.045] select-none dark:opacity-[0.06]" />

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20 lg:py-24">
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-14">

                {{-- Colonne texte --}}
                <div class="lg:col-span-7 text-center lg:text-start animate-fade-in">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded border border-surface-dim bg-surface-container-lowest text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-6 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" aria-hidden="true"></span>
                        {{ __('home.kicker') }}
                    </div>

                    <h1 class="text-display-hero-mobile sm:text-display-hero text-on-surface uppercase tracking-tight mb-5">
                        {{ __('app.tagline') }}
                    </h1>

                    {{-- Accent de marque : filet rouge sous le titre --}}
                    <div class="h-1.5 w-16 bg-primary rounded-full mb-6 mx-auto lg:mx-0" aria-hidden="true"></div>

                    <p class="text-body-lg text-on-surface-variant max-w-2xl mx-auto lg:mx-0 mb-8 sm:mb-10 leading-relaxed">
                        {{ __('app.description') }}
                    </p>

                    {{-- Un seul CTA primaire + action secondaire --}}
                    <div class="flex flex-col sm:flex-row items-center lg:items-stretch justify-center lg:justify-start gap-3 sm:gap-4">
                        @auth
                            @if ($hasRequests)
                                <a href="{{ route('mes-demandes') }}" class="ui-btn-primary w-full sm:w-auto px-8 py-3.5 text-body-md">
                                    {{ __('nav.my_requests') }}
                                </a>
                                <a href="#services" class="ui-soft-button w-full sm:w-auto px-8 py-3.5 text-body-md">
                                    {{ __('nav.services') }}
                                </a>
                            @else
                                <a href="#services" class="ui-btn-primary w-full sm:w-auto px-8 py-3.5 text-body-md">
                                    {{ __('nav.services') }}
                                </a>
                            @endif
                        @else
                            <a href="{{ route('register') }}" class="ui-btn-primary w-full sm:w-auto px-8 py-3.5 text-body-md">
                                {{ __('register.submit') }}
                            </a>
                            <a href="{{ route('login') }}" class="ui-soft-button w-full sm:w-auto px-8 py-3.5 text-body-md">
                                {{ __('login.title') }}
                            </a>
                        @endauth
                    </div>

                    {{-- Gages de confiance --}}
                    <div class="mt-10 pt-7 border-t border-surface-dim/70 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-x-8 gap-y-3 text-start">
                        <div class="inline-flex items-center gap-2.5">
                            <span class="material-symbols-outlined filled text-xl text-primary" aria-hidden="true">verified_user</span>
                            <span class="text-body-sm text-on-surface-variant font-medium">{{ __('home.trust_official') }}</span>
                        </div>
                        <div class="inline-flex items-center gap-2.5">
                            <span class="material-symbols-outlined filled text-xl text-primary" aria-hidden="true">devices</span>
                            <span class="text-body-sm text-on-surface-variant font-medium">{{ __('home.trust_online') }}</span>
                        </div>
                        <div class="inline-flex items-center gap-2.5">
                            <span class="material-symbols-outlined filled text-xl text-primary" aria-hidden="true">timeline</span>
                            <span class="text-body-sm text-on-surface-variant font-medium">{{ __('home.trust_tracking') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Colonne carte : numéros d'urgence (données officielles design/nouvelle.html) --}}
                <aside class="lg:col-span-5 w-full max-w-sm mx-auto lg:mx-0 lg:justify-self-end animate-fade-in delay-2">
                    <div class="relative rounded-lg border border-surface-dim bg-surface-container-lowest shadow-lg shadow-surface-dim/40 overflow-hidden">
                        <div class="h-1.5 bg-gradient-to-r from-primary via-primary-container to-transparent" aria-hidden="true"></div>

                        <div class="p-5 sm:p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-error-container">
                                    <span class="material-symbols-outlined filled text-xl text-error" aria-hidden="true">emergency</span>
                                </span>
                                <div>
                                    <p class="text-headline-sm text-on-surface">{{ __('home.emergency_title') }}</p>
                                    <p class="text-body-sm text-secondary">{{ __('home.emergency_subtitle') }}</p>
                                </div>
                            </div>

                            <div class="space-y-2.5">
                                {{-- 198 — Protection Civile --}}
                                <div class="flex items-center justify-between gap-3 rounded border border-surface-dim bg-surface-container-low px-4 py-3">
                                    <span class="text-body-sm text-on-surface-variant font-semibold">{{ __('home.emergency_civil') }}</span>
                                    <a href="tel:198" class="num-ltr text-2xl font-black tabular-nums text-primary hover:text-primary-container transition-colors" aria-label="198 {{ __('home.emergency_civil') }}">198</a>
                                </div>
                                {{-- 190 — SAMU --}}
                                <div class="flex items-center justify-between gap-3 rounded border border-surface-dim bg-surface-container-low px-4 py-3">
                                    <span class="text-body-sm text-on-surface-variant font-semibold">{{ __('home.emergency_samu') }}</span>
                                    <a href="tel:190" class="num-ltr text-2xl font-black tabular-nums text-primary hover:text-primary-container transition-colors" aria-label="190 {{ __('home.emergency_samu') }}">190</a>
                                </div>
                                {{-- 197 — Police Secours --}}
                                <div class="flex items-center justify-between gap-3 rounded border border-surface-dim bg-surface-container-low px-4 py-3">
                                    <span class="text-body-sm text-on-surface-variant font-semibold">{{ __('home.emergency_police') }}</span>
                                    <a href="tel:197" class="num-ltr text-2xl font-black tabular-nums text-primary hover:text-primary-container transition-colors" aria-label="197 {{ __('home.emergency_police') }}">197</a>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-surface-dim flex items-center justify-between gap-3">
                                <span class="inline-flex items-center gap-2 text-body-sm text-secondary font-medium">
                                    <span class="material-symbols-outlined text-lg" aria-hidden="true">call</span>
                                    {{ __('home.emergency_standard') }}
                                </span>
                                <a href="tel:71190100" class="num-ltr text-body-md font-black tabular-nums text-on-surface hover:text-primary transition-colors">{{ __('home.standard_number') }}</a>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <!-- ===========================================================
         LES 3 ÉTAPES — rassurer l'usager avant la liste des services
         =========================================================== -->
    <section class="py-12 sm:py-16 no-print" aria-label="{{ __('home.steps_title') }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-12">
                <h2 class="text-headline-md sm:text-headline-lg text-on-surface tracking-tight mb-3">{{ __('home.steps_title') }}</h2>
                <p class="text-body-md text-secondary">{{ __('home.steps_subtitle') }}</p>
            </div>

            <ol class="grid sm:grid-cols-3 gap-4 sm:gap-6 list-none">
                <li class="relative flex items-start gap-4 rounded-lg border border-surface-dim bg-surface-container-lowest p-5 shadow-[0_1px_2px_rgba(19,27,46,0.05)]">
                    <span class="w-10 h-10 shrink-0 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center font-black text-lg" aria-hidden="true">1</span>
                    <div>
                        <h3 class="text-headline-sm text-on-surface mb-1">{{ __('home.step1_title') }}</h3>
                        <p class="text-body-sm text-secondary leading-relaxed">{{ __('home.step1_desc') }}</p>
                    </div>
                </li>
                <li class="relative flex items-start gap-4 rounded-lg border border-surface-dim bg-surface-container-lowest p-5 shadow-[0_1px_2px_rgba(19,27,46,0.05)]">
                    <span class="w-10 h-10 shrink-0 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center font-black text-lg" aria-hidden="true">2</span>
                    <div>
                        <h3 class="text-headline-sm text-on-surface mb-1">{{ __('home.step2_title') }}</h3>
                        <p class="text-body-sm text-secondary leading-relaxed">{{ __('home.step2_desc') }}</p>
                    </div>
                </li>
                <li class="relative flex items-start gap-4 rounded-lg border border-surface-dim bg-surface-container-lowest p-5 shadow-[0_1px_2px_rgba(19,27,46,0.05)]">
                    <span class="w-10 h-10 shrink-0 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center font-black text-lg" aria-hidden="true">3</span>
                    <div>
                        <h3 class="text-headline-sm text-on-surface mb-1">{{ __('home.step3_title') }}</h3>
                        <p class="text-body-sm text-secondary leading-relaxed">{{ __('home.step3_desc') }}</p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    <!-- ===========================================================
         SERVICES — 3 services ouverts en cartes mises en avant,
         puis les prochains services en liste neutre compacte
         =========================================================== -->
    <section id="services" class="pb-12 sm:pb-16 lg:pb-20 scroll-mt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            {{-- En-tête (aligné à gauche, plus professionnel) --}}
            <div class="max-w-2xl mb-10 sm:mb-14">
                <div class="flex items-center gap-3 mb-3">
                    <span class="h-px w-8 bg-primary" aria-hidden="true"></span>
                    <span class="text-label-caps text-primary uppercase">{{ __('services.kicker') }}</span>
                </div>
                <h2 class="text-headline-md sm:text-headline-lg text-on-surface tracking-tight mb-3">
                    {{ __('services.subtitle') }}
                </h2>
                <p class="text-body-md text-secondary">{{ __('stats.services') }}</p>
            </div>

            {{-- Groupe 1 — services ouverts --}}
            <div class="flex items-center gap-2.5 mb-4">
                <span class="material-symbols-outlined filled text-lg text-emerald-600" aria-hidden="true">check_circle</span>
                <h3 class="text-title-section text-on-surface uppercase">{{ __('services.available') }}</h3>
                <span class="h-px flex-1 bg-surface-dim" aria-hidden="true"></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-12 sm:mb-14">

                {{-- Certificat de prévention --}}
                <a href="{{ route('prevention-requests.create') }}" class="group relative onpc-kpi flex flex-col">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <span class="w-12 h-12 shrink-0 rounded flex items-center justify-center bg-primary/10" aria-hidden="true">
                            <span class="material-symbols-outlined filled text-2xl text-primary">fact_check</span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-emerald-800 bg-emerald-50 rounded border border-emerald-200 dark:text-emerald-400 dark:bg-emerald-950/40 dark:border-emerald-900">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            {{ __('services.status_available') }}
                        </span>
                    </div>
                    <h3 class="text-headline-sm text-on-surface mb-1.5">{{ __('services.prevention_certificate') }}</h3>
                    <p class="text-body-sm text-secondary leading-relaxed flex-1">{{ __('services.prevention_certificate.desc') }}</p>
                    <span class="inline-flex items-center gap-1 mt-4 pt-3 border-t border-surface-dim text-body-sm font-bold text-primary group-hover:gap-2.5 transition-all">
                        {{ __('services.access_service') }}
                        <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
                    </span>
                </a>

                {{-- Présence préventive --}}
                <a href="{{ route('preventive-presence.create') }}" class="group relative onpc-kpi flex flex-col">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <span class="w-12 h-12 shrink-0 rounded flex items-center justify-center bg-emerald-50" aria-hidden="true">
                            <span class="material-symbols-outlined filled text-2xl text-emerald-700">visibility</span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-emerald-800 bg-emerald-50 rounded border border-emerald-200 dark:text-emerald-400 dark:bg-emerald-950/40 dark:border-emerald-900">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            {{ __('services.status_available') }}
                        </span>
                    </div>
                    <h3 class="text-headline-sm text-on-surface mb-1.5">{{ __('services.preventive_presence') }}</h3>
                    <p class="text-body-sm text-secondary leading-relaxed flex-1">{{ __('services.preventive_presence.desc') }}</p>
                    <span class="inline-flex items-center gap-1 mt-4 pt-3 border-t border-surface-dim text-body-sm font-bold text-primary group-hover:gap-2.5 transition-all">
                        {{ __('services.access_service') }}
                        <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
                    </span>
                </a>

                {{-- Certificat de secours et sauvetage --}}
                <a href="{{ route('rescue-certificates.create') }}" class="group relative onpc-kpi flex flex-col">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <span class="w-12 h-12 shrink-0 rounded flex items-center justify-center bg-error-container" aria-hidden="true">
                            <span class="material-symbols-outlined filled text-2xl text-error">health_and_safety</span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-emerald-800 bg-emerald-50 rounded border border-emerald-200 dark:text-emerald-400 dark:bg-emerald-950/40 dark:border-emerald-900">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">check_circle</span>
                            {{ __('services.free') }}
                        </span>
                    </div>
                    <h3 class="text-headline-sm text-on-surface mb-1.5">{{ __('services.rescue_certificate') }}</h3>
                    <p class="text-body-sm text-secondary leading-relaxed flex-1">{{ __('services.rescue_certificate.desc') }}</p>
                    <span class="inline-flex items-center gap-1 mt-4 pt-3 border-t border-surface-dim text-body-sm font-bold text-primary group-hover:gap-2.5 transition-all">
                        {{ __('services.access_service') }}
                        <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
                    </span>
                </a>
            </div>

            {{-- Groupe 2 — prochains services --}}
            <div class="flex items-center gap-2.5 mb-4">
                <span class="material-symbols-outlined text-lg text-secondary" aria-hidden="true">schedule</span>
                <h3 class="text-title-section text-on-surface uppercase">{{ __('services.upcoming') }}</h3>
                <span class="h-px flex-1 bg-surface-dim" aria-hidden="true"></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">

                {{-- Volontariat --}}
                <div class="flex items-start gap-4 rounded border border-dashed border-surface-dim bg-surface-container-lowest/70 p-4">
                    <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-surface-container" aria-hidden="true">
                        <span class="material-symbols-outlined filled text-xl text-secondary">volunteer_activism</span>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-body-md font-bold text-on-surface leading-snug mb-1">{{ __('services.volunteering') }}</h3>
                        <p class="text-[13px] text-secondary leading-relaxed line-clamp-2">{{ __('services.volunteering.desc') }}</p>
                    </div>
                </div>

                {{-- Formation et recyclage --}}
                <div class="flex items-start gap-4 rounded border border-dashed border-surface-dim bg-surface-container-lowest/70 p-4">
                    <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-surface-container" aria-hidden="true">
                        <span class="material-symbols-outlined filled text-xl text-secondary">school</span>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-body-md font-bold text-on-surface leading-snug mb-1">{{ __('services.training') }}</h3>
                        <p class="text-[13px] text-secondary leading-relaxed line-clamp-2">{{ __('services.training.desc') }}</p>
                    </div>
                </div>

                {{-- Plan d'intervention interne --}}
                <div class="flex items-start gap-4 rounded border border-dashed border-surface-dim bg-surface-container-lowest/70 p-4">
                    <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-surface-container" aria-hidden="true">
                        <span class="material-symbols-outlined filled text-xl text-secondary">architecture</span>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-body-md font-bold text-on-surface leading-snug mb-1">{{ __('services.internal_intervention_plan') }}</h3>
                        <p class="text-[13px] text-secondary leading-relaxed line-clamp-2">{{ __('services.internal_intervention_plan.desc') }}</p>
                    </div>
                </div>

                {{-- Dossier sécurité — bâtiments publics --}}
                <div class="flex items-start gap-4 rounded border border-dashed border-surface-dim bg-surface-container-lowest/70 p-4">
                    <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-surface-container" aria-hidden="true">
                        <span class="material-symbols-outlined filled text-xl text-secondary">apartment</span>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-body-md font-bold text-on-surface leading-snug mb-1">{{ __('services.safety_file_public') }}</h3>
                        <p class="text-[13px] text-secondary leading-relaxed line-clamp-2">{{ __('services.safety_file_public.desc') }}</p>
                    </div>
                </div>

                {{-- Dossier sécurité — établissements dangereux --}}
                <div class="flex items-start gap-4 rounded border border-dashed border-surface-dim bg-surface-container-lowest/70 p-4">
                    <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-surface-container" aria-hidden="true">
                        <span class="material-symbols-outlined filled text-xl text-secondary">report</span>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-body-md font-bold text-on-surface leading-snug mb-1">{{ __('services.safety_file_hazardous') }}</h3>
                        <p class="text-[13px] text-secondary leading-relaxed line-clamp-2">{{ __('services.safety_file_hazardous.desc') }}</p>
                    </div>
                </div>

                {{-- Notification de travaux --}}
                <div class="flex items-start gap-4 rounded border border-dashed border-surface-dim bg-surface-container-lowest/70 p-4">
                    <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-surface-container" aria-hidden="true">
                        <span class="material-symbols-outlined filled text-xl text-secondary">edit_note</span>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-body-md font-bold text-on-surface leading-snug mb-1">{{ __('services.works_notification') }}</h3>
                        <p class="text-[13px] text-secondary leading-relaxed line-clamp-2">{{ __('services.works_notification.desc') }}</p>
                    </div>
                </div>

                {{-- Interventions spéciales --}}
                <div class="flex items-start gap-4 rounded border border-dashed border-surface-dim bg-surface-container-lowest/70 p-4">
                    <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-surface-container" aria-hidden="true">
                        <span class="material-symbols-outlined filled text-xl text-secondary">bolt</span>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-body-md font-bold text-on-surface leading-snug mb-1">{{ __('services.special_interventions') }}</h3>
                        <p class="text-[13px] text-secondary leading-relaxed line-clamp-2">{{ __('services.special_interventions.desc') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===========================================================
         STATISTIQUES — chiffres sobres, accent rouge discret
         =========================================================== -->
    <section class="py-10 sm:py-12 border-y border-surface-dim bg-surface-container-low" aria-label="{{ __('stats.services') }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black tabular-nums text-on-surface mb-2">12+</p>
                    <div class="h-1 w-8 bg-primary/70 rounded-full mx-auto mb-2.5" aria-hidden="true"></div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('stats.services') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black tabular-nums text-on-surface mb-2">{{ \App\Models\User::count() }}+</p>
                    <div class="h-1 w-8 bg-primary/70 rounded-full mx-auto mb-2.5" aria-hidden="true"></div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('stats.users') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black tabular-nums text-on-surface mb-2">1 247</p>
                    <div class="h-1 w-8 bg-primary/70 rounded-full mx-auto mb-2.5" aria-hidden="true"></div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('stats.completed') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black tabular-nums text-on-surface mb-2">96%</p>
                    <div class="h-1 w-8 bg-primary/70 rounded-full mx-auto mb-2.5" aria-hidden="true"></div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('stats.satisfaction') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===========================================================
         BANDE D'ACTION FINALE — bleu nuit institutionnel (pas de rouge)
         =========================================================== -->
    <section class="cta-band relative overflow-hidden no-print">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-16 text-center">
            <h2 class="text-headline-md sm:text-headline-lg text-inverse-on-surface tracking-tight mb-4">
                {{ __('home.cta_title') }}
            </h2>
            <p class="text-body-lg text-inverse-on-surface/80 mb-8 max-w-2xl mx-auto leading-relaxed">
                {{ __('home.cta_text') }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
                @auth
                    @if ($hasRequests)
                        <a href="{{ route('mes-demandes') }}" class="ui-btn-primary w-full sm:w-auto px-8 py-3.5 text-body-md">
                            {{ __('nav.my_requests') }}
                        </a>
                    @endif
                    <a href="#services" class="inline-flex w-full sm:w-auto items-center justify-center px-8 py-3.5 rounded border border-inverse-on-surface/25 text-inverse-on-surface font-bold hover:bg-inverse-on-surface/10 transition-colors">
                        {{ __('nav.services') }}
                    </a>
                @else
                    <a href="{{ route('register') }}" class="ui-btn-primary w-full sm:w-auto px-8 py-3.5 text-body-md">
                        {{ __('register.submit') }}
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex w-full sm:w-auto items-center justify-center px-8 py-3.5 rounded border border-inverse-on-surface/25 text-inverse-on-surface font-bold hover:bg-inverse-on-surface/10 transition-colors">
                        {{ __('login.title') }}
                    </a>
                @endauth
            </div>
        </div>
    </section>
</x-app-layout>
