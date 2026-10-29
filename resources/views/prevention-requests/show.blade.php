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
            => ['bg-primary text-on-primary', 'campaign', __('prevention_request.chip_convocation'), 'bg-primary text-on-primary'],
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
    $isDocComplete = $documentTypes->count() > 0 && $uploadedTypes >= $documentTypes->count();
    $docPct        = $documentTypes->count() > 0 ? (int) round(min($uploadedTypes, $documentTypes->count()) / $documentTypes->count() * 100) : 0;
    $totalSize     = $fmtSize($documents->flatten()->sum('size'));

    /* ── Pastille de statut (héros) : couleur + icône ── */
    $statusPill = match ($status) {
        'draft', 'completed'  => ['bg-surface-container-high text-secondary', 'edit_note'],
        'submitted'           => ['bg-secondary-container text-on-secondary-container', 'schedule_send'],
        'under_consultation'  => ['bg-secondary-container text-on-secondary-container', 'manage_search'],
        'needs_correction'    => ['bg-error-container text-error', 'error'],
        'approved'            => ['bg-amber-100 text-amber-900', 'task_alt'],
        'cancelled'           => ['bg-surface-dim text-secondary', 'cancel'],
        default               => ['bg-surface-container-high text-secondary', 'info'],
    };
    [$statusPillCls, $statusPillIcon] = $statusPill;

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

    /* ── Fiche impression : édité aujourd'hui ── */
    $printDate = now()->isoFormat('dddd D MMMM YYYY, HH:mm');
@endphp

<style>
    /* ═══════════════════════════════════════════════════════════════════
       FICHE DU DOSSIER — impression professionnelle (A4)
       Le contenu écran (.dossier-screen) est masqué ; seule la fiche
       (.print-sheet) est imprimée. Palette fixe noire / rouge ONPC.
       ═══════════════════════════════════════════════════════════════════ */
    .print-sheet { display: none; }

    @media print {
        @page { size: A4; margin: 11mm 11mm 14mm; }

        header, footer, .no-print { display: none !important; }

        html, body {
            background: #ffffff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-sheet { display: block !important; }
    }

    /* — Structure de la fiche (rendu uniquement à l'impression) — */
    .fiche {
        font-family: var(--font-sans, 'Roboto', Arial, sans-serif);
        font-size: 11.5px;
        line-height: 1.5;
        color: #1a1d29;
        background: #ffffff;
    }

    .fiche-band {
        background: #9e001f;
        color: #ffffff;
        padding: 12px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        border-radius: 3px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .fiche-band-logo  { width: 46px; height: 46px; object-fit: contain; border-radius: 4px; background: #fff; padding: 3px; }
    .fiche-band-org   { font-size: 14.5px; font-weight: 800; letter-spacing: .02em; line-height: 1.25; }
    .fiche-band-sub   { font-size: 10px; opacity: .92; font-weight: 400; }
    .fiche-band-ref   { text-align: end; line-height: 1.3; }
    .fiche-band-ref-label { font-size: 9px; text-transform: uppercase; letter-spacing: .14em; opacity: .85; font-weight: 600; }
    .fiche-band-ref-value { font-family: 'Roboto Mono', Menlo, Consolas, monospace; font-size: 15px; font-weight: 700; letter-spacing: .03em; }

    .fiche-title-row {
        display: flex; align-items: baseline; justify-content: space-between; gap: 12px;
        margin: 14px 0 16px; padding-bottom: 8px; border-bottom: 2px solid #1a1d29;
    }
    .fiche-title   { font-size: 15px; font-weight: 800; letter-spacing: .01em; }
    .fiche-printed { font-size: 10px; color: #4b5563; font-weight: 500; white-space: nowrap; }

    .fiche-h2 {
        font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em;
        margin: 0 0 6px; padding-inline-start: 8px;
        border-inline-start: 3.5px solid #9e001f; color: #1a1d29;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .fiche-sec { break-inside: avoid; margin-bottom: 16px; }

    .fiche-table { width: 100%; border-collapse: collapse; }
    .fiche-table th, .fiche-table td {
        border: 1px solid #c7cad1;
        padding: 4.5px 9px;
        text-align: start;
        vertical-align: top;
        font-size: 10.5px;
    }
    .fiche-table th { background: #f4f4f7; font-weight: 700; color: #1a1d29; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .fiche-kv th:first-child  { width: 37%; }
    .fiche-kv-4 th { width: 17.5%; }
    .fiche-table td { color: #24273a; }
    .fiche-mono     { font-family: 'Roboto Mono', Menlo, Consolas, monospace; letter-spacing: .02em; }

    .fiche-thead th { background: #1a1d29; color: #ffffff; }
    .fiche-table-dark thead th { background: #1a1d29; color: #fff; }
    .fiche-tfoot td { background: #f4f4f7; font-weight: 700; font-size: 10.5px; padding: 6px 9px; }
    .fiche-dim      { color: #6b7280; }
    .fiche-missing  { color: #9ca3af; font-style: italic; }

    .fiche-cert {
        border: 1.5px solid #b45309; border-radius: 3px; padding: 8px 12px;
        background: #fffbeb; margin-bottom: 16px;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .fiche-cert-title { font-weight: 800; font-size: 11px; color: #92400e; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 3px; }

    .fiche-sign-row  { display: flex; gap: 28px; margin: 6px 0 10px; align-items: flex-end; }
    .fiche-sign-col  { flex: 1; text-align: center; }
    .fiche-sign-name { font-size: 10.5px; font-weight: 700; margin-bottom: 46px; text-transform: uppercase; letter-spacing: .04em; }
    .fiche-sign-line { border-top: 1px solid #6b7280; margin: 0 8px; padding-top: 3px; font-size: 9px; color: #6b7280; }

    .fiche-foot {
        margin-top: 10px; padding-top: 7px; border-top: 1px solid #c7cad1;
        display: flex; justify-content: space-between; gap: 12px;
        font-size: 9px; color: #6b7280;
    }
</style>

<x-app-layout>
    <main class="flex-1 w-full mx-auto sm:px-6 lg:px-8 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-0">

            {{-- ══════════════ CONTENU ÉCRAN ══════════════ --}}
            <div class="dossier-screen no-print">

                {{-- 1. Fil d'Ariane officiel --}}
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-secondary mb-4 overflow-x-auto whitespace-nowrap">
                    <a class="hover:text-primary transition-colors flex items-center gap-1" href="{{ route('dashboard') }}">
                        <span class="material-symbols-outlined text-xs" aria-hidden="true">home</span>
                        <span>{{ __('prevention_request.dashboard_title') }}</span>
                    </a>
                    <span class="material-symbols-outlined text-xs text-surface-dim rtl:rotate-180" aria-hidden="true">chevron_right</span>
                    <a class="hover:text-primary transition-colors" href="{{ route('prevention-requests.index') }}">{{ __('prevention_request.list_requests') }}</a>
                    <span class="material-symbols-outlined text-xs text-surface-dim rtl:rotate-180" aria-hidden="true">chevron_right</span>
                    <span class="text-on-surface font-bold" dir="ltr">{{ __('prevention_request.breadcrumb_here', ['ref' => $viewRef]) }}</span>
                </nav>

                @if (session('success'))
                    <div class="mb-4 bg-surface-container-lowest border border-surface-dim rounded-lg text-body-sm font-body-sm text-on-surface px-4 py-3 shadow-sm flex items-center gap-2.5 border-s-4 border-s-success" role="alert">
                        <span class="material-symbols-outlined text-success text-base" aria-hidden="true">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                {{-- ══════════════ 2. Bandeau Récapitulatif du Dossier (Hero) ══════════════ --}}
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm mb-6 overflow-hidden">
                    <div class="h-1 bg-gradient-to-r from-primary via-primary-container/70 to-transparent" aria-hidden="true"></div>

                    <div class="p-5 md:p-6">
                        {{-- Ligne haute : identité + actions --}}
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="bg-primary text-on-primary text-label-caps font-label-caps px-2.5 py-0.5 rounded tracking-wider flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">local_fire_department</span>
                                    {{ __('prevention_request.card_title') }}
                                </span>
                                <span class="bg-surface-container text-on-surface text-label-caps font-label-caps px-2.5 py-0.5 rounded font-bold border border-surface-dim">
                                    {{ __('prevention_request.code_structure') }} : {{ $dossier->governorate?->name ?? '—' }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 text-label-caps font-label-caps px-2.5 py-0.5 rounded font-bold {{ $statusPillCls }}">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $statusPillIcon }}</span>
                                    {{ __('prevention_request.status_' . str_replace('-', '_', $status)) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2.5 shrink-0">
                                <a href="{{ route('prevention-requests.index') }}"
                                    class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2.5 px-3 rounded transition-colors">
                                    <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                                    <span class="hidden sm:inline">{{ __('prevention_request.list_requests') }}</span>
                                </a>
                                <button type="button" onclick="window.print()"
                                    class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                                    <span>{{ __('prevention_request.print_fiche') }}</span>
                                </button>
                            </div>
                        </div>

                        {{-- Titre + date de dépôt --}}
                        <div class="mb-4">
                            <h1 class="text-headline-md font-headline-md text-on-surface tracking-tight leading-tight" dir="ltr">
                                {{ __('prevention_request.breadcrumb_here', ['ref' => $viewRef]) }}
                            </h1>
                            <p class="text-xs text-secondary mt-1 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-xs" aria-hidden="true">calendar_today</span>
                                {{ __('prevention_request.deposited_on', ['date' => $dossier->created_at->isoFormat('D MMMM YYYY')]) }}
                            </p>
                        </div>

                        {{-- Faits clés — grille scannable (type • activité • délégation • adresse) --}}
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 mb-4">
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">store</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention_request.building_type') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate">{{ $dossier->buildingType?->name ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">category</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention_request.building_activity') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate">{{ $dossier->buildingActivity?->name ?? $dossier->activity_other ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">account_balance</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention_request.delegation') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate">{{ $dossier->delegation?->name ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">location_on</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention_request.building_address') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate" title="{{ $dossier->building_address }}">{{ $dossier->building_address ?? '—' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Alerte d'état opérationnel — contextuelle --}}
                        @if ($heroAlert === 'visit')
                            <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-300 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-amber-600 text-base mt-px" aria-hidden="true">event_available</span>
                                <div class="font-bold text-amber-900 leading-relaxed">
                                    {{ __('prevention_request.next_appointment', ['date' => $pendingAppointment->visit_date->format('d/m/Y') . ' ' . substr($pendingAppointment->visit_time, 0, 5)]) }}
                                    <span class="block font-normal text-amber-800 mt-0.5">{{ __('prevention_request.phase_instruction', ['step' => $currentStep ?: 3]) }}</span>
                                </div>
                            </div>
                        @elseif ($heroAlert === 'info')
                            <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-300 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-amber-600 text-base mt-px" aria-hidden="true">hourglass_bottom</span>
                                <div class="font-bold text-amber-900 leading-relaxed">
                                    {{ $status === 'under_consultation' ? __('prevention_request.under_consultation_banner') : __('prevention_request.approved_banner') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'error')
                            <div class="flex items-start gap-2.5 bg-red-50 border border-red-300 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">notification_important</span>
                                <div class="font-bold text-red-900 leading-relaxed">
                                    {{ __('prevention_request.needs_correction_banner') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'draft')
                            <div class="flex items-start gap-2.5 bg-red-50 border border-red-300 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">edit_note</span>
                                <div class="font-bold text-red-900 leading-relaxed">
                                    {{ __('prevention_request.draft_notice') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'neutral')
                            <div class="flex items-start gap-2.5 bg-surface-container-low border border-surface-dim rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-secondary text-base mt-px" aria-hidden="true">{{ $status === 'cancelled' ? 'cancel' : 'hourglass_top' }}</span>
                                <div class="font-bold text-on-surface leading-relaxed">
                                    {{ __('prevention_request.status_' . str_replace('-', '_', $status)) }}
                                    <span class="font-normal text-secondary"> — {{ $status === 'cancelled' ? __('prevention_request.cancelled_banner') : __('prevention_request.review_note') }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- ══════════════ 3. Cycle de Vie du Dossier (Stepper + progression) ══════════════ --}}
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm mb-6 overflow-hidden">
                    <div class="p-5 md:px-6">
                        {{-- En-tête : titre + phase + barre de progression --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-surface-dim">
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">route</span>
                                {{ __('prevention_request.stepper_title') }}
                            </h3>
                            <div class="flex items-center gap-3 flex-1 sm:flex-none sm:min-w-64">
                                <span class="text-xs font-bold text-primary bg-primary-fixed/40 px-2 py-0.5 rounded whitespace-nowrap">
                                    {{ __('prevention_request.phase_instruction', ['step' => $currentStep ? min($currentStep, 4) : 1]) }}
                                </span>
                                <div class="hidden sm:flex-1 h-1.5 bg-surface-dim/50 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-primary-container to-amber-500 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                                </div>
                                <span class="text-[11px] font-mono font-bold text-secondary hidden sm:inline" dir="ltr">{{ $progress }}%</span>
                            </div>
                        </div>

                        {{-- Étapes reliées par une ligne de conduite --}}
                        <div class="relative grid grid-cols-1 md:grid-cols-4 gap-5 md:gap-3">
                            {{-- Ligne de conduite : uniquement ≥ md, symétrique (compatible RTL) --}}
                            <div class="hidden md:block absolute top-5 [inset-inline-start:12%] [inset-inline-end:12%] h-0.5 bg-surface-dim" aria-hidden="true"></div>

                            @foreach ($steps as $step)
                                <div class="relative flex md:flex-col items-start md:items-center gap-3 md:gap-2 md:text-center">
                                    {{-- Pastille circulaire --}}
                                    @if ($step['state'] === 'done')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-sm ring-4 ring-amber-100">
                                            <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">check</span>
                                        </span>
                                    @elseif ($step['state'] === 'current')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center text-sm font-black shadow-sm ring-4 ring-primary-fixed/70">
                                            {{ $step['num'] }}
                                        </span>
                                    @elseif ($step['state'] === 'error')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow-sm ring-4 ring-red-100">
                                            <span class="material-symbols-outlined text-lg" aria-hidden="true">priority_high</span>
                                        </span>
                                    @else
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-surface-container text-secondary flex items-center justify-center text-sm font-bold border border-surface-dim">
                                            {{ $step['num'] }}
                                        </span>
                                    @endif

                                    {{-- Libellé --}}
                                    <div class="min-w-0 md:px-1">
                                        <div class="flex md:justify-center items-center gap-2">
                                            <h4 class="text-xs font-bold {{ $step['state'] === 'pending' ? 'text-secondary' : 'text-on-surface' }}">
                                                {{ $step['title'] }}
                                            </h4>
                                            @if ($step['state'] === 'current')
                                                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse" aria-hidden="true"></span>
                                            @elseif ($step['state'] === 'error')
                                                <span class="text-[10px] uppercase font-black text-red-800 bg-red-100 px-1.5 py-px rounded">{{ __('prevention_request.chip_alert') }}</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] mt-0.5 leading-snug {{ $step['state'] === 'error' ? 'text-red-800 font-medium' : ($step['state'] === 'current' ? 'text-primary font-medium' : 'text-secondary') }}">
                                            {{ $step['sub'] }}
                                        </p>
                                        <p class="text-[10px] mt-1 font-semibold {{ $step['state'] === 'done' ? 'text-amber-800' : 'text-secondary' }}" dir="ltr">
                                            @if ($stepDates[$step['num']] ?? null)
                                                {{ $stepDates[$step['num']]->format('d/m/Y') }}
                                                @if ($step['num'] === 3 && $pendingAppointment && $pendingAppointment->visit_date->isSameDay($stepDates[3]))
                                                    <span class="text-secondary">• {{ substr($pendingAppointment->visit_time, 0, 5) }}</span>
                                                @endif
                                            @elseif ($step['state'] !== 'done')
                                                <span class="text-surface-dim font-normal">—</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- ══════════════ 4. Grille asymétrique 8/4 ══════════════ --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">

                    {{-- ══════ COLONNE GAUCHE (8 colonnes) ══════ --}}
                    <div class="lg:col-span-8 space-y-6">

                        {{-- Consignes impératives — visite programmée --}}
                        @if ($pendingAppointment)
                            <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print">
                                <div class="border-s-4 border-s-primary">
                                    <div class="p-5">
                                        <div class="flex items-start gap-3.5 mb-4">
                                            <div class="w-10 h-10 rounded bg-primary-fixed/60 text-primary flex items-center justify-center flex-shrink-0">
                                                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">assignment_late</span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <h4 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                                        {{ __('prevention_request.consignes_title', ['date' => $pendingAppointment->visit_date->format('d/m/Y') . ' ' . substr($pendingAppointment->visit_time, 0, 5)]) }}
                                                    </h4>
                                                    <span class="text-[10px] bg-primary text-on-primary font-bold px-2 py-0.5 rounded whitespace-nowrap">
                                                        {{ __('prevention_request.consignes_badge') }}
                                                    </span>
                                                </div>
                                                <p class="text-body-sm font-body-sm text-secondary mt-1.5">
                                                    {{ __('prevention_request.consignes_intro') }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                            <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                                                <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">person_check</span>
                                                <div>
                                                    <span class="font-bold text-on-surface block">{{ __('prevention_request.consignes_presence') }}</span>
                                                    <span class="text-secondary text-[11px]">{{ __('prevention_request.consignes_presence_d', ['name' => $dossier->nom_prenom]) }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                                                <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">menu_book</span>
                                                <div>
                                                    <span class="font-bold text-on-surface block">{{ __('prevention_request.consignes_registre') }}</span>
                                                    <span class="text-secondary text-[11px]">{{ __('prevention_request.consignes_registre_d') }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                                                <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">door_front</span>
                                                <div>
                                                    <span class="font-bold text-on-surface block">{{ __('prevention_request.consignes_issues') }}</span>
                                                    <span class="text-secondary text-[11px]">{{ __('prevention_request.consignes_issues_d') }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                                                <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">water_drop</span>
                                                <div>
                                                    <span class="font-bold text-on-surface block">{{ __('prevention_request.consignes_eau') }}</span>
                                                    <span class="text-secondary text-[11px]">{{ __('prevention_request.consignes_eau_d') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        @if ($pendingAppointment->note)
                                            <div class="mt-4 rounded border border-amber-200 bg-amber-50/60 p-3">
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

                        {{-- Correction requise — note du service --}}
                        @if ($status === 'needs_correction' && $dossier->admin_note)
                            <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                                <div class="border-s-4 border-s-red-600">
                                    <div class="p-5">
                                        <div class="flex items-start gap-3.5 mb-3">
                                            <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                                                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">assignment_late</span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <h4 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                                        {{ __('prevention_request.correction_title') }}
                                                    </h4>
                                                    <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded">
                                                        {{ __('prevention_request.chip_alert') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="rounded border border-red-200 bg-surface-container-low px-4 py-3 mb-4">
                                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 mb-1 flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                                                {{ __('prevention_request.admin_note_label') }}
                                            </p>
                                            <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $dossier->admin_note }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('prevention-requests.resubmit', $dossier) }}">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
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
                            <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                                <div class="border-s-4 border-s-amber-500">
                                    <div class="p-5">
                                        <div class="flex items-start gap-3.5">
                                            <div class="w-10 h-10 rounded bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
                                                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">workspace_premium</span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                                    <h4 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                                        {{ __('prevention_request.certificate_title') }} — <span class="font-mono" dir="ltr">{{ $cert->reference }}</span>
                                                    </h4>
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $cert->isCollected() ? 'bg-surface-dim text-secondary' : 'bg-amber-500 text-white' }}">
                                                        {{ $cert->isCollected() ? __('prevention_request.certificate_status_collected') : __('prevention_request.certificate_status_ready') }}
                                                    </span>
                                                </div>
                                                <div class="flex items-start gap-2 bg-surface-container-low p-3 rounded border border-surface-dim text-xs">
                                                    <span class="material-symbols-outlined text-amber-700 text-base mt-px" aria-hidden="true">where_to_vote</span>
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
                            </div>
                        @endif

                        {{-- Brouillon — reprise --}}
                        @if ($status === 'draft')
                            <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                                <div class="border-s-4 border-s-red-600">
                                    <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-start gap-3.5">
                                            <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                                                <span class="material-symbols-outlined text-xl" aria-hidden="true">edit_note</span>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('prevention_request.status_draft') }}</h4>
                                                <p class="text-body-sm text-secondary mt-0.5">{{ __('prevention_request.draft_notice') }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ $dossier->current_step >= 2 ? route('prevention-requests.step3.show', $dossier) : route('prevention-requests.step2.show', $dossier) }}"
                                            class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-base" aria-hidden="true">play_arrow</span>
                                            {{ __('prevention_request.draft_continue_step', ['step' => $dossier->current_step >= 2 ? 3 : 2]) }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ══════ TIMELINE — Historique & Journal d'audit ══════ --}}
                        @if ($dossier->events->isNotEmpty())
                            <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print">
                                <div class="p-5 md:p-6">
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
                                    <div class="relative ps-6 sm:ps-8 space-y-6 before:absolute before:start-3 sm:before:start-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                                        @foreach ($dossier->events as $event)
                                            @php [$dot, $icon, $chip, $chipCls] = $eventChip($event->type); @endphp
                                            <div class="relative">
                                                {{-- Pastille --}}
                                                <div class="absolute -start-6 sm:-start-8 top-0 w-6 sm:w-8 flex items-center justify-center">
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
                                                        <time class="text-xs font-mono font-medium text-secondary" dir="ltr">{{ $event->created_at->format('d/m/Y H:i') }}</time>
                                                    </div>
                                                    <h5 class="text-xs font-bold text-on-surface mb-1">
                                                        {{ __('prevention_request.timeline_' . $event->type) }}
                                                    </h5>
                                                    @if (in_array($event->type, ['appointment_scheduled', 'appointment_passed', 'appointment_failed', 'appointment_deleted']) && is_array($event->data) && ! empty($event->data['visit_date']))
                                                        <p class="text-xs inline-flex items-center gap-1 rounded bg-surface-dim px-2 py-0.5 font-semibold text-on-surface mb-1.5" dir="ltr">
                                                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">event</span>
                                                            {{ \Illuminate\Support\Carbon::parse($event->data['visit_date'])->format('d/m/Y') }} @if (! empty($event->data['visit_time'])) • {{ substr((string) $event->data['visit_time'], 0, 5) }} @endif
                                                        </p>
                                                    @endif
                                                    @if (in_array($event->type, ['certificate_issued', 'certificate_collected', 'certificate_deleted']) && is_array($event->data) && ! empty($event->data['reference']))
                                                        <p class="text-xs inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-0.5 font-mono font-bold text-amber-900 mb-1.5" dir="ltr">
                                                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">workspace_premium</span>
                                                            {{ $event->data['reference'] }}
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
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- ══════ COLONNE DROITE (4 colonnes) ══════ --}}
                    <div class="lg:col-span-4 space-y-6">

                        {{-- ENCADRÉ 1 : Pièces justificatives versées --}}
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                            <div class="p-5">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="w-8 h-8 rounded bg-primary-fixed/50 text-primary flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">attach_file</span>
                                    </span>
                                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">
                                        {{ __('prevention_request.pieces_title') }}
                                    </h3>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded whitespace-nowrap {{ $isDocComplete ? 'text-amber-900 bg-amber-100' : 'text-secondary bg-surface-dim' }}" dir="ltr">
                                        {{ $uploadedTypes }}/{{ $documentTypes->count() }}
                                    </span>
                                </div>

                                {{-- Barre de complétude --}}
                                <div class="h-2 bg-surface-dim/50 rounded-full overflow-hidden mb-1" role="progressbar" aria-valuenow="{{ $docPct }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $isDocComplete ? 'bg-amber-500' : 'bg-primary' }}" style="width: {{ $docPct }}%"></div>
                                </div>
                                <p class="text-[10px] font-semibold mb-4 {{ $isDocComplete ? 'text-amber-800' : 'text-secondary' }}">
                                    {{ $isDocComplete ? __('prevention_request.docs_complete') : __('prevention_request.docs_incomplete', ['done' => $uploadedTypes, 'total' => $documentTypes->count()]) }}
                                </p>

                                <div class="space-y-2.5">
                                    @forelse ($documentTypes as $docType)
                                        @php $files = $documents->get($docType->id, collect()); @endphp
                                        @forelse ($files as $doc)
                                            <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-2.5 group hover:border-primary/40 transition-colors">
                                                <div class="flex items-start gap-2.5 min-w-0">
                                                    <span class="material-symbols-outlined text-red-600 text-lg mt-0.5 shrink-0" aria-hidden="true">{{ str_contains((string) $doc->mime_type, 'pdf') ? 'picture_as_pdf' : 'description' }}</span>
                                                    <div class="min-w-0">
                                                        <h4 class="text-xs font-bold text-on-surface leading-snug">{{ $docType->name }}</h4>
                                                        <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap" dir="ltr">
                                                            <span class="font-mono">{{ $fmtSize($doc->size) }}</span>
                                                            <span class="text-surface-dim">•</span>
                                                            <span>{{ $doc->created_at->format('d/m/Y') }}</span>
                                                            @if ($doc->original_name)
                                                                <span class="truncate max-w-36 text-secondary/80" title="{{ $doc->original_name }}">— {{ $doc->original_name }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <a href="{{ route('prevention-requests.documents.download', [$dossier, $doc]) }}"
                                                    class="w-7 h-7 rounded bg-surface-container text-secondary hover:bg-primary hover:text-on-primary flex items-center justify-center shrink-0 transition-colors"
                                                    title="{{ __('prevention_request.download') }}">
                                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                                </a>
                                            </div>
                                        @empty
                                            <div class="p-3 border border-dashed border-surface-dim rounded flex items-start justify-between gap-2.5 hover:border-outline/60 transition-colors">
                                                <div class="flex items-start gap-2.5 min-w-0">
                                                    <span class="w-7 h-7 rounded bg-surface-dim/40 text-outline flex items-center justify-center shrink-0" aria-hidden="true">
                                                        <span class="material-symbols-outlined text-sm">{{ $docType->is_conditional ? 'help_outline' : 'error_outline' }}</span>
                                                    </span>
                                                    <div class="min-w-0">
                                                        <h4 class="text-xs font-bold {{ $docType->is_conditional ? 'text-secondary' : 'text-on-surface font-semibold' }}">{{ $docType->name }}</h4>
                                                        <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap">
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
                                        <div class="pt-2 mt-1 border-t border-surface-dim/60">
                                            <p class="text-[11px] font-bold text-secondary flex items-center gap-1.5 mb-2">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">attachment</span>
                                                {{ __('prevention_request.other_files') }} ({{ $orphanDocs->count() }})
                                            </p>
                                            <div class="space-y-2.5">
                                                @foreach ($orphanDocs as $doc)
                                                    <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-2.5 group hover:border-primary/40 transition-colors">
                                                        <div class="flex items-start gap-2.5 min-w-0">
                                                            <span class="material-symbols-outlined text-red-600 text-lg mt-0.5 shrink-0" aria-hidden="true">{{ str_contains((string) $doc->mime_type, 'pdf') ? 'picture_as_pdf' : 'description' }}</span>
                                                            <div class="min-w-0">
                                                                <h4 class="text-xs font-bold text-on-surface truncate" title="{{ $doc->original_name }}">{{ $doc->original_name ?? __('prevention_request.download') }}</h4>
                                                                <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5" dir="ltr">
                                                                    <span class="font-mono">{{ $fmtSize($doc->size) }}</span>
                                                                    <span class="text-surface-dim">•</span>
                                                                    <span>{{ $doc->created_at->format('d/m/Y') }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <a href="{{ route('prevention-requests.documents.download', [$dossier, $doc]) }}"
                                                            class="w-7 h-7 rounded bg-surface-container text-secondary hover:bg-primary hover:text-on-primary flex items-center justify-center shrink-0 transition-colors"
                                                            title="{{ __('prevention_request.download') }}">
                                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ENCADRÉ 2 : Direction régionale + coordonnées du demandeur --}}
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                            <div class="p-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="w-8 h-8 rounded bg-primary-fixed/50 text-primary flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">apartment</span>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary">{{ __('prevention_request.regional_title') }}</h3>
                                        <p class="text-[11px] text-on-surface font-bold truncate">{{ $dossier->governorate?->name ?? '—' }} — {{ __('prevention_request.regional_bureau') }}</p>
                                    </div>
                                </div>

                                <div class="space-y-3 text-xs">
                                    <div class="flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-primary text-base shrink-0 mt-0.5" aria-hidden="true">location_on</span>
                                        <div class="min-w-0">
                                            <span class="text-[10px] uppercase tracking-wider font-bold text-secondary block">{{ __('prevention_request.building_address') }}</span>
                                            <span class="text-on-surface font-medium">{{ $dossier->building_address ?? '—' }}</span>
                                        </div>
                                    </div>
                                    {{-- Téléphone du demandeur — appelable --}}
                                    @if ($dossier->phone)
                                        <div class="flex items-start gap-2.5">
                                            <span class="material-symbols-outlined text-primary text-base shrink-0 mt-0.5" aria-hidden="true">call</span>
                                            <div class="min-w-0">
                                                <span class="text-[10px] uppercase tracking-wider font-bold text-secondary block">{{ __('prevention_request.phone_applicant') }}</span>
                                                <span class="inline-flex items-center gap-2 mt-0.5">
                                                    <a class="hover:text-primary font-mono font-bold text-on-surface text-xs" href="tel:{{ preg_replace('/[^0-9+]/', '', $dossier->phone) }}" dir="ltr">{{ $dossier->phone }}</a>
                                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $dossier->phone) }}"
                                                        class="inline-flex items-center gap-1 bg-primary-container hover:bg-primary text-on-primary text-[10px] font-bold px-2 py-0.5 rounded transition-colors">
                                                        <span class="material-symbols-outlined text-xs" aria-hidden="true">phone_in_talk</span>
                                                        {{ __('prevention_request.call') }}
                                                    </a>
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="flex items-start gap-2.5">
                                        <span class="material-symbols-outlined text-primary text-base shrink-0 mt-0.5" aria-hidden="true">badge</span>
                                        <div class="min-w-0">
                                            <span class="text-[10px] uppercase tracking-wider font-bold text-secondary block">{{ __('prevention_request.nom_prenom') }}</span>
                                            <span class="text-on-surface font-medium block truncate">{{ $dossier->nom_prenom }}</span>
                                            <span class="text-secondary font-mono text-[11px]" dir="ltr">{{ __('prevention_request.cin') }}: {{ $dossier->cin }}</span>
                                        </div>
                                    </div>
                                    @if ($dossier->num_demande)
                                        <div class="flex items-start gap-2.5">
                                            <span class="material-symbols-outlined text-primary text-base shrink-0 mt-0.5" aria-hidden="true">receipt_long</span>
                                            <div class="min-w-0">
                                                <span class="text-[10px] uppercase tracking-wider font-bold text-secondary block">{{ __('prevention_request.num_demande') }}</span>
                                                <span class="font-mono font-bold text-on-surface" dir="ltr">{{ $dossier->num_demande }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ENCADRÉ 3 : Besoin d'assistance ? --}}
                        <div class="bg-gradient-to-br from-surface-container to-surface-container-high border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print">
                            <div class="p-5">
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
            </div>

            {{-- ═════════════════════════════════════════════════════════════════
                FICHE IMPRIMABLE — feuille professionnelle du dossier
                Masquée à l'écran, seule visible sous @media print.
                Contenu : demandeur • bâtiment • références • documents (n°, nom,
                taille, date, complétude X/Y). Sans statut ni historique.
                ═════════════════════════════════════════════════════════════════ --}}
            <div class="print-sheet" aria-hidden="true">
                <div class="fiche">
                    {{-- Bandeau institutionnel --}}
                    <div class="fiche-band">
                        <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                            <img src="/images/logo.png" alt="" class="fiche-band-logo">
                            <div style="min-width:0;">
                                <div class="fiche-band-org">{{ setting('app_name', __('app.name')) }}</div>
                                <div class="fiche-band-sub">{{ __('prevention_request.fiche_header_service', ['place' => $dossier->governorate?->name ?? '—']) }}</div>
                            </div>
                        </div>
                        <div class="fiche-band-ref">
                            <div class="fiche-band-ref-label">{{ __('prevention_request.fiche_ref_label') }}</div>
                            <div class="fiche-band-ref-value" dir="ltr">{{ $viewRef }}</div>
                        </div>
                    </div>

                    {{-- Titre + date d'édition --}}
                    <div class="fiche-title-row">
                        <div class="fiche-title">{{ __('prevention_request.fiche_title') }}</div>
                        <div class="fiche-printed" dir="ltr">{{ __('prevention_request.fiche_printed_on', ['date' => $printDate]) }}</div>
                    </div>

                    {{-- Bloc jumelé : demandeur • bâtiment --}}
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                        <section class="fiche-sec">
                            <h2 class="fiche-h2">{{ __('prevention_request.fiche_section_applicant') }}</h2>
                            <table class="fiche-table fiche-kv">
                                <tr><th>{{ __('prevention_request.nom_prenom') }}</th><td style="font-weight:700;">{{ $dossier->nom_prenom }}</td></tr>
                                <tr><th>{{ __('prevention_request.cin') }}</th><td class="fiche-mono" dir="ltr">{{ $dossier->cin }}</td></tr>
                                <tr><th>{{ __('prevention_request.phone_applicant') }}</th><td class="fiche-mono" dir="ltr">{{ $dossier->phone ?? '—' }}</td></tr>
                                <tr><th>{{ __('prevention_request.email') }}</th><td dir="ltr">{{ $dossier->user?->email ?? '—' }}</td></tr>
                            </table>
                        </section>
                        <section class="fiche-sec">
                            <h2 class="fiche-h2">{{ __('prevention_request.fiche_section_building') }}</h2>
                            <table class="fiche-table fiche-kv">
                                <tr><th>{{ __('prevention_request.building_type') }}</th><td>{{ $dossier->buildingType?->name ?? '—' }}</td></tr>
                                <tr><th>{{ __('prevention_request.building_activity') }}</th><td>{{ $dossier->buildingActivity?->name ?? $dossier->activity_other ?? '—' }}</td></tr>
                                <tr><th>{{ __('prevention_request.delegation') }}</th><td>{{ $dossier->delegation?->name ?? '—' }}</td></tr>
                                <tr><th>{{ __('prevention_request.building_address') }}</th><td>{{ $dossier->building_address ?? '—' }}</td></tr>
                            </table>
                        </section>
                    </div>

                    {{-- Références administratives --}}
                    <section class="fiche-sec">
                        <h2 class="fiche-h2">{{ __('prevention_request.fiche_section_refs') }}</h2>
                        <table class="fiche-table fiche-kv-4">
                            <tr>
                                <th>{{ __('prevention_request.num_demande') }}</th>
                                <td class="fiche-mono" dir="ltr">{{ $dossier->num_demande ?: '—' }}</td>
                                <th>{{ __('prevention_request.reference') }}</th>
                                <td class="fiche-mono" dir="ltr">{{ $dossier->api_reference ?: $dossier->reference ?: '—' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('prevention_request.date_demande') }}</th>
                                <td class="fiche-mono" dir="ltr">{{ $dossier->created_at->format('d/m/Y') }}</td>
                                <th>{{ __('prevention_request.code_structure') }}</th>
                                <td>{{ $dossier->governorate?->name ?? '—' }}</td>
                            </tr>
                        </table>
                    </section>

                    {{-- Documents versés au dossier — avec numérotation et complétude --}}
                    <section class="fiche-sec">
                        <h2 class="fiche-h2">
                            {{ __('prevention_request.fiche_section_documents') }}
                            <span dir="ltr" style="font-weight:700;">— {{ $uploadedTypes }}/{{ $documentTypes->count() }}</span>
                        </h2>
                        <table class="fiche-table fiche-table-dark">
                            <thead>
                                <tr>
                                    <th style="width:6%;">{{ __('prevention_request.fiche_col_no') }}</th>
                                    <th>{{ __('prevention_request.fiche_col_document') }}</th>
                                    <th style="width:auto;">{{ __('prevention_request.fiche_col_file') }}</th>
                                    <th style="width:11%;">{{ __('prevention_request.fiche_col_size') }}</th>
                                    <th style="width:13%;">{{ __('prevention_request.fiche_col_date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $docNo = 0; @endphp
                                @forelse ($documentTypes as $docType)
                                    @php $files = $documents->get($docType->id, collect()); @endphp
                                    @forelse ($files as $doc)
                                        @php $docNo++; @endphp
                                        <tr>
                                            <td class="fiche-mono" dir="ltr" style="text-align:center;">{{ str_pad($docNo, 2, '0', STR_PAD_LEFT) }}</td>
                                            <td>{{ $docType->name }}</td>
                                            <td dir="ltr">{{ $doc->original_name ?: '—' }}</td>
                                            <td class="fiche-mono" dir="ltr">{{ $fmtSize($doc->size) }}</td>
                                            <td class="fiche-mono" dir="ltr">{{ $doc->created_at->format('d/m/Y') }}</td>
                                        </tr>
                                    @empty
                                        @php $docNo++; @endphp
                                        <tr>
                                            <td class="fiche-mono" dir="ltr" style="text-align:center;">{{ str_pad($docNo, 2, '0', STR_PAD_LEFT) }}</td>
                                            <td>
                                                {{ $docType->name }}
                                                @if ($docType->is_conditional)
                                                    <span class="fiche-dim" style="font-size:9px;">({{ __('prevention_request.conditional') }})</span>
                                                @endif
                                            </td>
                                            <td class="fiche-missing">— {{ __('prevention_request.fiche_missing_doc') }}</td>
                                            <td class="fiche-dim">—</td>
                                            <td class="fiche-dim">—</td>
                                        </tr>
                                    @endforelse
                                @empty
                                    <tr><td colspan="5" class="fiche-dim" style="text-align:center;">—</td></tr>
                                @endforelse
                                {{-- Fichiers hors checklist --}}
                                @foreach ($documents->filter(fn ($group, $typeId) => ! $documentTypes->contains('id', $typeId))->flatten() as $doc)
                                    @php $docNo++; @endphp
                                    <tr>
                                        <td class="fiche-mono" dir="ltr" style="text-align:center;">{{ str_pad($docNo, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td class="fiche-dim">{{ __('prevention_request.other_files') }}</td>
                                        <td dir="ltr">{{ $doc->original_name ?: '—' }}</td>
                                        <td class="fiche-mono" dir="ltr">{{ $fmtSize($doc->size) }}</td>
                                        <td class="fiche-mono" dir="ltr">{{ $doc->created_at->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="fiche-tfoot">
                                        {{ __('prevention_request.fiche_docs_summary', ['done' => $uploadedTypes, 'total' => $documentTypes->count()]) }}
                                        — {{ __('prevention_request.fiche_total_files', ['n' => $uploads]) }}
                                        • {{ __('prevention_request.fiche_total_size', ['size' => $totalSize]) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </section>

                    {{-- Certificat — le cas échéant (n° de document officiel) --}}
                    @if ($dossier->certificate)
                        <div class="fiche-cert fiche-sec">
                            <div class="fiche-cert-title">{{ __('prevention_request.fiche_section_certificate') }}</div>
                            <table class="fiche-table" style="border:none;">
                                <tr>
                                    <td style="border:none; padding-inline-start:0;">
                                        <strong>{{ __('prevention_request.fiche_certificate_ref') }} :</strong>
                                        <span class="fiche-mono" dir="ltr">{{ $dossier->certificate->reference }}</span>
                                        @if ($dossier->certificate->issued_at)
                                            — {{ __('prevention_request.certificate_issued_on') }} {{ $dossier->certificate->issued_at->format('d/m/Y') }}
                                        @endif
                                        @if ($dossier->certificate->valid_until)
                                            — {{ __('prevention_request.certificate_valid_until') }} {{ $dossier->certificate->valid_until->format('d/m/Y') }}
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @endif

                    {{-- Signatures --}}
                    <div class="fiche-sign-row fiche-sec">
                        <div class="fiche-sign-col">
                            <div class="fiche-sign-name">{{ __('prevention_request.fiche_sign_applicant') }}</div>
                            <div class="fiche-sign-line">{{ __('prevention_request.fiche_sign_place', ['place' => $dossier->governorate?->name ?? '—']) }} — {{ __('prevention_request.fiche_printed_on', ['date' => now()->format('d/m/Y')]) }}</div>
                        </div>
                        <div class="fiche-sign-col">
                            <div class="fiche-sign-name">{{ __('prevention_request.fiche_sign_service') }}</div>
                            <div class="fiche-sign-line">{{ __('prevention_request.fiche_sign_place', ['place' => $dossier->governorate?->name ?? '—']) }} — {{ __('prevention_request.fiche_printed_on', ['date' => now()->format('d/m/Y')]) }}</div>
                        </div>
                    </div>

                    {{-- Pied de fiche --}}
                    <div class="fiche-foot">
                        <span>{{ __('prevention_request.fiche_footer_note') }}</span>
                        <span dir="ltr">{{ url()->current() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</x-app-layout>
