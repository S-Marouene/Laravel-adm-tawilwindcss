@php
    $intervention->loadMissing(['user', 'reviewer']);
    $status = $intervention->status;
    $viewRef = $intervention->reference ?: \App\Support\Reference::provisional('special-intervention');

    $advancedEvent = $intervention->events->last(fn ($e) => $e->type === 'advanced_to_consultation');
    $approvedEvent = $intervention->events->last(fn ($e) => $e->type === 'approved');
    $servedEvent   = $intervention->events->last(fn ($e) => $e->type === 'served');

    $currentStep = match ($status) {
        'draft'              => 1,
        'completed'          => 2,
        'under_consultation' => 2,
        'needs_correction'   => 2,
        'approved'           => 3,
        'served'             => 5,
        'cancelled'          => match (true) {
            $servedEvent !== null                                        => 5,
            $approvedEvent !== null                                      => 3,
            $advancedEvent !== null
                || $intervention->events->contains(fn ($e) => $e->type === 'correction_requested') => 2,
            default                                                            => 2,
        },
        default              => null,
    };
    $isCancelled = $status === 'cancelled';

    [$statusPillCls, $statusPillIcon] = match ($status) {
        'draft', 'completed'  => ['bg-surface-container-high text-secondary', 'edit_note'],
        'under_consultation'  => ['bg-blue-100 text-blue-800', 'manage_search'],
        'needs_correction'    => ['bg-red-100 text-red-800', 'error'],
        'approved'            => ['bg-amber-100 text-amber-900', 'task_alt'],
        'served'              => ['bg-teal-100 text-teal-800', 'handshake'],
        'cancelled'           => ['bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300', 'cancel'],
        default               => ['bg-surface-container-high text-secondary', 'info'],
    };

    $selectedInterventions = $intervention->interventions ?? [];
    $totalAmount = $intervention->total_amount;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface dark:text-gray-100">{{ __('special_intervention.card_title') }}</h2>
                <p class="mt-0.5 text-sm text-secondary dark:text-gray-400">{{ $viewRef }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Status banner --}}
            @if ($status === 'needs_correction')
            <section class="mb-6 rounded-lg border border-red-200 bg-red-50 dark:border-red-800/30 dark:bg-red-900/20">
                <div class="border-l-4 border-l-red-600 p-5">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">assignment_late</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('special_intervention.correction_title') }}</h3>
                            <p class="text-xs text-red-700 dark:text-red-300 mt-0.5">{{ __('special_intervention.status_needs_correction') }}</p>
                        </div>
                    </div>
                    @if ($intervention->admin_note)
                    <p class="text-body-sm text-red-900 dark:text-red-200 whitespace-pre-line leading-relaxed">{{ $intervention->admin_note }}</p>
                    @endif
                </div>
            </section>
            @elseif ($status === 'cancelled')
            <section class="mb-6 rounded-lg border border-surface-dim bg-surface-container dark:border-gray-600 dark:bg-gray-700/30">
                <div class="border-l-4 border-l-gray-500 p-5">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-10 h-10 rounded bg-gray-100 text-gray-700 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">cancel</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('special_intervention.status_cancelled') }}</h3>
                            <p class="text-xs text-secondary mt-0.5">{{ __('special_intervention.cancelled_banner') }}</p>
                        </div>
                    </div>
                    @if ($intervention->admin_note)
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $intervention->admin_note }}</p>
                    @endif
                </div>
            </section>
            @elseif ($status === 'approved')
            <section class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 dark:border-emerald-800/30 dark:bg-emerald-900/20">
                <div class="border-l-4 border-l-emerald-600 p-5">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-10 h-10 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">task_alt</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('special_intervention.admin_approved_panel') }}</h3>
                            <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-0.5">{{ __('special_intervention.admin_approved_panel_d') }}</p>
                        </div>
                    </div>
                </div>
            </section>
            @elseif ($status === 'served')
            <section class="mb-6 rounded-lg border border-teal-200 bg-teal-50 dark:border-teal-800/30 dark:bg-teal-900/20">
                <div class="border-l-4 border-l-teal-600 p-5">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-10 h-10 rounded bg-teal-100 text-teal-700 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">handshake</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('special_intervention.admin_served_panel') }}</h3>
                            <p class="text-xs text-teal-700 dark:text-teal-300 mt-0.5">{{ __('special_intervention.admin_served_panel_d') }}</p>
                        </div>
                    </div>
                </div>
            </section>
            @endif

            {{-- Actions toolbar --}}
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.special-interventions.index') }}"
                   class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-3 rounded transition-colors">
                    <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                    {{ __('special_intervention.admin_back_to_list') }}
                </a>

                @php
                    $canAdvance   = in_array($status, ['completed', 'needs_correction']);
                    $canCorrect   = $status === 'completed' || $status === 'under_consultation' || $status === 'needs_correction';
                    $canApprove   = $status === 'under_consultation';
                    $canCancel    = $status === 'completed' || $status === 'under_consultation' || $status === 'needs_correction';
                    $canServe     = $status === 'approved';
                @endphp

                @if ($canAdvance)
                <form method="POST" action="{{ route('admin.special-interventions.advance', $intervention) }}"
                      data-confirm data-confirm-style="warning" class="inline">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded shadow-sm transition-colors">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">sync_alt</span>
                        {{ __('special_intervention.admin_advance_btn') }}
                    </button>
                </form>
                @endif

                @if ($canCorrect)
                <a href="#admin-actions-panel"
                    class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold py-2 px-4 rounded shadow-sm transition-colors">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">edit_note</span>
                    {{ __('special_intervention.admin_correction_btn') }}
                </a>
                @endif

                @if ($canApprove)
                <form method="POST" action="{{ route('admin.special-interventions.approve', $intervention) }}"
                      data-confirm data-confirm-style="success" class="inline">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2 px-4 rounded shadow-sm transition-colors">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">task_alt</span>
                        {{ __('special_intervention.admin_approve_btn') }}
                    </button>
                </form>
                @endif

                @if ($canServe)
                <form method="POST" action="{{ route('admin.special-interventions.serve', $intervention) }}"
                      data-confirm data-confirm-style="success" class="inline">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold py-2 px-4 rounded shadow-sm transition-colors">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">handshake</span>
                        {{ __('special_intervention.admin_serve_btn') }}
                    </button>
                </form>
                @endif

                @if ($canCancel)
                <a href="#admin-actions-panel"
                    class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2 px-4 rounded shadow-sm transition-colors">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">cancel</span>
                    {{ __('special_intervention.admin_cancel_btn') }}
                </a>
                @endif

                <div class="ms-auto flex items-center gap-2">
                    <button type="button" onclick="window.print()"
                        class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                    </button>
                </div>
            </div>

            {{-- Informations clés --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-surface-container-low border border-surface-dim rounded p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-1">{{ __('special_intervention.nom_prenom') }}</p>
                    <p class="text-sm font-bold text-on-surface">{{ $intervention->nom_prenom }}</p>
                </div>
                <div class="bg-surface-container-low border border-surface-dim rounded p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-1">{{ __('special_intervention.cin') }}</p>
                    <p class="text-sm font-bold text-on-surface num-ltr" dir="ltr">{{ $intervention->cin }}</p>
                </div>
                <div class="bg-surface-container-low border border-surface-dim rounded p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-1">{{ __('special_intervention.phone') }}</p>
                    <p class="text-sm font-bold text-on-surface num-ltr" dir="ltr">{{ $intervention->phone }}</p>
                </div>
                <div class="bg-surface-container-low border border-surface-dim rounded p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-1">{{ __('special_intervention.total_amount') }}</p>
                    <p class="text-sm font-bold text-on-surface num-ltr" dir="ltr">{{ number_format($totalAmount, 0) }} {{ __('special_intervention.currency_tnd') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Colonne gauche: détails --}}
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-surface-container-low border border-surface-dim rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">build</span>
                                {{ __('special_intervention.intervention_section') }}
                            </h3>
                        </div>
                        <div class="p-4">
                            @if (!empty($selectedInterventions))
                            <div class="space-y-3">
                                @foreach ($selectedInterventions as $item)
                                <div class="p-3 rounded border border-surface-dim bg-surface-container-low/60 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-bold text-on-surface leading-snug">{{ app()->getLocale() === 'ar' ? ($item['name_ar'] ?? $item['name_fr'] ?? $item['key']) : ($item['name_fr'] ?? $item['name_ar'] ?? $item['key']) }}</h4>
                                        <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap" dir="ltr">
                                            <span>{{ __('special_intervention.intervention_unit_price') }}: <span class="font-mono font-bold">{{ number_format($item['price_tnd'] ?? 0, 0) }}</span> {{ __('special_intervention.currency_tnd') }}</span>
                                            <span class="text-surface-dim">•</span>
                                            <span>{{ __('special_intervention.intervention_qty') }}: <span class="font-mono font-bold">{{ $item['hours'] ?? 1 }}</span> h</span>
                                            <span class="text-surface-dim">•</span>
                                            <span>{{ __('special_intervention.intervention_amount') }}: <span class="font-mono font-bold">{{ number_format($item['amount'] ?? 0, 0) }}</span> {{ __('special_intervention.currency_tnd') }}</span>
                                        </div>
                                    </div>
                                    <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400" dir="ltr">{{ number_format($item['amount'] ?? 0, 0) }}</span>
                                </div>
                                @endforeach
                                <div class="mt-3 pt-3 border-t border-surface-dim flex items-baseline justify-between gap-2">
                                    <span class="text-xs font-bold text-on-surface uppercase tracking-wider">{{ __('special_intervention.total_amount') }}</span>
                                    <span class="text-lg font-black tabular-nums text-primary" dir="ltr">{{ number_format($totalAmount, 0) }}</span>
                                    <span class="text-xs font-semibold text-secondary uppercase">{{ __('special_intervention.currency_tnd') }}</span>
                                </div>
                            </div>
                            @else
                            <p class="text-xs text-secondary text-center py-4">{{ __('special_intervention.no_interventions_selected') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="bg-surface-container-low border border-surface-dim rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">event</span>
                                {{ __('special_intervention.intervention_section') }} - {{ __('special_intervention.intervention_date') }}
                            </h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-secondary text-base" aria-hidden="true">calendar_today</span>
                                    <div>
                                        <p class="text-xs font-bold text-secondary uppercase">{{ __('special_intervention.intervention_date') }}</p>
                                        <p class="text-sm text-on-surface">{{ $intervention->intervention_date?->format('d/m/Y') ?? '—' }}</p>
                                    </div>
                                </div>
                                @if ($intervention->intervention_time)
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-secondary text-base" aria-hidden="true">access_time</span>
                                    <div>
                                        <p class="text-xs font-bold text-secondary uppercase">{{ __('special_intervention.intervention_time') }}</p>
                                        <p class="text-sm text-on-surface num-ltr" dir="ltr">{{ $intervention->intervention_time->format('H:i') }}</p>
                                    </div>
                                </div>
                                @endif
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-secondary text-base" aria-hidden="true">account_balance</span>
                                    <div>
                                        <p class="text-xs font-bold text-secondary uppercase">{{ __('special_intervention.code_structure') }}</p>
                                        <p class="text-sm text-on-surface">{{ $intervention->governorate?->name ?? '—' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-secondary text-base" aria-hidden="true">location_city</span>
                                    <div>
                                        <p class="text-xs font-bold text-secondary uppercase">{{ __('special_intervention.delegation') }}</p>
                                        <p class="text-sm text-on-surface">{{ $intervention->delegation?->name ?? '—' }}</p>
                                    </div>
                                </div>
                                @if ($intervention->notes)
                                <div>
                                    <p class="text-xs font-bold text-secondary uppercase">{{ __('special_intervention.notes') }}</p>
                                    <p class="text-sm text-on-surface whitespace-pre-line">{{ $intervention->notes }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Colonne droite: actions admin + documents --}}
                <div class="lg:col-span-1 space-y-6">
                    <div id="admin-actions-panel" class="bg-surface-container-low border border-surface-dim rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">shield</span>
                                {{ __('special_intervention.admin_actions_title') }}
                            </h3>
                            <p class="text-[10px] text-secondary mt-1">{{ __('special_intervention.admin_actions_subtitle') }}</p>
                        </div>
                        <div class="p-4 space-y-3" x-data="{ fixOpen: false, cancelOpen: false }">
                            @php
                                $canAdvance   = in_array($status, ['completed', 'needs_correction']);
                                $canCorrect   = $status === 'completed' || $status === 'under_consultation' || $status === 'needs_correction';
                                $canApprove   = $status === 'under_consultation';
                                $canCancel    = $status === 'completed' || $status === 'under_consultation' || $status === 'needs_correction';
                                $canServe     = $status === 'approved';
                            @endphp

                            @if ($canAdvance)
                            <form method="POST" action="{{ route('admin.special-interventions.advance', $intervention) }}"
                                  data-confirm data-confirm-style="warning">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center justify-between gap-2 bg-blue-50 text-blue-700 border border-blue-200 rounded p-3 text-left text-xs font-semibold hover:bg-blue-100 transition-colors">
                                    <span>{{ __('special_intervention.admin_advance_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]" aria-hidden="true">sync_alt</span>
                                </button>
                            </form>
                            @endif

                            @if ($canCorrect)
                            {{-- Correction: toggle form with bilingual note --}}
                            <div>
                                <button type="button" @click="fixOpen = !fixOpen; cancelOpen = false"
                                    class="w-full flex items-center justify-between gap-2 bg-amber-50 text-amber-700 border border-amber-200 rounded p-3 text-left text-xs font-semibold hover:bg-amber-100 transition-colors">
                                    <span x-text="fixOpen ? '{{ __('special_intervention.admin_hide_note') }}' : '{{ __('special_intervention.admin_correction_btn') }}'">{{ __('special_intervention.admin_correction_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]" aria-hidden="true">edit_note</span>
                                </button>
                                <div x-show="fixOpen" x-cloak class="mt-2 rounded border border-amber-200 bg-amber-50 p-4 space-y-3">
                                    <div class="flex items-start gap-2 mb-2">
                                        <div class="w-7 h-7 rounded bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
                                            <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1" aria-hidden="true">rate_review</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">{{ __('special_intervention.admin_correction_btn') }}</p>
                                            <p class="text-[11px] text-secondary mt-0.5">{{ __('special_intervention.admin_correction_hint') }}</p>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.special-interventions.correction', $intervention) }}">
                                        @csrf
                                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300 mb-1.5">
                                            {{ __('special_intervention.admin_note_to_applicant') }} <span class="text-red-500">*</span>
                                        </label>
                                        <x-admin.bilingual-note name="admin_note" :rows="3" required
                                            placeholder_fr="{{ __('special_intervention.note_fix_placeholder', [], 'fr') }}"
                                            placeholder_ar="{{ __('special_intervention.note_fix_placeholder', [], 'ar') }}"
                                        />
                                        {{-- Fields to fix checkboxes --}}
                                        <div class="mt-3">
                                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-amber-800 mb-1.5">{{ __('special_intervention.admin_fields_to_fix') }}</p>
                                            <div class="grid grid-cols-2 gap-1">
                                                @php
                                                    $fixableFields = [
                                                        'cin'               => __('special_intervention.cin'),
                                                        'nom_prenom'        => __('special_intervention.nom_prenom'),
                                                        'phone'             => __('special_intervention.phone'),
                                                        'governorate_id'    => __('special_intervention.code_structure'),
                                                        'delegation_id'     => __('special_intervention.delegation'),
                                                        'intervention_date' => __('special_intervention.intervention_date'),
                                                        'intervention_time' => __('special_intervention.intervention_time'),
                                                        'notes'             => __('special_intervention.notes'),
                                                        'interventions'     => __('special_intervention.intervention_section'),
                                                    ];
                                                @endphp
                                                @foreach ($fixableFields as $key => $label)
                                                    <label class="flex items-center gap-1.5 text-[11px] text-amber-800 cursor-pointer">
                                                        <input type="checkbox" name="fields_to_fix[]" value="{{ $key }}" class="rounded text-amber-600 focus:ring-amber-500">
                                                        {{ $label }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                        {{-- Docs to fix checkboxes --}}
                                        @if ($documentTypes->isNotEmpty())
                                        <div class="mt-3">
                                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-amber-800 mb-1.5">{{ __('special_intervention.admin_fields_to_fix') }} — {{ __('special_intervention.documents_title') }}</p>
                                            <div class="space-y-1">
                                                @foreach ($documentTypes as $dt)
                                                    <label class="flex items-center gap-1.5 text-[11px] text-amber-800 cursor-pointer">
                                                        <input type="checkbox" name="docs_to_fix[]" value="{{ $dt->id }}" class="rounded text-amber-600 focus:ring-amber-500">
                                                        {{ $dt->name }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        <button type="submit"
                                            class="mt-3 w-full flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">send</span>
                                            {{ __('special_intervention.admin_send_correction') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif

                            @if ($canApprove)
                            <form method="POST" action="{{ route('admin.special-interventions.approve', $intervention) }}"
                                  data-confirm data-confirm-style="success">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center justify-between gap-2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded p-3 text-left text-xs font-semibold hover:bg-emerald-100 transition-colors">
                                    <span>{{ __('special_intervention.admin_approve_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]" aria-hidden="true">task_alt</span>
                                </button>
                            </form>
                            @endif

                            @if ($canServe)
                            <form method="POST" action="{{ route('admin.special-interventions.serve', $intervention) }}"
                                  data-confirm data-confirm-style="success">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center justify-between gap-2 bg-teal-50 text-teal-700 border border-teal-200 rounded p-3 text-left text-xs font-semibold hover:bg-teal-100 transition-colors">
                                    <span>{{ __('special_intervention.admin_serve_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]" aria-hidden="true">handshake</span>
                                </button>
                            </form>
                            @endif

                            @if ($canCancel)
                            {{-- Cancel: toggle form with bilingual note --}}
                            <div>
                                <button type="button" @click="cancelOpen = !cancelOpen; fixOpen = false"
                                    class="w-full flex items-center justify-between gap-2 bg-red-50 text-red-700 border border-red-200 rounded p-3 text-left text-xs font-semibold hover:bg-red-100 transition-colors">
                                    <span x-text="cancelOpen ? '{{ __('special_intervention.admin_hide_note') }}' : '{{ __('special_intervention.admin_cancel_btn') }}'">{{ __('special_intervention.admin_cancel_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]" aria-hidden="true">cancel</span>
                                </button>
                                <div x-show="cancelOpen" x-cloak class="mt-2 rounded border border-red-200 bg-red-50 p-4">
                                    <form method="POST" action="{{ route('admin.special-interventions.cancel', $intervention) }}">
                                        @csrf
                                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-red-800 mb-1.5">
                                            {{ __('special_intervention.admin_cancel_reason') }} <span class="text-red-500">*</span>
                                        </label>
                                        <x-admin.bilingual-note name="admin_note" :rows="3" required
                                            placeholder_fr="{{ __('special_intervention.note_cancel_placeholder', [], 'fr') }}"
                                            placeholder_ar="{{ __('special_intervention.note_cancel_placeholder', [], 'ar') }}"
                                        />
                                        <button type="submit"
                                            class="mt-3 w-full flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">cancel</span>
                                            {{ __('special_intervention.admin_confirm_cancel') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Documents --}}
                    <div class="bg-surface-container-low border border-surface-dim rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">attach_file</span>
                                {{ __('special_intervention.documents_title') }}
                            </h3>
                        </div>
                        <div class="p-4 space-y-3">
                            @forelse ($documentTypes as $docType)
                                @php $doc = $uploadedDocs->get($docType->id, null); @endphp
                                <div class="p-2 rounded border {{ $doc && $doc->status === 'uploaded' ? 'border-emerald-200 bg-emerald-50/60' : 'border-surface-dim bg-surface-container-low' }} flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold {{ $doc && $doc->status === 'uploaded' ? 'text-emerald-700 dark:text-emerald-400' : 'text-secondary' }}">{{ $docType->name }}</p>
                                        @if ($doc && $doc->status === 'uploaded')
                                            <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">
                                                {{ number_format($doc->size / 1024, 0) }} KB • {{ $doc->updated_at->format('d/m/Y') }}
                                            </p>
                                        @else
                                            <p class="text-[10px] text-red-500 dark:text-red-400 mt-0.5">{{ __('special_intervention.documents_not_uploaded') }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @if ($doc && $doc->status === 'uploaded')
                                        <a href="{{ route('admin.special-interventions.documents.download', $doc) }}"
                                           class="w-6 h-6 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center justify-center shrink-0 transition-colors"
                                           title="{{ __('special_intervention.download_label') }}">
                                            <span class="material-symbols-outlined text-xs" aria-hidden="true">download</span>
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-secondary text-center py-4">{{ __('special_intervention.admin_no_doctypes') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Admin note --}}
            @if ($intervention->admin_note && $status !== 'needs_correction')
            <div class="mt-6 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-3">
                <p class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-1 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">sticky_note_2</span>
                    {{ __('special_intervention.admin_note_label') }}
                </p>
                <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $intervention->admin_note }}</p>
            </div>
            @endif

            {{-- Timeline --}}
            @if ($intervention->events->isNotEmpty())
            <div class="mt-6 bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                        class="w-full flex items-center justify-between gap-3 p-5 md:px-6 pb-4 border-b border-surface-dim hover:bg-surface-container dark:hover:bg-gray-700/40 transition-colors text-start">
                    <div>
                        <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">history</span>
                            {{ __('special_intervention.timeline_title') }}
                        </h3>
                        <p class="text-xs text-secondary mt-0.5">{{ __('special_intervention.timeline_subtitle') }}</p>
                    </div>
                    <span class="text-[11px] text-secondary font-semibold flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">receipt_long</span>
                        {{ __('special_intervention.admin_events_count', ['n' => $intervention->events->count()]) }}
                    </span>
                    <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak class="p-5 md:p-6 pt-5">
                    <div class="relative ps-6 sm:ps-8 space-y-6 before:absolute before:start-3 sm:before:start-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                        @foreach ($intervention->events->reverse()->values() as $event)
                            @php
                                $dot = match ($event->type) {
                                    'submitted', 'resubmitted'  => 'bg-teal-500',
                                    'advanced_to_consultation'  => 'bg-purple-500',
                                    'correction_requested'      => 'bg-amber-500',
                                    'approved'                  => 'bg-emerald-500',
                                    'served'                    => 'bg-teal-500',
                                    'cancelled'                 => 'bg-red-400',
                                    default                     => 'bg-gray-400',
                                };
                                $icon = match ($event->type) {
                                    'submitted', 'resubmitted'  => 'post_add',
                                    'advanced_to_consultation'  => 'sync_alt',
                                    'correction_requested'      => 'edit_note',
                                    'approved'                  => 'task_alt',
                                    'served'                    => 'handshake',
                                    'cancelled'                 => 'cancel',
                                    default                     => 'error',
                                };
                                $chip = match ($event->type) {
                                    'submitted', 'resubmitted'  => __('special_intervention.chip_depot'),
                                    'advanced_to_consultation'  => __('special_intervention.chip_phase'),
                                    'correction_requested'      => __('special_intervention.chip_correction'),
                                    'approved'                  => __('special_intervention.chip_favorable'),
                                    'served'                    => __('special_intervention.chip_done'),
                                    'cancelled'                 => __('special_intervention.chip_alert'),
                                    default                     => __('special_intervention.chip_alert'),
                                };
                                $chipCls = match ($event->type) {
                                    'submitted', 'resubmitted'  => 'bg-secondary-container text-on-secondary-container',
                                    'advanced_to_consultation'  => 'bg-violet-600 text-white',
                                    'correction_requested'      => 'bg-amber-500 text-white',
                                    'approved'                  => 'bg-amber-600 text-white',
                                    'served'                    => 'bg-teal-600 text-white',
                                    'cancelled'                 => 'bg-red-600 text-white',
                                    default                     => 'bg-gray-600 text-white',
                                };
                            @endphp
                            <div class="relative">
                                <div class="absolute -start-6 sm:-start-8 top-0 w-6 sm:w-8 flex items-center justify-center">
                                    <div class="w-5 h-5 rounded-full {{ $dot }} flex items-center justify-center ring-4 ring-surface-container-lowest shadow-xs">
                                        <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $icon }}</span>
                                    </div>
                                </div>
                                <div class="bg-surface-container-low/60 border border-surface-dim rounded-lg p-4 transition-all hover:border-primary/40">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold {{ $chipCls }} px-2 py-0.5 rounded uppercase">{{ $chip }}</span>
                                            <span class="text-xs font-semibold text-on-surface">{{ in_array($event->type, ['submitted', 'resubmitted']) ? __('special_intervention.timeline_actor_you') : __('special_intervention.timeline_actor_admin') }}</span>
                                        </div>
                                        <time class="text-xs font-mono font-medium text-secondary" dir="ltr">{{ $event->created_at->format('d/m/Y H:i') }}</time>
                                    </div>
                                    <h5 class="text-xs font-bold text-on-surface mb-1">{{ __('special_intervention.tl_' . $event->type) }}</h5>
                                    @if ($event->type === 'served' && is_array($event->data) && ! empty($event->data['total_amount']))
                                    <p class="text-xs inline-flex items-center gap-1 rounded bg-teal-100 px-2 py-0.5 font-mono font-bold text-teal-900 mb-1.5" dir="ltr">
                                        <span class="material-symbols-outlined text-[13px]" aria-hidden="true">payments</span>
                                        {{ __('special_intervention.total_paid_label', ['amount' => number_format($event->data['total_amount'], 0)]) }}
                                    </p>
                                    @endif
                                    @if ($event->note)
                                    <p class="text-body-sm font-body-sm text-secondary">
                                        <span class="font-bold text-on-surface">{{ __('special_intervention.timeline_note_label') }}</span>
                                        {{ $event->note }}
                                    </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>{{-- /x-show --}}
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
