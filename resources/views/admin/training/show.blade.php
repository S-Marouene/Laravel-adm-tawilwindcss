@extends('admin.layouts.admin')

@section('title', __('training.card_title') . ' — ' . ($trainingRequest->reference ?: 'TRN-####'))

@php
    $tr      = $trainingRequest;
    $status  = $tr->status;
    $viewRef = $tr->reference ?: \App\Support\Reference::provisional('training');

    // ── Audit proof events ──
    $advancedEvent  = $tr->events->last(fn ($e) => $e->type === 'advanced_to_consultation');
    $approvedEvent  = $tr->events->last(fn ($e) => $e->type === 'approved');
    $paidEvent      = $tr->events->last(fn ($e) => $e->type === 'payment_recorded');
    $scheduledEvent = $tr->events->last(fn ($e) => $e->type === 'scheduled');
    $startedEvent   = $tr->events->last(fn ($e) => $e->type === 'started');
    $resultEvent    = $tr->events->last(fn ($e) => $e->type === 'result_recorded');

    // ── 6-step stepper ──
    $totalSteps = 6;
    $currentStep = match ($status) {
        'draft'              => 1,
        'completed'          => 2,
        'under_consultation' => 2,
        'needs_correction'   => 2,
        'payment_pending'    => 3,
        'payment_confirmed'  => 4,
        'scheduled'          => 5,
        'in_progress'        => 5,
        'passed'             => 6,
        'failed'             => 6,
        'served'             => 7,
        'cancelled'          => match (true) {
            $resultEvent    !== null => 6,
            $startedEvent   !== null => 5,
            $scheduledEvent !== null => 4,
            $paidEvent      !== null => 3,
            $approvedEvent  !== null => 3,
            $advancedEvent  !== null
                || $tr->events->contains(fn ($e) => $e->type === 'correction_requested') => 2,
            default => 2,
        },
        default => null,
    };
    $isCancelled   = $status === 'cancelled';
    $cancelledAt   = $isCancelled ? min($currentStep, $totalSteps) : null;
    $hadCorrection = $tr->events->contains(fn ($e) => $e->type === 'correction_requested');

    $stepDates = [];
    $stepDates[1] = $tr->created_at;
    $stepDates[2] = $advancedEvent?->created_at;
    $stepDates[3] = $approvedEvent?->created_at;
    $stepDates[4] = $paidEvent?->created_at;
    $stepDates[5] = $scheduledEvent?->created_at;
    $stepDates[6] = $resultEvent?->created_at;

    $steps = [];
    foreach (range(1, $totalSteps) as $i) {
        if ($isCancelled) {
            if ($i < $cancelledAt)       $state = 'done';
            elseif ($i === $cancelledAt) $state = 'cancelled';
            else                         $state = 'pending';
        } else {
            if ($currentStep === 7 || $i < $currentStep) $state = 'done';
            elseif ($i === $currentStep)                 $state = $status === 'needs_correction' ? 'error' : 'current';
            else                                         $state = 'pending';
        }
        if ($state === 'done' && $i === 2 && $hadCorrection) $state = 'warning';

        $sub = match (true) {
            $state === 'cancelled'            => __('training.stepper_cancelled'),
            $state === 'warning' && $i === 2  => __('training.stepper_had_correction'),
            $status === 'needs_correction' && $state === 'error' => __('training.stepper_correction'),
            $state === 'done'                 => __('training.stepper_done'),
            $state === 'pending'              => __('training.stepper_pending'),
            $i === 2 && $status === 'completed'          => __('training.stepper_verify_pending'),
            $i === 2 && $status === 'under_consultation' => __('training.stepper_verify_waiting'),
            $i === 3 && $status === 'payment_pending'    => __('training.stepper_pending'),
            $i === 4 && $status === 'payment_confirmed'  => __('training.stepper_pending'),
            $i === 5 && $status === 'in_progress'        => __('training.stepper_current'),
            $i === 6 && in_array($status, ['passed','failed']) => __('training.stepper_current'),
            default => __('training.stepper_current'),
        };
        $alert = in_array($state, ['error', 'cancelled']);
        $steps[] = [
            'num'   => $i,
            'title' => __('training.stepper_' . $i),
            'sub'   => $sub,
            'state' => $state,
            'alert' => $alert,
        ];
    }
    $doneSteps = collect($steps)->whereIn('state', ['done', 'warning'])->count();
    $progress  = (int) round($doneSteps / $totalSteps * 100);

    // ── Status pill ──
    [$statusPillCls, $statusPillIcon] = match ($status) {
        'draft', 'completed'    => ['bg-surface-container-high text-secondary', 'edit_note'],
        'under_consultation'    => ['bg-blue-100 text-blue-800', 'manage_search'],
        'needs_correction'      => ['bg-red-100 text-red-800', 'error'],
        'payment_pending'       => ['bg-orange-100 text-orange-900', 'payments'],
        'payment_confirmed'     => ['bg-blue-100 text-blue-800', 'task_alt'],
        'scheduled'             => ['bg-indigo-100 text-indigo-800', 'calendar_today'],
        'in_progress'           => ['bg-sky-100 text-sky-800', 'play_circle'],
        'passed'                => ['bg-emerald-100 text-emerald-800', 'workspace_premium'],
        'failed'                => ['bg-red-100 text-red-800', 'cancel'],
        'served'                => ['bg-teal-100 text-teal-800', 'handshake'],
        'cancelled'             => ['bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300', 'cancel'],
        default                 => ['bg-surface-container-high text-secondary', 'info'],
    };

    // ── Hero alert ──
    $heroAlert = match (true) {
        $status === 'cancelled'                           => 'error',
        $status === 'draft'                               => 'draft',
        $status === 'needs_correction'                    => 'error',
        in_array($status, ['completed','under_consultation','payment_pending','payment_confirmed','scheduled','in_progress']) => 'info',
        in_array($status, ['passed','served'])            => 'neutral',
        $status === 'failed'                              => 'error',
        default                                           => 'neutral',
    };

    // ── Documents completeness (static per-participant requirements) ──
    $documentRequirements = collect($documentGroups)
        ->flatMap(fn (array $group) => $group['documents'])
        ->values()
        ->all();
    $activeDocuments = $tr->documents->filter(fn ($d) => $d->status !== 'archived');
    $documentsByKey  = $activeDocuments->groupBy('document_key');
    $archivedDocuments = $tr->documents
        ->filter(fn ($d) => $d->status === 'archived')
        ->sortBy('created_at');
    $totalDocTypes   = count($documentRequirements);
    $uploadedTypes   = collect($documentRequirements)
        ->filter(fn ($req) => $documentsByKey->get($req['key'], collect())->isNotEmpty())
        ->count();
    $fmtSize         = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1) . ' Mo' : number_format($b / 1024, 0) . ' Ko';
    $isDocComplete   = $totalDocTypes > 0 && $uploadedTypes >= $totalDocTypes;
    $docPct          = $totalDocTypes > 0 ? (int) round(min($uploadedTypes, $totalDocTypes) / $totalDocTypes * 100) : 0;
    $correction      = $tr->lastCorrectionData();

    // ── Timeline chips ──
    $eventChip = fn ($type) => match (true) {
        in_array($type, ['submitted', 'resubmitted'])
            => ['bg-secondary-container text-on-secondary-container', 'post_add', __('training.chip_depot'), 'bg-surface-dim text-secondary'],
        $type === 'advanced_to_consultation'
            => ['bg-violet-600 text-white', 'sync_alt', __('training.chip_phase'), 'bg-surface-dim text-secondary'],
        $type === 'correction_requested'
            => ['bg-amber-500 text-white', 'edit_note', __('training.chip_correction'), 'bg-amber-100 text-amber-800'],
        $type === 'approved'
            => ['bg-blue-600 text-white', 'task_alt', __('training.chip_phase'), 'bg-blue-100 text-blue-800'],
        $type === 'payment_recorded'
            => ['bg-orange-500 text-white', 'payments', __('training.chip_payment'), 'bg-orange-100 text-orange-800'],
        $type === 'scheduled'
            => ['bg-indigo-600 text-white', 'calendar_today', __('training.chip_schedule'), 'bg-indigo-100 text-indigo-800'],
        $type === 'started'
            => ['bg-sky-600 text-white', 'play_circle', __('training.chip_session'), 'bg-sky-100 text-sky-800'],
        $type === 'attendance_recorded'
            => ['bg-sky-500 text-white', 'how_to_reg', __('training.chip_session'), 'bg-sky-100 text-sky-800'],
        $type === 'result_recorded'
            => ['bg-purple-600 text-white', 'grading', __('training.chip_result'), 'bg-purple-100 text-purple-800'],
        in_array($type, ['certificate_issued','certificate_collected','certificate_post_sent','certificate_post_delivered'])
            => ['bg-teal-600 text-white', 'workspace_premium', __('training.chip_certificate'), 'bg-teal-100 text-teal-800'],
        $type === 'cancelled'
            => ['bg-red-600 text-white', 'cancel', __('training.chip_alert'), 'bg-red-100 text-red-700'],
        default
            => ['bg-gray-500 text-white', 'info', __('training.chip_alert'), 'bg-surface-dim text-secondary'],
    };

    // ── Admin action flags ──
    $canAdvance       = in_array($status, ['completed', 'needs_correction']);
    $canCorrect       = in_array($status, ['completed', 'under_consultation', 'needs_correction']);
    $canApprove       = $status === 'under_consultation';
    $canCancel        = in_array($status, ['completed', 'under_consultation', 'needs_correction', 'payment_pending', 'payment_confirmed', 'scheduled']);
    $canRecordPayment = in_array($status, ['payment_pending', 'payment_confirmed', 'scheduled']);
    $canSchedule      = $status === 'payment_confirmed';
    $canStart         = $status === 'scheduled';
    $canAttendance    = $status === 'in_progress';
    $canResult        = $status === 'in_progress';
    $canIssueCert     = $status === 'passed' && ! $tr->hasCertificate();
    $canUpdateCert    = $tr->hasCertificate() && ! in_array($tr->cert_status, ['collected', 'post_delivered']);
    $canRevokeCert    = $tr->hasCertificate() && ! in_array($tr->cert_status, ['collected', 'post_delivered']);
    $hasActionBand    = $canAdvance || $canCorrect || $canApprove || $canRecordPayment || $canStart || $canAttendance || $canResult || $canIssueCert;

    $fixableFields = [
        'cin'            => ['label' => __('training.cin'),            'icon' => 'badge'],
        'nom_prenom'     => ['label' => __('training.nom_prenom'),     'icon' => 'person'],
        'phone'          => ['label' => __('training.phone'),          'icon' => 'call'],
        'governorate_id' => ['label' => __('training.code_structure'), 'icon' => 'public'],
        'delegation_id'  => ['label' => __('training.delegation'),     'icon' => 'account_balance'],
        'training_type'  => ['label' => __('training.training_type'),  'icon' => 'school'],
        'training_kind'  => ['label' => __('training.training_kind'),  'icon' => 'sync'],
        'notes'          => ['label' => __('training.notes'),          'icon' => 'edit_note'],
    ];
    $correctionDocs = ! empty($correction['docs_to_fix'])
        ? collect($documentRequirements)->filter(fn ($req) => in_array($req['key'], $correction['docs_to_fix'], true))
        : collect();

    $printDate = now()->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY, HH:mm');
@endphp

<style>
    .print-sheet { display: none; }
    @media print {
        @page { size: A4; margin: 11mm 11mm 14mm; }
        header, footer, aside, .no-print { display: none !important; }
        html, body { background: #ffffff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .print-sheet { display: block !important; }
    }
    .fiche { font-family: var(--font-sans,'Roboto',Arial,sans-serif); font-size: 11.5px; line-height: 1.5; color: #1a1d29; background: #ffffff; }
    .fiche-band { background: #9e001f; color: #ffffff; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; gap: 16px; border-radius: 3px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .fiche-band-logo { width: 46px; height: 46px; object-fit: contain; border-radius: 4px; background: #fff; padding: 3px; }
    .fiche-band-org { font-size: 14.5px; font-weight: 800; letter-spacing: .02em; line-height: 1.25; }
    .fiche-band-sub { font-size: 10px; opacity: .92; font-weight: 400; }
    .fiche-band-ref { text-align: end; line-height: 1.3; }
    .fiche-band-ref-label { font-size: 9px; text-transform: uppercase; letter-spacing: .14em; opacity: .85; font-weight: 600; }
    .fiche-band-ref-value { font-family: 'Roboto Mono',Menlo,Consolas,monospace; font-size: 15px; font-weight: 700; letter-spacing: .03em; }
    .fiche-title-row { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin: 14px 0 16px; padding-bottom: 8px; border-bottom: 2px solid #1a1d29; }
    .fiche-title { font-size: 15px; font-weight: 800; letter-spacing: .01em; }
    .fiche-title-ar { font-size: 13px; font-weight: 700; }
    .fiche-date { font-size: 9.5px; color: #6b7280; text-align: end; line-height: 1.4; }
    .fiche-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .fiche-table th, .fiche-table td { border: 1px solid #c7cad1; padding: 6px 9px; text-align: start; vertical-align: top; font-size: 11px; }
    .fiche-table th { background: #f4f4f7; font-weight: 700; width: 34%; }
    .fiche-table-dark thead th { background: #1a1d29; color: #fff; }
    .fiche-dim { color: #6b7280; }
    .fiche-missing { color: #9ca3af; font-style: italic; }
    .fiche-cert { border: 1px solid #c7cad1; border-inline-start: 4px solid #0f766e; padding: 8px 12px; margin-bottom: 12px; font-size: 11px; }
    .fiche-cert-title { font-weight: 800; text-transform: uppercase; letter-spacing: .06em; font-size: 10px; margin-bottom: 3px; }
    .fiche-sign-row { display: flex; gap: 28px; margin: 6px 0 10px; align-items: flex-end; }
    .fiche-sign-col { flex: 1; text-align: center; }
    .fiche-sign-name { font-size: 10.5px; font-weight: 700; margin-bottom: 46px; text-transform: uppercase; letter-spacing: .04em; }
    .fiche-sign-line { border-top: 1px solid #6b7280; margin: 0 8px; padding-top: 3px; font-size: 9px; color: #6b7280; }
    .fiche-foot { margin-top: 10px; padding-top: 7px; border-top: 1px solid #c7cad1; display: flex; justify-content: space-between; gap: 12px; font-size: 9px; color: #6b7280; }
</style>

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- ══════════════ CONTENU ÉCRAN ══════════════ --}}
    <div class="dossier-screen no-print space-y-6">

        {{-- ── Barre d'outils ── --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('admin.training.index') }}"
                   class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-3 rounded transition-colors">
                    <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                    {{ __('training.admin_back_to_list') }}
                </a>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-secondary">{{ __('training.card_title') }}</p>
                    <p class="font-mono text-sm font-bold text-on-surface truncate" dir="ltr">{{ $viewRef }}</p>
                </div>
            </div>
            <button type="button" onclick="window.print()" title="{{ __('training.print_title') }}"
                    class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded transition-colors shadow-sm">
                <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
            </button>
        </div>

        {{-- ── Erreurs ── --}}
        @if ($errors->any())
            <div class="bg-surface-container-lowest border border-red-200 rounded-lg text-body-sm text-red-800 px-4 py-3 shadow-sm flex items-start gap-2.5 border-s-4 border-s-red-600" role="alert">
                <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">error</span>
                <div class="space-y-0.5">
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════════════
             BANDES D'ACTION ADMINISTRATIVES — contextuelles, en tête de page.
             Chaque bande correspond au PROCHAIN ACTE POSSIBLE du processus :
             étude → paiement → planification → formation → résultat → certificat.
        ══════════════════════════════════════════════════════════════════════════ --}}
        @can('manage training')

            {{-- ── ACTE 1 : Étude — avancer / approuver / correction ── --}}
            @if ($canAdvance || $canCorrect || $canApprove)
            <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden" x-data="{ fixOpen: false }">
                <div class="border-s-4 border-s-primary p-5">
                    <div class="flex flex-wrap items-start gap-3.5 mb-4">
                        <div class="w-10 h-10 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">fact_check</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.admin_actions_title') }}</h2>
                                <span class="text-[10px] bg-primary text-on-primary font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('training.admin_actions_subtitle') }}</span>
                            </div>
                            <p class="text-body-sm text-secondary mt-1">
                                @if ($status === 'under_consultation')
                                    {{ __('training.under_consultation_banner') }}
                                @elseif ($status === 'needs_correction')
                                    {{ __('training.needs_correction_banner') }}
                                @else
                                    {{ __('training.under_review_banner') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        @if ($canAdvance)
                            <form method="POST" action="{{ route('admin.training.advance', $tr) }}"
                                  data-confirm data-confirm-style="info">
                                @csrf
                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-3 px-6 rounded transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_circle_right</span>
                                    {{ __('training.admin_advance_btn') }}
                                </button>
                            </form>
                        @endif

                        @if ($canApprove)
                            <form method="POST" action="{{ route('admin.training.approve', $tr) }}"
                                  data-confirm data-confirm-style="success">
                                @csrf
                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-3 px-6 rounded transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">task_alt</span>
                                    {{ __('training.admin_approve_btn') }}
                                </button>
                            </form>
                        @endif

                        @if ($canCorrect)
                            <button type="button" @click="fixOpen = !fixOpen"
                                class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">edit_note</span>
                                <span x-text="fixOpen ? '{{ __('training.admin_hide_note') }}' : '{{ __('training.admin_correction_btn') }}'">{{ __('training.admin_correction_btn') }}</span>
                            </button>
                        @endif
                    </div>

                    @if ($canCorrect)
                    <div x-show="fixOpen" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mt-4 rounded-lg border border-amber-200 bg-amber-50/60 dark:border-amber-800/40 dark:bg-amber-900/10 p-4">
                        <form method="POST" action="{{ route('admin.training.correction', $tr) }}">
                            @csrf
                            <div class="mb-3 flex items-start gap-3">
                                <span class="grid h-9 w-9 flex-none place-items-center rounded-lg bg-amber-500/15 text-amber-700" aria-hidden="true">
                                    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">edit_note</span>
                                </span>
                                <div>
                                    <p class="text-xs font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">{{ __('training.admin_correction_btn') }}</p>
                                    <p class="text-[11px] text-secondary mt-0.5">{{ __('training.admin_correction_hint') }}</p>
                                </div>
                            </div>

                            <label class="block text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300 mb-1.5">
                                {{ __('training.admin_note_to_applicant') }} <span class="text-red-500">*</span>
                            </label>
                            <x-admin.bilingual-note name="admin_note" :rows="3" required
                                placeholder-key="training.note_fix_placeholder" />

                            <p class="mt-4 mb-2 flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">fact_check</span>
                                {{ __('training.section_training') }}
                            </p>
                            <div class="grid gap-1.5 sm:grid-cols-2">
                                @foreach ($fixableFields as $fieldKey => $fieldDef)
                                <label class="group flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-lowest px-3 py-2 transition
                                              hover:border-amber-400/70 hover:bg-surface-container
                                              has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/80 has-[:checked]:shadow-sm
                                              dark:border-gray-600 dark:bg-gray-800/50
                                              dark:has-[:checked]:border-amber-500/70 dark:has-[:checked]:bg-amber-900/20">
                                    <input type="checkbox" name="fields_to_fix[]" value="{{ $fieldKey }}"
                                           class="h-4 w-4 shrink-0 cursor-pointer rounded border-surface-dim text-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                           {{ in_array($fieldKey, old('fields_to_fix', [])) ? 'checked' : '' }}>
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-surface-container text-secondary transition group-has-[:checked]:bg-amber-100 group-has-[:checked]:text-amber-700 dark:bg-gray-700 dark:text-gray-300" aria-hidden="true">
                                        <span class="material-symbols-outlined text-sm">{{ $fieldDef['icon'] }}</span>
                                    </span>
                                    <span class="flex-1 text-xs font-semibold text-on-surface dark:text-gray-200">{{ $fieldDef['label'] }}</span>
                                </label>
                                @endforeach
                            </div>

                            @if (! empty($documentGroups))
                            <p class="mt-4 mb-2 flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">folder_open</span>
                                {{ __('training.documents_title') }}
                            </p>
                            <div class="space-y-2">
                                @foreach ($documentGroups as $group)
                                    <div class="rounded-lg border border-amber-200/70 p-2.5">
                                        <p class="mb-1.5 text-[10px] font-extrabold uppercase tracking-wide text-amber-900">{{ $group['name'] ?: __('training.participants_section') }}</p>
                                        <div class="grid gap-1.5">
                                            @foreach ($group['documents'] as $requirement)
                                            <label class="group flex min-h-10 cursor-pointer items-center gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-lowest px-3 py-1.5 transition hover:border-amber-400/70 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/80 dark:border-gray-600 dark:bg-gray-800/50">
                                                <input type="checkbox" name="docs_to_fix[]" value="{{ $requirement['key'] }}"
                                                       class="h-4 w-4 shrink-0 cursor-pointer rounded border-surface-dim text-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                                       {{ in_array($requirement['key'], old('docs_to_fix', [])) ? 'checked' : '' }}>
                                                <span class="flex-1 text-xs font-semibold text-on-surface dark:text-gray-200">{{ $requirement['name'] }}</span>
                                                @if ($documentsByKey->get($requirement['key'], collect())->isNotEmpty())
                                                    <span class="text-[10px] rounded-full bg-blue-100 dark:bg-blue-900/30 px-1.5 py-0.5 text-blue-700 dark:text-blue-300 font-bold whitespace-nowrap">{{ __('training.documents_title') }}</span>
                                                @else
                                                    <span class="text-[10px] rounded-full bg-red-100 dark:bg-red-900/30 px-1.5 py-0.5 text-red-600 dark:text-red-400 font-bold whitespace-nowrap">{{ __('training.documents_not_uploaded') }}</span>
                                                @endif
                                            </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @endif

                            <button type="submit" class="mt-4 w-full inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-2.5 px-5 rounded-lg transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">send</span>
                                {{ __('training.admin_send_correction') }}
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </section>
            @endif

            {{-- ── ACTE 2 : Paiement puis planification ── --}}
            @if ($canRecordPayment)
            <section class="bg-surface-container-lowest border border-orange-200 dark:border-orange-800/40 rounded-lg shadow-sm overflow-hidden">
                <div class="border-s-4 border-s-orange-500 p-5">
                    <div class="flex flex-wrap items-start gap-3.5 mb-4">
                        <div class="w-10 h-10 rounded bg-orange-100 text-orange-700 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">payments</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.payment_title') }}</h2>
                                <span class="text-[10px] bg-orange-500 text-white font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('training.status_' . $status) }}</span>
                            </div>
                            <p class="text-body-sm text-secondary mt-1">{{ $status === 'payment_pending' ? __('training.payment_pending_banner') : __('training.payment_confirmed_banner') }}</p>
                        </div>
                    </div>

                    @php $estFee = $tr->fee_amount ?? $tr->computedFee(); @endphp
                    @if (! $tr->isPaid() && $estFee > 0)
                    <div class="mb-4 rounded-lg border border-orange-200 bg-orange-50/60 px-4 py-2.5 flex items-center gap-3">
                        <span class="material-symbols-outlined text-orange-600 text-lg" aria-hidden="true">toll</span>
                        <div>
                            <p class="text-[10px] font-bold text-orange-800 uppercase tracking-wider">{{ __('training.estimated_fee') }}</p>
                            <p class="text-sm font-black text-orange-700" dir="ltr">{{ number_format($estFee, 3) }} {{ __('training.fee_tnd') }}</p>
                        </div>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('admin.training.payment', $tr) }}" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.fee_amount') }} <span class="text-red-500">*</span></label>
                                <input type="number" name="fee_amount" step="0.001" min="0" value="{{ old('fee_amount', $tr->fee_amount ?? $tr->computedFee()) }}"
                                       class="w-full text-sm" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.receipt_number') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="receipt_number" value="{{ old('receipt_number', $tr->receipt_number) }}"
                                       class="w-full text-sm" required>
                            </div>
                        </div>
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">save</span>
                            {{ __('training.admin_payment_btn') }}
                        </button>
                    </form>

                    @if ($canSchedule)
                    <div class="mt-5 border-t border-orange-200/70 pt-5">
                        <div class="mb-3 flex items-start gap-3">
                            <span class="grid h-9 w-9 flex-none place-items-center rounded-lg bg-indigo-500/15 text-indigo-700" aria-hidden="true">
                                <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">calendar_today</span>
                            </span>
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-wider text-indigo-900 dark:text-indigo-300">{{ __('training.admin_schedule_btn') }}</p>
                                <p class="text-[11px] text-secondary mt-0.5">{{ __('training.scheduled_banner') }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.training.schedule', $tr) }}" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.training_start_date') }} <span class="text-red-500">*</span></label>
                                    <input type="date" name="training_start_date" value="{{ old('training_start_date') }}" min="{{ now()->format('Y-m-d') }}"
                                           class="w-full text-sm" required>
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.training_start_time') }}</label>
                                    <input type="time" name="training_start_time" value="{{ old('training_start_time') }}" class="w-full text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.training_duration_days') }} <span class="text-red-500">*</span></label>
                                    <input type="number" name="training_duration_days" min="1" max="30"
                                           value="{{ old('training_duration_days', \App\Models\TrainingRequest::durationWeeks($tr->training_type ?? '') * 5) }}"
                                           class="w-full text-sm" required>
                                </div>
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">save</span>
                                {{ __('training.admin_schedule_btn') }}
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </section>
            @endif

            {{-- ── ACTE 3 : Démarrer la formation ── --}}
            @if ($canStart)
            <section class="bg-surface-container-lowest border border-sky-200 dark:border-sky-800/40 rounded-lg shadow-sm overflow-hidden">
                <div class="border-s-4 border-s-sky-500 p-5">
                    <div class="flex flex-wrap items-start gap-3.5 mb-4">
                        <div class="w-10 h-10 rounded bg-sky-100 text-sky-700 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">play_circle</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.status_scheduled') }}</h2>
                                <span class="text-[10px] bg-sky-600 text-white font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('training.admin_actions_subtitle') }}</span>
                            </div>
                            <p class="text-body-sm text-secondary mt-1">{{ __('training.scheduled_banner') }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.training.start', $tr) }}" data-confirm data-confirm-style="success">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold py-3 px-6 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">play_circle</span>
                            {{ __('training.admin_start_btn') }}
                        </button>
                    </form>
                </div>
            </section>
            @endif

            {{-- ── ACTE 4 : Formation en cours — présence + résultat ── --}}
            @if ($canAttendance || $canResult)
            <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                <div class="border-s-4 border-s-sky-500 p-5">
                    <div class="flex flex-wrap items-start gap-3.5 mb-4">
                        <div class="w-10 h-10 rounded bg-sky-100 text-sky-700 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">how_to_reg</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.status_in_progress') }}</h2>
                                <span class="text-[10px] bg-sky-600 text-white font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('training.admin_actions_subtitle') }}</span>
                            </div>
                            <p class="text-body-sm text-secondary mt-1">{{ __('training.in_progress_banner') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        @if ($canAttendance)
                        <form method="POST" action="{{ route('admin.training.attendance', $tr) }}" class="rounded-lg border border-sky-200 bg-sky-50/60 dark:border-sky-800/40 dark:bg-sky-900/10 p-4 space-y-3">
                            @csrf
                            <p class="text-xs font-extrabold uppercase tracking-wider text-sky-900 dark:text-sky-300">{{ __('training.admin_attendance_btn') }}</p>
                            <div>
                                <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.session_date') }} <span class="text-red-500">*</span></label>
                                <input type="date" name="session_date" value="{{ old('session_date', now()->format('Y-m-d')) }}" class="w-full text-sm" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.attendance') }} <span class="text-red-500">*</span></label>
                                <select name="attendance" class="w-full text-sm" required>
                                    @foreach (['present','absent','excused'] as $att)
                                        <option value="{{ $att }}">{{ __('training.attendance_' . $att) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold py-2.5 px-4 rounded transition-colors">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">save</span>
                                {{ __('training.admin_attendance_btn') }}
                            </button>
                        </form>
                        @endif

                        @if ($canResult)
                        <form method="POST" action="{{ route('admin.training.result', $tr) }}" class="rounded-lg border border-purple-200 bg-purple-50/60 dark:border-purple-800/40 dark:bg-purple-900/10 p-4 space-y-3">
                            @csrf
                            <p class="text-xs font-extrabold uppercase tracking-wider text-purple-900 dark:text-purple-300">{{ __('training.admin_result_btn') }}</p>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach (['passed','failed'] as $res)
                                    <label class="flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-3 py-2 cursor-pointer has-[:checked]:border-purple-500/60 has-[:checked]:bg-purple-50">
                                        <input type="radio" name="result" value="{{ $res }}" @checked($res === 'passed') class="h-4 w-4 text-purple-600">
                                        <span class="text-xs font-semibold">{{ __('training.result_' . $res) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.result_note') }}</label>
                                <x-admin.bilingual-note name="result_note" :rows="2"
                                    placeholder-key="training.note_fix_placeholder" />
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2.5 px-4 rounded transition-colors">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">grading</span>
                                {{ __('training.admin_result_btn') }}
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </section>
            @endif

            {{-- ── ACTE 5 : Émettre le certificat ── --}}
            @if ($canIssueCert)
            <section class="bg-surface-container-lowest border border-teal-200 dark:border-teal-800/40 rounded-lg shadow-sm overflow-hidden">
                <div class="border-s-4 border-s-teal-600 p-5">
                    <div class="flex flex-wrap items-start gap-3.5 mb-4">
                        <div class="w-10 h-10 rounded bg-teal-100 text-teal-700 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">workspace_premium</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.admin_issue_cert_btn') }}</h2>
                                <span class="text-[10px] bg-teal-600 text-white font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('training.status_passed') }}</span>
                            </div>
                            <p class="text-body-sm text-secondary mt-1">{{ __('training.passed_banner') }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.training.certificate.store', $tr) }}" class="space-y-3" x-data="{ method: '{{ old('delivery_method', 'pickup') }}' }">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['pickup' => 'delivery_method_pickup', 'post' => 'delivery_method_post'] as $m => $lk)
                                <label class="flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-3 py-2 cursor-pointer has-[:checked]:border-teal-500/60 has-[:checked]:bg-teal-50">
                                    <input type="radio" name="delivery_method" value="{{ $m }}" @checked(old('delivery_method', 'pickup') === $m) @click="method = '{{ $m }}'" class="h-4 w-4 text-teal-600">
                                    <span class="text-xs font-semibold">{{ __('training.' . $lk) }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div x-show="method === 'pickup'" class="space-y-2">
                            <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.code_structure') }} <span class="text-red-500">*</span></label>
                            <select name="pickup_governorate_id" :required="method === 'pickup'" class="w-full text-sm">
                                <option value="">{{ __('training.code_structure_select') }}</option>
                                @foreach ($governorates as $gov)
                                    <option value="{{ $gov->id }}" @selected(old('pickup_governorate_id', $tr->pickup_governorate_id) == $gov->id)>{{ $gov->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="method === 'post'" x-cloak class="space-y-2">
                            <input type="text" name="post_recipient" value="{{ old('post_recipient') }}" placeholder="{{ __('training.nom_prenom') }}" class="w-full text-sm">
                            <input type="text" name="post_phone" value="{{ old('post_phone') }}" placeholder="{{ __('training.phone') }}" class="w-full text-sm">
                            <textarea name="post_address" rows="2" placeholder="{{ __('training.notes') }}" class="w-full text-sm">{{ old('post_address') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.valid_until') }}</label>
                                <input type="date" name="valid_until" value="{{ old('valid_until') }}" min="{{ now()->format('Y-m-d') }}" class="w-full text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.certificate_number') }}</label>
                                <input type="text" name="certificate_number" value="{{ old('certificate_number') }}" class="w-full text-sm" placeholder="{{ __('training.certificate_number') }}">
                            </div>
                        </div>

                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">workspace_premium</span>
                            {{ __('training.admin_issue_cert_btn') }}
                        </button>
                    </form>
                </div>
            </section>
            @endif

        @endcan

        {{-- ── Correction demandée — rappel de la note envoyée ── --}}
        @if ($status === 'needs_correction')
        <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden">
            <div class="border-s-4 border-s-red-600 p-5">
                <div class="flex items-start gap-3.5 mb-3">
                    <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">assignment_late</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.correction_title') }}</h2>
                            <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded">{{ __('training.chip_alert') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('training.admin_correction_hint') }}</p>
                    </div>
                </div>

                @if ($tr->admin_note)
                <div class="rounded border border-red-200 bg-red-50 dark:bg-red-900/10 px-4 py-3">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                        {{ __('training.admin_note_label') }}
                    </p>
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $tr->admin_note }}</p>
                </div>
                @endif

                @if (! empty($correction['fields_to_fix']) || $correctionDocs->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($correction['fields_to_fix'] ?? [] as $fk)
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700/40">
                            <span class="material-symbols-outlined text-[11px]" aria-hidden="true">edit</span>
                            {{ $fixableFields[$fk]['label'] ?? $fk }}
                        </span>
                    @endforeach
                    @foreach ($correctionDocs as $docRequirement)
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-700/40">
                            <span class="material-symbols-outlined text-[11px]" aria-hidden="true">upload_file</span>
                            {{ $docRequirement['name'] }}
                        </span>
                    @endforeach
                </div>
                @endif
            </div>
        </section>
        @endif

        {{-- ── Non validé — bandeau ── --}}
        @if ($status === 'failed')
        <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden">
            <div class="border-s-4 border-s-red-600 p-5">
                <div class="flex items-start gap-3.5 mb-2">
                    <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">cancel</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.result_failed') }}</h2>
                            <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded">{{ __('training.chip_alert') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-2 leading-relaxed">{{ __('training.failed_banner') }}</p>
                    </div>
                </div>
                @if ($tr->result_note)
                <div class="mt-3 rounded border border-red-200 bg-red-50 dark:bg-red-900/10 px-4 py-3">
                    <p class="text-body-sm text-on-surface whitespace-pre-line">{{ $tr->result_note }}</p>
                </div>
                @endif
            </div>
        </section>
        @endif

        {{-- ════════ LIFECYCLE STEPPER ════════ --}}
        <section class="bg-surface-container-lowest border {{ $isCancelled ? 'border-red-200 dark:border-red-800/40' : 'border-surface-dim' }} rounded-lg shadow-sm overflow-hidden">
            @if ($isCancelled)
            <div class="border-b border-red-200 dark:border-red-800/40 bg-red-50 dark:bg-red-900/20 border-s-4 border-s-red-600 p-5 md:px-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">cancel</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.cancelled_banner') }}</h3>
                        @if ($tr->admin_note)<p class="text-body-sm text-secondary mt-1 whitespace-pre-line">{{ $tr->admin_note }}</p>@endif
                    </div>
                </div>
            </div>
            @endif

            <div class="p-5 md:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-surface-dim">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">route</span>
                        {{ __('training.stepper_title') }}
                    </h3>
                    <div class="flex items-center gap-3 flex-1 sm:flex-none sm:min-w-72">
                        @if ($isCancelled)
                            <span class="text-xs font-bold text-white bg-red-600 px-2 py-0.5 rounded whitespace-nowrap">{{ __('training.stepper_cancelled') }}</span>
                        @else
                            <span class="text-xs font-bold text-primary bg-primary-fixed/40 px-2 py-0.5 rounded whitespace-nowrap">
                                {{ __('training.phase_instruction', ['step' => min($currentStep ?? 1, $totalSteps)]) }}
                            </span>
                        @endif
                        <div class="hidden sm:flex-1 h-1.5 bg-surface-dim/50 rounded-full overflow-hidden">
                            <div class="h-full {{ $isCancelled ? 'bg-red-500' : 'bg-gradient-to-r from-primary-container to-amber-500' }} rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                        </div>
                        <span class="text-[11px] font-mono font-bold text-secondary hidden sm:inline" dir="ltr">{{ $progress }}%</span>
                    </div>
                </div>

                <div class="relative grid grid-cols-2 md:grid-cols-6 gap-5 md:gap-3">
                    <div class="hidden md:block absolute top-5 [inset-inline-start:8%] [inset-inline-end:8%] h-0.5 bg-surface-dim" aria-hidden="true"></div>

                    @foreach ($steps as $step)
                    <div class="relative flex md:flex-col items-start md:items-center gap-3 md:gap-2 md:text-center">
                        @if ($step['state'] === 'done' || $step['state'] === 'warning')
                            <span class="relative z-10 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-sm ring-4 ring-amber-100">
                                <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1" aria-hidden="true">check</span>
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
                                <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1" aria-hidden="true">cancel</span>
                            </span>
                        @else
                            <span class="relative z-10 w-10 h-10 rounded-full bg-surface-container text-secondary flex items-center justify-center text-sm font-bold border border-surface-dim">
                                {{ $step['num'] }}
                            </span>
                        @endif

                        <div class="min-w-0 md:px-1">
                            <div class="flex md:justify-center items-center gap-2">
                                <h4 class="text-xs font-bold {{ $step['state'] === 'pending' ? 'text-secondary' : 'text-on-surface' }}">{{ $step['title'] }}</h4>
                                @if ($step['state'] === 'current')
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                                @elseif ($step['state'] === 'error' || $step['state'] === 'cancelled')
                                    <span class="text-[10px] uppercase font-black text-red-800 bg-red-100 px-1.5 py-px rounded">{{ __('training.chip_alert') }}</span>
                                @endif
                            </div>
                            <p class="text-[11px] mt-0.5 leading-snug {{ $step['alert'] ? 'text-red-700 font-medium' : ($step['state'] === 'current' ? 'text-amber-700 font-medium' : (in_array($step['state'], ['done','warning']) ? 'text-amber-700 font-medium' : 'text-secondary')) }}">
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

        {{-- ════════ HERO CARD ════════ --}}
        <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
            <div class="h-1 bg-gradient-to-r from-primary via-primary-container/70 to-transparent" aria-hidden="true"></div>

            <div class="p-5 md:p-6">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                    <div class="min-w-0">
                        <h1 class="text-headline-md font-headline-md text-on-surface tracking-tight leading-tight" dir="ltr">
                            {{ __('training.card_title') }} · <span class="font-mono">{{ $viewRef }}</span>
                        </h1>
                        <p class="text-xs text-secondary mt-1 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-xs" aria-hidden="true">calendar_today</span>
                            {{ __('training.deposited_on', ['date' => $tr->created_at->isoFormat('D MMMM YYYY')]) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <span class="inline-flex items-center gap-1.5 text-label-caps font-label-caps px-2.5 py-1 rounded font-bold {{ $statusPillCls }}">
                            <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $statusPillIcon }}</span>
                            {{ $tr->statusLabel() }}
                        </span>
                        <a href="{{ route('admin.training.index') }}"
                           class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-2.5 rounded transition-colors">
                            <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                        </a>
                        <button type="button" onclick="window.print()"
                            class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">fact_check</span>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-secondary">{{ __('training.details_title') }}</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">badge</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('training.nom_prenom') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate" title="{{ $tr->nom_prenom ?? '' }}">{{ $tr->nom_prenom ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">fingerprint</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('training.cin') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate font-mono num-ltr" dir="ltr">{{ $tr->cin ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">call</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('training.phone') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate font-mono num-ltr" dir="ltr">{{ $tr->phone ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">account_balance</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('training.code_structure') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $tr->governorate?->name ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">location_city</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('training.delegation') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $tr->delegation?->name ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">school</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('training.training_type') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $tr->training_type ? __('training.type_' . $tr->training_type) : '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">sync</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('training.training_kind') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $tr->training_kind ? __('training.kind_' . $tr->training_kind) : '—' }}</span>
                        </div>
                    </div>
                    @if ($tr->isCentralized())
                        @php
                            $sess = $tr->plannedSession;
                            $placeLabel = $sess?->placeLabel() ?? \App\Models\TrainingPlannedSession::defaultPlaceLabel();
                            $placeName  = $sess?->placeName() ?? \App\Models\TrainingPlannedSession::defaultPlaceLabel();
                        @endphp
                        <div class="col-span-2 sm:col-span-3 lg:col-span-4 rounded @if($tr->isWaitListed()) border border-amber-300 bg-amber-50 dark:border-amber-700/50 dark:bg-amber-900/20 @else border border-primary/30 bg-primary/5 dark:border-primary/40 dark:bg-primary/10 @endif p-3 flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-base mt-px shrink-0 @if($tr->isWaitListed()) text-amber-600 @else text-primary @endif" aria-hidden="true">hub</span>
                            <div class="min-w-0 space-y-0.5">
                                <span class="block text-[10px] uppercase tracking-wider font-bold @if($tr->isWaitListed()) text-amber-800 dark:text-amber-300 @else text-primary @endif">{{ __('training.centralized_title') }}</span>
                                <span class="block text-xs font-bold text-on-surface dark:text-gray-100">
                                    {{ __('training.training_type') }}: {{ __('training.type_' . $tr->training_type) }}
                                </span>
                                @if ($tr->isWaitListed())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-200 text-amber-900 text-[11px] font-bold dark:bg-amber-900/40 dark:text-amber-200">
                                        <span class="material-symbols-outlined text-[13px]" aria-hidden="true">hourglass_top</span>{{ __('training.waitlist_badge') }}
                                    </span>
                                @else
                                    <span class="block text-xs font-bold text-on-surface dark:text-gray-100" dir="ltr">
                                        {{ __('training.planned_starts_at') }}: {{ $sess?->starts_at?->format('d/m/Y') ?? '—' }}@if($sess?->ends_at) — {{ $sess->ends_at->format('d/m/Y') }}@endif
                                    </span>
                                    <span class="block text-[11px] text-secondary">
                                        {{ __('training.planned_place') }}: <span class="font-semibold text-on-surface dark:text-gray-200">{{ $placeName }}</span>
                                    </span>
                                @endif
                                <span class="block text-[11px] text-secondary mt-0.5">{{ __('training.centralized_hint_short', ['place' => $placeLabel]) }}</span>
                            </div>
                        </div>
                    @endif
                    @if ($tr->training_start_date)
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">calendar_today</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('training.training_start_date') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate" dir="ltr">
                                {{ $tr->training_start_date->format('d/m/Y') }}
                                @if ($tr->training_start_time) · {{ $tr->training_start_time->format('H:i') }}@endif
                            </span>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Note admin hors correction --}}
                @if ($tr->admin_note && $status !== 'needs_correction' && $status !== 'cancelled' && $status !== 'failed')
                <div class="mt-4 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-3">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">sticky_note_2</span>
                        {{ __('training.admin_note_label') }}
                    </p>
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $tr->admin_note }}</p>
                </div>
                @endif

                {{-- Observations du demandeur --}}
                @if ($tr->notes)
                <div class="mt-3 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-3">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">subject</span>
                        {{ __('training.notes') }}
                    </p>
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $tr->notes }}</p>
                </div>
                @endif

                <div class="h-2"></div>

                {{-- Alerte contextuelle --}}
                @if ($heroAlert === 'info')
                    <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-300 rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-amber-600 text-base mt-px" aria-hidden="true">hourglass_bottom</span>
                        <div class="font-bold text-amber-900 leading-relaxed">{{ __('training.' . $status . '_banner') }}</div>
                    </div>
                @elseif ($heroAlert === 'error' && $status !== 'needs_correction' && $status !== 'failed')
                    <div class="flex items-start gap-2.5 bg-red-50 border border-red-300 rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">notification_important</span>
                        <div class="font-bold text-red-900 leading-relaxed">{{ __('training.cancelled_banner') }}</div>
                    </div>
                @elseif ($heroAlert === 'draft')
                    <div class="flex items-start gap-2.5 bg-red-50 border border-red-300 rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">edit_note</span>
                        <div class="font-bold text-red-900 leading-relaxed">{{ __('training.draft_notice') }}</div>
                    </div>
                @elseif ($heroAlert === 'neutral')
                    <div class="flex items-start gap-2.5 bg-surface-container-low border border-surface-dim rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-secondary text-base mt-px" aria-hidden="true">{{ $status === 'served' ? 'handshake' : ($status === 'passed' ? 'workspace_premium' : 'hourglass_top') }}</span>
                        <div class="font-bold text-on-surface leading-relaxed">
                            {{ $tr->statusLabel() }}
                            <span class="font-normal text-secondary"> — {{ __('training.review_note') }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- ════════ CERTIFICAT (réussi / remis) ════════ --}}
        @if ($tr->hasCertificate())
        <section class="bg-surface-container-lowest border {{ $status === 'served' ? 'border-teal-200 dark:border-teal-800/40' : 'border-emerald-200 dark:border-emerald-800/40' }} rounded-lg shadow-sm overflow-hidden">
            <div class="border-s-4 border-s-{{ $status === 'served' ? 'teal' : 'emerald' }}-500 p-5">
                <div class="flex items-start gap-3.5 mb-3">
                    <div class="w-10 h-10 rounded {{ $status === 'served' ? 'bg-teal-100 text-teal-700' : 'bg-emerald-100 text-emerald-700' }} flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">workspace_premium</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                {{ __('training.certificate_title') }}
                                @if ($tr->certificate_number) — <span class="font-mono" dir="ltr">{{ $tr->certificate_number }}</span>@endif
                            </h2>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $status === 'served' ? 'bg-teal-100 text-teal-800' : 'bg-emerald-500 text-white' }}">
                                {{ __('training.cert_status_' . $tr->cert_status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-surface-dim bg-surface-container-low p-4">
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        @if ($tr->certificate_number)
                        <div>
                            <p class="font-bold text-secondary uppercase tracking-wider mb-0.5">{{ __('training.certificate_number') }}</p>
                            <p class="font-mono font-bold text-on-surface">{{ $tr->certificate_number }}</p>
                        </div>
                        @endif
                        @if ($tr->valid_until)
                        <div>
                            <p class="font-bold text-secondary uppercase tracking-wider mb-0.5">{{ __('training.valid_until') }}</p>
                            <p class="text-on-surface">{{ $tr->valid_until->format('d/m/Y') }}</p>
                        </div>
                        @endif
                        @if ($tr->delivery_method)
                        <div>
                            <p class="font-bold text-secondary uppercase tracking-wider mb-0.5">{{ __('training.delivery_title') }}</p>
                            <p class="text-on-surface">{{ __('training.delivery_method_' . $tr->delivery_method) }}</p>
                        </div>
                        @endif
                        @if ($tr->isPost() && $tr->post_tracking)
                        <div>
                            <p class="font-bold text-secondary uppercase tracking-wider mb-0.5">{{ __('training.receipt_number') }}</p>
                            <p class="font-mono text-on-surface" dir="ltr">{{ $tr->post_tracking }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                @can('manage training')
                @if ($canUpdateCert || $canRevokeCert)
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if ($tr->isPickup() && $tr->cert_status === \App\Models\TrainingRequest::CERT_STATUS_READY)
                        <form method="POST" action="{{ route('admin.training.certificate.update', $tr) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="collected">
                            <button type="submit" class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">handshake</span>
                                {{ __('training.cert_status_collected') }}
                            </button>
                        </form>
                    @endif

                    @if ($tr->isPost() && $tr->cert_status === \App\Models\TrainingRequest::CERT_STATUS_READY)
                        <form method="POST" action="{{ route('admin.training.certificate.update', $tr) }}" class="flex flex-wrap items-center gap-2">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="sent">
                            <input type="text" name="post_tracking" value="{{ old('post_tracking', $tr->post_tracking) }}" placeholder="{{ __('training.receipt_number') }}" class="h-10 px-3 text-xs rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none">
                            <button type="submit" class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">local_post_office</span>
                                {{ __('training.cert_status_post_sent') }}
                            </button>
                        </form>
                    @endif

                    @if ($tr->isPost() && $tr->cert_status === \App\Models\TrainingRequest::CERT_STATUS_POST_SENT)
                        <form method="POST" action="{{ route('admin.training.certificate.update', $tr) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="delivered">
                            <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">mark_email_read</span>
                                {{ __('training.cert_status_post_delivered') }}
                            </button>
                        </form>
                    @endif

                    @if ($canRevokeCert)
                        <form method="POST" action="{{ route('admin.training.certificate.destroy', $tr) }}" data-confirm data-confirm-style="danger" class="ms-auto">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-600 hover:text-red-800 transition-colors">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">delete_forever</span>
                                {{ __('training.admin_cert_revoked') }}
                            </button>
                        </form>
                    @endif
                </div>
                @endif
                @endcan
            </div>
        </section>
        @endif

        {{-- ════════ GRILLE ASYMÉTRIQUE 8/4 ════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- ══ COLONNE GAUCHE (8) ══ --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- Calendrier + présences --}}
                @if ($tr->training_start_date || $tr->sessions->isNotEmpty())
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                    <div class="flex items-center gap-2.5 p-5 md:px-6 border-b border-surface-dim">
                        <span class="w-8 h-8 rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">calendar_today</span>
                        </span>
                        <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('training.schedule_title') }}</h3>
                    </div>
                    <div class="p-5 md:p-6 space-y-4">
                        @if ($tr->training_start_date)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <div class="bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.training_start_date') }}</span>
                                <span class="block text-xs font-bold text-on-surface mt-0.5">{{ $tr->training_start_date->format('d/m/Y') }}</span>
                            </div>
                            @if ($tr->training_start_time)
                            <div class="bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.training_start_time') }}</span>
                                <span class="block text-xs font-bold text-on-surface mt-0.5" dir="ltr">{{ $tr->training_start_time->format('H:i') }}</span>
                            </div>
                            @endif
                            @if ($tr->training_duration_days)
                            <div class="bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.training_duration_days') }}</span>
                                <span class="block text-xs font-bold text-on-surface mt-0.5">{{ $tr->training_duration_days }} {{ __('training.fee_weeks') }}</span>
                            </div>
                            @endif
                        </div>
                        @endif

                        @if ($tr->sessions->isNotEmpty())
                        <div class="{{ $tr->training_start_date ? 'border-t border-surface-dim pt-4' : '' }}">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-3">{{ __('training.attendance_title') }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tr->sessions as $session)
                                    @php
                                        $attCls = match($session->attendance) {
                                            'present' => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800/40',
                                            'excused' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800/40',
                                            default   => 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800/40',
                                        };
                                        $attIcon = match($session->attendance) {
                                            'present' => 'check_circle',
                                            'excused' => 'info',
                                            default   => 'cancel',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded border text-[11px] font-bold {{ $attCls }}">
                                        <span class="material-symbols-outlined text-[13px]" aria-hidden="true">{{ $attIcon }}</span>
                                        {{ $session->session_date->format('d/m') }}
                                        <span class="font-normal">· {{ __('training.attendance_' . $session->attendance) }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Paiement --}}
                @if ($tr->isPaid())
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                    <div class="flex items-center gap-2.5 p-5 md:px-6 border-b border-surface-dim">
                        <span class="w-8 h-8 rounded bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">payments</span>
                        </span>
                        <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('training.payment_title') }}</h3>
                    </div>
                    <div class="p-5 md:p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <div class="bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.fee_amount') }}</span>
                                <span class="block text-xs font-bold text-on-surface mt-0.5 font-mono" dir="ltr">{{ number_format($tr->fee_amount, 3) }} {{ __('training.fee_tnd') }}</span>
                            </div>
                            <div class="bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.receipt_number') }}</span>
                                <span class="block text-xs font-bold text-on-surface mt-0.5 font-mono">{{ $tr->receipt_number }}</span>
                            </div>
                            <div class="bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('training.paid_at') }}</span>
                                <span class="block text-xs font-bold text-on-surface mt-0.5">{{ $tr->paid_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50/60 px-4 py-3 dark:border-emerald-800/40 dark:bg-emerald-900/10">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-emerald-600 text-lg" aria-hidden="true">check_circle</span>
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">{{ __('training.status_payment_confirmed') }}</span>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 whitespace-nowrap">{{ __('training.payment_proof') }}</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Résultat --}}
                @if ($tr->result)
                <div class="bg-surface-container-lowest border {{ $tr->result === 'passed' ? 'border-emerald-200 dark:border-emerald-800/40' : 'border-red-200 dark:border-red-800/40' }} rounded-lg shadow-sm overflow-hidden">
                    <div class="flex items-center gap-2.5 p-5 md:px-6 border-b border-surface-dim">
                        <span class="w-8 h-8 rounded {{ $tr->result === 'passed' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }} flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">grading</span>
                        </span>
                        <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('training.result_title') }}</h3>
                    </div>
                    <div class="p-5 md:p-6">
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded {{ $tr->result === 'passed' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }} text-sm font-bold mb-3">
                            <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1" aria-hidden="true">{{ $tr->result === 'passed' ? 'workspace_premium' : 'cancel' }}</span>
                            {{ __('training.result_' . $tr->result) }}
                        </span>
                        @if ($tr->result_note)
                            <p class="text-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $tr->result_note }}</p>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Timeline --}}
                @if ($tr->events->isNotEmpty())
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="w-full flex flex-wrap items-center justify-between gap-3 p-5 md:px-6 pb-4 border-b border-surface-dim hover:bg-surface-container dark:hover:bg-gray-700/40 transition-colors text-start">
                        <div>
                            <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">history</span>
                                {{ __('training.timeline_title') }}
                            </h3>
                            <p class="text-xs text-secondary mt-0.5">{{ __('training.timeline_subtitle') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] text-secondary font-semibold flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">receipt_long</span>
                                {{ __('training.events_count', ['n' => $tr->events->count()]) }}
                            </span>
                            <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>

                    <div x-show="open" x-cloak class="p-5 md:p-6 pt-5">
                        <div class="relative ps-6 sm:ps-8 space-y-6 before:absolute before:start-3 sm:before:start-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                            @foreach ($tr->events->reverse()->values() as $event)
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
                                                <span class="text-xs font-semibold text-on-surface">{{ in_array($event->type, ['submitted','resubmitted']) ? __('training.timeline_actor_you') : __('training.timeline_actor_admin') }}</span>
                                            </div>
                                            <time class="text-xs font-mono font-medium text-secondary" dir="ltr">{{ $event->created_at->format('d/m/Y H:i') }}</time>
                                        </div>
                                        <h5 class="text-xs font-bold text-on-surface mb-1">{{ __('training.tl_' . $event->type) }}</h5>
                                        @if ($event->note)
                                            <p class="text-body-sm font-body-sm text-secondary">
                                                <span class="font-bold text-on-surface">{{ __('training.timeline_note_label') }}</span>
                                                {{ $event->note }}
                                            </p>
                                        @endif
                                        @if (is_array($event->data))
                                            @if (!empty($event->data['fee_amount']))<p class="mt-1 text-xs inline-flex items-center gap-1 rounded bg-orange-100 px-2 py-0.5 font-mono font-bold text-orange-900" dir="ltr"><span class="material-symbols-outlined text-[13px]">payments</span>{{ number_format($event->data['fee_amount'], 3) }} {{ __('training.fee_tnd') }}</p>@endif
                                            @if (!empty($event->data['result']))<p class="mt-1 text-xs inline-flex items-center gap-1 rounded bg-purple-100 px-2 py-0.5 font-bold text-purple-900">{{ __('training.result_' . $event->data['result']) }}</p>@endif
                                            @if (!empty($event->data['attendance']))<p class="mt-1 text-xs inline-flex items-center gap-1 rounded bg-sky-100 px-2 py-0.5 font-bold text-sky-900">{{ __('training.attendance_' . $event->data['attendance']) }}</p>@endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- ══ COLONNE DROITE (4) ══ --}}
            <div class="lg:col-span-4 space-y-6">

                {{-- Documents --}}
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center gap-2 p-5 hover:bg-surface-container transition-colors text-start border-b border-surface-dim">
                        <span class="w-8 h-8 rounded bg-primary-fixed/50 text-primary flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">attach_file</span>
                        </span>
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">{{ __('training.documents_title') }}</h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded whitespace-nowrap {{ $isDocComplete ? 'text-amber-900 bg-amber-100 dark:bg-amber-900/40 dark:text-amber-300' : 'text-secondary bg-surface-dim' }}" dir="ltr">{{ $uploadedTypes }}/{{ $totalDocTypes }}</span>
                        <svg class="w-4 h-4 text-secondary transition-transform shrink-0 ms-1" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak class="p-5">
                        <div class="h-2 bg-surface-dim/50 rounded-full overflow-hidden mb-1" role="progressbar" aria-valuenow="{{ $docPct }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full transition-all duration-500 {{ $isDocComplete ? 'bg-amber-500' : 'bg-primary' }}" style="width: {{ $docPct }}%"></div>
                        </div>
                        <p class="text-[10px] font-semibold mb-4 {{ $isDocComplete ? 'text-amber-800 dark:text-amber-400' : 'text-secondary' }}">
                            {{ $isDocComplete ? __('training.documents_title') : $uploadedTypes . ' / ' . $totalDocTypes }}
                        </p>

                        <div class="space-y-4">
                            @forelse ($documentGroups as $group)
                                @php
                                    $groupTotal = count($group['documents']);
                                    $groupDone  = collect($group['documents'])->filter(fn ($d) => $documentsByKey->get($d['key'], collect())->isNotEmpty())->count();
                                    $groupComplete = $groupTotal > 0 && $groupDone >= $groupTotal;
                                @endphp
                                <div class="rounded border border-surface-dim dark:border-gray-700 overflow-hidden">
                                    <div class="flex items-center justify-between gap-3 px-3 py-2.5 border-b border-surface-dim bg-surface-container-low dark:border-gray-700 dark:bg-gray-900/40">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">person</span>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-xs font-bold text-on-surface dark:text-gray-100">{{ $group['name'] ?: __('training.participants_section') }}</p>
                                                <div class="flex flex-wrap items-center gap-1.5 text-[10px] text-secondary">
                                                    @if ($group['cin'])<span dir="ltr">{{ __('training.cin') }} : {{ $group['cin'] }}</span>@endif
                                                    @if ($group['training_kind'] === \App\Models\TrainingRequest::KIND_RECYCLING)
                                                        <span class="rounded border border-amber-200 bg-amber-100 px-1.5 py-0.5 font-bold text-amber-800 dark:border-amber-800/40 dark:bg-amber-900/30 dark:text-amber-300">{{ __('training.kind_recycling') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <span class="shrink-0 rounded px-2 py-0.5 text-[10px] font-bold {{ $groupComplete ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-surface-dim text-secondary' }}" dir="ltr">{{ $groupDone }}/{{ $groupTotal }}</span>
                                    </div>

                                    <div class="space-y-2.5 p-3">
                                        @foreach ($group['documents'] as $requirement)
                                            @php $files = $documentsByKey->get($requirement['key'], collect()); @endphp
                                            @forelse ($files as $doc)
                                                <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-2.5 group hover:border-primary/40 transition-colors">
                                                    <div class="flex items-start gap-2.5 min-w-0">
                                                        <span class="material-symbols-outlined text-red-600 text-lg mt-0.5 shrink-0" aria-hidden="true">{{ str_contains((string) $doc->mime_type, 'pdf') ? 'picture_as_pdf' : 'description' }}</span>
                                                        <div class="min-w-0">
                                                            <h4 class="text-xs font-bold text-on-surface leading-snug">{{ $requirement['name'] }}</h4>
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
                                                    <a href="{{ route('admin.training.documents.download', $doc) }}"
                                                       class="w-7 h-7 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-600 hover:text-white flex items-center justify-center shrink-0 transition-colors"
                                                       title="{{ __('training.documents_title') }}">
                                                        <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                                    </a>
                                                </div>
                                            @empty
                                                <div class="p-3 border border-dashed border-surface-dim rounded flex items-start gap-2.5">
                                                    <span class="w-7 h-7 rounded bg-surface-dim/40 text-outline flex items-center justify-center shrink-0">
                                                        <span class="material-symbols-outlined text-sm">error_outline</span>
                                                    </span>
                                                    <div class="min-w-0">
                                                        <h4 class="text-xs font-bold text-on-surface">{{ $requirement['name'] }}</h4>
                                                        <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap">
                                                            <span class="text-red-600 dark:text-red-400">{{ __('training.documents_not_uploaded') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforelse
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center">
                                    <span class="material-symbols-outlined text-2xl text-outline" aria-hidden="true">folder_off</span>
                                    <p class="text-xs text-secondary mt-1">{{ __('training.admin_no_doctypes') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Documents archivés --}}
                @if ($archivedDocuments->isNotEmpty())
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print" x-data="{ openArchive: false }">
                    <div class="p-5">
                        <button type="button" @click="openArchive = !openArchive" class="w-full text-start">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-8 h-8 rounded bg-surface-dim/60 text-secondary flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">inventory_2</span>
                                </span>
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">{{ __('training.archive_title') }}</h3>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-surface-dim text-secondary" dir="ltr">{{ $archivedDocuments->count() }}</span>
                                <svg class="w-4 h-4 text-secondary transition-transform" :class="openArchive ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                            <p class="text-[11px] text-secondary leading-relaxed">{{ __('training.archive_desc') }}</p>
                        </button>
                        <div x-show="openArchive" x-cloak class="pt-4 space-y-2.5">
                            @foreach ($archivedDocuments as $doc)
                                <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-2.5">
                                    <div class="flex items-start gap-2.5 min-w-0">
                                        <span class="material-symbols-outlined text-outline text-lg mt-0.5 shrink-0" aria-hidden="true">inventory_2</span>
                                        <div class="min-w-0">
                                            <h4 class="text-xs font-bold text-secondary leading-snug">{{ $doc->document_key ? $tr->documentKeyLabel($doc->document_key) : ($doc->documentType?->name ?? __('training.documents_title')) }}</h4>
                                            <div class="flex items-center gap-2 text-[11px] text-secondary/80 mt-0.5 flex-wrap" dir="ltr">
                                                <span class="font-mono">{{ $fmtSize($doc->size) }}</span>
                                                <span class="text-surface-dim">•</span>
                                                <span>{{ $doc->created_at->format('d/m/Y') }}</span>
                                                @if ($doc->original_name)<span class="truncate max-w-36" title="{{ $doc->original_name }}">— {{ $doc->original_name }}</span>@endif
                                            </div>
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.training.documents.download', $doc) }}"
                                       class="w-7 h-7 rounded bg-surface-container text-secondary hover:bg-primary hover:text-on-primary flex items-center justify-center shrink-0 transition-colors"
                                       title="{{ __('training.documents_title') }}">
                                        <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                {{-- Rappel suivi --}}
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print">
                    <div class="p-5 md:px-6">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">phone_in_talk</span>
                            {{ __('training.phone_notification_hint') }}
                        </h3>
                        <p class="text-xs text-secondary leading-relaxed">{{ __('training.review_note') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════════
             ZONE DANGEREUSE — annulation définitive de la demande (bas de page).
        ══════════════════════════════════════════════════════════════════════════ --}}
        @can('manage training')
        @if ($canCancel)
        <section class="mt-6 border border-red-300 dark:border-red-800/50 rounded-lg bg-red-50/40 dark:bg-red-900/5 overflow-hidden" x-data="{ cancelOpen: false }">
            <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0 dark:bg-red-900/30 dark:text-red-300">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">warning</span>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-extrabold text-red-700 dark:text-red-300 uppercase tracking-wide">{{ __('training.admin_cancel_btn') }}</h2>
                        <p class="text-xs text-secondary mt-0.5">{{ __('training.admin_cancel_reason') }}</p>
                    </div>
                </div>
                <button type="button" @click="cancelOpen = !cancelOpen"
                    class="inline-flex items-center justify-center gap-2 border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold py-2.5 px-5 rounded transition-colors dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 shrink-0">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">cancel</span>
                    <span x-text="cancelOpen ? '{{ __('training.admin_hide_note') }}' : '{{ __('training.admin_cancel_btn') }}'">{{ __('training.admin_cancel_btn') }}</span>
                </button>
            </div>
            <div x-show="cancelOpen" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="border-t border-red-200 dark:border-red-800/40 p-5">
                <form method="POST" action="{{ route('admin.training.cancel', $tr) }}"
                      data-confirm data-confirm-style="danger"
                      data-confirm-focus-cancel="false">
                    @csrf
                    <label class="block text-xs font-semibold text-red-800 dark:text-red-200 mb-1.5">{{ __('training.admin_cancel_reason') }} <span class="text-red-500">*</span></label>
                    <x-admin.bilingual-note name="admin_note" :rows="3" required
                        placeholder-key="training.note_cancel_placeholder" />
                    <button type="submit" class="mt-3 w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">cancel</span>
                        {{ __('training.admin_confirm_cancel') }}
                    </button>
                </form>
            </div>
        </section>
        @endif
        @endcan

    </div>{{-- /dossier-screen --}}

    {{-- ══════════════ FICHE IMPRIMABLE (A4) ══════════════ --}}
    <div class="print-sheet" aria-hidden="true">
        <div class="fiche">
            <div class="fiche-band">
                <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                    <img src="/images/logo.png" alt="" class="fiche-band-logo">
                    <div style="min-width:0;">
                        <div class="fiche-band-org">{{ setting('app_name', __('app.name')) }}</div>
                        <div class="fiche-band-sub">{{ __('training.print_header_service', ['place' => $tr->governorate?->name ?? '—']) }}</div>
                    </div>
                </div>
                <div class="fiche-band-ref">
                    <div class="fiche-band-ref-label">{{ __('training.print_ref_label') }}</div>
                    <div class="fiche-band-ref-value" dir="ltr">{{ $viewRef }}</div>
                </div>
            </div>

            <div class="fiche-title-row">
                <div>
                    <div class="fiche-title">{{ __('training.print_title') }}</div>
                    <div class="fiche-title-ar" dir="rtl">طلب دورة تكوينية</div>
                </div>
                <div class="fiche-date">{{ __('training.print_edited_on') }}<br>{{ $printDate }}</div>
            </div>

            <table class="fiche-table fiche-table-dark">
                <thead><tr><th colspan="2">{{ __('training.section_applicant') }}</th></tr></thead>
                <tbody>
                    <tr><th>{{ __('training.nom_prenom') }}</th><td>{{ $tr->nom_prenom ?? __('training.print_missing') }}</td></tr>
                    <tr><th>{{ __('training.cin') }}</th><td dir="ltr">{{ $tr->cin ?? __('training.print_missing') }}</td></tr>
                    <tr><th>{{ __('training.phone') }}</th><td dir="ltr">{{ $tr->phone ?? __('training.print_missing') }}</td></tr>
                    <tr><th>{{ __('training.code_structure') }} / {{ __('training.delegation') }}</th>
                        <td>{{ $tr->governorate?->name ?? '—' }} — {{ $tr->delegation?->name ?? '—' }}</td></tr>
                    <tr><th>{{ __('training.training_type') }}</th><td>{{ $tr->training_type ? __('training.type_' . $tr->training_type) : '—' }}</td></tr>
                    <tr><th>{{ __('training.training_kind') }}</th><td>{{ $tr->training_kind ? __('training.kind_' . $tr->training_kind) : '—' }}</td></tr>
                </tbody>
            </table>

            @if ($tr->isPaid())
            <table class="fiche-table">
                <thead><tr><th colspan="2">{{ __('training.payment_title') }}</th></tr></thead>
                <tbody>
                    <tr><th>{{ __('training.fee_amount') }}</th><td dir="ltr">{{ number_format($tr->fee_amount, 3) }} {{ __('training.fee_tnd') }}</td></tr>
                    <tr><th>{{ __('training.receipt_number') }}</th><td>{{ $tr->receipt_number }}</td></tr>
                    <tr><th>{{ __('training.paid_at') }}</th><td>{{ $tr->paid_at->format('d/m/Y') }}</td></tr>
                </tbody>
            </table>
            @endif

            @if ($tr->cert_status)
            <div class="fiche-cert">
                <div class="fiche-cert-title">{{ __('training.certificate_title') }}</div>
                @if ($tr->certificate_number)<strong dir="ltr">{{ $tr->certificate_number }}</strong>@endif
                @if ($tr->valid_until) — {{ __('training.valid_until') }}: {{ $tr->valid_until->format('d/m/Y') }}@endif
            </div>
            @endif

            <div class="fiche-sign-row">
                <div class="fiche-sign-col">
                    <div class="fiche-sign-name">إمضاء طالب الطلب</div>
                    <div class="fiche-sign-line">{{ __('training.print_missing') }}</div>
                </div>
                <div class="fiche-sign-col">
                    <div class="fiche-sign-name">{{ __('training.print_stamp') }}</div>
                    <div class="fiche-sign-line">{{ __('training.card_title') }}</div>
                </div>
            </div>

            <div class="fiche-foot">
                <span>{{ __('training.print_header_service', ['place' => $tr->governorate?->name ?? '—']) }}</span>
                <span dir="ltr">{{ $viewRef }} — {{ now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

</div>
@endsection
