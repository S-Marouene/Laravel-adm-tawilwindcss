<x-app-layout>
    {{-- ================================================================
         ESPACE CITOYEN — Mes Demandes (design/demades.html)
         Filtre client-side réel (Alpine) : recherche, statut, type.
         ================================================================ --}}
    @php
        // Normalisation sérialisable pour le filtre Alpine
        $iconMap  = ['prevention' => 'fire_extinguisher', 'presence' => 'event_available', 'rescue' => 'ecg_heart', 'works' => 'construction'];
        $badgeMap = [
            'draft'              => ['progress',  'text-red-700 bg-red-50 border-red-200'],
            'needs_correction'   => ['action',    'text-error bg-[#FFE8E4] border-error/30'],
            'under_consultation' => ['progress',  'text-primary bg-primary/10 border-primary/20'],
            'approved'           => ['done',      'text-emerald-800 bg-emerald-50 border-emerald-300'],
            'served'             => ['done',      'text-emerald-800 bg-emerald-50 border-emerald-300'],
            'cancelled'          => ['cancelled', 'text-secondary bg-surface-container border-surface-dim'],
        ];
        $itemsJson = $items->map(function ($item, $index) use ($iconMap, $badgeMap) {
            [$bucket, $badgeClass] =
                $badgeMap[$item['status']] ?? ['progress', 'text-amber-800 bg-amber-50 border-amber-200'];

            return array_merge($item, [
                'id'             => $item['kind'] . '-' . $index,
                'icon'           => $iconMap[$item['kind']] ?? 'article',
                'date'           => $item['created_at']->format(app()->getLocale() === 'ar' ? 'Y/m/d' : 'd/m/Y'),
                'badge'          => $item['status_label'],
                'badgeClass'     => $badgeClass,
                'bucket'         => $bucket,
                'requiresAction' => $item['status'] === 'needs_correction',
                'destroyUrl'     => $item['destroy_url'],
                'confirmText'    => $item['destroy_url'] ? __($item['lang'] . '.delete_confirm') : '',
                'summary'        => implode(' · ', $item['meta']),
                'open'           => false,
            ]);
        })->values();

        $kpi = [
            'total'    => $items->count(),
            'action'   => $items->where('status', 'needs_correction')->count(),
            'done'     => $items->whereIn('status', ['approved', 'served'])->count(),
            'prog'     => $items->whereNotIn('status', ['needs_correction', 'approved', 'served', 'cancelled'])->count(),
        ];
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6"
             x-data="mesDemandesFilter(@js($itemsJson))">

            {{-- Bloc d'en-tête façon design/demades.html --}}
            <section class="relative overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-sm">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-primary-container" aria-hidden="true"></div>
                <div class="p-6 md:p-8 space-y-6">
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        <div class="space-y-2 max-w-2xl">
                            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded bg-secondary-container text-on-secondary-fixed text-label-caps font-label-caps uppercase">
                                <span class="material-symbols-outlined text-[14px]" aria-hidden="true">verified_user</span>
                                <span>{{ app()->getLocale() === 'ar' ? 'البوابة الرسمية المؤمّنة — وكالة الحماية المدنية' : "PORTAIL OFFICIEL SÉCURISÉ — GUICHET UNIQUE ONPC" }}</span>
                            </div>
                            <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">
                                {{ __('demandes.title') }}
                            </h1>
                            <p class="text-body-md leading-relaxed text-secondary">
                                {{ __('demandes.subtitle') }}
                            </p>
                        </div>
                        <a href="{{ url('/') }}#services" class="bg-primary-container hover:bg-primary text-on-primary font-bold px-5 py-3 rounded-lg shadow-sm flex items-center justify-center gap-2 transition-colors text-body-sm">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">add_circle</span>
                            <span>{{ app()->getLocale() === 'ar' ? 'طلب خدمة جديد' : 'Nouvelle demande de service' }}</span>
                        </a>
                    </div>

                    {{-- KPI strip : valeurs réelles calculées sur la collection --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-6 border-t border-surface-dim">
                        <div class="bg-surface-container-low p-4 rounded-lg border border-surface-dim flex items-center justify-between">
                            <div>
                                <div class="text-label-caps font-label-caps text-secondary uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'مجموع الطلبات' : 'Total demandes' }}</div>
                                <div class="text-3xl font-black text-on-surface mt-1">{{ $kpi['total'] }}</div>
                                <div class="text-[11px] text-secondary mt-0.5">{{ app()->getLocale() === 'ar' ? 'ملفات مودعة' : 'Dossiers déposés' }}</div>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-secondary">
                                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">folder_open</span>
                            </div>
                        </div>
                        <div class="bg-amber-50 p-4 rounded-lg border border-amber-200 flex items-center justify-between">
                            <div>
                                <div class="text-label-caps font-label-caps text-amber-900 uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'قيد المعالجة' : "En cours d'instruction" }}</div>
                                <div class="text-3xl font-black text-amber-900 mt-1">{{ $kpi['prog'] }}</div>
                                <div class="text-[11px] text-amber-700 mt-0.5">{{ app()->getLocale() === 'ar' ? 'تحليل الملف' : 'Analyse & instruction' }}</div>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-800">
                                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">pending_actions</span>
                            </div>
                        </div>
                        <div class="bg-emerald-50 p-4 rounded-lg border border-emerald-200 flex items-center justify-between">
                            <div>
                                <div class="text-label-caps font-label-caps text-emerald-900 uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'مقبولة ومكتملة' : 'Validées & prêtes' }}</div>
                                <div class="text-3xl font-black text-emerald-900 mt-1">{{ $kpi['done'] }}</div>
                                <div class="text-[11px] text-emerald-700 mt-0.5">{{ app()->getLocale() === 'ar' ? 'مؤشر للتنزيل' : 'Attestation disponible' }}</div>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-800">
                                <span class="material-symbols-outlined filled text-[20px]" aria-hidden="true">task_alt</span>
                            </div>
                        </div>
                        <div class="bg-[#FFE8E4] border border-error/30 p-4 rounded-lg flex items-center justify-between">
                            <div>
                                <div class="text-label-caps font-label-caps text-error uppercase tracking-wider">{{ app()->getLocale() === 'ar' ? 'إجراء مطلوب' : 'Complément requis' }}</div>
                                <div class="text-3xl font-black text-error mt-1">{{ $kpi['action'] }}</div>
                                <div class="text-[11px] text-error/70 mt-0.5">{{ app()->getLocale() === 'ar' ? 'وثيقة مطلوبة' : 'Pièce justificative exigée' }}</div>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-error/10 flex items-center justify-center text-error">
                                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">notification_important</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if (session('success'))
                <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-body-sm font-medium text-emerald-800" role="alert">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-[20px] flex-none text-emerald-600" aria-hidden="true">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            {{-- État vide --}}
            @if ($items->isEmpty())
                <x-onpc.empty-state
                    icon="folder_off"
                    :title="__('demandes.empty_title')"
                    :description="__('demandes.empty_subtitle')"
                    :action="__('demandes.discover_services')"
                    :href="url('/') . '#services'"
                    class="bg-surface-container-lowest" />
            @else
                {{-- Barre de filtre & recherche (Alpine, réellement fonctionnelle) --}}
                <div class="bg-surface-container-lowest p-4 rounded border border-surface-dim shadow-sm flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none text-secondary">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">search</span>
                        </span>
                        <input x-model.debounce.300ms="q"
                               type="text"
                               class="w-full ps-9 pe-4 py-2 border border-surface-dim rounded-md bg-surface-container-lowest text-body-sm text-on-surface placeholder:text-secondary focus:outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container"
                               placeholder="{{ app()->getLocale() === 'ar' ? 'بحث بالمرجع، العنوان أو الولاية…' : 'Rechercher par référence, établissement ou ville…' }}">
                    </div>
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-3">
                        <div class="flex items-center gap-1.5 w-full sm:w-auto">
                            <span class="text-xs font-semibold text-secondary whitespace-nowrap">{{ app()->getLocale() === 'ar' ? 'الحالة :' : 'Statut :' }}</span>
                            <select x-model="status" class="w-full sm:w-auto text-body-sm border border-surface-dim rounded-md py-2 px-3 bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary-container">
                                <option value="all">{{ app()->getLocale() === 'ar' ? 'كل الحالات' : 'Tous les statuts' }}</option>
                                <option value="progress">{{ app()->getLocale() === 'ar' ? 'قيد المعالجة' : "En cours d'instruction" }}</option>
                                <option value="done">{{ app()->getLocale() === 'ar' ? 'مقبولة' : 'Validées & disponibles' }}</option>
                                <option value="action">{{ app()->getLocale() === 'ar' ? 'إجراء مطلوب' : 'Complément requis' }}</option>
                                <option value="cancelled">{{ app()->getLocale() === 'ar' ? 'ملغاة' : 'Annulées' }}</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-1.5 w-full sm:w-auto">
                            <span class="text-xs font-semibold text-secondary whitespace-nowrap">{{ app()->getLocale() === 'ar' ? 'النوع :' : 'Type :' }}</span>
                            <select x-model="kind" class="w-full sm:w-auto text-body-sm border border-surface-dim rounded-md py-2 px-3 bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary-container">
                                <option value="all">{{ app()->getLocale() === 'ar' ? 'كل الطلبات' : 'Toutes les démarches' }}</option>
                                <option value="prevention">{{ __('services.prevention_certificate') }}</option>
                                <option value="presence">{{ __('services.preventive_presence') }}</option>
                                <option value="rescue">{{ __('services.rescue_certificate') }}</option>
                                <option value="works">{{ __('services.works_notification') }}</option>
                            </select>
                        </div>
                        <button type="button" @click="q = ''; status = 'all'; kind = 'all'"
                                class="p-2 border border-surface-dim rounded-md text-secondary hover:text-primary hover:bg-surface-container transition-colors"
                                title="{{ app()->getLocale() === 'ar' ? 'إعادة التعيين' : 'Réinitialiser' }}">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">restart_alt</span>
                        </button>
                    </div>
                </div>

                {{-- Liste des demandes — fiches compactes : synthèse visible, détails dépliables --}}
                <div class="space-y-4" role="list">
                    <template x-for="item in filteredItems" :key="item.id" role="listitem">
                        <article class="group bg-surface-container-lowest border rounded-lg shadow-sm transition-all hover:shadow-md hover:border-outline-variant/70"
                                 :class="item.status === 'draft'
                                     ? 'border-red-200 bg-red-50/60'
                                     : (item.requiresAction ? 'border-primary-container/50' : 'border-surface-dim')"
                                 :data-open="item.open">

                            {{-- ── Ligne principale (toujours compacte) ── --}}
                            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 p-5 md:p-6">
                                <div class="flex items-start gap-3.5 min-w-0">
                                    <div class="w-11 h-11 rounded-lg bg-primary/10 border border-outline-variant flex items-center justify-center text-primary shrink-0">
                                        <span class="material-symbols-outlined text-[22px]" aria-hidden="true" x-text="item.icon"></span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="bg-surface-container border border-surface-dim text-label-caps font-label-caps px-2 py-0.5 rounded text-secondary" x-text="item.label"></span>
                                            <span class="text-xs text-secondary flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]" aria-hidden="true">calendar_today</span>
                                                <span x-text="item.date"></span>
                                            </span>
                                        </div>
                                        <div class="flex items-start justify-between gap-3 mt-1.5">
                                            <h2 class="text-headline-sm font-headline-sm text-on-surface truncate" x-text="item.title"></h2>
                                            <span class="text-label-caps font-label-caps px-2 py-0.5 rounded border font-bold shrink-0"
                                                  :class="item.badgeClass" x-show="item.badge" x-text="item.badge"></span>
                                        </div>
                                        {{-- Synthèse courte (1 ligne, tronquée) : date clé + lieu --}}
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-secondary min-w-0">
                                            <span x-show="item.summary" class="truncate max-w-full flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-[14px] text-outline shrink-0" aria-hidden="true">info</span>
                                                <span class="truncate" x-text="item.summary"></span>
                                            </span>
                                            <a href="#" x-show="item.requiresAction" x-bind:href="item.url" class="inline-flex items-center gap-1 text-xs font-bold text-error hover:underline">
                                                {{ app()->getLocale() === 'ar' ? 'التصرف الآن' : 'Action requise' }}
                                                <span class="material-symbols-outlined text-[14px] rtl:rotate-180" aria-hidden="true">arrow_forward</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-end md:self-auto shrink-0">
                                    @php $detailsToggleLabel = app()->getLocale() === 'ar' ? 'التفاصيل' : 'Détails'; @endphp
                                    <button type="button"
                                            x-show="(item.details || []).length"
                                            @click="item.open = !item.open"
                                            class="text-xs font-bold px-2.5 py-1.5 rounded border border-surface-dim bg-surface-container text-secondary hover:text-primary hover:border-primary/40 transition-colors flex items-center gap-1"
                                            :aria-expanded="item.open"
                                            :title="item.open ? '{{ __('demandes.hide_details') }}' : '{{ __('demandes.details') }}'">
                                        <span x-text="item.open ? '{{ __('demandes.hide_details') }}' : '{{ __('demandes.details') }}'"></span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform duration-200"
                                              :class="item.open ? 'rotate-180' : ''" aria-hidden="true">keyboard_arrow_down</span>
                                    </button>
                                    <a class="text-xs font-bold px-3 py-1.5 rounded border flex items-center gap-1 transition-colors"
                                       x-bind:href="item.url"
                                       x-bind:class="item.status === 'draft'
                                            ? 'bg-red-50 border-red-300 text-red-700 hover:bg-red-100'
                                            : 'bg-surface-container hover:bg-surface-dim text-on-surface border-surface-dim'">
                                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true"
                                              x-text="item.status === 'draft' ? 'edit_note' : 'visibility'"></span>
                                        <span x-text="item.status === 'draft' ? '{{ __('demandes.complete_process') }}' : '{{ __('demandes.view_details') }}'"></span>
                                    </a>
                                    <form x-show="item.destroyUrl" method="POST" x-bind:action="item.destroyUrl"
                                          @submit.prevent="if (confirm(item.confirmText)) $el.submit();">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 rounded border border-red-200 bg-red-50 px-2 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-100"
                                                title="{{ __('demandes.delete') }}">
                                            <span class="material-symbols-outlined text-[16px]" aria-hidden="true">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- ── Panneau des détails : minimaliste (pas d'arrière-plan), ouverture/fermeture animée en hauteur ── --}}
                            <div x-collapse="item.open" x-cloak>
                                <div class="px-5 md:px-6 pb-5 min-w-0">
                                    <div class="border-t border-dashed border-surface-dim pt-3">
                                        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-2">
                                            <template x-for="(detail, di) in item.details" :key="item.id + '-d-' + di">
                                                <div class="flex items-start gap-2 text-xs text-secondary min-w-0">
                                                    <span class="material-symbols-outlined text-[15px] text-outline/70 mt-0.5 shrink-0" aria-hidden="true" x-text="detail.icon"></span>
                                                    <span class="min-w-0 break-words" x-text="detail.text"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </template>

                    {{-- Résultat de filtre vide --}}
                    <div x-show="filteredItems.length === 0" class="text-center py-12 px-6 bg-surface-container-low border border-dashed border-surface-dim rounded">
                        <span class="material-symbols-outlined text-4xl text-outline mx-auto mb-3" aria-hidden="true">search_off</span>
                        <h3 class="text-headline-sm text-on-surface">{{ app()->getLocale() === 'ar' ? 'لا نتائج مطابقة' : 'Aucun résultat pour ce filtre' }}</h3>
                        <button type="button" @click="q = ''; status = 'all'; kind = 'all'" class="ui-soft-button mt-4 inline-flex items-center gap-2 text-body-sm">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">restart_alt</span>
                            {{ app()->getLocale() === 'ar' ? 'إعادة تعيين الفلاتر' : 'Réinitialiser les filtres' }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

<script>
    // ── Directive x-collapse : ouverture/fermeture animée en hauteur (sans plugin) ──
    // Reproduit le comportement d'Alpine Collapse : height 0px ⇄ auto, fluide.
    document.addEventListener('alpine:init', () => {
        Alpine.directive('collapse', (el, { expression }, { effect, evaluateLater }) => {
            const isOpen = evaluateLater(expression);
            let last = null;

            el.style.overflow = 'hidden';

            effect(() => isOpen((open) => {
                if (open === last) return;

                const animate = (from, to, duration) => {
                    el.style.height = from;
                    el.getBoundingClientRect(); // force reflow
                    el.style.transition = 'height ' + duration + 'ms cubic-bezier(0.4, 0, 0.2, 1)';
                    el.style.height = to;
                    el.addEventListener('transitionend', () => {
                        el.style.transition = '';
                        el.style.height = to === '0px' ? '0px' : 'auto';
                    }, { once: true });
                };

                if (last === null && !open) {
                    // État initial : fermé, aucune animation (pas de clignotement)
                    el.style.height = '0px';
                } else if (open) {
                    animate('0px', el.scrollHeight + 'px', 300);
                } else {
                    animate(el.scrollHeight + 'px', '0px', 200);
                }

                last = open;
            }));
        });
    });

    // Filtre client (Alpine) : données réelles injectées par Blade.
    function mesDemandesFilter(items) {
        return {
            items,
            q: '',
            status: 'all',
            kind: 'all',
            get filteredItems() {
                const q = this.q.trim().toLowerCase();
                return this.items.filter((item) => {
                    if (this.kind !== 'all' && item.kind !== this.kind) return false;
                    if (this.status !== 'all' && item.bucket !== this.status) return false;
                if (q) {
                        const haystack = [item.title, item.label, item.meta.join(' '),
                            (item.details || []).map((d) => d.text).join(' '), item.date].join(' ').toLowerCase();
                        if (!haystack.includes(q)) return false;
                    }
                    return true;
                });
            },
        };
    }
</script>
