@php
    $si = $specialIntervention;

    $step1Lines = array_filter([
        $si->nom_prenom,
        $si->cin,
        $si->phone,
        $si->governorate?->name,
    ]);

    $existing = collect($si->interventions ?? []);

    $wizardSteps = [
        [
            'state'     => 'done',
            'title'     => __('special_intervention.step_one_title'),
            'data'      => array_values($step1Lines),
            'editUrl'   => route('special-interventions.step1.edit', $si),
            'editLabel' => __('special_intervention.edit_step1'),
        ],
        [
            'state'   => 'active',
            'title'   => __('special_intervention.step_two_title'),
        ],
        [
            'state' => 'upcoming',
            'title' => __('special_intervention.step_three_title'),
        ],
    ];
@endphp

<x-app-layout>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            <x-onpc.dossier-stepper :steps="$wizardSteps" />

            <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <span class="text-label-caps text-primary uppercase tracking-wider">
                        {{ __('special_intervention.step_of', ['step' => 2, 'total' => 3]) }}
                    </span>
                    <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('special_intervention.step2_heading') }} — {{ __('special_intervention.step_two_title') }}</h2>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-amber-50 text-amber-800 text-body-sm font-semibold border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800">
                    <span class="material-symbols-outlined text-[15px]" aria-hidden="true">payments</span>
                    <span id="selection-count">0</span> {{ __('special_intervention.selected') }}
                </span>
            </div>

            <form method="POST" action="{{ route('special-interventions.step2.store', $si) }}"
                  id="si-step2-form"
                  class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800">
                @csrf

                <div class="p-6 pb-0">
                    @if ($errors->any())
                        <div class="rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800 dark:bg-red-900/30 dark:text-red-200 mb-4" role="alert">
                            <p class="font-bold">{{ __('special_intervention.create_error_title') }}</p>
                            <ul class="mt-1.5 list-disc space-y-1 ps-5">
                                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                {{-- ═══════════════════════════════════════════════════════
                     Billing note
                     ═══════════════════════════════════════════════════════ --}}
                <div class="px-6 pt-4 pb-0">
                    <div class="flex items-start gap-3 rounded border border-amber-200 bg-amber-50 p-4 dark:bg-amber-900/10 dark:border-amber-800/40">
                        <span class="material-symbols-outlined text-amber-600 text-[20px] mt-px shrink-0" aria-hidden="true">info</span>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wider mb-1">{{ __('special_intervention.fees_title') }}</p>
                            <p class="text-[11px] text-amber-700 dark:text-amber-400 leading-relaxed">{{ __('special_intervention.billing_note') }}</p>
                            <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-1">{{ __('special_intervention.diving_team_note') }}</p>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════
                     SECTION — INTERVENTIONS + SUMMARY
                     ═══════════════════════════════════════════════════════ --}}
                <div class="p-6 space-y-5">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                        <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">A</span>
                        <h3 class="text-title-section text-on-surface uppercase tracking-wider">{{ __('special_intervention.intervention_section') }}</h3>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {{-- Intervention list --}}
                        <div class="lg:col-span-2">
                            <ol id="interventions-list" class="space-y-2.5">
                                @foreach ($interventions as $key => $intervention)
                                    @php
                                        $ex      = $existing->firstWhere('key', $key);
                                        $checked = $ex !== null;
                                        $hours   = $ex ? ($ex['hours'] ?? 1) : 1;
                                    @endphp
                                    <li class="intervention-card rounded border p-3.5 transition dark:border-gray-700 {{ $checked ? 'border-primary/40 bg-primary/5 dark:bg-primary/10' : 'border-surface-dim bg-surface-container-low/40' }}"
                                        data-key="{{ $key }}"
                                        data-price="{{ $intervention['price'] }}">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox"
                                                   id="intv_{{ $key }}"
                                                   name="interventions[{{ $key }}][key]"
                                                   value="{{ $key }}"
                                                   class="h-4 w-4 rounded border-surface-dim text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-800"
                                                   {{ $checked ? 'checked' : '' }}>
                                            <label for="intv_{{ $key }}" class="flex-1 min-w-0 cursor-pointer">
                                                <span class="block text-body-sm font-semibold text-on-surface dark:text-gray-100 leading-snug">{{ app()->getLocale() === 'ar' ? $intervention['name_ar'] : $intervention['name_fr'] }}</span>
                                                <span class="block text-[11px] text-secondary mt-0.5" dir="ltr">
                                                    {{ number_format($intervention['price'], 0) }} {{ __('special_intervention.currency_tnd') }} / {{ __('special_intervention.intervention_qty') }}
                                                </span>
                                            </label>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <label class="flex items-center gap-1.5 rounded border border-surface-dim bg-surface-container px-2 py-1.5 text-[11px] font-bold text-secondary uppercase dark:border-gray-600 dark:bg-gray-800">
                                                    {{ __('special_intervention.intervention_qty') }}
                                                    <input type="number"
                                                           name="interventions[{{ $key }}][hours]"
                                                           value="{{ $hours }}"
                                                           min="1"
                                                           max="168"
                                                           class="w-14 text-center text-xs font-mono font-bold text-on-surface border-b border-surface-dim focus:border-primary bg-transparent focus:outline-none dark:text-gray-100"
                                                           {{ $checked ? '' : 'disabled' }}>
                                                </label>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        {{-- Live summary --}}
                        <div class="lg:col-span-1">
                            <div class="rounded-lg border border-surface-dim bg-surface-container-low p-5 sticky top-6 space-y-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-secondary">{{ __('special_intervention.intervention_total') }}</h4>
                                <div id="summary-rows" class="space-y-2 min-h-[2rem]">
                                    <p id="summary-empty" class="text-xs text-outline italic {{ $existing->isEmpty() ? '' : 'hidden' }}">{{ __('special_intervention.no_interventions_selected') }}</p>
                                </div>
                                <div class="border-t border-surface-dim pt-3 dark:border-gray-700">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <span class="text-xs font-bold text-on-surface uppercase tracking-wider">{{ __('special_intervention.total_amount') }}</span>
                                        <span id="total-display" class="text-xl font-black tabular-nums text-primary" dir="ltr">
                                            {{ number_format($si->total_amount ?? 0, 0) }}
                                        </span>
                                        <span class="text-xs font-semibold text-secondary uppercase">{{ __('special_intervention.currency_tnd') }}</span>
                                    </div>
                                </div>
                                <p class="text-[10px] text-secondary leading-relaxed">{{ __('special_intervention.payment_proof') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim px-6 py-5 dark:border-gray-700">
                    <a href="{{ route('special-interventions.step1.edit', $si) }}"
                       class="inline-flex items-center justify-center rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        <svg class="me-2 h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ __('special_intervention.back') }}
                    </a>
                    <button type="submit" id="step2-submit-btn"
                        class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                        data-loading-text="{{ __('special_intervention.submitting') }}">
                        <span id="step2-submit-label">{{ __('special_intervention.continue') }}</span>
                        <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        var list        = document.getElementById('interventions-list');
        var summaryRows = document.getElementById('summary-rows');
        var summaryEmpty= document.getElementById('summary-empty');
        var totalDisplay= document.getElementById('total-display');
        var selCount    = document.getElementById('selection-count');
        var form        = document.getElementById('si-step2-form');
        var submitBtn   = document.getElementById('step2-submit-btn');
        var submitLbl   = document.getElementById('step2-submit-label');
        var TND         = @json(__('special_intervention.currency_tnd'));

        function update() {
            var total = 0, count = 0;
            summaryRows.querySelectorAll('.summary-row').forEach(function (r) { r.remove(); });

            list.querySelectorAll('.intervention-card').forEach(function (card) {
                var cb    = card.querySelector('input[type="checkbox"]');
                var inp   = card.querySelector('input[type="number"]');
                var price = parseInt(card.dataset.price || '0', 10);
                var name  = card.querySelector('label span:first-child').textContent.trim();

                if (!cb.checked) return;
                var hours  = parseInt(inp.value, 10) || 1;
                var amount = price * hours;
                total += amount; count++;

                var row = document.createElement('div');
                row.className = 'summary-row flex items-start justify-between gap-2 text-xs';
                row.innerHTML =
                    '<span class="text-secondary leading-snug max-w-[120px]">' + name + ' ×' + hours + 'h</span>' +
                    '<span class="font-mono font-bold text-on-surface tabular-nums shrink-0" dir="ltr">' + amount.toLocaleString() + ' ' + TND + '</span>';
                summaryRows.appendChild(row);
            });

            totalDisplay.textContent = total.toLocaleString();
            if (selCount) selCount.textContent = count;
            summaryEmpty.classList.toggle('hidden', count > 0);
        }

        list.addEventListener('change', function (e) {
            var card = e.target.closest('.intervention-card');
            if (!card) return;
            var cb  = card.querySelector('input[type="checkbox"]');
            var inp = card.querySelector('input[type="number"]');
            if (e.target === cb) {
                inp.disabled = !cb.checked;
                card.classList.toggle('border-primary/40', cb.checked);
                card.classList.toggle('bg-primary/5', cb.checked);
                card.classList.toggle('dark:bg-primary/10', cb.checked);
                card.classList.toggle('border-surface-dim', !cb.checked);
                card.classList.toggle('bg-surface-container-low/40', !cb.checked);
            }
            update();
        });

        list.addEventListener('input', function (e) {
            if (e.target.matches('input[type="number"]')) update();
        });

        if (form) {
            form.addEventListener('submit', function () {
                if (submitBtn) submitBtn.disabled = true;
                var lt = submitBtn ? submitBtn.getAttribute('data-loading-text') : null;
                if (submitLbl && lt) submitLbl.textContent = lt;
            });
        }

        update();
    }());
    </script>
</x-app-layout>
