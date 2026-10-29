<x-app-layout>
    {{-- ================================================================
         PAGE D'ACCUEIL — Portail Citoyen ONPC (design/landing.html)
         ================================================================ --}}

    <!-- Hero Section -->
    <section class="hero-panel relative overflow-hidden no-print">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20 lg:py-28">
            <div class="text-center max-w-3xl mx-auto">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded bg-white/10 border border-white/20 text-white/85 text-xs font-bold uppercase tracking-wider mb-6">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    {{ __('services.title') }}
                </div>

                <h1 class="text-display-hero-mobile sm:text-display-hero font-black text-white uppercase tracking-tight mb-4 sm:mb-6">
                    {{ __('app.tagline') }}
                </h1>

                <p class="text-body-lg text-white/85 max-w-2xl mx-auto mb-8 sm:mb-10 leading-relaxed">
                    {{ __('app.description') }}
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
                    @auth
                        <a href="#services" class="ui-ghost-button w-full sm:w-auto px-8 py-3.5 text-body-md text-center">
                            {{ __('nav.services') }}
                        </a>
                        @if ($hasRequests)
                            <a href="{{ route('mes-demandes') }}" class="ui-soft-button w-full sm:w-auto px-8 py-3.5 text-body-md text-center">
                                {{ __('nav.my_requests') }}
                            </a>
                        @endif
                    @else
                        <a href="{{ route('register') }}" class="ui-ghost-button w-full sm:w-auto px-8 py-3.5 text-body-md text-center">
                            {{ __('register.submit') }}
                        </a>
                        <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-3.5 text-body-md text-center inline-flex items-center justify-center gap-2 bg-surface-container-highest hover:bg-surface-bright text-on-surface font-bold rounded transition-colors">
                            {{ __('login.title') }}
                        </a>
                    @endauth
                </div>

            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-12 sm:py-16 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-14">
                <span class="inline-block bg-primary text-on-primary text-label-caps font-label-caps px-2 py-0.5 rounded uppercase mb-3">
                    {{ __('services.title') }}
                </span>
                <h2 class="text-headline-lg text-on-surface tracking-tight mb-3">
                    {{ __('services.subtitle') }}
                </h2>
                <p class="text-body-md text-secondary">
                    {{ __('stats.services') }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">

                {{-- Service 1 - شهادة الوقاية --}}
                <a href="{{ route('prevention-requests.index') }}" class="group relative onpc-kpi hover:shadow-md flex flex-col">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="material-symbols-outlined filled text-3xl text-primary p-2.5 bg-primary/10 rounded" aria-hidden="true">fact_check</span>
                            <h3 class="text-headline-sm text-on-surface mt-3">{{ __('services.prevention_certificate') }}</h3>
                        </div>
                    </div>
                    <p class="text-body-sm text-secondary mt-2 mb-4 flex-1">{{ __('services.prevention_certificate.desc') }}</p>
                    <span class="inline-flex items-center gap-1 text-body-sm font-bold text-primary group-hover:gap-2 transition-all">
                        {{ __('services.access_service') }}
                        <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
                    </span>
                </a>

                {{-- Service 2 - التطوع في خدمة الحماية المدنية --}}
                <div class="group relative onpc-kpi opacity-80 cursor-not-allowed border-dashed border-error/40 flex flex-col">
                    <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-amber-800 bg-amber-50 rounded border border-amber-200">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">warning</span>
                            {{ __('services.under_construction') }}
                        </span>
                    </div>
                    <span class="material-symbols-outlined filled text-3xl text-error p-2.5 bg-error-container rounded w-fit" aria-hidden="true">volunteer_activism</span>
                    <h3 class="text-headline-sm text-on-surface mt-3 ps-0 me-16">{{ __('services.volunteering') }}</h3>
                    <p class="text-body-sm text-secondary mt-2 mb-4 flex-1">{{ __('services.volunteering.desc') }}</p>
                    <span class="inline-flex items-center gap-1 text-body-sm font-bold text-secondary">
                        {{ __('services.coming_soon') }}
                    </span>
                </div>

                {{-- Service 3 - خدمات التكوين والرسكلة بمقابل --}}
                <div class="group relative onpc-kpi opacity-80 cursor-not-allowed border-dashed border-error/40 flex flex-col">
                    <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-amber-800 bg-amber-50 rounded border border-amber-200">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">warning</span>
                            {{ __('services.under_construction') }}
                        </span>
                    </div>
                    <span class="material-symbols-outlined filled text-3xl text-tertiary p-2.5 bg-tertiary-container/15 rounded w-fit" aria-hidden="true">school</span>
                    <h3 class="text-headline-sm text-on-surface mt-3 me-16">{{ __('services.training') }}</h3>
                    <p class="text-body-sm text-secondary mt-2 mb-4 flex-1">{{ __('services.training.desc') }}</p>
                    <span class="inline-flex items-center gap-1 text-body-sm font-bold text-secondary">
                        {{ __('services.coming_soon') }}
                    </span>
                </div>

                {{-- Service 4 - الحضور الوقائي --}}
                <a href="{{ route('preventive-presence.index') }}" class="group relative onpc-kpi flex flex-col">
                    <span class="material-symbols-outlined filled text-3xl text-emerald-700 p-2.5 bg-emerald-50 rounded w-fit" aria-hidden="true">visibility</span>
                    <h3 class="text-headline-sm text-on-surface mt-3">{{ __('services.preventive_presence') }}</h3>
                    <p class="text-body-sm text-secondary mt-2 mb-4 flex-1">{{ __('services.preventive_presence.desc') }}</p>
                    <span class="inline-flex items-center gap-1 text-body-sm font-bold text-primary group-hover:gap-2 transition-all">
                        {{ __('services.access_service') }}
                        <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
                    </span>
                </a>

                {{-- Service 5 - المصادقة على مخطط التدخل الداخلي --}}
                <div class="group relative onpc-kpi opacity-80 cursor-not-allowed border-dashed border-error/40 flex flex-col">
                    <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-amber-800 bg-amber-50 rounded border border-amber-200">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">warning</span>
                            {{ __('services.under_construction') }}
                        </span>
                    </div>
                    <span class="material-symbols-outlined filled text-3xl text-amber-700 p-2.5 bg-amber-50 rounded w-fit" aria-hidden="true">architecture</span>
                    <h3 class="text-headline-sm text-on-surface mt-3 me-16">{{ __('services.internal_intervention_plan') }}</h3>
                    <p class="text-body-sm text-secondary mt-2 mb-4 flex-1">{{ __('services.internal_intervention_plan.desc') }}</p>
                    <span class="inline-flex items-center gap-1 text-body-sm font-bold text-secondary">
                        {{ __('services.coming_soon') }}
                    </span>
                </div>

                {{-- Service 6 - ملف السلامة - البنايات العموم --}}
                <div class="group relative onpc-kpi opacity-80 cursor-not-allowed border-dashed border-error/40 flex flex-col">
                    <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-amber-800 bg-amber-50 rounded border border-amber-200">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">warning</span>
                            {{ __('services.under_construction') }}
                        </span>
                    </div>
                    <span class="material-symbols-outlined filled text-3xl text-secondary p-2.5 bg-surface-container rounded w-fit" aria-hidden="true">apartment</span>
                    <h3 class="text-headline-sm text-on-surface mt-3 me-16">{{ __('services.safety_file_public') }}</h3>
                    <p class="text-body-sm text-secondary mt-2 mb-4 flex-1">{{ __('services.safety_file_public.desc') }}</p>
                    <span class="inline-flex items-center gap-1 text-body-sm font-bold text-secondary">
                        {{ __('services.coming_soon') }}
                    </span>
                </div>

                {{-- Service 7 - ملف السلامة - المؤسسات الخطرة --}}
                <div class="group relative onpc-kpi opacity-80 cursor-not-allowed border-dashed border-error/40 flex flex-col">
                    <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-amber-800 bg-amber-50 rounded border border-amber-200">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">warning</span>
                            {{ __('services.under_construction') }}
                        </span>
                    </div>
                    <span class="material-symbols-outlined filled text-3xl text-tertiary p-2.5 bg-tertiary-container/15 rounded w-fit" aria-hidden="true">report</span>
                    <h3 class="text-headline-sm text-on-surface mt-3 me-16">{{ __('services.safety_file_hazardous') }}</h3>
                    <p class="text-body-sm text-secondary mt-2 mb-4 flex-1">{{ __('services.safety_file_hazardous.desc') }}</p>
                    <span class="inline-flex items-center gap-1 text-body-sm font-bold text-secondary">
                        {{ __('services.coming_soon') }}
                    </span>
                </div>

                {{-- Service 8 - الإعلام عن أشغال --}}
                <div class="group relative onpc-kpi opacity-80 cursor-not-allowed border-dashed border-error/40 flex flex-col">
                    <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-amber-800 bg-amber-50 rounded border border-amber-200">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">warning</span>
                            {{ __('services.under_construction') }}
                        </span>
                    </div>
                    <span class="material-symbols-outlined filled text-3xl text-amber-700 p-2.5 bg-amber-50 rounded w-fit" aria-hidden="true">edit_note</span>
                    <h3 class="text-headline-sm text-on-surface mt-3 me-16">{{ __('services.works_notification') }}</h3>
                    <p class="text-body-sm text-secondary mt-2 mb-4 flex-1">{{ __('services.works_notification.desc') }}</p>
                    <span class="inline-flex items-center gap-1 text-body-sm font-bold text-secondary">
                        {{ __('services.coming_soon') }}
                    </span>
                </div>

                {{-- Service 9 - شهادة نجدة وإنقاذ --}}
                <a href="{{ route('rescue-certificates.index') }}" class="group relative onpc-kpi flex flex-col">
                    <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-emerald-800 bg-emerald-50 rounded border border-emerald-200">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">check_circle</span>
                            {{ __('services.free') }}
                        </span>
                    </div>
                    <span class="material-symbols-outlined filled text-3xl text-error p-2.5 bg-error-container rounded w-fit" aria-hidden="true">health_and_safety</span>
                    <h3 class="text-headline-sm text-on-surface mt-3 me-16">{{ __('services.rescue_certificate') }}</h3>
                    <p class="text-body-sm text-secondary mt-2 mb-4 flex-1">{{ __('services.rescue_certificate.desc') }}</p>
                    <span class="inline-flex items-center gap-1 text-body-sm font-bold text-primary group-hover:gap-2 transition-all">
                        {{ __('services.access_service') }}
                        <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
                    </span>
                </a>

                {{-- Service 10 - التدخلات الخاصة بمقابل --}}
                <div class="group relative onpc-kpi opacity-80 cursor-not-allowed border-dashed border-error/40 flex flex-col">
                    <div class="absolute top-3 {{ app()->getLocale() === 'ar' ? 'left-3' : 'right-3' }}">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-amber-800 bg-amber-50 rounded border border-amber-200">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">warning</span>
                            {{ __('services.under_construction') }}
                        </span>
                    </div>
                    <span class="material-symbols-outlined filled text-3xl text-primary p-2.5 bg-primary/10 rounded w-fit" aria-hidden="true">bolt</span>
                    <h3 class="text-headline-sm text-on-surface mt-3 me-16">{{ __('services.special_interventions') }}</h3>
                    <p class="text-body-sm text-secondary mt-2 mb-4 flex-1">{{ __('services.special_interventions.desc') }}</p>
                    <span class="inline-flex items-center gap-1 text-body-sm font-bold text-secondary">
                        {{ __('services.coming_soon') }}
                    </span>
                </div>

            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 sm:py-16 bg-surface-container-low border-y border-surface-dim">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black text-primary mb-1">12+</p>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('stats.services') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black text-primary mb-1">{{ \App\Models\User::count() }}+</p>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('stats.users') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black text-primary mb-1">1 247</p>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('stats.completed') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black text-primary mb-1">96%</p>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('stats.satisfaction') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="hero-panel relative overflow-hidden py-12 sm:py-16 no-print">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 text-center">
            <h2 class="text-headline-md sm:text-headline-lg font-bold text-white uppercase tracking-tight mb-4">
                {{ __('app.tagline') }}
            </h2>
            <p class="text-body-lg text-white/85 mb-8 max-w-2xl mx-auto">
                {{ __('app.description') }}
            </p>
            @auth
                @if ($hasRequests)
                    <a href="{{ route('mes-demandes') }}" class="ui-soft-button px-8 py-3.5 text-body-md">
                        {{ __('nav.my_requests') }}
                    </a>
                @endif
                <a href="#services" class="ui-ghost-button px-8 py-3.5 text-body-md">
                    {{ __('nav.services') }}
                </a>
            @else
                <a href="{{ route('register') }}" class="ui-ghost-button px-8 py-3.5 text-body-md">
                    {{ __('register.submit') }}
                </a>
            @endauth
        </div>
    </section>
</x-app-layout>
