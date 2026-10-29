{{-- =====================================================================
     BANDEAU OFFICIEL DE L'ÉTAT — République Tunisienne / Ministère Intérieur
     Reusable across citizen + admin chrome. Usage: @include('partials.super-band')
     Affiche uniquement la langue active (fr → français, ar → arabe),
     design identique dans les deux cas. Horloge localisée.
     ===================================================================== --}}
@php
    $isAr = app()->getLocale() === 'ar';
@endphp
<div class="bg-inverse-surface text-inverse-on-surface text-body-sm font-body-sm border-b border-secondary/40">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-1.5 flex items-center justify-between gap-3">
        <!-- Pays & Ministère — langue active uniquement -->
        <div class="flex items-center gap-2 text-[11px] font-semibold tracking-wider min-w-0 truncate">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-primary-container animate-alert-pulse shrink-0"></span>
            <span class="text-inverse-primary uppercase">{{ $isAr ? 'الجمهورية التونسية' : 'RÉPUBLIQUE TUNISIENNE' }}</span>
            <span class="text-secondary-fixed opacity-60 hidden sm:inline" aria-hidden="true">|</span>
            <span class="uppercase hidden sm:inline">{{ $isAr ? 'وزارة الداخلية' : "Ministère de l'Intérieur" }}</span>
        </div>
        <!-- Horloge opérationnelle (temps réel) -->
        <div class="hidden md:flex items-center gap-1.5 text-[11px] font-mono shrink-0">
            <span class="material-symbols-outlined text-[14px]" aria-hidden="true">cloud_sync</span>
            <span class="font-bold tracking-widest" id="onpcLiveClock" dir="ltr">
                @if(function_exists('now'))
                    {{ now()->timezone('Africa/Tunis')->format('d/m/Y — H:i') }}
                @endif
            </span>
        </div>
    </div>
</div>

<script>
/* Horloge temps réelle — Africa/Tunis, format adapté à la langue active */
    (function () {
        function updateOnpcClock() {
            var el = document.getElementById('onpcLiveClock');
            if (!el) return;
            var isAr = document.documentElement.lang === 'ar';
            try {
                var fmt = new Intl.DateTimeFormat(isAr ? 'ar-TN-u-nu-latn' : 'fr-FR', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', second: '2-digit',
                    timeZone: 'Africa/Tunis'
                });
                var stamp = fmt.format(new Date());
                el.textContent = isAr ? stamp + ' — CET' : stamp.toUpperCase() + ' CET';
            } catch (e) { /* no-op */ }
        }
        document.addEventListener('DOMContentLoaded', function () {
            updateOnpcClock();
            setInterval(updateOnpcClock, 1000);
        });
    })();
</script>
