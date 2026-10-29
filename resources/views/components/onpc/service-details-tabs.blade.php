{{-- ============================================================
     CARTE À ONGLETS « Détails de la démarche » — Certificat de prévention
     Onglet 1 : dossier/brouillon en cours (reprise + suppression)
     Onglets 2..6 : contenu réglementaire officiel (service_details.php)
     Transitions : fondu + glissement 220ms, responsive + ARIA tabs
     Répliqué du gabarit design/nouvelle.html / welcome.blade.php
     Props : drafts (collection PreventionDossier)
     ============================================================ --}}
@php
    $docs       = __('service_details.docs');
    $legals     = __('service_details.legals');
    $stepsData  = __('service_details.steps');
    $entitiesRx = __('service_details.receiving_entities');
    $entitiesTx = __('service_details.providing_entities');
@endphp

<div class="rounded-lg border border-surface-dim bg-surface-container-lowest shadow-sm overflow-hidden dark:border-gray-700 dark:bg-gray-800" data-svc-tabs>

    {{-- Filet accent ONPC --}}
    <div class="h-1.5 bg-gradient-to-r from-primary via-primary-container to-transparent" aria-hidden="true"></div>

    {{-- En-tête --}}
    <div class="flex items-center gap-3 px-4 sm:px-5 pt-4 pb-0">
        <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-primary/10">
            <span class="material-symbols-outlined filled text-xl text-primary" aria-hidden="true">description</span>
        </span>
        <div class="min-w-0">
            <span class="text-label-caps text-primary uppercase tracking-wider block">{{ __('service_details.about_kicker') }}</span>
            <h2 class="text-headline-sm text-on-surface">{{ __('service_details.about_title') }}</h2>
        </div>

        @if (isset($drafts) && $drafts->isNotEmpty())
            <span class="ms-auto hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-200 text-[11px] font-bold text-amber-800 dark:bg-amber-800/40 dark:text-amber-300">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                {{ $drafts->count() }} &bull; {{ __('prevention_request.drafts_title') }}
            </span>
        @endif
    </div>

    {{-- ── Barre d'onglets ── --}}
    <div class="mt-3 border-b border-surface-dim dark:border-gray-700 px-2 sm:px-4">
        <div role="tablist" aria-label="{{ __('service_details.about_title') }}"
             class="flex gap-1 overflow-x-auto -mb-px no-scrollbar">

            @foreach (['dossier' => __('service_details.tab_current'), 'process' => __('service_details.tab_process'), 'docs' => __('service_details.tab_docs'), 'fees' => __('service_details.tab_fees'), 'conditions' => __('service_details.tab_conditions'), 'more' => __('service_details.tab_more')] as $key => $label)
                <button type="button" role="tab" id="svcTab-{{ $key }}"
                        data-tab="{{ $key }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-body-sm font-semibold whitespace-nowrap rounded-t border-b-2 transition-colors cursor-pointer
                               text-secondary hover:text-on-surface hover:bg-surface-container
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-gov-500/40"
                        @if ($loop->first)
                            aria-selected="true" data-active
                            style="border-color: var(--color-primary); color: var(--color-primary); background: color-mix(in srgb, var(--color-primary) 6%, transparent);"
                        @else
                            aria-selected="false" style="border-color: transparent;"
                        @endif>
                        {{ $label }}
                    </button>
            @endforeach
        </div>
    </div>

    {{-- ── Panneaux ── --}}

    {{-- 1. Votre dossier — reprise / suppression du brouillon --}}
    <div role="tabpanel" aria-labelledby="svcTab-dossier" id="svcPanel-dossier" tabindex="0"
         class="svc-panel p-4 sm:p-5 focus:outline-none">
        @if (isset($drafts) && $drafts->isNotEmpty())
            <p class="text-body-sm text-secondary mb-3">{{ __('prevention_request.draft_banner_desc') }}</p>
            <div class="rounded border border-amber-200 bg-amber-50 dark:border-amber-800/40 dark:bg-amber-900/10 divide-y divide-amber-100 dark:divide-amber-800/20">
                @foreach ($drafts as $draft)
                    @php
                        $draftResumeUrl = $draft->current_step >= 2
                            ? route('prevention-requests.step3.show', $draft)
                            : route('prevention-requests.step2.show', $draft);
                        $draftNextStep  = min($draft->current_step + 1, 3);
                    @endphp
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 py-3.5">
                        <div class="min-w-0">
                            <p class="text-body-sm font-bold text-on-surface dark:text-gray-100 truncate">{{ $draft->nom_prenom }}</p>
                            <p class="text-xs text-secondary dark:text-gray-400 mt-0.5">
                                {{ $draft->created_at->diffForHumans() }}
                                @if ($draft->governorate?->name) &bull; {{ $draft->governorate->name }} @endif
                                @if ($draft->delegation?->name) &bull; {{ $draft->delegation->name }} @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <form method="POST" action="{{ route('prevention-requests.destroy', $draft->id) }}"
                                  onsubmit="return confirm('{{ __('prevention_request.delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="back" value="wizard">
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 cursor-pointer dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50"
                                        title="{{ __('prevention_request.delete') }}">
                                    <span class="material-symbols-outlined text-[15px]" aria-hidden="true">delete</span>
                                    {{ __('prevention_request.delete') }}
                                </button>
                            </form>
                            <a href="{{ $draftResumeUrl }}"
                               class="ui-btn-primary inline-flex items-center justify-center gap-2 px-4 py-2 text-body-sm cursor-pointer">
                                {{ __('prevention_request.draft_continue_step', ['step' => $draftNextStep]) }}
                                <svg class="h-3.5 w-3.5 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="pt-3 text-center">
                <button type="button" data-start-new-dossier
                        class="inline-flex items-center justify-center gap-1.5 rounded border border-primary/30 bg-primary/5 px-4 py-2 text-body-sm font-semibold text-primary transition hover:bg-primary/10 cursor-pointer focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">note_add</span>
                    {{ __('service_details.create_new_draft') }}
                </button>
            </div>
        @else
            {{-- État vide : aucun brouillon — carte cliquable rouge « créer un nouveau dossier » + note --}}
            <button type="button" data-start-new-dossier aria-label="{{ __('service_details.empty_draft_aria') }}"
                    class="group flex w-full items-start gap-4 rounded border border-red-300 bg-red-50 p-5 text-start transition hover:border-red-500 hover:bg-red-100 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/50 dark:border-red-900/50 dark:bg-red-950/20 dark:hover:border-red-700 dark:hover:bg-red-950/40">
                <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-red-100 transition dark:bg-red-900/30">
                    <span class="material-symbols-outlined filled text-xl text-red-600 transition group-hover:scale-110" aria-hidden="true">add</span>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-body-md font-bold text-red-700 transition dark:text-red-300">{{ __('service_details.empty_draft_title') }}</p>
                    <p class="text-body-sm text-red-700/80 mt-1 leading-relaxed dark:text-red-200/80">{{ __('service_details.draft_saved_note') }}</p>
                </div>
                <span class="material-symbols-outlined flex-none text-red-500 transition group-hover:translate-x-1 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" aria-hidden="true">chevron_right</span>
            </button>
        @endif
    </div>

    {{-- 2. La démarche — identité réglementaire --}}
    <div role="tabpanel" aria-labelledby="svcTab-process" id="svcPanel-process" tabindex="0"
         class="svc-panel hidden p-4 sm:p-6 space-y-4 focus:outline-none">

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 rounded border border-surface-dim bg-surface-container-low p-4 dark:border-gray-700 dark:bg-gray-900/40">
            <div>
                <span class="text-label-caps text-secondary uppercase tracking-wider block">{{ __('service_details.legal_name_label') }}</span>
                <strong class="text-body-md text-on-surface dark:text-gray-100">{{ __('services.prevention_certificate') }}</strong>
            </div>
            <div class="text-end">
                <span class="text-label-caps text-secondary uppercase tracking-wider block">{{ __('service_details.legal_name_ar_label') }}</span>
                <strong class="text-body-md text-on-surface dark:text-gray-100" lang="ar" dir="rtl">{{ __('service_details.legal_name_ar_value') }}</strong>
            </div>
        </div>

        {{-- Administration réceptrice --}}
        <div class="rounded border border-surface-dim bg-surface-container-lowest p-4 dark:border-gray-700 dark:bg-gray-900/40">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">move_to_inbox</span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-body-sm font-bold text-on-surface dark:text-gray-100">{{ __('service_details.receiving_label') }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-primary/10 text-[11px] font-bold text-primary">{{ __('service_details.level_regional') }}</span>
                    </div>
                    <ul class="mt-1.5 space-y-0.5">
                        @foreach ($entitiesRx as $entity)
                            <li class="text-body-sm text-secondary dark:text-gray-400">{{ $entity }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Administration délivrante --}}
        <div class="rounded border border-surface-dim bg-surface-container-lowest p-4 dark:border-gray-700 dark:bg-gray-900/40">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">corporate_fare</span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-body-sm font-bold text-on-surface dark:text-gray-100">{{ __('service_details.providing_label') }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-primary/10 text-[11px] font-bold text-primary">{{ __('service_details.level_regional') }}</span>
                    </div>
                    <ul class="mt-1.5 space-y-0.5">
                        @foreach ($entitiesTx as $entity)
                            <li class="text-body-sm text-secondary dark:text-gray-400">{{ $entity }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Documents requis --}}
    <div role="tabpanel" aria-labelledby="svcTab-docs" id="svcPanel-docs" tabindex="0"
         class="svc-panel hidden p-4 sm:p-6 focus:outline-none">
        <ol class="space-y-2.5">
            @foreach ($docs as $doc)
                <li class="flex items-start gap-3 rounded border border-surface-dim bg-surface-container-low px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <span class="w-6 h-6 shrink-0 mt-0.5 rounded-full bg-primary text-white text-label-caps flex items-center justify-center" aria-hidden="true">{{ $loop->iteration }}</span>
                    <p class="min-w-0 flex-1 text-body-sm text-on-surface dark:text-gray-200 leading-relaxed">{{ $doc['text'] }}</p>
                    <span class="hidden sm:flex flex-col items-end gap-1 shrink-0">
                        @foreach ($doc['tags'] as $tag)
                            @if ($tag === 'required' && count($doc['tags']) === 1)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-emerald-800 bg-emerald-50 rounded border border-emerald-200 dark:text-emerald-400 dark:bg-emerald-950/40 dark:border-emerald-900">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>{{ __('service_details.tag_required') }}
                                </span>
                            @elseif ($tag === 'form')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-primary bg-primary/10 rounded border border-primary/20">
                                    <span class="material-symbols-outlined text-[12px]" aria-hidden="true">edit_note</span>{{ __('service_details.tag_form') }}
                                </span>
                            @elseif ($tag === 'renewal')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-accent-600 bg-accent-50 rounded border border-accent-500/20 dark:bg-accent-900/30 dark:text-accent-500">
                                    <span class="material-symbols-outlined text-[12px]" aria-hidden="true">cached</span>{{ __('service_details.tag_renewal') }}
                                </span>
                            @endif
                        @endforeach
                    </span>
                </li>
            @endforeach
        </ol>

        {{-- Badges visibles aussi en mobile (sous les textes) --}}
        <p class="sm:hidden mt-3 text-xs text-secondary">{{ __('service_details.tag_required') }} · {{ __('service_details.tag_form') }} · {{ __('service_details.tag_renewal') }}</p>
    </div>

    {{-- 4. Frais & paiement --}}
    <div role="tabpanel" aria-labelledby="svcTab-fees" id="svcPanel-fees" tabindex="0"
         class="svc-panel hidden p-4 sm:p-6 focus:outline-none">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="rounded border border-surface-dim bg-surface-container-low p-4 dark:border-gray-700 dark:bg-gray-900/40">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="material-symbols-outlined filled text-lg text-primary" aria-hidden="true">payments</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-secondary">{{ __('service_details.fees_payable_label') }}</span>
                </div>
                <p class="text-body-md font-bold text-on-surface dark:text-gray-100">{{ __('service_details.fees_payable_value') }}</p>
            </div>
            <div class="rounded border border-surface-dim bg-surface-container-low p-4 dark:border-gray-700 dark:bg-gray-900/40">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="material-symbols-outlined filled text-lg text-primary" aria-hidden="true">receipt_long</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-secondary">{{ __('service_details.fees_type_label') }}</span>
                </div>
                <p class="text-body-md font-bold text-on-surface dark:text-gray-100">{{ __('service_details.fees_type_value') }}</p>
            </div>
            <div class="rounded border border-surface-dim bg-surface-container-low p-4 dark:border-gray-700 dark:bg-gray-900/40">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="material-symbols-outlined filled text-lg text-primary" aria-hidden="true">verified</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-secondary">{{ __('service_details.fees_proof_label') }}</span>
                </div>
                <p class="text-body-md font-bold text-on-surface dark:text-gray-100">{{ __('service_details.fees_proof_value') }}</p>
            </div>
        </div>
        <p class="mt-3 text-xs text-secondary leading-relaxed">* {{ __('service_details.docs.' . (6 - 1) . '.text') }}</p>
    </div>

    {{-- 5. Conditions & étapes --}}
    <div role="tabpanel" aria-labelledby="svcTab-conditions" id="svcPanel-conditions" tabindex="0"
         class="svc-panel hidden p-4 sm:p-6 space-y-5 focus:outline-none">

        {{-- Conditions --}}
        <div class="flex items-start gap-3 rounded border border-surface-dim bg-surface-container-low p-4 dark:border-gray-700 dark:bg-gray-900/40">
            <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">checklist</span>
            <div>
                <h3 class="text-label-caps text-secondary uppercase tracking-wider mb-0.5">{{ __('service_details.conditions_title') }}</h3>
                <p class="text-body-sm text-on-surface dark:text-gray-200 leading-relaxed">{{ __('service_details.conditions_text') }}</p>
            </div>
        </div>

        {{-- Références juridiques --}}
        <div>
            <h3 class="flex items-center gap-2 text-label-caps text-secondary uppercase tracking-wider mb-2">
                <span class="material-symbols-outlined text-[16px] text-outline" aria-hidden="true">gavel</span>
                {{ __('service_details.legals_title') }}
            </h3>
            <ol class="space-y-1.5">
                @foreach ($legals as $legal)
                    <li class="flex items-start gap-2.5 text-body-sm text-secondary dark:text-gray-400 leading-relaxed">
                        <span class="w-5 h-5 shrink-0 mt-0.5 rounded-full border border-surface-dim text-[11px] font-bold text-secondary flex items-center justify-center" aria-hidden="true">{{ $loop->iteration }}</span>
                        <span><strong class="text-on-surface dark:text-gray-200">{{ $legal['prefix'] }} :</strong> {{ $legal['text'] }}</span>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Délai + suivi --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="flex items-center gap-3 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900 dark:bg-emerald-950/30">
                <span class="material-symbols-outlined filled text-emerald-600" aria-hidden="true">schedule</span>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400">{{ __('service_details.delay_label') }}</p>
                    <p class="text-body-md font-black text-emerald-700 dark:text-emerald-400">{{ __('service_details.delay_value') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded border border-surface-dim bg-surface-container-low px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                <span class="material-symbols-outlined filled text-primary" aria-hidden="true">phone_in_talk</span>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('service_details.tracking_label') }}</p>
                    <p class="text-body-md font-bold text-on-surface dark:text-gray-100">{{ __('service_details.tracking_value') }}</p>
                </div>
            </div>
        </div>

        {{-- Étapes (mini timeline) --}}
        <div>
            <h3 class="text-label-caps text-secondary uppercase tracking-wider mb-2.5">{{ __('service_details.steps_title') }}</h3>
            <ol class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ($stepsData as $stepLabel)
                    <li class="relative flex flex-col items-start gap-2 rounded border border-surface-dim bg-surface-container-lowest p-3 dark:border-gray-700 dark:bg-gray-900/40">
                        <span class="w-7 h-7 shrink-0 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center font-black text-sm" aria-hidden="true">{{ $loop->iteration }}</span>
                        <p class="text-xs font-semibold text-on-surface dark:text-gray-200 leading-snug">{{ $stepLabel }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    {{-- 6. Infos complémentaires --}}
    <div role="tabpanel" aria-labelledby="svcTab-more" id="svcPanel-more" tabindex="0"
         class="svc-panel hidden p-4 sm:p-6 focus:outline-none">
        <div class="flex items-start gap-3 rounded border border-surface-dim bg-surface-container-low p-4 dark:border-gray-700 dark:bg-gray-900/40">
            <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">event_repeat</span>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('service_details.validity_label') }}</p>
                <p class="text-body-md font-bold text-on-surface dark:text-gray-100 mt-0.5">{{ __('service_details.validity_value') }}</p>
            </div>
        </div>
    </div>

    {{-- Animation d'apparition des panneaux --}}
    <style>
        @keyframes svcPanelIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .svc-panel.svc-in { animation: svcPanelIn 0.22s ease-out both; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @media (prefers-reduced-motion: reduce) {
            .svc-panel.svc-in { animation: none; }
        }
    </style>

    <script>
    (function () {
        var root = document.currentScript.closest('[data-svc-tabs]');
        var tabs    = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
        var panels  = Array.prototype.slice.call(root.querySelectorAll('[role="tabpanel"]'));
        var rtl     = (document.documentElement.getAttribute('dir') || '').toLowerCase() === 'rtl';

        function activate(tab) {
            tabs.forEach(function (t) {
                var isSel = t === tab;
                if (isSel) {
                    t.setAttribute('aria-selected', 'true');
                    t.dataset.active = '';
                    t.style.borderColor = 'var(--color-primary)';
                    t.style.color = 'var(--color-primary)';
                    t.style.background = 'color-mix(in srgb, var(--color-primary) 6%, transparent)';
                } else {
                    t.setAttribute('aria-selected', 'false');
                    t.style.borderColor = 'transparent';
                    t.style.color = '';
                    t.style.background = '';
                }
            });
            panels.forEach(function (p) {
                var show = p.id === 'svcPanel-' + tab.dataset.tab;
                if (show) {
                    p.classList.remove('hidden');
                    p.classList.remove('svc-in');
                    // reflow pour relancer l'animation
                    void p.offsetWidth;
                    p.classList.add('svc-in');
                } else {
                    p.classList.add('hidden');
                    p.classList.remove('svc-in');
                }
            });
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () { activate(tab); });

            // Navigation clavier : ← → Home End (ARIA tabs pattern)
            tab.addEventListener('keydown', function (e) {
                var dir = (e.key === 'ArrowRight' ? (rtl ? -1 : 1)
                        : e.key === 'ArrowLeft'  ? (rtl ? 1 : -1)
                        : 0);
                var next = null;
                if (dir) next = tabs[(index + dir + tabs.length) % tabs.length];
                if (e.key === 'Home') next = tabs[0];
                if (e.key === 'End')  next = tabs[tabs.length - 1];
                if (!next) return;
                e.preventDefault();
                next.focus();
                activate(next);
            });
        });
    }());
    </script>
</div>
