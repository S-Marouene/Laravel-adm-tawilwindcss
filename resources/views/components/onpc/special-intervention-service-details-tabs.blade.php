{{-- ============================================================
     CARTE À ONGLETS « Détails de la démarche »
     التدخلات والعمليات والخدمات الخاصة بمقابل
     Onglet 1 : demande/brouillon en cours
     Onglet 2 : la démarche (entités, niveau)
     Onglet 3 : documents requis
     Onglet 4 : tableau des tarifs
     Props : drafts (collection SpecialIntervention)
     ============================================================ --}}

<div class="rounded-lg border border-surface-dim bg-surface-container-lowest shadow-sm overflow-hidden dark:border-gray-700 dark:bg-gray-800" data-si-tabs>

    {{-- Filet accent ONPC --}}
    <div class="h-1.5 bg-gradient-to-r from-primary via-primary-container to-transparent" aria-hidden="true"></div>

    {{-- En-tête --}}
    <div class="flex items-center gap-3 px-4 sm:px-5 pt-4 pb-0">
        <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-primary/10">
            <span class="material-symbols-outlined filled text-xl text-primary" aria-hidden="true">build</span>
        </span>
        <div class="min-w-0">
            <span class="text-label-caps text-primary uppercase tracking-wider block">{{ __('special_intervention.card_title') }}</span>
            <h2 class="text-headline-sm text-on-surface">{{ __('special_intervention.card_description') }}</h2>
        </div>

        @if (isset($drafts) && $drafts->isNotEmpty())
            <span class="ms-auto hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-200 text-[11px] font-bold text-amber-800 dark:bg-amber-800/40 dark:text-amber-300">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                {{ $drafts->count() }} &bull; {{ __('special_intervention.drafts_title') }}
            </span>
        @endif
    </div>

    {{-- ── Barre d'onglets ── --}}
    <div class="mt-3 border-b border-surface-dim dark:border-gray-700 px-2 sm:px-4">
        <div role="tablist" aria-label="{{ __('special_intervention.card_title') }}"
             class="flex gap-1 overflow-x-auto -mb-px no-scrollbar">

            @foreach ([
                'dossier' => __('special_intervention.my_requests'),
                'process' => __('special_intervention.step_one_title'),
                'docs'    => __('special_intervention.documents_title'),
                'fees'    => __('special_intervention.fees_title'),
            ] as $key => $label)
                <button type="button" role="tab" id="siTab-{{ $key }}"
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

    {{-- ── Panneau 1 : Votre demande ── --}}
    <div role="tabpanel" aria-labelledby="siTab-dossier" id="siPanel-dossier" tabindex="0"
         class="si-panel p-4 sm:p-5 focus:outline-none">
        @if (isset($drafts) && $drafts->isNotEmpty())
            <p class="text-body-sm text-secondary mb-3">{{ __('special_intervention.resubmit_info') }}</p>
            <div class="rounded border border-amber-200 bg-amber-50 dark:border-amber-800/40 dark:bg-amber-900/10 divide-y divide-amber-100 dark:divide-amber-800/20">
                @foreach ($drafts as $draft)
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
                            <form method="POST" action="{{ route('special-interventions.destroy', $draft->id) }}"
                                  data-confirm data-confirm-style="danger">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 cursor-pointer dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                                    <span class="material-symbols-outlined text-[15px]" aria-hidden="true">delete</span>
                                    {{ __('special_intervention.cancel') }}
                                </button>
                            </form>
                            <a href="{{ route('special-interventions.step2.show', $draft) }}"
                               class="ui-btn-primary inline-flex items-center justify-center gap-2 px-4 py-2 text-body-sm cursor-pointer">
                                {{ __('special_intervention.continue') }}
                                <svg class="h-3.5 w-3.5 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="pt-3 text-center">
                <button type="button" data-start-new-si
                        class="inline-flex items-center justify-center gap-1.5 rounded border border-primary/30 bg-primary/5 px-4 py-2 text-body-sm font-semibold text-primary transition hover:bg-primary/10 cursor-pointer focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">note_add</span>
                    {{ __('special_intervention.new_request') }}
                </button>
            </div>
        @else
            {{-- État vide : aucun brouillon — bouton « créer une nouvelle demande » --}}
            <button type="button" data-start-new-si aria-label="{{ __('special_intervention.new_request') }}"
                    class="group flex w-full items-start gap-4 rounded border border-red-300 bg-red-50 p-5 text-start transition hover:border-red-500 hover:bg-red-100 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/50 dark:border-red-900/50 dark:bg-red-950/20 dark:hover:border-red-700 dark:hover:bg-red-950/40">
                <span class="w-10 h-10 shrink-0 rounded flex items-center justify-center bg-red-100 transition dark:bg-red-900/30">
                    <span class="material-symbols-outlined filled text-xl text-red-600 transition group-hover:scale-110" aria-hidden="true">add</span>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-body-md font-bold text-red-700 transition dark:text-red-300">{{ __('special_intervention.new_request') }}</p>
                    <p class="text-body-sm text-red-700/80 mt-1 leading-relaxed dark:text-red-200/80">{{ __('special_intervention.card_description') }}</p>
                </div>
                <span class="material-symbols-outlined flex-none text-red-500 transition group-hover:translate-x-1 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" aria-hidden="true">chevron_right</span>
            </button>
        @endif
    </div>

    {{-- ── Panneau 2 : La démarche ── --}}
    <div role="tabpanel" aria-labelledby="siTab-process" id="siPanel-process" tabindex="0"
         class="si-panel hidden p-4 sm:p-6 space-y-4 focus:outline-none">

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 rounded border border-surface-dim bg-surface-container-low p-4 dark:border-gray-700 dark:bg-gray-900/40">
            <div>
                <span class="text-label-caps text-secondary uppercase tracking-wider block">{{ __('special_intervention.card_title') }}</span>
                <strong class="text-body-md text-on-surface dark:text-gray-100">{{ __('special_intervention.card_title') }}</strong>
            </div>
            <div class="text-end">
                <span class="text-label-caps text-secondary uppercase tracking-wider block">التسمية القانونية بالعربية</span>
                <strong class="text-body-md text-on-surface dark:text-gray-100" lang="ar" dir="rtl">التدخلات والعمليات والخدمات الخاصة بمقابل</strong>
            </div>
        </div>

        {{-- Administration réceptrice --}}
        <div class="rounded border border-surface-dim bg-surface-container-lowest p-4 dark:border-gray-700 dark:bg-gray-900/40">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">move_to_inbox</span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-body-sm font-bold text-on-surface dark:text-gray-100">الجهة الإدارية المتعهدة بقبول المطلب</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-primary/10 text-[11px] font-bold text-primary">جهوي</span>
                    </div>
                    <ul class="mt-1.5 space-y-0.5">
                        <li class="text-body-sm text-secondary dark:text-gray-400">الإدارة الجهوية للحماية المدنية بسوسة</li>
                        <li class="text-body-sm text-secondary dark:text-gray-400">الإدارة الجهوية للحماية المدنية بأريانة</li>
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
                        <span class="text-body-sm font-bold text-on-surface dark:text-gray-100">الجهة الإدارية المتعهدة بإسداء الخدمة</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-primary/10 text-[11px] font-bold text-primary">جهوي</span>
                    </div>
                    <ul class="mt-1.5 space-y-0.5">
                        <li class="text-body-sm text-secondary dark:text-gray-400">الإدارة الجهوية للحماية المدنية بتونس</li>
                        <li class="text-body-sm text-secondary dark:text-gray-400">الإدارة الجهوية للحماية المدنية بأريانة</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Délai + suivi --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="flex items-center gap-3 rounded border border-orange-200 bg-orange-50 px-4 py-3 dark:border-orange-900 dark:bg-orange-950/30">
                <span class="material-symbols-outlined filled text-orange-600" aria-hidden="true">payments</span>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-orange-800 dark:text-orange-400">{{ __('special_intervention.fees_title') }}</p>
                    <p class="text-body-md font-black text-orange-700 dark:text-orange-400">{{ __('special_intervention.fees_info') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded border border-surface-dim bg-surface-container-low px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                <span class="material-symbols-outlined filled text-primary" aria-hidden="true">receipt_long</span>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('special_intervention.payment_proof') }}</p>
                    <p class="text-body-md font-bold text-on-surface dark:text-gray-100">{{ __('special_intervention.payment_proof') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Panneau 3 : Documents requis ── --}}
    <div role="tabpanel" aria-labelledby="siTab-docs" id="siPanel-docs" tabindex="0"
         class="si-panel hidden p-4 sm:p-6 focus:outline-none">
        <ol class="space-y-2.5">
            @foreach ([
                ['text' => __('special_intervention.nom_prenom') . ' — ' . __('special_intervention.cin'), 'tag' => 'form'],
                ['text' => 'نسخة من بطاقة التعريف الوطنية', 'tag' => 'required'],
                ['text' => 'طلب كتابي باسم المدير الجهوي', 'tag' => 'required'],
                ['text' => 'إذن شراء بالنسبة للمؤسسات العمومية والخاصة', 'tag' => 'required'],
            ] as $doc)
                <li class="flex items-start gap-3 rounded border border-surface-dim bg-surface-container-low px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <span class="w-6 h-6 shrink-0 mt-0.5 rounded-full bg-primary text-white text-label-caps flex items-center justify-center" aria-hidden="true">{{ $loop->iteration }}</span>
                    <p class="min-w-0 flex-1 text-body-sm text-on-surface dark:text-gray-200 leading-relaxed">{{ $doc['text'] }}</p>
                    <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold
                        {{ $doc['tag'] === 'form' ? 'text-primary bg-primary/10 rounded border border-primary/20' : 'text-emerald-800 bg-emerald-50 rounded border border-emerald-200 dark:text-emerald-400 dark:bg-emerald-950/40 dark:border-emerald-900' }}">
                        @if ($doc['tag'] === 'form')
                            <span class="material-symbols-outlined text-[12px]" aria-hidden="true">edit_note</span>{{ __('special_intervention.step_heading') }}
                        @else
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>وثيقة مطلوبة
                        @endif
                    </span>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- ── Panneau 4 : Tableau des tarifs ── --}}
    <div role="tabpanel" aria-labelledby="siTab-fees" id="siPanel-fees" tabindex="0"
         class="si-panel hidden p-4 sm:p-6 focus:outline-none">

        {{-- Paid banner --}}
        <div class="mb-4 flex items-center gap-3 rounded border border-orange-200 bg-orange-50 px-4 py-3 dark:border-orange-900/40 dark:bg-orange-950/20">
            <span class="material-symbols-outlined filled text-orange-600 text-lg" aria-hidden="true">payments</span>
            <p class="text-body-sm font-semibold text-orange-800 dark:text-orange-300">{{ __('special_intervention.fees_info') }}</p>
        </div>

        {{-- Fee table --}}
        <div class="overflow-x-auto rounded border border-surface-dim dark:border-gray-700">
            <table class="min-w-full divide-y divide-surface-dim dark:divide-gray-700 text-xs">
                <thead class="bg-surface-container-low dark:bg-gray-900/40">
                    <tr>
                        <th class="px-4 py-2.5 text-start font-bold uppercase tracking-wider text-secondary">{{ __('special_intervention.intervention_section') }}</th>
                        <th class="px-4 py-2.5 text-end font-bold uppercase tracking-wider text-secondary">{{ __('special_intervention.intervention_unit_price') }} ({{ __('special_intervention.currency_tnd') }})</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-dim dark:divide-gray-700/50 bg-surface-container-lowest dark:bg-gray-900/20">
                    @foreach (\App\Models\SpecialIntervention::INTERVENTIONS as $key => $data)
                        <tr class="hover:bg-surface-container/50 transition-colors">
                            <td class="px-4 py-2.5 text-on-surface dark:text-gray-200">{{ __('special_intervention.intervention_' . $key) }}</td>
                            <td class="px-4 py-2.5 text-end font-mono font-bold text-on-surface dark:text-gray-200" dir="ltr">{{ number_format($data['price'], 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-3 text-[11px] text-secondary leading-relaxed">{{ __('special_intervention.billing_note') }}</p>
        <p class="mt-1 text-[11px] text-secondary leading-relaxed">{{ __('special_intervention.diving_team_note') }}</p>
    </div>

    {{-- Animation des panneaux --}}
    <style>
        @keyframes siPanelIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .si-panel.si-in { animation: siPanelIn 0.22s ease-out both; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @media (prefers-reduced-motion: reduce) { .si-panel.si-in { animation: none; } }
    </style>

    <script>
    (function () {
        var root   = document.currentScript.closest('[data-si-tabs]');
        var tabs   = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
        var panels = Array.prototype.slice.call(root.querySelectorAll('[role="tabpanel"]'));
        var rtl    = (document.documentElement.getAttribute('dir') || '').toLowerCase() === 'rtl';

        function activate(tab) {
            tabs.forEach(function (t) {
                var sel = t === tab;
                if (sel) {
                    t.setAttribute('aria-selected', 'true');
                    t.dataset.active = '';
                    t.style.borderColor = 'var(--color-primary)';
                    t.style.color       = 'var(--color-primary)';
                    t.style.background  = 'color-mix(in srgb, var(--color-primary) 6%, transparent)';
                } else {
                    t.setAttribute('aria-selected', 'false');
                    delete t.dataset.active;
                    t.style.borderColor = 'transparent';
                    t.style.color       = '';
                    t.style.background  = '';
                }
            });
            panels.forEach(function (p) {
                var show = p.id === 'siPanel-' + tab.dataset.tab;
                if (show) {
                    p.classList.remove('hidden');
                    p.classList.remove('si-in');
                    void p.offsetWidth;
                    p.classList.add('si-in');
                } else {
                    p.classList.add('hidden');
                    p.classList.remove('si-in');
                }
            });
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () { activate(tab); });
            tab.addEventListener('keydown', function (e) {
                var dir = e.key === 'ArrowRight' ? (rtl ? -1 : 1)
                        : e.key === 'ArrowLeft'  ? (rtl ? 1 : -1) : 0;
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
