<x-app-layout>
    {{-- ================================================================
         ESPACE CITOYEN — Tableau de bord (design/demades.html)
         ================================================================ --}}
    {{-- Pas de slot "header" : le bloc héro ci-dessous sert d'en-tête de page --}}
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Bloc de bienvenue façon design/demades.html --}}
            <section class="relative overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-sm">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-primary-container" aria-hidden="true"></div>
                <div class="p-6 md:p-8 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="space-y-2 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded bg-secondary-container text-on-secondary-fixed text-label-caps font-label-caps uppercase">
                            <span class="material-symbols-outlined text-[14px]" aria-hidden="true">verified_user</span>
                            <span>{{ app()->getLocale() === 'ar' ? 'البوابة الرسمية المؤمّنة — وكالة الحماية المدنية' : "PORTAIL OFFICIEL SÉCURISÉ — GUICHET UNIQUE ONPC" }}</span>
                        </div>
                        <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">
                            {{ __('prevention_request.dashboard_title') }}
                        </h1>
                        <p class="text-body-md leading-relaxed text-secondary">
                            {{ __('prevention_request.dashboard_subtitle') }}
                        </p>
                    </div>
                </div>
            </section>

            {{-- Services disponibles --}}
            <section aria-label="{{ __('nav.services') }}">
                <h2 class="text-title-section text-secondary uppercase tracking-wider mb-4">
                    {{ app()->getLocale() === 'ar' ? 'الخدمات المتاحة' : 'Services disponibles' }}
                </h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Service 1 : Certificat de prévention --}}
                    <article class="group relative overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition-all duration-300 hover:border-primary-container/40 hover:shadow-md">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-primary-container" aria-hidden="true"></div>
                        <div class="p-6 sm:p-7">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <span class="material-symbols-outlined filled text-2xl" aria-hidden="true">verified_user</span>
                            </div>
                            <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('services.prevention_certificate') }}</h3>
                            <p class="mt-2 text-body-sm leading-relaxed text-secondary">
                                {{ __('services.prevention_certificate.desc') }}
                            </p>
                            <a href="{{ route('prevention-requests.index') }}" class="ui-btn-primary mt-5 inline-flex w-full items-center justify-center gap-2 px-5 py-3 text-body-sm">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">description</span>
                                <span>{{ __('prevention_request.list_requests') }}</span>
                            </a>
                        </div>
                    </article>

                    {{-- Service 2 : escorte / présence préventive --}}
                    <article class="group relative overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition-all duration-300 hover:border-primary-container/40 hover:shadow-md">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-primary-container" aria-hidden="true"></div>
                        <div class="p-6 sm:p-7">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-secondary-container text-on-secondary-fixed">
                                <span class="material-symbols-outlined filled text-2xl" aria-hidden="true">group</span>
                            </div>
                            <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('services.preventive_presence') }}</h3>
                            <p class="mt-2 text-body-sm leading-relaxed text-secondary">
                                {{ __('services.preventive_presence.desc') }}
                            </p>
                            <a href="{{ route('preventive-presence.index') }}" class="ui-btn-primary mt-5 inline-flex w-full items-center justify-center gap-2 px-5 py-3 text-body-sm">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">description</span>
                                <span>{{ __('preventive_presence.my_requests') }}</span>
                            </a>
                        </div>
                    </article>
                </div>
            </section>

            {{-- Accès rapide citoyen --}}
            <section aria-label="{{ app()->getLocale() === 'ar' ? 'روابط مهنية' : 'Accès rapides' }}"
                     class="grid gap-4 sm:grid-cols-3">
                <a href="{{ route('mes-demandes') }}" class="flex items-center justify-between gap-3 rounded border border-surface-dim bg-surface-container-low p-4 shadow-sm transition-colors hover:border-primary-container/40 hover:bg-surface-container">
                    <span class="text-label-caps font-label-caps uppercase tracking-wider text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">folder_shared</span>
                        {{ __('nav.my_requests') }}
                    </span>
                    <span class="material-symbols-outlined text-[18px] rtl:rotate-180 text-secondary" aria-hidden="true">chevron_right</span>
                </a>
                <div class="flex items-center justify-between gap-3 rounded border border-surface-dim bg-surface-container-low p-4 shadow-sm" title="{{ app()->getLocale() === 'ar' ? 'قريبًا' : 'Bientôt disponible' }}">
                    <span class="text-label-caps font-label-caps uppercase tracking-wider text-secondary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-outline" aria-hidden="true">workspace_premium</span>
                        {{ app()->getLocale() === 'ar' ? 'شهادات الإسعاف' : 'Mes attestations de secourisme' }}
                    </span>
                    <span class="material-symbols-outlined text-[16px] text-outline" aria-hidden="true">schedule</span>
                </div>
                <div class="flex items-center justify-between gap-3 rounded border border-surface-dim bg-surface-container-low p-4 shadow-sm" title="{{ app()->getLocale() === 'ar' ? 'قريبًا' : 'Bientôt disponible' }}">
                    <span class="text-label-caps font-label-caps uppercase tracking-wider text-secondary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-outline" aria-hidden="true">event_available</span>
                        {{ app()->getLocale() === 'ar' ? 'حجز موعد' : 'Rendez-vous en salle des opérations' }}
                    </span>
                    <span class="material-symbols-outlined text-[16px] text-outline" aria-hidden="true">schedule</span>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
