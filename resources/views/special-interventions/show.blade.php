@php
    $specialIntervention->loadMissing('user');
    $status  = $specialIntervention->status;
    $viewRef = $specialIntervention->reference ?: \App\Support\Reference::provisional('special-intervention');

    /* ── Preuves issues du journal d'audit (dates des étapes) ── */
    $advancedEvent = $specialIntervention->events->last(fn ($e) => $e->type === 'advanced_to_consultation');
    $approvedEvent = $specialIntervention->events->last(fn ($e) => $e->type === 'approved');
    $servedEvent   = $specialIntervention->events->last(fn ($e) => $e->type === 'served');

    /* ── Stepper 5 étapes : dépôt → vérification → approbation → planification → réalisation ── */
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
                || $specialIntervention->events->contains(fn ($e) => $e->type === 'correction_requested') => 2,
            default                                                            => 2,
        },
        default              => null,
    };
    $isCancelled = $status === 'cancelled';

    $stepDates = [];
    $stepDates[1] = $specialIntervention->created_at;
    $stepDates[2] = $advancedEvent?->created_at;
    $stepDates[3] = $approvedEvent?->created_at;
    $stepDates[4] = $specialIntervention->updated_at;
    $stepDates[5] = $servedEvent?->created_at;

    $steps = [];
    $hadCorrection = $specialIntervention->events->contains(fn ($e) => $e->type === 'correction_requested');
    $cancelledAt = $isCancelled ? min($currentStep, 5) : null;

    foreach ([1, 2, 3, 4, 5] as $i) {
        if ($isCancelled) {
            if ($i < $cancelledAt)       $state = 'done';
            elseif ($i === $cancelledAt) $state = 'cancelled';
            else                         $state = 'pending';
        } else {
            if ($currentStep === 5 || $i < $currentStep) $state = 'done';
            elseif ($i === $currentStep)                 $state = $status === 'needs_correction' ? 'error' : 'current';
            else                                         $state = 'pending';
        }

        if ($state === 'done' && $i === 2 && $hadCorrection) $state = 'warning';

        $sub = match (true) {
            $state === 'cancelled'          => __('special_intervention.stepper_cancelled'),
            $state === 'warning' && $i === 2 => __('special_intervention.stepper_had_correction'),
            $status === 'needs_correction' && $state === 'error' => __('special_intervention.stepper_correction'),
            $state === 'done'               => __('special_intervention.stepper_done'),
            $state === 'pending'            => __('special_intervention.stepper_pending'),
            $i === 1                        => __('special_intervention.draft_notice'),
            $i === 2 && $status === 'completed' => __('special_intervention.stepper_verify_pending'),
            $i === 2 && $status === 'under_consultation' => __('special_intervention.stepper_verify_waiting'),
            $i === 3                        => __('special_intervention.stepper_cert_approved'),
            $i === 4                        => __('special_intervention.stepper_delivery_pending'),
            $i === 5                        => __('special_intervention.stepper_delivery_done'),
            default                         => __('special_intervention.stepper_current'),
        };

        $alert = in_array($state, ['error', 'cancelled']);

        $steps[] = [
            'num' => $i, 'title' => __('special_intervention.stepper_' . $i),
            'sub' => $sub, 'state' => $state, 'alert' => $alert,
        ];
    }

    $doneSteps = collect($steps)->whereIn('state', ['done', 'warning'])->count();
    $progress  = (int) round($doneSteps / 5 * 100);

    /* ── Pastille de statut (héros) ── */
    [$statusPillCls, $statusPillIcon] = match ($status) {
        'draft', 'completed'  => ['bg-surface-container-high text-secondary', 'edit_note'],
        'under_consultation'  => ['bg-blue-100 text-blue-800', 'manage_search'],
        'needs_correction'    => ['bg-red-100 text-red-800', 'error'],
        'approved'            => ['bg-amber-100 text-amber-900', 'task_alt'],
        'served'              => ['bg-teal-100 text-teal-800', 'handshake'],
        'cancelled'           => ['bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300', 'cancel'],
        default               => ['bg-surface-container-high text-secondary', 'info'],
    };

    /* ── Bandeau d'état du héros ── */
    $heroAlert = match (true) {
        $status === 'cancelled'          => 'error',
        $status === 'draft'              => 'draft',
        $status === 'needs_correction'   => 'error',
        $status === 'under_consultation' => 'info',
        $status === 'approved'           => 'info',
        $status === 'served'             => 'neutral',
        default                          => 'neutral',
    };

    /* ── Documents : complétude ── */
    $activeDocuments = $specialIntervention->documents->filter(fn ($d) => $d->status !== 'archived');
    $documentsByType = $activeDocuments->groupBy('document_type_id');
    $uploadedDocs    = $activeDocuments->keyBy('document_type_id');
    $uploadedTypes   = $documentsByType->filter(fn ($files, $typeId) => $documentTypes->contains('id', $typeId) && $files->isNotEmpty())->count();
    $fmtSize = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1) . ' Mo' : number_format($b / 1024, 0) . ' Ko';
    $isDocComplete = $documentTypes->count() > 0 && $uploadedTypes >= $documentTypes->count();
    $docPct        = $documentTypes->count() > 0 ? (int) round(min($uploadedTypes, $documentTypes->count()) / $documentTypes->count() * 100) : 0;
    $correction    = $specialIntervention->lastCorrectionData();

    /* ── Timeline event chips ── */
    $eventChip = fn ($type) => match (true) {
        in_array($type, ['submitted', 'resubmitted'])
            => ['bg-secondary-container text-on-secondary-container', 'post_add', __('special_intervention.chip_depot'), 'bg-surface-dim text-secondary'],
        $type === 'advanced_to_consultation'
            => ['bg-violet-600 text-white', 'sync_alt', __('special_intervention.chip_phase'), 'bg-surface-dim text-secondary'],
        $type === 'correction_requested'
            => ['bg-amber-500 text-white', 'edit_note', __('special_intervention.chip_correction'), 'bg-amber-100 text-amber-800'],
        $type === 'approved'
            => ['bg-amber-600 text-white', 'task_alt', __('special_intervention.chip_favorable'), 'bg-amber-100 text-amber-800'],
        $type === 'served'
            => ['bg-teal-600 text-white', 'handshake', __('special_intervention.chip_done'), 'bg-teal-100 text-teal-800'],
        default
            => ['bg-red-600 text-white', 'error', __('special_intervention.chip_alert'), 'bg-red-100 text-red-700'],
    };

    /* ── Fiche impression : édité aujourd'hui ── */
    $printDate = now()->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY, HH:mm');

    /* ── Interventions sélectionnées avec totaux ── */
    $selectedInterventions = $specialIntervention->interventions ?? [];
    $totalAmount = $specialIntervention->total_amount;
@endphp

<style>
    .print-sheet { display: none; }

    @media print {
        @page { size: A4; margin: 11mm 11mm 14mm; }
        header, footer, .no-print { display: none !important; }
        html, body { background: #ffffff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .print-sheet { display: block !important; }
    }

    .fiche {
        font-family: var(--font-sans, 'Roboto', Arial, sans-serif);
        font-size: 11.5px; line-height: 1.5; color: #1a1d29; background: #ffffff;
    }
    .fiche-band {
        background: #9e001f; color: #ffffff; padding: 12px 18px;
        display: flex; align-items: center; justify-content: space-between; gap: 16px;
        border-radius: 3px; -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .fiche-band-logo { width: 46px; height: 46px; object-fit: contain; border-radius: 4px; background: #fff; padding: 3px; }
    .fiche-band-org   { font-size: 14.5px; font-weight: 800; letter-spacing: .02em; line-height: 1.25; }
    .fiche-band-sub   { font-size: 10px; opacity: .92; font-weight: 400; }
    .fiche-band-ref   { text-align: end; line-height: 1.3; }
    .fiche-band-ref-label { font-size: 9px; text-transform: uppercase; letter-spacing: .14em; opacity: .85; font-weight: 600; }
    .fiche-band-ref-value { font-family: 'Roboto Mono', Menlo, Consolas, monospace; font-size: 15px; font-weight: 700; letter-spacing: .03em; }
    .fiche-title-row {
        display: flex; align-items: baseline; justify-content: space-between; gap: 12px;
        margin: 14px 0 16px; padding-bottom: 8px; border-bottom: 2px solid #1a1d29;
    }
    .fiche-title      { font-size: 15px; font-weight: 800; letter-spacing: .01em; }
    .fiche-title-ar   { font-size: 13px; font-weight: 700; }
    .fiche-date       { font-size: 9.5px; color: #6b7280; text-align: end; line-height: 1.4; }
    .fiche-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .fiche-table th, .fiche-table td {
        border: 1px solid #c7cad1; padding: 6px 9px; text-align: start; vertical-align: top; font-size: 11px;
    }
    .fiche-table th  { background: #f4f4f7; font-weight: 700; width: 34%; }
    .fiche-table-dark thead th { background: #1a1d29; color: #fff; }
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

                {{-- ── Correction requise ── --}}
                @if ($status === 'needs_correction')
                <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden mb-6 no-print">
                    <div class="border-s-4 border-s-red-600 p-5">
                        <div class="flex items-start gap-3.5 mb-3">
                            <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">assignment_late</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                        {{ __('special_intervention.correction_title') }}
                                    </h2>
                                    <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded">{{ __('special_intervention.chip_alert') }}</span>
                                </div>
                            </div>
                        </div>
                        @if ($specialIntervention->admin_note)
                        <div class="rounded border border-red-200 bg-red-50 dark:bg-red-900/10 px-4 py-3 mb-4">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                                {{ __('special_intervention.admin_note_label') }}
                            </p>
                            <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $specialIntervention->admin_note }}</p>
                        </div>
                        @endif

                        @php
                            $siFieldLabels = [
                                'cin'                  => __('special_intervention.cin'),
                                'nom_prenom'           => __('special_intervention.nom_prenom'),
                                'phone'                => __('special_intervention.phone'),
                                'governorate_id'       => __('special_intervention.code_structure'),
                                'delegation_id'        => __('special_intervention.delegation'),
                                'intervention_date'    => __('special_intervention.intervention_date'),
                                'intervention_time'    => __('special_intervention.intervention_time'),
                                'notes'                => __('special_intervention.notes'),
                                'interventions'        => __('special_intervention.intervention_section'),
                            ];
                            $correctionDocs = ! empty($correction['docs_to_fix'])
                                ? $documentTypes->filter(fn ($t) => in_array($t->id, $correction['docs_to_fix']))
                                : collect();
                        @endphp
                        @if (! empty($correction['fields_to_fix']) || $correctionDocs->isNotEmpty())
                        <div class="mb-4 flex flex-wrap gap-1.5">
                            @foreach ($correction['fields_to_fix'] ?? [] as $fk)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700/40">
                                    <span class="material-symbols-outlined text-[11px]" aria-hidden="true">edit</span>
                                    {{ $siFieldLabels[$fk] ?? $fk }}
                                </span>
                            @endforeach
                            @foreach ($correctionDocs as $dt)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-700/40">
                                    <span class="material-symbols-outlined text-[11px]" aria-hidden="true">upload_file</span>
                                    {{ $dt->name }}
                                </span>
                            @endforeach
                        </div>
                        @endif

                        <a href="{{ route('special-interventions.correct', $specialIntervention) }}"
                           class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">edit_note</span>
                            {{ __('special_intervention.fix_and_resubmit') }}
                        </a>
                    </div>
                </section>
                @endif

                {{-- ── Approuvé / Servi — panneau de renseignements ── --}}
                @if (in_array($status, ['approved', 'served', 'under_consultation']))
                <section class="bg-surface-container-lowest border {{ $status === 'served' ? 'border-teal-200 dark:border-teal-800/40' : 'border-amber-200 dark:border-amber-700/40' }} rounded-lg shadow-sm overflow-hidden mb-6 no-print">
                    <div class="border-s-4 border-s-{{ $status === 'served' ? 'teal' : 'amber' }}-500 p-5">
                        <div class="flex items-start gap-3.5 mb-3">
                            <div class="w-10 h-10 rounded {{ $status === 'served' ? 'bg-teal-100 text-teal-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">{{ $status === 'served' ? 'handshake' : 'workspace_premium' }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                        {{ __('special_intervention.approved_banner') }}
                                    </h2>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $status === 'served' ? 'bg-teal-100 text-teal-800' : 'bg-amber-500 text-white' }}">
                                        {{ $status === 'served' ? __('special_intervention.stepper_delivery_done') : __('special_intervention.stepper_cert_approved') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Montant total --}}
                        @if ($totalAmount > 0)
                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/30 dark:bg-amber-900/10">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-amber-600 text-lg" aria-hidden="true">payments</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wider mb-1">{{ __('special_intervention.fees_title') }}</p>
                                    <p class="text-body-sm text-amber-900 dark:text-amber-200 font-bold">
                                        {{ __('special_intervention.total_amount') }}: <span dir="ltr">{{ number_format($totalAmount, 0) }} {{ __('special_intervention.currency_tnd') }}</span>
                                    </p>
                                    <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-1">{{ __('special_intervention.billing_note') }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Détails de l'intervention --}}
                        @if ($specialIntervention->intervention_date)
                        <div class="mt-3 rounded-lg border border-surface-dim bg-surface-container-low p-4">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-primary text-lg" aria-hidden="true">event</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold text-secondary uppercase tracking-wider mb-1">{{ __('special_intervention.intervention_section') }}</p>
                                    <p class="text-body-sm text-on-surface">
                                        {{ $specialIntervention->intervention_date->format('d/m/Y') }}
                                        @if ($specialIntervention->intervention_time) · {{ $specialIntervention->intervention_time->format('H:i') }}@endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-secondary mt-3">
                            {{ __('special_intervention.review_note') }}
                        </div>
                    </div>
                </section>
                @endif

                {{-- ── Brouillon ── --}}
                @if ($status === 'draft')
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden mb-6 no-print">
                    <div class="border-s-4 border-s-red-600 p-5 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl" aria-hidden="true">edit_note</span>
                            </div>
                            <div>
                                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('special_intervention.status_draft') }}</h2>
                                <p class="text-body-sm text-secondary mt-0.5">{{ __('special_intervention.draft_notice') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('special-interventions.step2.show', $specialIntervention) }}"
                            class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">play_arrow</span>
                            {{ __('special_intervention.continue') }}
                        </a>
                    </div>
                </section>
                @endif

                {{-- ══════════════ Cycle de Vie de la Demande (Stepper + progression) ══════════════ --}}
                <section class="bg-surface-container-lowest border {{ $isCancelled ? 'border-red-200 dark:border-red-800/40' : 'border-surface-dim' }} rounded-lg shadow-sm mb-6 overflow-hidden">
                    @if ($isCancelled)
                    <div class="border-b border-red-200 dark:border-red-800/40 bg-red-50 dark:bg-red-900/20 border-s-4 border-s-red-600 p-5 md:px-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">cancel</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('special_intervention.cancelled_banner') }}</h3>
                                <p class="text-body-sm text-secondary mt-1">{{ __('special_intervention.status_cancelled') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="p-5 md:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-surface-dim">
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">route</span>
                                {{ __('special_intervention.stepper_title') }}
                            </h3>
                            <div class="flex items-center gap-3 flex-1 sm:flex-none sm:min-w-64">
                                @if ($isCancelled)
                                <span class="text-xs font-bold text-white bg-red-600 px-2 py-0.5 rounded whitespace-nowrap">
                                    {{ __('special_intervention.stepper_cancelled') }}
                                </span>
                                @else
                                <span class="text-xs font-bold text-primary bg-primary-fixed/40 px-2 py-0.5 rounded whitespace-nowrap">
                                    {{ __('special_intervention.phase_instruction', ['step' => $currentStep ? min($currentStep, 5) : 1]) }}
                                </span>
                                @endif
                                <div class="hidden sm:flex-1 h-1.5 bg-surface-dim/50 rounded-full overflow-hidden">
                                    <div class="h-full {{ $isCancelled ? 'bg-red-500' : 'bg-gradient-to-r from-primary-container to-amber-500' }} rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                                </div>
                                <span class="text-[11px] font-mono font-bold text-secondary hidden sm:inline" dir="ltr">{{ $progress }}%</span>
                            </div>
                        </div>

                        <div class="relative grid grid-cols-1 md:grid-cols-5 gap-5 md:gap-3">
                            <div class="hidden md:block absolute top-5 [inset-inline-start:10%] [inset-inline-end:10%] h-0.5 bg-surface-dim" aria-hidden="true"></div>

                            @foreach ($steps as $step)
                                <div class="relative flex md:flex-col items-start md:items-center gap-3 md:gap-2 md:text-center">
                                    @if ($step['state'] === 'done')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-sm ring-4 ring-amber-100">
                                            <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">check</span>
                                        </span>
                                    @elseif ($step['state'] === 'warning')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-sm ring-4 ring-amber-100">
                                            <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">check</span>
                                        </span>
                                    @elseif ($step['state'] === 'current')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center text-sm font-black shadow-sm ring-4 ring-amber-200 animate-pulse">
                                            {{ $step['num'] }}
                                        </span>
                                    @elseif ($step['state'] === 'error')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow-sm ring-4 ring-red-100">
                                            <span class="material-symbols-outlined text-lg" aria-hidden="true">edit_note</span>
                                        </span>
                                    @elseif ($step['state'] === 'cancelled')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow-sm ring-4 ring-red-100">
                                            <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">cancel</span>
                                        </span>
                                    @else
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-surface-container text-secondary flex items-center justify-center text-sm font-bold border border-surface-dim">
                                            {{ $step['num'] }}
                                        </span>
                                    @endif

                                    <div class="min-w-0 md:px-1">
                                        <div class="flex md:justify-center items-center gap-2">
                                            <h4 class="text-xs font-bold {{ $step['state'] === 'pending' ? 'text-secondary' : 'text-on-surface' }}">
                                                {{ $step['title'] }}
                                            </h4>
                                            @if ($step['state'] === 'current')
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                                            @elseif ($step['state'] === 'error' || $step['state'] === 'cancelled')
                                                <span class="text-[10px] uppercase font-black text-red-800 bg-red-100 px-1.5 py-px rounded">{{ __('special_intervention.chip_alert') }}</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] mt-0.5 leading-snug {{ $step['alert'] ? 'text-red-700 font-medium' : ($step['state'] === 'current' ? 'text-amber-700 font-medium' : ($step['state'] === 'done' || $step['state'] === 'warning' ? 'text-amber-700 font-medium' : 'text-secondary')) }}">
                                            {{ $step['sub'] }}
                                        </p>
                                        <p class="text-[10px] mt-1 font-semibold {{ in_array($step['state'], ['done','warning']) ? 'text-amber-700' : ($step['state'] === 'current' ? 'text-amber-600' : 'text-secondary') }}" dir="ltr">
                                            @if ($stepDates[$step['num']] ?? null)
                                                {{ $stepDates[$step['num']]->format('d/m/Y') }}
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

                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm mb-6 overflow-hidden">
                    <div class="h-1 bg-gradient-to-r from-primary via-primary-container/70 to-transparent" aria-hidden="true"></div>

                    <div class="p-5 md:p-6">
                        {{-- Ligne haute : titre + actions --}}
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                            <div class="min-w-0">
                                <h1 class="text-headline-md font-headline-md text-on-surface tracking-tight leading-tight" dir="ltr">
                                    {{ __('special_intervention.card_title') }} · <span class="font-mono">{{ $viewRef }}</span>
                                </h1>
                                <p class="text-xs text-secondary mt-1 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">calendar_today</span>
                                    {{ __('special_intervention.deposited_on', ['date' => $specialIntervention->created_at->isoFormat('D MMMM YYYY')]) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2.5 shrink-0">
                                <span class="inline-flex items-center gap-1.5 text-label-caps font-label-caps px-2.5 py-1 rounded font-bold {{ $statusPillCls }}">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $statusPillIcon }}</span>
                                    {{ __('special_intervention.status_' . $status) }}
                                </span>
                                <a href="{{ route('special-interventions.index') }}"
                                    class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-2.5 rounded transition-colors">
                                    <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                                </a>
                                <button type="button" onclick="window.print()"
                                    class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                                </button>
                            </div>
                        </div>

                        {{-- Faits clés --}}
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">fact_check</span>
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-secondary">{{ __('special_intervention.details_title') }}</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">badge</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('special_intervention.nom_prenom') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate" title="{{ $specialIntervention->nom_prenom ?? '' }}">{{ $specialIntervention->nom_prenom ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">fingerprint</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('special_intervention.cin') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate font-mono num-ltr" dir="ltr" title="{{ $specialIntervention->cin ?? '' }}">{{ $specialIntervention->cin ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">call</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('special_intervention.phone') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate font-mono num-ltr" dir="ltr" title="{{ $specialIntervention->phone ?? '' }}">{{ $specialIntervention->phone ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">account_balance</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('special_intervention.code_structure') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate">{{ $specialIntervention->governorate?->name ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">location_city</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('special_intervention.delegation') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate">{{ $specialIntervention->delegation?->name ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">event</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('special_intervention.intervention_date') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate" dir="ltr">
                                        {{ $specialIntervention->intervention_date?->format('d/m/Y') ?? '—' }}@if ($specialIntervention->intervention_time) · {{ $specialIntervention->intervention_time->format('H:i') }}@endif
                                    </span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">payments</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('special_intervention.total_amount') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate font-mono num-ltr" dir="ltr">{{ $totalAmount > 0 ? number_format($totalAmount, 0) . ' ' . __('special_intervention.currency_tnd') : '—' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Note de l'administration --}}
                        @if ($specialIntervention->admin_note && $status !== 'needs_correction')
                        <div class="mt-4 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-3">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-1 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">sticky_note_2</span>
                                {{ __('special_intervention.admin_note_label') }}
                            </p>
                            <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $specialIntervention->admin_note }}</p>
                        </div>
                        @endif

                        <div class="h-2"></div>

                        {{-- Alerte d'état opérationnel --}}
                        @if ($heroAlert === 'info')
                            <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-300 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-amber-600 text-base mt-px" aria-hidden="true">hourglass_bottom</span>
                                <div class="font-bold text-amber-900 leading-relaxed">
                                    {{ $status === 'under_consultation' ? __('special_intervention.under_consultation_banner') : __('special_intervention.approved_banner') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'error')
                            <div class="flex items-start gap-2.5 bg-red-50 border border-red-300 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">notification_important</span>
                                <div class="font-bold text-red-900 leading-relaxed">
                                    {{ $status === 'needs_correction' ? __('special_intervention.needs_correction_banner') : __('special_intervention.cancelled_banner') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'draft')
                            <div class="flex items-start gap-2.5 bg-red-50 border border-red-300 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">edit_note</span>
                                <div class="font-bold text-red-900 leading-relaxed">
                                    {{ __('special_intervention.draft_notice') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'neutral')
                            <div class="flex items-start gap-2.5 bg-surface-container-low border border-surface-dim rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-secondary text-base mt-px" aria-hidden="true">{{ $status === 'served' ? 'handshake' : 'hourglass_top' }}</span>
                                <div class="font-bold text-on-surface leading-relaxed">
                                    {{ __('special_intervention.status_' . $status) }}
                                    <span class="font-normal text-secondary"> — {{ $status === 'served' ? __('special_intervention.served_banner') : __('special_intervention.review_note') }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- ══════════════ Grille asymétrique 8/4 ══════════════ --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">

                    {{-- COLONNE GAUCHE (8 colonnes) --}}
                    <div class="lg:col-span-8 space-y-6">

                        {{-- Détails de l'intervention --}}
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                            <div class="flex items-center gap-2.5 p-5 md:px-6 border-b border-surface-dim">
                                <span class="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">build</span>
                                </span>
                                <div>
                                    <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('special_intervention.intervention_section') }}</h3>
                                </div>
                            </div>
                            <div class="p-5 md:p-6 space-y-4">
                                @if (!empty($selectedInterventions))
                                    <div class="space-y-2">
                                        @foreach ($selectedInterventions as $item)
                                        <div class="p-3 rounded border border-surface-dim bg-surface-container-low/60 flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold text-on-surface leading-snug">{{ app()->getLocale() === 'ar' ? ($item['name_ar'] ?? $item['name_fr'] ?? $item['key']) : ($item['name_fr'] ?? $item['name_ar'] ?? $item['key']) }}</h4>
                                                <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap">
                                                    <span>{{ __('special_intervention.intervention_unit_price') }}: <span class="font-mono font-bold" dir="ltr">{{ number_format($item['price_tnd'] ?? 0, 0) }}</span> {{ __('special_intervention.currency_tnd') }}</span>
                                                    <span class="text-surface-dim">•</span>
                                                    <span>{{ __('special_intervention.intervention_qty') }}: <span class="font-mono font-bold" dir="ltr">{{ $item['hours'] ?? 1 }}</span> h</span>
                                                    <span class="text-surface-dim">•</span>
                                                    <span>{{ __('special_intervention.intervention_amount') }}: <span class="font-mono font-bold" dir="ltr">{{ number_format($item['amount'] ?? 0, 0) }}</span> {{ __('special_intervention.currency_tnd') }}</span>
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
                                    <div class="p-4 text-center">
                                        <span class="material-symbols-outlined text-2xl text-outline" aria-hidden="true">build</span>
                                        <p class="text-xs text-secondary mt-1">{{ __('special_intervention.no_interventions_selected') }}</p>
                                    </div>
                                @endif

                                {{-- Frais payants --}}
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50/60 px-4 py-3 dark:border-amber-800/40 dark:bg-amber-900/10">
                                    <div class="flex items-center gap-2.5">
                                        <span class="material-symbols-outlined text-amber-600 text-lg" aria-hidden="true">payments</span>
                                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300">{{ __('special_intervention.fees_title') }}</span>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 whitespace-nowrap">{{ __('special_intervention.payment_proof') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- TIMELINE --}}
                        @if ($specialIntervention->events->isNotEmpty())
                            <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print" x-data="{ open: false }">
                                <button type="button" @click="open = !open"
                                        class="w-full flex flex-wrap items-center justify-between gap-3 p-5 md:px-6 pb-4 border-b border-surface-dim hover:bg-surface-container dark:hover:bg-gray-700/40 transition-colors text-start">
                                    <div>
                                        <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">
                                            <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">history</span>
                                            {{ __('special_intervention.timeline_title') }}
                                        </h3>
                                        <p class="text-xs text-secondary mt-0.5">{{ __('special_intervention.timeline_subtitle') }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-[11px] text-secondary font-semibold flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">receipt_long</span>
                                            {{ __('special_intervention.events_count', ['n' => $specialIntervention->events->count()]) }}
                                        </span>
                                        <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </button>

                                <div x-show="open" x-cloak class="p-5 md:p-6 pt-5">
                                    <div class="relative ps-6 sm:ps-8 space-y-6 before:absolute before:start-3 sm:before:start-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                                        @foreach ($specialIntervention->events->reverse()->values() as $event)
                                            @php [$dot, $icon, $chip, $chipCls] = $eventChip($event->type); @endphp
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
                                                    <h5 class="text-xs font-bold text-on-surface mb-1">
                                                        {{ __('special_intervention.tl_' . $event->type) }}
                                                    </h5>
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

                    {{-- COLONNE DROITE (4 colonnes) --}}
                    <div class="lg:col-span-4 space-y-6">

                        {{-- DOCUMENTS JOINTS --}}
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print" x-data="{ open: false }">
                            <button type="button" @click="open = !open"
                                    class="w-full flex items-center gap-2 p-5 hover:bg-surface-container transition-colors text-start border-b border-surface-dim">
                                <span class="w-8 h-8 rounded bg-primary-fixed/50 text-primary flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">attach_file</span>
                                </span>
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">{{ __('special_intervention.documents_title') }}</h3>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded whitespace-nowrap {{ $isDocComplete ? 'text-amber-900 bg-amber-100 dark:bg-amber-900/40 dark:text-amber-300' : 'text-secondary bg-surface-dim' }}" dir="ltr">{{ $uploadedTypes }}/{{ $documentTypes->count() }}</span>
                                <svg class="w-4 h-4 text-secondary transition-transform shrink-0 ms-1" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open" x-cloak class="p-5">
                                <div class="h-2 bg-surface-dim/50 rounded-full overflow-hidden mb-1" role="progressbar" aria-valuenow="{{ $docPct }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $isDocComplete ? 'bg-amber-500' : 'bg-primary' }}" style="width: {{ $docPct }}%"></div>
                                </div>
                                <p class="text-[10px] font-semibold mb-4 {{ $isDocComplete ? 'text-amber-800 dark:text-amber-400' : 'text-secondary' }}">
                                    {{ $isDocComplete ? __('special_intervention.documents_title') : $uploadedTypes . ' / ' . $documentTypes->count() }}
                                </p>

                                <div class="space-y-2.5">
                                    @forelse ($documentTypes as $docType)
                                        @php $files = $documentsByType->get($docType->id, collect()); @endphp
                                        @forelse ($files as $doc)
                                            <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-2.5 group hover:border-primary/40 transition-colors">
                                                <div class="flex items-start gap-2.5 min-w-0">
                                                    <span class="material-symbols-outlined text-red-600 text-lg mt-0.5 shrink-0" aria-hidden="true">{{ str_contains((string) $doc->mime_type, 'pdf') ? 'picture_as_pdf' : 'description' }}</span>
                                                    <div class="min-w-0">
                                                        <h4 class="text-xs font-bold text-on-surface leading-snug">{{ $docType->name }}</h4>
                                                        <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap" dir="ltr">
                                                            <span class="font-mono">{{ $fmtSize($doc->size) }}</span>
                                                            <span class="text-surface-dim">•</span>
                                                            <span>{{ $doc->updated_at->format('d/m/Y') }}</span>
                                                            @if ($doc->original_name)
                                                                <span class="truncate max-w-36 text-secondary/80" title="{{ $doc->original_name }}">— {{ $doc->original_name }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <a href="{{ route('special-interventions.documents.download', $doc) }}"
                                                   class="w-7 h-7 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-600 hover:text-white flex items-center justify-center shrink-0 transition-colors"
                                                   title="{{ __('special_intervention.download_label') }}">
                                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                                </a>
                                            </div>
                                        @empty
                                            <div class="p-3 border border-dashed border-surface-dim rounded flex items-start justify-between gap-2.5">
                                                <div class="flex items-start gap-2.5 min-w-0">
                                                    <span class="w-7 h-7 rounded bg-surface-dim/40 text-outline flex items-center justify-center shrink-0" aria-hidden="true">
                                                        <span class="material-symbols-outlined text-sm">{{ $docType->is_conditional ? 'help_outline' : 'error_outline' }}</span>
                                                    </span>
                                                    <div class="min-w-0">
                                                        <h4 class="text-xs font-bold {{ $docType->is_conditional ? 'text-secondary' : 'text-on-surface' }}">{{ $docType->name }}</h4>
                                                        <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap">
                                                            @if ($docType->is_conditional)
                                                                <span class="font-semibold text-amber-900 bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40 px-1.5 rounded">{{ __('special_intervention.document_conditional') }}</span>
                                                            @endif
                                                            <span class="{{ $docType->is_conditional ? '' : 'text-red-600 dark:text-red-400' }}">{{ __('special_intervention.documents_not_uploaded') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforelse
                                    @empty
                                        <div class="p-4 text-center">
                                            <span class="material-symbols-outlined text-2xl text-outline" aria-hidden="true">folder_off</span>
                                            <p class="text-xs text-secondary mt-1">{{ __('special_intervention.admin_no_doctypes') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- Anciennes versions archivées --}}
                        @if (isset($archivedDocuments) && $archivedDocuments->isNotEmpty())
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print" x-data="{ openArchive: false }">
                            <div class="p-5">
                                <button type="button" @click="openArchive = !openArchive" class="w-full text-start">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="w-8 h-8 rounded bg-surface-dim/60 text-secondary flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-base" aria-hidden="true">inventory_2</span>
                                        </span>
                                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">
                                            {{ __('special_intervention.archive_title') }}
                                        </h3>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-surface-dim text-secondary" dir="ltr">{{ $archivedDocuments->count() }}</span>
                                        <svg class="w-4 h-4 text-secondary transition-transform" :class="openArchive ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                    <p class="text-[11px] text-secondary leading-relaxed">{{ __('special_intervention.archive_desc') }}</p>
                                </button>

                                <div x-show="openArchive" x-cloak class="pt-4 space-y-2.5">
                                    @foreach ($archivedDocuments as $doc)
                                        <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-2.5">
                                            <div class="flex items-start gap-2.5 min-w-0">
                                                <span class="material-symbols-outlined text-outline text-lg mt-0.5 shrink-0" aria-hidden="true">inventory_2</span>
                                                <div class="min-w-0">
                                                    <h4 class="text-xs font-bold text-secondary leading-snug">{{ $doc->documentType?->name ?? __('special_intervention.documents_title') }}</h4>
                                                    <div class="flex items-center gap-2 text-[11px] text-secondary/80 mt-0.5 flex-wrap" dir="ltr">
                                                        <span class="font-mono">{{ $fmtSize($doc->size) }}</span>
                                                        <span class="text-surface-dim">•</span>
                                                        <span>{{ $doc->created_at->format('d/m/Y') }}</span>
                                                        @if ($doc->original_name)
                                                            <span class="truncate max-w-36" title="{{ $doc->original_name }}">— {{ $doc->original_name }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="{{ route('special-interventions.documents.download', $doc) }}"
                                               class="w-7 h-7 rounded bg-surface-container text-secondary hover:bg-primary hover:text-on-primary flex items-center justify-center shrink-0 transition-colors"
                                               title="{{ __('special_intervention.download_label') }}">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Suivi par téléphone --}}
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print">
                            <div class="p-5 md:px-6">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2 mb-3">
                                    <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">phone_in_talk</span>
                                    {{ __('special_intervention.phone_notification_hint') }}
                                </h3>
                                <p class="text-xs text-secondary leading-relaxed">{{ __('special_intervention.review_note') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>{{-- /dossier-screen --}}

            {{-- ══════════════ FICHE IMPRESSION (A4) ══════════════ --}}
            <div class="print-sheet">
                <div class="fiche">
                    <div class="fiche-band">
                        <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                            <img src="/images/logo.png" alt="" class="fiche-band-logo">
                            <div style="min-width:0;">
                                <div class="fiche-band-org">{{ setting('app_name', __('app.name')) }}</div>
                                <div class="fiche-band-sub">{{ __('special_intervention.print_header_service', ['place' => $specialIntervention->governorate?->name ?? '—']) }}</div>
                            </div>
                        </div>
                        <div class="fiche-band-ref">
                            <div class="fiche-band-ref-label">{{ __('special_intervention.print_ref_label') }}</div>
                            <div class="fiche-band-ref-value" dir="ltr">{{ $viewRef }}</div>
                        </div>
                    </div>

                    <div class="fiche-title-row">
                        <div>
                            <div class="fiche-title">{{ __('special_intervention.print_title') }}</div>
                            <div class="fiche-title-ar" dir="rtl">{{ __('special_intervention.print_title_ar') }}</div>
                        </div>
                        <div class="fiche-date">
                            {{ __('special_intervention.print_edited_on') }}<br>{{ $printDate }}
                        </div>
                    </div>

                    <table class="fiche-table fiche-table-dark">
                        <thead><tr><th colspan="2">{{ __('special_intervention.print_applicant') }}</th></tr></thead>
                        <tbody>
                            <tr><th>{{ __('special_intervention.nom_prenom') }}</th><td>{{ $specialIntervention->nom_prenom ?? __('special_intervention.print_missing') }}</td></tr>
                            <tr><th>{{ __('special_intervention.cin') }}</th><td dir="ltr">{{ $specialIntervention->cin ?? __('special_intervention.print_missing') }}</td></tr>
                            <tr><th>{{ __('special_intervention.phone') }}</th><td dir="ltr">{{ $specialIntervention->phone ?? __('special_intervention.print_missing') }}</td></tr>
                            <tr><th>{{ __('special_intervention.code_structure') }} / {{ __('special_intervention.delegation') }}</th>
                                <td>{{ $specialIntervention->governorate?->name ?? '—' }} — {{ $specialIntervention->delegation?->name ?? '—' }}</td></tr>
                        </tbody>
                    </table>

                    <table class="fiche-table">
                        <thead><tr><th colspan="2">{{ __('special_intervention.intervention_section') }}</th></tr></thead>
                        <tbody>
                            <tr><th>{{ __('special_intervention.intervention_date') }}</th>
                                <td dir="ltr">{{ $specialIntervention->intervention_date?->format('d/m/Y') ?? '—' }}@if ($specialIntervention->intervention_time) {{ $specialIntervention->intervention_time->format('H:i') }}@endif</td></tr>
                            @foreach ($selectedInterventions as $item)
                            <tr>
                                <th>{{ app()->getLocale() === 'ar' ? ($item['name_ar'] ?? $item['name_fr'] ?? $item['key']) : ($item['name_fr'] ?? $item['name_ar'] ?? $item['key']) }}</th>
                                <td class="fiche-dim">{{ $item['hours'] ?? 1 }} h × {{ number_format($item['price_tnd'] ?? 0, 0) }} {{ __('special_intervention.currency_tnd') }} = {{ number_format($item['amount'] ?? 0, 0) }} {{ __('special_intervention.currency_tnd') }}</td>
                            </tr>
                            @endforeach
                            <tr><th class="font-bold">{{ __('special_intervention.total_amount') }}</th><td class="font-bold" dir="ltr">{{ number_format($totalAmount, 0) }} {{ __('special_intervention.currency_tnd') }}</td></tr>
                        </tbody>
                    </table>

                    <div class="fiche-cert">
                        <div class="fiche-cert-title">{{ __('special_intervention.fees_title') }}</div>
                        {{ __('special_intervention.billing_note') }}
                        <br>{{ __('special_intervention.diving_team_note') }}
                        <br>{{ __('special_intervention.payment_proof') }}
                    </div>

                    <div class="fiche-sign-row">
                        <div class="fiche-sign-col">
                            <div class="fiche-sign-name">{{ __('special_intervention.print_sign_applicant') }}</div>
                            <div class="fiche-sign-line">{{ __('special_intervention.print_read_and_agreed') }}</div>
                        </div>
                        <div class="fiche-sign-col">
                            <div class="fiche-sign-name">{{ __('special_intervention.print_sign_office') }}</div>
                            <div class="fiche-sign-line">{{ __('special_intervention.print_stamp') }}</div>
                        </div>
                    </div>

                    <div class="fiche-foot">
                        <span>{{ __('special_intervention.review_note') }}</span>
                        <span dir="ltr">{{ $viewRef }}</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</x-app-layout>
