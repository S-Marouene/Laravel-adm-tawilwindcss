@php
    $status = $status ?? $dossier->status;
    // ── Référence affichée : reçu officiel > référence stockée > provisoire ──
    $viewRef = $dossier->num_demande ?: ($dossier->reference ?: \App\Support\Reference::provisional('prevention'));

    $pendingAppointment = $dossier->appointments->first(fn ($a) => ! $a->isPassed() && ! $a->isFailed());

    /* ── Stepper 4 étapes : dépôt → étude → inspection → certificat ── */
    $currentStep = match ($status) {
        'draft'                  => 1,
        'completed', 'submitted' => 2,
        'under_consultation'     => 3,
        'approved'               => 4,
        'needs_correction'       => 2,
        default                  => null,
    };
    if ($dossier->certificate) {
        $currentStep = 5; // finalisé
    }

    $stepDates = [];
    $stepDates[1] = $dossier->created_at;
    $stepDates[3] = $pendingAppointment->visit_date ?? null;
    $stepDates[4] = $dossier->certificate?->issued_at;

    $steps = [];
    foreach ([1, 2, 3, 4] as $i) {
        if ($currentStep === 5 || $i < $currentStep) $state = 'done';
        elseif ($i === $currentStep)                 $state = $status === 'needs_correction' ? 'error' : 'current';
        else                                         $state = 'pending';

        $sub = match (true) {
            $status === 'needs_correction' && $state === 'error' => __('prevention_request.stepper_correction'),
            $state === 'done'               => __('prevention_request.step_done'),
            $state === 'pending'            => __('prevention_request.stepper_pending'),
            $i === 3 && $pendingAppointment => __('prevention_request.stepper_inspection_scheduled', ['date' => $pendingAppointment->visit_date->format('d/m/Y')]),
            $i === 3                        => __('prevention_request.stepper_inspection_waiting'),
            $i === 4                        => __('prevention_request.stepper_certificate_waiting'),
            default                         => __('prevention_request.active_step'),
        };

        $steps[] = [
            'num' => $i, 'title' => __('prevention_request.stepper_' . $i),
            'sub' => $sub, 'state' => $state,
        ];
    }
    $doneSteps = collect($steps)->where('state', 'done')->count();
    $progress  = $currentStep === 5 ? 100 : (int) round($doneSteps / 4 * 100);

    /*
     ── Timeline : pastilles + badges « journal d'audit » ──
       ● or/ambre  = validé, officialisé (remplace le vert du template)
       ● rouge ONPC = convocation / action officielle / incident
       ● ardoise    = dépôt, transfert interne
    */
    $eventChip = fn ($type) => match (true) {
        in_array($type, ['submitted', 'resubmitted'])
            => ['bg-secondary-container text-on-secondary-container', 'post_add', __('prevention_request.chip_depot'), 'bg-surface-dim text-secondary'],
        $type === 'appointment_scheduled'
            => ['bg-primary-container text-white', 'campaign', __('prevention_request.chip_convocation'), 'bg-primary text-on-primary'],
        in_array($type, ['advanced_to_consultation', 'appointment_deleted'])
            => ['bg-secondary text-white', 'sync_alt', __('prevention_request.chip_phase'), 'bg-surface-dim text-secondary'],
        in_array($type, ['approved', 'appointment_passed', 'certificate_issued'])
            => ['bg-amber-600 text-white', 'task_alt', __('prevention_request.chip_favorable'), 'bg-amber-100 text-amber-900'],
        $type === 'certificate_collected'
            => ['bg-amber-600 text-white', 'handshake', __('prevention_request.chip_done'), 'bg-amber-100 text-amber-900'],
        // default: correction_requested, cancelled, appointment_failed, certificate_deleted
        default
            => ['bg-red-600 text-white', 'error', __('prevention_request.chip_alert'), 'bg-red-100 text-red-800'],
    };

    $uploads      = $documents->flatten()->count();
    $uploadedTypes = $documentTypes->filter(fn ($t) => $documents->has($t->id))->count();
    $fmtSize = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1) . ' Mo' : number_format($b / 1024, 0) . ' Ko';

    /* ── Bandeau d'état du héros (contextuel, jamais permanent) ── */
    $heroAlert = match (true) {
        $status === 'draft'               => 'draft',
        $status === 'needs_correction'    => 'error',
        $pendingAppointment !== null      => 'visit',
        $status === 'under_consultation'  => 'info',
        $status === 'approved' && ! $dossier->certificate => 'info',
        in_array($status, ['completed', 'submitted'])     => 'neutral',
        $status === 'cancelled'           => 'neutral',
        default                           => null,
    };
@endphp

<x-app-layout>
    <main class="flex-1 w-full mx-auto sm:px-6 lg:px-8 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-0">

            {{-- ══════════════ 1. Fil d'Ariane officiel ══════════════ --}}
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-secondary mb-4 overflow-x-auto whitespace-nowrap">
                <a class="hover:text-primary transition-colors flex items-center gap-1" href="{{ route('dashboard') }}">
                    <span class="material-symbols-outlined text-xs" aria-hidden="true">home</span>
                    <span>{{ __('prevention_request.dashboard_title') }}</span>
                </a>
                <span class="text-surface-dim font-bold">/</span>
                <a class="hover:text-primary transition-colors" href="{{ route('prevention-requests.index') }}">{{ __('prevention_request.list_requests') }}</a>
                <span class="text-surface-dim font-bold">/</span>
                <span class="text-on-surface font-bold" dir="ltr">{{ __('prevention_request.breadcrumb_here', ['ref' => $viewRef]) }}</span>
            </nav>

            @if (session('success'))
                <div class="mb-4 bg-surface-container-lowest border-l-4 border-l-primary border border-surface-dim rounded-lg text-body-sm font-body-sm text-on-surface px-4 py-3 shadow-sm flex items-center gap-2.5" role="alert">
                    <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- ══════════════ 2. Bandeau Récapitulatif du Dossier (Hero Card) ══════════════ --}}
            <section class="bg-surface-container-lowest border border-surface-dim rounded-lg p-5 md:p-6 mb-6 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-6">
                    {{-- Bloc identité du dossier --}}
                    <div class="space-y-3 flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="bg-primary text-on-primary text-label-caps font-label-caps px-2.5 py-0.5 rounded tracking-wider">
                                {{ __('prevention_request.card_title') }}
                            </span>
                            <span class="bg-surface-container text-on-surface text-label-caps font-label-caps px-2.5 py-0.5 rounded font-bold border border-surface-dim">
                                {{ __('prevention_request.code_structure') }} : {{ $dossier->governorate?->name ?? '—' }}
                            </span>
                            <span class="text-xs text-secondary flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs" aria-hidden="true">calendar_today</span>
                                {{ __('prevention_request.deposited_on', ['date' => $dossier->created_at->isoFormat('D MMMM YYYY')]) }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-headline-md font-headline-md text-on-surface tracking-tight" dir="ltr">
                                {{ __('prevention_request.breadcrumb_here', ['ref' => $viewRef]) }} — {{ __('prevention_request.card_title') }}
                            </h2>
                            <div class="flex flex-wrap items-center gap-y-1 gap-x-3 text-body-sm font-body-sm text-secondary mt-1">
                                <span class="font-bold text-on-surface flex items-center gap-1">
                                    <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">store</span>
                                    {{ __('prevention_request.building_type') }} : {{ $dossier->buildingType?->name ?? '—' }}
                                </span>
                                <span>•</span>
                                <span>{{ __('prevention_request.building_activity') }} : {{ $dossier->buildingActivity?->name ?? $dossier->activity_other ?? '—' }}</span>
                                <span>•</span>
                                <span>{{ __('prevention_request.delegation') }} : {{ $dossier->delegation?->name ?? '—' }}</span>
                                <span>•</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">location_on</span>
                                    {{ $dossier->building_address ?? '—' }}
                                </span>
                            </div>
                        </div>

                        {{-- Alerte d'état opérationnel — contextuelle --}}
                        @if ($heroAlert === 'visit')
                            <div class="inline-flex items-center gap-3 bg-amber-50 border border-amber-300 px-3.5 py-2 rounded text-xs">
                                <span class="flex h-2.5 w-2.5 relative shrink-0">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-600"></span>
                                </span>
                                <div class="font-bold text-amber-900 flex flex-wrap items-center gap-1.5">
                                    <span>{{ __('prevention_request.status_in_progress') }}</span>
                                    <span class="underline decoration-amber-400 font-extrabold">
                                        {{ __('prevention_request.next_appointment', ['date' => $pendingAppointment->visit_date->format('d/m/Y') . ' ' . substr($pendingAppointment->visit_time, 0, 5)]) }}
                                    </span>
                                    <span class="text-amber-800 font-normal">({{ __('prevention_request.phase_instruction', ['step' => $currentStep ?: 3]) }})</span>
                                </div>
                            </div>
                        @elseif ($heroAlert === 'info')
                            <div class="inline-flex items-center gap-3 bg-amber-50 border border-amber-300 px-3.5 py-2 rounded text-xs">
                                <span class="flex h-2.5 w-2.5 relative shrink-0">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-600"></span>
                                </span>
                                <div class="font-bold text-amber-900">
                                    {{ $status === 'under_consultation' ? __('prevention_request.under_consultation_banner') : __('prevention_request.approved_banner') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'error')
                            <div class="inline-flex items-center gap-3 bg-red-50 border border-red-300 px-3.5 py-2 rounded text-xs">
                                <span class="material-symbols-outlined text-red-600 text-base" aria-hidden="true">notification_important</span>
                                <div class="font-bold text-red-900">
                                    {{ $status === 'draft' ? __('prevention_request.draft_notice') : __('prevention_request.needs_correction_banner') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'draft')
                            <div class="inline-flex items-center gap-3 bg-red-50 border border-red-300 px-3.5 py-2 rounded text-xs">
                                <span class="material-symbols-outlined text-red-600 text-base" aria-hidden="true">edit_note</span>
                                <div class="font-bold text-red-900">{{ __('prevention_request.draft_notice') }}</div>
                            </div>
                        @elseif ($heroAlert === 'neutral')
                            <div class="inline-flex items-center gap-3 bg-surface-container-low border border-surface-dim px-3.5 py-2 rounded text-xs">
                                <span class="material-symbols-outlined text-secondary text-base" aria-hidden="true">{{ $status === 'cancelled' ? 'cancel' : 'hourglass_top' }}</span>
                                <div class="font-bold text-on-surface">
                                    {{ __('prevention_request.status_' . str_replace('-', '_', $status)) }} <span class="font-normal text-secondary">— {{ $status === 'cancelled' ? __('prevention_request.cancelled_banner') : __('prevention_request.review_note') }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Actions rapides --}}
                    <div class="flex flex-wrap lg:flex-col items-stretch gap-2.5 lg:w-64 flex-shrink-0">
                        <button type="button" onclick="window.print()"
                            class="flex items-center justify-center gap-2 bg-primary-container hover:bg-primary text-on-primary text-xs font-bold py-2.5 px-3.5 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">download</span>
                            <span>{{ __('prevention_request.print_fiche') }}</span>
                        </button>
                        <a href="{{ route('prevention-requests.index') }}"
                            class="flex items-center justify-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-3 rounded transition-colors">
                            <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                            <span>{{ __('prevention_request.list_requests') }}</span>
                        </a>
                    </div>
                </div>
            </section>

            {{-- ══════════════ 3. Cycle de Vie du Dossier (Stepper Horizontal) ══════════════ --}}
            <section class="bg-surface-container-lowest border border-surface-dim rounded-lg p-5 mb-8 shadow-sm">
                <div class="flex items-center justify-between mb-4 border-b border-surface-dim pb-3">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">route</span>
                        {{ __('prevention_request.stepper_title') }}
                    </h3>
                    <span class="text-xs font-bold text-primary bg-primary-fixed/40 px-2 py-0.5 rounded">
                        {{ __('prevention_request.phase_instruction', ['step' => $currentStep ? min($currentStep, 4) : 1]) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 relative">
                    @foreach ($steps as $step)
                        {{-- {{ $step['num'] }}. Étape --}}
                        @if ($step['state'] === 'done')
                            <div class="relative bg-surface-container-low border border-surface-dim rounded p-3 flex flex-col justify-between">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="w-6 h-6 rounded-full bg-amber-600 text-white flex items-center justify-center text-xs font-bold shadow-xs">
                                        <span class="material-symbols-outlined text-sm font-bold" aria-hidden="true">check</span>
                                    </span>
                                    <span class="text-[10px] uppercase font-bold text-amber-900 bg-amber-100 px-1.5 py-0.5 rounded">
                                        {{ __('prevention_request.step_done') }}
                                    </span>
                                </div>
                        @elseif ($step['state'] === 'current')
                            <div class="relative bg-amber-50/70 border-2 border-amber-500 rounded p-3 flex flex-col justify-between shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs font-black ring-4 ring-amber-100">
                                        {{ $step['num'] }}
                                    </span>
                                    <span class="text-[10px] uppercase font-black text-amber-900 bg-amber-200 px-1.5 py-0.5 rounded flex items-center gap-1">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                        {{ __('prevention_request.active_step') }}
                                    </span>
                                </div>
                        @elseif ($step['state'] === 'error')
                            <div class="relative bg-red-50/70 border-2 border-red-500 rounded p-3 flex flex-col justify-between shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="w-6 h-6 rounded-full bg-red-600 text-white flex items-center justify-center text-xs font-black ring-4 ring-red-100">
                                        <span class="material-symbols-outlined text-sm" aria-hidden="true">priority_high</span>
                                    </span>
                                    <span class="text-[10px] uppercase font-black text-red-800 bg-red-100 px-1.5 py-0.5 rounded flex items-center gap-1">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                                        {{ __('prevention_request.chip_alert') }}
                                    </span>
                                </div>
                        @else
                            <div class="relative bg-surface-container-low/50 border border-surface-dim/70 rounded p-3 flex flex-col justify-between opacity-70">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="w-6 h-6 rounded-full bg-surface-dim text-secondary flex items-center justify-center text-xs font-bold">
                                        {{ $step['num'] }}
                                    </span>
                                    <span class="text-[10px] uppercase font-semibold text-secondary bg-surface-dim/60 px-1.5 py-0.5 rounded">
                                        {{ __('prevention_request.stepper_pending') }}
                                    </span>
                                </div>
                        @endif
                                <div>
                                    <h4 class="text-xs font-bold {{ $step['state'] === 'pending' ? 'text-secondary' : 'text-on-surface' }}">
                                        {{ $step['num'] }}. {{ $step['title'] }}
                                    </h4>
                                    <p class="text-[11px] {{ $step['state'] === 'error' ? 'text-red-800 font-medium' : ($step['state'] === 'current' ? 'text-amber-900 font-medium' : 'text-secondary') }} mt-0.5 leading-snug">
                                        {{ $step['sub'] }}
                                    </p>
                                </div>
                                <div class="mt-2 pt-2 border-t {{ $step['state'] === 'current' ? 'border-amber-200' : ($step['state'] === 'error' ? 'border-red-200' : 'border-surface-dim/60') }} text-[10px] {{ $step['state'] === 'error' ? 'font-bold text-red-900' : ($step['state'] === 'current' ? 'font-bold text-amber-900' : 'text-secondary') }}">
                                    @if ($stepDates[$step['num']] ?? null)
                                        {{ $stepDates[$step['num']]->format('d/m/Y') }}
                                        @if ($step['num'] === 3 && $pendingAppointment && $pendingAppointment->visit_date->isSameDay($stepDates[3]))
                                            • {{ substr($pendingAppointment->visit_time, 0, 5) }}
                                        @endif
                                    @else
                                        {{ $step['num'] === 4 ? __('prevention_request.stepper_certificate_waiting') : __('prevention_request.stepper_pending') }}
                                    @endif
                                </div>
                            </div>
                    @endforeach
                </div>
            </section>

            {{-- ══════════════ 4. Grille asymétrique 8/4 ══════════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">

                {{-- ══════ COLONNE GAUCHE (8 colonnes) ══════ --}}
                <div class="lg:col-span-8 space-y-6">

                    {{-- BOÎTE D'ALERTE : consignes avant la visite programmée --}}
                    @if ($pendingAppointment)
                        <div class="bg-surface-container-lowest border-l-4 border-l-primary border border-surface-dim rounded-lg p-5 shadow-sm">
                            <div class="flex items-start gap-3.5">
                                <div class="w-9 h-9 rounded bg-primary-fixed/60 text-primary flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">assignment_late</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                                        <h4 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                            {{ __('prevention_request.consignes_title', ['date' => $pendingAppointment->visit_date->format('d/m/Y') . ' ' . substr($pendingAppointment->visit_time, 0, 5)]) }}
                                        </h4>
                                        <span class="text-[10px] bg-primary text-on-primary font-bold px-2 py-0.5 rounded">
                                            {{ __('prevention_request.consignes_badge') }}
                                        </span>
                                    </div>
                                    <p class="text-body-sm font-body-sm text-secondary mb-3">
                                        {{ __('prevention_request.consignes_intro') }}
                                    </p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                        <div class="flex items-start gap-2 bg-surface-container-low p-2.5 rounded border border-surface-dim">
                                            <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">person_check</span>
                                            <div>
                                                <span class="font-bold text-on-surface block">{{ __('prevention_request.consignes_presence') }}</span>
                                                <span class="text-secondary text-[11px]">{{ __('prevention_request.consignes_presence_d', ['name' => $dossier->nom_prenom]) }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2 bg-surface-container-low p-2.5 rounded border border-surface-dim">
                                            <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">menu_book</span>
                                            <div>
                                                <span class="font-bold text-on-surface block">{{ __('prevention_request.consignes_registre') }}</span>
                                                <span class="text-secondary text-[11px]">{{ __('prevention_request.consignes_registre_d') }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2 bg-surface-container-low p-2.5 rounded border border-surface-dim">
                                            <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">door_front</span>
                                            <div>
                                                <span class="font-bold text-on-surface block">{{ __('prevention_request.consignes_issues') }}</span>
                                                <span class="text-secondary text-[11px]">{{ __('prevention_request.consignes_issues_d') }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2 bg-surface-container-low p-2.5 rounded border border-surface-dim">
                                            <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">water_drop</span>
                                            <div>
                                                <span class="font-bold text-on-surface block">{{ __('prevention_request.consignes_eau') }}</span>
                                                <span class="text-secondary text-[11px]">{{ __('prevention_request.consignes_eau_d') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($pendingAppointment->note)
                                        <div class="mt-3 rounded border border-amber-200 bg-amber-50/60 p-3">
                                            <p class="text-[11px] font-bold text-amber-900 mb-0.5 flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">event_note</span>
                                                {{ __('prevention_request.appointment_note_label') }}
                                            </p>
                                            <p class="text-xs text-amber-900 whitespace-pre-line leading-relaxed">{{ $pendingAppointment->note }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- BOÎTE D'ALERTE : correction requise (remplace les consignes tant qu'il n'y a pas de RDV) --}}
                    @if ($status === 'needs_correction' && $dossier->admin_note)
                        <div class="bg-surface-container-lowest border-l-4 border-l-red-600 border border-surface-dim rounded-lg p-5 shadow-sm">
                            <div class="flex items-start gap-3.5">
                                <div class="w-9 h-9 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">assignment_late</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                                        <h4 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                            {{ __('prevention_request.correction_title') }}
                                        </h4>
                                        <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded">
                                            {{ __('prevention_request.chip_alert') }}
                                        </span>
                                    </div>
                                    <div class="rounded border border-red-200 bg-surface-container-low px-4 py-3 mb-3">
                                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 mb-1 flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                                            {{ __('prevention_request.admin_note_label') }}
                                        </p>
                                        <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $dossier->admin_note }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('prevention-requests.resubmit', $dossier) }}">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center gap-2 bg-primary-container hover:bg-primary text-on-primary text-xs font-bold py-2.5 px-3.5 rounded transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-base" aria-hidden="true">edit_note</span>
                                            {{ __('prevention_request.fix_and_resubmit') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Certificat — carte dorée (une fois délivré) --}}
                    @if ($dossier->certificate)
                        @php $cert = $dossier->certificate; @endphp
                        <div class="bg-surface-container-lowest border-l-4 border-l-amber-500 border border-surface-dim rounded-lg p-5 shadow-sm">
                            <div class="flex items-start gap-3.5">
                                <div class="w-9 h-9 rounded bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">workspace_premium</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                                        <h4 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                            {{ __('prevention_request.certificate_title') }} — <span class="font-mono" dir="ltr">{{ $cert->reference }}</span>
                                        </h4>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $cert->isCollected() ? 'bg-surface-dim text-secondary' : 'bg-amber-500 text-white' }}">
                                            {{ $cert->isCollected() ? __('prevention_request.certificate_status_collected') : __('prevention_request.certificate_status_ready') }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                        <div class="flex items-start gap-2 bg-surface-container-low p-2.5 rounded border border-surface-dim sm:col-span-2">
                                            <span class="material-symbols-outlined text-amber-700 text-base" aria-hidden="true">where_to_vote</span>
                                            <div>
                                                <span class="font-bold text-on-surface block">{{ __('prevention_request.certificate_pickup_text', ['place' => $cert->delegation?->name ?? $cert->governorate?->name ?? '—']) }}</span>
                                                <span class="text-secondary text-[11px]">
                                                    {{ __('prevention_request.certificate_brigade', ['name' => $cert->governorate?->name ?? '—']) }}
                                                    @if ($cert->valid_until)
                                                        • {{ __('prevention_request.certificate_valid_until') }} {{ $cert->valid_until->format('d/m/Y') }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Brouillon — reprise --}}
                    @if ($status === 'draft')
                        <div class="bg-surface-container-lowest border-l-4 border-l-red-600 border border-surface-dim rounded-lg p-5 shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-start gap-3.5">
                                    <div class="w-9 h-9 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-xl" aria-hidden="true">edit_note</span>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('prevention_request.status_draft') }}</h4>
                                        <p class="text-body-sm text-secondary mt-0.5">{{ __('prevention_request.draft_notice') }}</p>
                                    </div>
                                </div>
                                <a href="{{ $dossier->current_step >= 2 ? route('prevention-requests.step3.show', $dossier) : route('prevention-requests.step2.show', $dossier) }}"
                                    class="flex items-center justify-center gap-2 bg-primary-container hover:bg-primary text-on-primary text-xs font-bold py-2.5 px-3.5 rounded transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">play_arrow</span>
                                    {{ __('prevention_request.draft_continue_step', ['step' => $dossier->current_step >= 2 ? 3 : 2]) }}
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- ══════ TIMELINE — Historique & Journal d'audit ══════ --}}
                    @if ($dossier->events->isNotEmpty())
                    <div class="bg-surface-container-lowest border border-surface-dim rounded-lg p-5 md:p-6 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-dim pb-4 mb-6">
                            <div>
                                <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">history</span>
                                    {{ __('prevention_request.timeline_title') }}
                                </h3>
                                <p class="text-xs text-secondary mt-0.5">{{ __('prevention_request.timeline_subtitle') }}</p>
                            </div>
                            <span class="text-[11px] text-secondary font-semibold flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">receipt_long</span>
                                {{ __('prevention_request.events_count', ['n' => $dossier->events->count()]) }}
                            </span>
                        </div>

                        {{-- Chronologie linéaire haute densité --}}
                        <div class="relative pl-6 sm:pl-8 space-y-8 before:absolute before:left-3 sm:before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                            @foreach ($dossier->events as $event)
                                @php [$dot, $icon, $chip, $chipCls] = $eventChip($event->type); @endphp
                                <div class="relative">
                                    {{-- Pastille --}}
                                    <div class="absolute -left-6 sm:-left-8 top-0 w-6 sm:w-8 flex items-center justify-center">
                                        <div class="w-5 h-5 rounded-full {{ $dot }} flex items-center justify-center ring-4 ring-surface-container-lowest shadow-xs">
                                            <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $icon }}</span>
                                        </div>
                                    </div>
                                    {{-- Carte de contenu --}}
                                    <div class="bg-surface-container-low/60 border border-surface-dim rounded-lg p-4 transition-all hover:border-primary/40">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold {{ $chipCls }} px-2 py-0.5 rounded uppercase">{{ $chip }}</span>
                                                <span class="text-xs font-semibold text-on-surface">{{ in_array($event->type, ['submitted', 'resubmitted']) ? __('prevention_request.timeline_actor_you') : __('prevention_request.timeline_actor_admin') }}</span>
                                            </div>
                                            <time class="text-xs font-mono font-medium text-secondary">{{ $event->created_at->format('d/m/Y à H:i') }}</time>
                                        </div>
                                        <h5 class="text-xs font-bold text-on-surface mb-1">
                                            {{ __('prevention_request.timeline_' . $event->type) }}
                                        </h5>
                                        @if (in_array($event->type, ['appointment_scheduled', 'appointment_passed', 'appointment_failed', 'appointment_deleted']) && is_array($event->data) && ! empty($event->data['visit_date']))
                                            <p class="text-xs inline-flex items-center gap-1 rounded bg-surface-dim px-2 py-0.5 font-semibold text-on-surface mb-1.5">
                                                <span class="material-symbols-outlined text-[13px]" aria-hidden="true">event</span>
                                                {{ __('prevention_request.timeline_visit_label') }} {{ \Illuminate\Support\Carbon::parse($event->data['visit_date'])->format('d/m/Y') }} @if (! empty($event->data['visit_time'])) • {{ substr((string) $event->data['visit_time'], 0, 5) }} @endif
                                            </p>
                                        @endif
                                        @if (in_array($event->type, ['certificate_issued', 'certificate_collected', 'certificate_deleted']) && is_array($event->data) && ! empty($event->data['reference']))
                                            <p class="text-xs inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-0.5 font-mono font-bold text-amber-900 mb-1.5" dir="ltr">
                                                <span class="material-symbols-outlined text-[13px]" aria-hidden="true">workspace_premium</span>
                                                {{ __('prevention_request.timeline_certificate_label') }} {{ $event->data['reference'] }}
                                            </p>
                                        @endif
                                        @if ($event->note)
                                            <p class="text-body-sm font-body-sm text-secondary">
                                                <span class="font-bold text-on-surface">{{ __('prevention_request.timeline_note_label') }}</span>
                                                {{ $event->note }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Note de bas de journal --}}
                        <div class="mt-8 pt-4 border-t border-surface-dim flex items-center justify-between text-xs text-secondary">
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">lock</span>
                                {{ __('prevention_request.data_privacy') }}
                            </span>
                            <button type="button" onclick="window.print()" class="hover:text-primary font-semibold transition-colors flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs" aria-hidden="true">download</span>
                                {{ __('prevention_request.print_fiche') }}
                            </button>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- ══════ COLONNE DROITE (4 colonnes) ══════ --}}
                <div class="lg:col-span-4 space-y-6">

                    {{-- ENCADRÉ 1 : Pièces justificatives versées au dossier --}}
                    <div class="bg-surface-container-lowest border border-surface-dim rounded-lg p-5 shadow-sm">
                        <div class="flex items-center justify-between border-b border-surface-dim pb-3 mb-4">
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">attach_file</span>
                                {{ __('prevention_request.pieces_title') }} ({{ $uploadedTypes }}/{{ $documentTypes->count() }})
                            </h3>
                            @if ($uploadedTypes >= $documentTypes->count() && $documentTypes->count() > 0)
                                <span class="text-[10px] font-bold text-amber-900 bg-amber-100 px-2 py-0.5 rounded">
                                    {{ __('prevention_request.docs_complete') }}
                                </span>
                            @else
                                <span class="text-[10px] font-bold text-secondary bg-surface-dim px-2 py-0.5 rounded">
                                    {{ __('prevention_request.docs_incomplete', ['done' => $uploadedTypes, 'total' => $documentTypes->count()]) }}
                                </span>
                            @endif
                        </div>

                        <div class="space-y-3">
                            @forelse ($documentTypes as $docType)
                                @php $files = $documents->get($docType->id, collect()); @endphp
                                @forelse ($files as $doc)
                                    <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-3 group hover:border-primary/40 transition-colors">
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <span class="material-symbols-outlined text-primary-container text-xl mt-0.5" aria-hidden="true">{{ str_contains((string) $doc->mime_type, 'pdf') ? 'picture_as_pdf' : 'description' }}</span>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold text-on-surface group-hover:text-primary transition-colors truncate">{{ $docType->name }}</h4>
                                                <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap">
                                                    <span>{{ $fmtSize($doc->size) }} • {{ $doc->created_at->format('d/m/Y') }}</span>
                                                    @if ($doc->original_name)
                                                        <span class="truncate max-w-40">— {{ $doc->original_name }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('prevention-requests.documents.download', [$dossier, $doc]) }}" class="text-secondary hover:text-primary p-1 rounded shrink-0" title="{{ __('prevention_request.download') }}">
                                            <span class="material-symbols-outlined text-base" aria-hidden="true">download</span>
                                        </a>
                                    </div>
                                @empty
                                    <div class="p-3 border border-dashed border-surface-dim rounded flex items-start justify-between gap-3 opacity-80">
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <span class="material-symbols-outlined text-outline text-xl mt-0.5" aria-hidden="true">error</span>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold text-secondary">{{ $docType->name }}</h4>
                                                <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5">
                                                    @if ($docType->is_conditional)
                                                        <span class="font-semibold text-amber-900 bg-amber-50 border border-amber-200 px-1.5 rounded">{{ __('prevention_request.conditional') }}</span>
                                                    @endif
                                                    <span>{{ __('prevention_request.not_uploaded') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforelse
                            @empty
                                <div class="p-4 text-center">
                                    <span class="material-symbols-outlined text-2xl text-outline" aria-hidden="true">folder_off</span>
                                    <p class="text-xs text-secondary mt-1">—</p>
                                </div>
                            @endforelse

                            {{-- Fichiers transmis hors checklist --}}
                            @php $orphanDocs = $documents->filter(fn ($group, $typeId) => ! $documentTypes->contains('id', $typeId))->flatten(); @endphp
                            @if ($orphanDocs->isNotEmpty())
                                <div class="pt-1">
                                    <p class="text-[11px] font-bold text-secondary flex items-center gap-1.5 mb-2">
                                        <span class="material-symbols-outlined text-sm" aria-hidden="true">attachment</span>
                                        {{ __('prevention_request.other_files') }} ({{ $orphanDocs->count() }})
                                    </p>
                                    <div class="space-y-3">
                                        @foreach ($orphanDocs as $doc)
                                            <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-3 group hover:border-primary/40 transition-colors">
                                                <div class="flex items-start gap-2.5 min-w-0">
                                                    <span class="material-symbols-outlined text-primary-container text-xl mt-0.5" aria-hidden="true">{{ str_contains((string) $doc->mime_type, 'pdf') ? 'picture_as_pdf' : 'description' }}</span>
                                                    <div class="min-w-0">
                                                        <h4 class="text-xs font-bold text-on-surface truncate">{{ $doc->original_name ?? __('prevention_request.download') }}</h4>
                                                        <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5">
                                                            <span>{{ $fmtSize($doc->size) }} • {{ $doc->created_at->format('d/m/Y') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <a href="{{ route('prevention-requests.documents.download', [$dossier, $doc]) }}" class="text-secondary hover:text-primary p-1 rounded shrink-0" title="{{ __('prevention_request.download') }}">
                                                    <span class="material-symbols-outlined text-base" aria-hidden="true">download</span>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- ENCADRÉ 2 : Direction régionale référente + coordonnées demandeur --}}
                    <div class="bg-surface-container-lowest border border-surface-dim rounded-lg p-5 shadow-sm">
                        <div class="flex items-center gap-2 border-b border-surface-dim pb-3 mb-4">
                            <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">apartment</span>
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary">
                                {{ __('prevention_request.regional_title') }}
                            </h3>
                        </div>
                        <div class="space-y-3.5 text-xs">
                            <div>
                                <div class="font-bold text-on-surface text-sm">
                                    {{ $dossier->governorate?->name ?? '—' }}
                                </div>
                                <div class="text-secondary text-xs">{{ __('prevention_request.regional_bureau') }} — {{ $dossier->delegation?->name ?? '' }}</div>
                            </div>
                            <div class="space-y-2 pt-1 border-t border-surface-dim/60">
                                <div class="flex items-start gap-2.5 text-secondary">
                                    <span class="material-symbols-outlined text-primary text-base flex-shrink-0 mt-0.5" aria-hidden="true">location_on</span>
                                    <div>
                                        <span class="font-semibold text-on-surface block">{{ __('prevention_request.building_address') }}</span>
                                        <span>{{ $dossier->building_address ?? '—' }}</span>
                                    </div>
                                </div>
                                {{-- Téléphone du demandeur — toujours visible, appelable --}}
                                <div class="flex items-start gap-2.5 text-secondary">
                                    <span class="material-symbols-outlined text-primary text-base flex-shrink-0 mt-0.5" aria-hidden="true">call</span>
                                    <div>
                                        <span class="font-semibold text-on-surface block">{{ __('prevention_request.phone_applicant') }}</span>
                                        <span class="inline-flex items-center gap-2">
                                            <a class="hover:text-primary font-mono font-bold" href="tel:{{ preg_replace('/[^0-9+]/', '', $dossier->phone) }}" dir="ltr">{{ $dossier->phone ?? '—' }}</a>
                                            @if ($dossier->phone)
                                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $dossier->phone) }}"
                                                    class="inline-flex items-center gap-1 bg-primary-container hover:bg-primary text-on-primary text-[10px] font-bold px-2 py-0.5 rounded transition-colors">
                                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">phone_in_talk</span>
                                                    {{ __('prevention_request.call') }}
                                                </a>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2.5 text-secondary">
                                    <span class="material-symbols-outlined text-primary text-base flex-shrink-0 mt-0.5" aria-hidden="true">badge</span>
                                    <div>
                                        <span class="font-semibold text-on-surface block">{{ __('prevention_request.nom_prenom') }}</span>
                                        <span class="text-on-surface font-medium">{{ $dossier->nom_prenom }} <span class="text-secondary font-mono text-[11px]" dir="ltr">({{ __('prevention_request.cin') }}: {{ $dossier->cin }})</span></span>
                                    </div>
                                </div>
                                @if ($dossier->num_demande)
                                    <div class="flex items-start gap-2.5 text-secondary">
                                        <span class="material-symbols-outlined text-primary text-base flex-shrink-0 mt-0.5" aria-hidden="true">receipt_long</span>
                                        <div>
                                            <span class="font-semibold text-on-surface block">{{ __('prevention_request.num_demande') }}</span>
                                            <span class="font-mono font-bold text-on-surface" dir="ltr">{{ $dossier->num_demande }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- ENCADRÉ 3 : Besoin d'assistance ? --}}
                    <div class="bg-gradient-to-br from-surface-container to-surface-container-high border border-surface-dim rounded-lg p-5">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-primary text-lg" aria-hidden="true">support_agent</span>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface">
                                {{ __('prevention_request.support_title') }}
                            </h4>
                        </div>
                        <p class="text-xs text-secondary mb-3 leading-relaxed">
                            {{ __('prevention_request.support_desc') }}
                        </p>
                        <div class="space-y-2">
                            <a class="flex items-center justify-between bg-surface-container-lowest p-2.5 rounded border border-surface-dim text-xs font-bold text-on-surface hover:text-primary transition-colors" href="{{ route('dashboard') }}">
                                <span class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base text-primary" aria-hidden="true">home</span>
                                    <span>{{ __('prevention_request.dashboard_title') }}</span>
                                </span>
                                <span class="material-symbols-outlined text-sm text-secondary rtl:rotate-180" aria-hidden="true">chevron_right</span>
                            </a>
                            <a class="flex items-center justify-between bg-surface-container-lowest p-2.5 rounded border border-surface-dim text-xs font-bold text-on-surface hover:text-primary transition-colors" href="{{ route('prevention-requests.index') }}">
                                <span class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base text-secondary" aria-hidden="true">folder_open</span>
                                    <span>{{ __('prevention_request.list_requests') }}</span>
                                </span>
                                <span class="material-symbols-outlined text-sm text-secondary rtl:rotate-180" aria-hidden="true">chevron_right</span>
                            </a>
                        </div>
                        <p class="mt-3 pt-3 border-t border-surface-dim/60 text-[10px] text-secondary flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">lock</span>
                            {{ __('prevention_request.data_privacy') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</x-app-layout>
