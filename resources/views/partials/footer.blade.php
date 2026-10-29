{{-- =====================================================================
     PIED DE PAGE INSTITUTIONNEL CITOTYEN — style design/landing-footer
     ===================================================================== --}}
<footer class="mt-auto bg-inverse-surface text-inverse-on-surface border-t border-secondary">
    {{-- Colonnes liens & légal --}}
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6 text-body-sm">
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center gap-3 mb-3">
                    <img src="/images/logo.png" alt="" class="w-8 h-8 object-contain">
                    <div class="leading-tight">
                        <span class="block font-bold text-surface-bright text-xs uppercase tracking-wide">{{ __('app.name') }}</span>
                        <span class="block text-[10px] text-surface-dim">{{ app()->getLocale() === 'ar' ? __('app.name') : 'الديوان الوطني للحماية المدنية' }}</span>
                    </div>
                </div>
                <p class="text-xs sm:text-sm text-surface-dim leading-relaxed">{{ __('app.description') }}</p>
            </div>
            <div>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-surface-dim mb-3">{{ __('footer.about') }}</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-xs sm:text-sm text-surface-dim hover:text-surface-bright transition-colors">{{ __('footer.about') }}</a></li>
                    <li><a href="#" class="text-xs sm:text-sm text-surface-dim hover:text-surface-bright transition-colors">{{ __('footer.contact') }}</a></li>
                    <li><a href="#" class="text-xs sm:text-sm text-surface-dim hover:text-surface-bright transition-colors">{{ __('footer.help') }}</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-surface-dim mb-3">{{ __('services.title') }}</h4>
                <ul class="space-y-2">
                    <li><a href="#services" class="text-xs sm:text-sm text-surface-dim hover:text-surface-bright transition-colors">{{ __('services.prevention_certificate') }}</a></li>
                    <li><a href="#services" class="text-xs sm:text-sm text-surface-dim hover:text-surface-bright transition-colors">{{ __('services.preventive_presence') }}</a></li>
                    <li><a href="#services" class="text-xs sm:text-sm text-surface-dim hover:text-surface-bright transition-colors">{{ __('services.rescue_certificate') }}</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-surface-dim mb-3">{{ __('footer.terms') }}</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-xs sm:text-sm text-surface-dim hover:text-surface-bright transition-colors">{{ __('footer.privacy') }}</a></li>
                    <li><a href="#" class="text-xs sm:text-sm text-surface-dim hover:text-surface-bright transition-colors">{{ __('footer.terms') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-secondary/40 pt-5 flex flex-col md:flex-row items-center justify-between gap-2 text-[11px] text-surface-dim">
            <span>&copy; {{ date('Y') }} {{ __('app.name') }} — {{ app()->getLocale() === 'ar' ? __('app.name') : 'الديوان الوطني للحماية المدنية' }}. {{ __('footer.rights') }}.</span>
            <span class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-emerald-400 text-sm" aria-hidden="true">verified_user</span>
                {{ app()->getLocale() === 'ar' ? 'Plateforme sécurisée — Chiffrement SSL/TLS' : "Plateforme sécurisée TLS / Cachet TunTrust — Ministère de l'Intérieur" }}
            </span>
        </div>
    </div>
</footer>
