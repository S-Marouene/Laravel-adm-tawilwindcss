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
        'served'             => 7,   // sentinel — all done
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

    // ── Documents completeness ──
    $activeDocuments = $tr->documents->filter(fn ($d) => $d->status !== 'archived');
    $documentsByType = $activeDocuments->groupBy('document_type_id');
    $uploadedDocs    = $activeDocuments->keyBy('document_type_id');
    $uploadedTypes   = $documentsByType->filter(fn ($files, $typeId) => $documentTypes->contains('id', $typeId) && $files->isNotEmpty())->count();
    $fmtSize         = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1) . ' Mo' : number_format($b / 1024, 0) . ' Ko';
    $isDocComplete   = $documentTypes->count() > 0 && $uploadedTypes >= $documentTypes->count();
    $docPct          = $documentTypes->count() > 0 ? (int) round(min($uploadedTypes, $documentTypes->count()) / $documentTypes->count() * 100) : 0;
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

    $printDate = now()->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY, HH:mm');
@endphp

<style>
    .print-sheet { display: none; }
    @media print {
        @page { size: A4; margin: 11mm 11mm 14mm; }
        header, footer, .no-print { display: none !important; }
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
    .fiche-sign-row { display: flex; gap: 28px; margin: 6px 0 10px; align-items: flex-end; }
    .fiche-sign-col { flex: 1; text-align: center; }
    .fiche-sign-name { font-size: 10.5px; font-weight: 700; margin-bottom: 46px; text-transform: uppercase; letter-spacing: .04em; }
    .fiche-sign-line { border-top: 1px solid #6b7280; margin: 0 8px; padding-top: 3px; font-size: 9px; color: #6b7280; }
    .fiche-foot { margin-top: 10px; padding-top: 7px; border-top: 1px solid #c7cad1; display: flex; justify-content: space-between; gap: 12px; font-size: 9px; color: #6b7280; }
</style>

<x-app-layout>
    <main class="flex-1 w-full mx-auto sm:px-6 lg:px-8 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-0">

            <div class="dossier-screen no-print">

                {{-- ── Correction banner ── --}}
                @if ($status === 'needs_correction')
                <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden mb-6 no-print">
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
                            </div>
                        </div>
                        @if ($tr->admin_note)
                        <div class="rounded border border-red-200 bg-red-50 dark:bg-red-900/10 px-4 py-3 mb-4">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                                {{ __('training.admin_note_label') }}
                            </p>
                            <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $tr->admin_note }}</p>
                        </div>
                        @endif
                        @php
                            $fieldLabels = [
                                'cin'           => __('training.cin'),
                                'nom_prenom'    => __('training.nom_prenom'),
                                'phone'         => __('training.phone'),
                                'governorate_id'=> __('training.code_structure'),
                                'delegation_id' => __('training.delegation'),
                                'training_type' => __('training.training_type'),
                                'training_kind' => __('training.training_kind'),
                                'notes'         => __('training.notes'),
                            ];
                            $correctionDocs = !empty($correction['docs_to_fix'])
                                ? $documentTypes->filter(fn ($t) => in_array($t->id, $correction['docs_to_fix']))
                                : collect();
                        @endphp
                        @if (!empty($correction['fields_to_fix']) || $correctionDocs->isNotEmpty())
                        <div class="mb-4 flex flex-wrap gap-1.5">
                            @foreach ($correction['fields_to_fix'] ?? [] as $fk)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700/40">
                                    <span class="material-symbols-outlined text-[11px]" aria-hidden="true">edit</span>
                                    {{ $fieldLabels[$fk] ?? $fk }}
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
                        <a href="{{ route('training.correct', $tr) }}"
                           class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">edit_note</span>
                            {{ __('training.fix_and_resubmit') }}
                        </a>
                    </div>
                </section>
                @endif

                {{-- ── Payment pending panel ── --}}
                @if ($status === 'payment_pending')
                <section class="bg-surface-container-lowest border border-orange-200 dark:border-orange-800/40 rounded-lg shadow-sm overflow-hidden mb-6 no-print">
                    <div class="border-s-4 border-s-orange-500 p-5">
                        <div class="flex items-start gap-3.5 mb-2">
                            <div class="w-10 h-10 rounded bg-orange-100 text-orange-700 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">payments</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.payment_title') }}</h2>
                                    <span class="text-[10px] bg-orange-500 text-white font-bold px-2 py-0.5 rounded">{{ __('training.status_payment_pending') }}</span>
                                </div>
                                <p class="text-body-sm text-on-surface mt-2 leading-relaxed">{{ __('training.payment_pending_banner') }}</p>
                            </div>
                        </div>
                        @php $estFee = $tr->computedFee(); @endphp
                        @if ($estFee > 0)
                        <div class="mt-3 rounded-lg border border-orange-200 bg-orange-50/60 px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-orange-600 text-lg" aria-hidden="true">toll</span>
                                <div>
                                    <p class="text-[11px] font-bold text-orange-800 uppercase tracking-wider mb-0.5">{{ __('training.estimated_fee') }}</p>
                                    <p class="text-body-md font-black text-orange-700" dir="ltr">{{ number_format($estFee, 3) }} {{ __('training.fee_tnd') }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </section>
                @endif

                {{-- ── Passed + certificate panel ── --}}
                @if (in_array($status, ['passed', 'served']))
                <section class="bg-surface-container-lowest border {{ $status === 'served' ? 'border-teal-200 dark:border-teal-800/40' : 'border-emerald-200 dark:border-emerald-800/40' }} rounded-lg shadow-sm overflow-hidden mb-6 no-print">
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
                                        {{ $status === 'served' ? __('training.cert_status_collected') : __('training.cert_status_ready') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if ($tr->cert_status)
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
                            </div>
                            @if ($tr->cert_status === 'ready' && $tr->isPickup())
                                <p class="mt-3 text-[11px] text-secondary leading-relaxed flex items-start gap-1.5">
                                    <span class="material-symbols-outlined text-primary text-sm shrink-0 mt-px" aria-hidden="true">storefront</span>
                                    {{ __('training.approved_next_hint') }}
                                </p>
                            @endif
                        </div>
                        @endif
                    </div>
                </section>
                @endif

                {{-- ── Failed banner ── --}}
                @if ($status === 'failed')
                <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden mb-6 no-print">
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
                                <p class="text-body-sm text-on-surface mt-2 leading-relaxed">{{ __('training.failed_banner') }}</p>
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

                {{-- ── Draft banner ── --}}
                @if ($status === 'draft')
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden mb-6 no-print">
                    <div class="border-s-4 border-s-primary p-5 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl" aria-hidden="true">edit_note</span>
                            </div>
                            <div>
                                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.status_draft') }}</h2>
                                <p class="text-body-sm text-secondary mt-0.5">{{ __('training.draft_notice') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('training.step2.show', $tr) }}"
                           class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">play_arrow</span>
                            {{ __('training.continue') }}
                        </a>
                    </div>
                </section>
                @endif

                {{-- ════════ LIFECYCLE STEPPER ════════ --}}
                <section class="bg-surface-container-lowest border {{ $isCancelled ? 'border-red-200 dark:border-red-800/40' : 'border-surface-dim' }} rounded-lg shadow-sm mb-6 overflow-hidden">
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
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm mb-6 overflow-hidden">
                    <div class="h-1 bg-gradient-to-r from-primary via-primary-container/70 to-transparent" aria-hidden="true"></div>

                    <div class="p-5 md:p-6">
                        {{-- Top row --}}
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                            <div class="min-w-0">
                                <h1 class="text-headline-md font-headline-md text-on-surface tracking-tight leading-tight" dir="ltr">
                                    {{ __('training.card_title') }} · <span class="font-mono">{{ $viewRef }}</span>
                                </h1>
                                <p class="text-xs text-secondary mt-1 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">calendar_today</span>
                                    {{ __('training.deposited_on', ['date' => $tr->created_at->isoFormat('D MMMM YYYY')]) ?? $tr->created_at->format('d/m/Y') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2.5 shrink-0">
                                <span class="inline-flex items-center gap-1.5 text-label-caps font-label-caps px-2.5 py-1 rounded font-bold {{ $statusPillCls }}">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $statusPillIcon }}</span>
                                    {{ $tr->statusLabel() }}
                                </span>
                                <a href="{{ route('training.index') }}"
                                   class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-2.5 rounded transition-colors">
                                    <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                                </a>
                                <button type="button" onclick="window.print()"
                                    class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                                </button>
                            </div>
                        </div>

                        {{-- Key facts grid chips — exact same pattern as rescue/special-interventions --}}
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

                        {{-- Admin note outside correction --}}
                        @if ($tr->admin_note && $status !== 'needs_correction' && $status !== 'cancelled' && $status !== 'failed')
                        <div class="mt-4 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-3">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-1 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">sticky_note_2</span>
                                {{ __('training.admin_note_label') }}
                            </p>
                            <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $tr->admin_note }}</p>
                        </div>
                        @endif

                        <div class="h-2"></div>

                        {{-- Contextual status alert --}}
                        @if ($heroAlert === 'info')
                            <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-300 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-amber-600 text-base mt-px" aria-hidden="true">hourglass_bottom</span>
                                <div class="font-bold text-amber-900 leading-relaxed">
                                    {{ __('training.' . $status . '_banner') }}
                                </div>
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

                {{-- ════════ ASYMMETRIC GRID 8/4 ════════ --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">

                    {{-- ══ LEFT COLUMN (8 cols) ══ --}}
                    <div class="lg:col-span-8 space-y-6">

                        {{-- Schedule + Attendance --}}
                        @if ($tr->training_start_date || $tr->sessions->isNotEmpty())
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                            <div class="flex items-center gap-2.5 p-5 md:px-6 border-b border-surface-dim">
                                <span class="w-8 h-8 rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">calendar_today</span>
                                </span>
                                <div>
                                    <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('training.schedule_title') }}</h3>
                                </div>
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

                        {{-- Payment info --}}
                        @if ($tr->isPaid())
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                            <div class="flex items-center gap-2.5 p-5 md:px-6 border-b border-surface-dim">
                                <span class="w-8 h-8 rounded bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">payments</span>
                                </span>
                                <div>
                                    <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('training.payment_title') }}</h3>
                                </div>
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

                        {{-- Result --}}
                        @if ($tr->result)
                        <div class="bg-surface-container-lowest border {{ $tr->result === 'passed' ? 'border-emerald-200 dark:border-emerald-800/40' : 'border-red-200 dark:border-red-800/40' }} rounded-lg shadow-sm overflow-hidden">
                            <div class="flex items-center gap-2.5 p-5 md:px-6 border-b border-surface-dim">
                                <span class="w-8 h-8 rounded {{ $tr->result === 'passed' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }} flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">grading</span>
                                </span>
                                <div>
                                    <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('training.result_title') }}</h3>
                                </div>
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

                    {{-- ══ RIGHT COLUMN (4 cols) ══ --}}
                    <div class="lg:col-span-4 space-y-6">

                        {{-- Documents (collapsible with progress bar) --}}
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print" x-data="{ open: false }">
                            <button type="button" @click="open = !open"
                                    class="w-full flex items-center gap-2 p-5 hover:bg-surface-container transition-colors text-start border-b border-surface-dim">
                                <span class="w-8 h-8 rounded bg-primary-fixed/50 text-primary flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">attach_file</span>
                                </span>
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">{{ __('training.documents_title') }}</h3>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded whitespace-nowrap {{ $isDocComplete ? 'text-amber-900 bg-amber-100 dark:bg-amber-900/40 dark:text-amber-300' : 'text-secondary bg-surface-dim' }}" dir="ltr">{{ $uploadedTypes }}/{{ $documentTypes->count() }}</span>
                                <svg class="w-4 h-4 text-secondary transition-transform shrink-0 ms-1" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open" x-cloak class="p-5">
                                {{-- Completeness bar --}}
                                <div class="h-2 bg-surface-dim/50 rounded-full overflow-hidden mb-1" role="progressbar" aria-valuenow="{{ $docPct }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $isDocComplete ? 'bg-amber-500' : 'bg-primary' }}" style="width: {{ $docPct }}%"></div>
                                </div>
                                <p class="text-[10px] font-semibold mb-4 {{ $isDocComplete ? 'text-amber-800 dark:text-amber-400' : 'text-secondary' }}">
                                    {{ $isDocComplete ? __('training.documents_title') : $uploadedTypes . ' / ' . $documentTypes->count() }}
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
                                                <a href="{{ route('training.documents.download', $doc) }}"
                                                   class="w-7 h-7 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-600 hover:text-white flex items-center justify-center shrink-0 transition-colors"
                                                   title="{{ __('training.download_label') }}">
                                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                                </a>
                                            </div>
                                        @empty
                                            <div class="p-3 border border-dashed border-surface-dim rounded flex items-start gap-2.5">
                                                <span class="w-7 h-7 rounded bg-surface-dim/40 text-outline flex items-center justify-center shrink-0">
                                                    <span class="material-symbols-outlined text-sm">{{ $docType->is_conditional ? 'help_outline' : 'error_outline' }}</span>
                                                </span>
                                                <div class="min-w-0">
                                                    <h4 class="text-xs font-bold {{ $docType->is_conditional ? 'text-secondary' : 'text-on-surface' }}">{{ $docType->name }}</h4>
                                                    <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap">
                                                        @if ($docType->is_conditional)
                                                            <span class="font-semibold text-amber-900 bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40 px-1.5 rounded">{{ __('training.conditional') }}</span>
                                                        @endif
                                                        <span class="{{ $docType->is_conditional ? '' : 'text-red-600 dark:text-red-400' }}">{{ __('training.documents_not_uploaded') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforelse
                                    @empty
                                        <div class="p-4 text-center">
                                            <span class="material-symbols-outlined text-2xl text-outline" aria-hidden="true">folder_off</span>
                                            <p class="text-xs text-secondary mt-1">{{ __('training.admin_no_doctypes') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- Archived documents --}}
                        @if (isset($archivedDocuments) && $archivedDocuments->isNotEmpty())
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
                                                    <h4 class="text-xs font-bold text-secondary leading-snug">{{ $doc->documentType?->name ?? __('training.documents_title') }}</h4>
                                                    <div class="flex items-center gap-2 text-[11px] text-secondary/80 mt-0.5 flex-wrap" dir="ltr">
                                                        <span class="font-mono">{{ $fmtSize($doc->size) }}</span>
                                                        <span class="text-surface-dim">•</span>
                                                        <span>{{ $doc->created_at->format('d/m/Y') }}</span>
                                                        @if ($doc->original_name)<span class="truncate max-w-36" title="{{ $doc->original_name }}">— {{ $doc->original_name }}</span>@endif
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="{{ route('training.documents.download', $doc) }}"
                                               class="w-7 h-7 rounded bg-surface-container text-secondary hover:bg-primary hover:text-on-primary flex items-center justify-center shrink-0 transition-colors"
                                               title="{{ __('training.download_label') }}">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Phone tracking reminder --}}
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
            </div>{{-- /dossier-screen --}}

            {{-- ════════ PRINT FICHE A4 ════════ --}}
            <div class="print-sheet">
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
    </main>
</x-app-layout>
