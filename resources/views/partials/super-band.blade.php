{{-- =====================================================================
     BANDEAU OFFICIEL DE L'ÉTAT — République Tunisienne / Ministère Intérieur
     Reusable across citizen + admin chrome. Usage: @include('partials.super-band')
     ===================================================================== --}}
<div class="bg-inverse-surface text-inverse-on-surface text-body-sm font-body-sm border-b border-secondary/40">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-1.5 flex items-center justify-between gap-3">
        <!-- Pays & Ministère -->
        <div class="flex items-center gap-2 text-[11px] font-semibold tracking-wider min-w-0 truncate">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-primary-container animate-alert-pulse shrink-0"></span>
            <span class="text-inverse-primary uppercase">{{ app()->getLocale() === 'ar' ? 'الجمهورية التونسية' : 'RÉPUBLIQUE TUNISIENNE' }}</span>
            <span class="text-secondary-fixed opacity-60 hidden sm:inline">|</span>
            <span class="text-surface-dim uppercase hidden sm:inline">{{ app()->getLocale() === 'ar' ? "Ministère de l'Intérieur" : "MINISTÈRE DE L'INTÉRIEUR" }}</span>
            <span class="font-arabic-crest text-sm tracking-normal text-surface-variant font-bold hidden md:inline">الجمهورية التونسية - وزارة الداخلية</span>
        </div>
        <!-- Horloge opérationnelle (temps réel) -->
        <div class="hidden md:flex items-center gap-1.5 text-[11px] font-mono text-surface-dim shrink-0">
            <span class="material-symbols-outlined text-[14px] text-primary-fixed" aria-hidden="true">cloud_sync</span>
            <span class="font-bold text-surface-bright tracking-widest" id="onpcLiveClock">
                @if(function_exists('now'))
                    {{ now()->timezone('Africa/Tunis')->format('d/m/Y — H:i') }} CET
                @endif
            </span>
        </div>
    </div>
</div>

<script>
/* Horloge temps réelle — Africa/Tunis */
    (function () {
        function updateOnpcClock() {
            var el = document.getElementById('onpcLiveClock');
            if (!el) return;
            try {
                var fmt = new Intl.DateTimeFormat('fr-FR', {
                    day: '2-digit', month: 'long', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', second: '2-digit',
                    timeZone: 'Africa/Tunis'
                });
                el.textContent = fmt.format(new Date()).toUpperCase() + ' CET';
            } catch (e) { /* no-op */ }
        }
        document.addEventListener('DOMContentLoaded', function () {
            updateOnpcClock();
            setInterval(updateOnpcClock, 1000);
        });
    })();
</script>
