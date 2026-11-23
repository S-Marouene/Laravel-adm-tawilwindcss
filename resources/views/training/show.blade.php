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
    // 1 تقديم المطلب
    // 2 مراجعة الملف
    // 3 أداء المعلوم
    // 4 جدولة الدورة
    // 5 الدورة التكوينية
    // 6 النتيجة والشهادة
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
            $resultEvent   !== null => 6,
            $startedEvent  !== null => 5,
            $scheduledEvent !== null => 4,
            $paidEvent     !== null => 3,
            $approvedEvent !== null => 3,
            $advancedEvent !== null
                || $tr->events->contains(fn ($e) => $e->type === 'correction_requested') => 2,
            default => 2,
        },
        default => null,
    };
    $isCancelled = $status === 'cancelled';
    $cancelledAt = $isCancelled ? min($currentStep, $totalSteps) : null;
    $hadCorrection = $tr->events->contains(fn ($e) => $e->type === 'correction_requested');

    $steps = [];
    foreach (range(1, $totalSteps) as $i) {
        if ($isCancelled) {
            if ($i < $cancelledAt)       $state = 'done';
            elseif ($i === $cancelledAt) $state = 'cancelled';
            else                         $state = 'pending';
        } else {
            if ($currentStep === 7 || $i < $currentStep)  $state = 'done';
            elseif ($i === $currentStep)                   $state = $status === 'needs_correction' ? 'error' : 'current';
            else                                           $state = 'pending';
        }
        if ($state === 'done' && $i === 2 && $hadCorrection) $state = 'warning';

        $sub = match (true) {
            $state === 'cancelled'            => __('training.stepper_cancelled'),
            $state === 'warning' && $i === 2  => __('training.stepper_had_correction') ?? __('training.stepper_done'),
            $status === 'needs_correction' && $state === 'error' => __('training.stepper_correction'),
            $state === 'done'                 => __('training.stepper_done'),
            $state === 'pending'              => __('training.stepper_pending'),
            $i === 2 && $status === 'completed'          => __('training.stepper_verify_pending') ?? __('training.stepper_pending'),
            $i === 2 && $status === 'under_consultation' => __('training.stepper_verify_waiting') ?? __('training.stepper_current'),
            $i === 3 && $status === 'payment_pending'    => __('training.stepper_pending'),
            $i === 4 && $status === 'payment_confirmed'  => __('training.stepper_pending'),
            $i === 5 && $status === 'in_progress'        => __('training.stepper_current'),
            $i === 6 && in_array($status, ['passed','failed']) => __('training.stepper_current'),
            default                           => __('training.stepper_current'),
        };
        $alert = in_array($state, ['error', 'cancelled']);
        $steps[] = ['num' => $i, 'title' => __('training.stepper_' . $i), 'sub' => $sub, 'state' => $state, 'alert' => $alert];
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
        in_array($type, ['certificate_issued', 'certificate_collected', 'certificate_post_sent', 'certificate_post_delivered'])
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

            {{-- ═══ SCREEN CONTENT ═══ --}}
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

                {{-- ── Payment pending banner ── --}}
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
                                    <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.certificate_title') }}</h2>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $status === 'served' ? 'bg-teal-100 text-teal-800' : 'bg-emerald-500 text-white' }}">
                                        {{ $status === 'served' ? __('training.cert_status_collected') : __('training.cert_status_ready') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if ($tr->cert_status)
                        <div class="rounded-lg border border-surface-dim bg-surface-container-low p-4 mt-2">
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
                                <div>
                                    <p class="font-bold text-secondary uppercase tracking-wider mb-0.5">{{ __('training.delivery_title') }}</p>
                                    <p class="text-on-surface">{{ $tr->delivery_method ? __('training.delivery_method_' . $tr->delivery_method) : '—' }}</p>
                                </div>
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
                                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.result_failed') }}</h2>
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
                                    <span class="text-xs font-bold text-primary bg-primary-fixed/40 px-2 py-0.5 rounded whitespace-nowrap">{{ __('training.phase_instruction', ['step' => min($currentStep ?? 1, $totalSteps)]) }}</span>
                                @endif
                                <div class="hidden sm:block flex-1 h-1.5 bg-surface-dim/50 rounded-full overflow-hidden">
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
                                        <h4 class="text-xs font-bold {{ $step['state'] === 'pending' ? 'text-secondary' : 'text-on-surface' }}">
                                            {{ $step['title'] }}
                                        </h4>
                                        @if ($step['state'] === 'current')
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] mt-0.5 {{ $step['alert'] ? 'text-red-600 dark:text-red-400 font-bold' : ($step['state'] === 'warning' ? 'text-amber-700 dark:text-amber-400' : 'text-secondary') }}">
                                        {{ $step['sub'] }}
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- ════════ DETAILS + TIMELINE + DOCUMENTS ════════ --}}
                {{-- Hero header --}}
                <div class="mb-5 md:mb-6 flex flex-wrap items-start gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <a href="{{ route('training.index') }}" class="inline-flex items-center gap-1.5 text-body-sm text-secondary hover:text-on-surface transition-colors">
                                <span class="material-symbols-outlined text-[16px] rtl:rotate-180" aria-hidden="true">arrow_back</span>
                                {{ __('training.my_requests') }}
                            </a>
                            <span class="text-outline" aria-hidden="true">/</span>
                        </div>
                        <div class="flex items-center flex-wrap gap-3">
                            <h1 class="text-headline-sm text-on-surface">{{ __('training.card_title') }}</h1>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $statusPillCls }}">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">{{ $statusPillIcon }}</span>
                                {{ $tr->statusLabel() }}
                            </span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1 font-mono" dir="ltr">{{ $viewRef }}</p>
                    </div>
                    <div class="ms-auto flex items-center gap-2">
                        <button onclick="window.print()" class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    {{-- Left column --}}
                    <div class="lg:col-span-8 space-y-6">

                        {{-- Request details --}}
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                            <div class="p-5 md:px-6 pb-4 border-b border-surface-dim">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">badge</span>
                                    {{ __('training.details_title') ?? __('training.section_applicant') }}
                                </h3>
                            </div>
                            <div class="p-5 md:px-6">
                                <table class="w-full text-sm">
                                    <tbody class="divide-y divide-surface-dim/50">
                                        @foreach ([
                                            [__('training.nom_prenom'),    $tr->nom_prenom],
                                            [__('training.cin'),           $tr->cin],
                                            [__('training.phone'),         $tr->phone],
                                            [__('training.code_structure'),$tr->governorate?->name],
                                            [__('training.delegation'),    $tr->delegation?->name],
                                            [__('training.training_type'), $tr->training_type ? __('training.type_' . $tr->training_type) : null],
                                            [__('training.training_kind'), $tr->training_kind ? __('training.kind_' . $tr->training_kind) : null],
                                            [__('training.notes'),         $tr->notes],
                                        ] as [$label, $value])
                                            @if ($value)
                                            <tr>
                                                <td class="py-2 pe-4 font-bold text-secondary w-40 shrink-0 align-top">{{ $label }}</td>
                                                <td class="py-2 text-on-surface">{{ $value }}</td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Schedule + Attendance --}}
                        @if ($tr->training_start_date)
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                            <div class="p-5 md:px-6 pb-4 border-b border-surface-dim">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">calendar_today</span>
                                    {{ __('training.schedule_title') }}
                                </h3>
                            </div>
                            <div class="p-5 md:px-6">
                                <div class="grid grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-1">{{ __('training.training_start_date') }}</p>
                                        <p class="text-sm font-semibold text-on-surface">{{ $tr->training_start_date->format('d/m/Y') }}</p>
                                    </div>
                                    @if ($tr->training_start_time)
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-1">{{ __('training.training_start_time') }}</p>
                                        <p class="text-sm font-semibold text-on-surface" dir="ltr">{{ $tr->training_start_time->format('H:i') }}</p>
                                    </div>
                                    @endif
                                    @if ($tr->training_duration_days)
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-1">{{ __('training.training_duration_days') }}</p>
                                        <p class="text-sm font-semibold text-on-surface">{{ $tr->training_duration_days }} {{ __('training.fee_weeks') }}</p>
                                    </div>
                                    @endif
                                </div>

                                @if ($tr->sessions->isNotEmpty())
                                <div class="border-t border-surface-dim pt-4">
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

                        {{-- Result --}}
                        @if ($tr->result)
                        <div class="bg-surface-container-lowest border {{ $tr->result === 'passed' ? 'border-emerald-200 dark:border-emerald-800/40' : 'border-red-200 dark:border-red-800/40' }} rounded-lg shadow-sm overflow-hidden">
                            <div class="border-s-4 {{ $tr->result === 'passed' ? 'border-s-emerald-500' : 'border-s-red-600' }} p-5 md:px-6">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="material-symbols-outlined text-{{ $tr->result === 'passed' ? 'emerald' : 'red' }}-600 text-2xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">
                                        {{ $tr->result === 'passed' ? 'workspace_premium' : 'cancel' }}
                                    </span>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-secondary">{{ __('training.result_title') }}</p>
                                        <h3 class="text-sm font-extrabold text-on-surface uppercase">{{ __('training.result_' . $tr->result) }}</h3>
                                    </div>
                                </div>
                                @if ($tr->result_note)
                                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $tr->result_note }}</p>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- Documents --}}
                        @if ($documentTypes->isNotEmpty())
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                            <div class="p-5 md:px-6 pb-4 border-b border-surface-dim">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">attach_file</span>
                                        {{ __('training.documents_title') }}
                                    </h3>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[11px] font-bold {{ $isDocComplete ? 'text-emerald-700 dark:text-emerald-400' : 'text-secondary' }}">{{ $uploadedTypes }}/{{ $documentTypes->count() }}</span>
                                        <div class="w-20 h-1.5 rounded-full bg-surface-dim overflow-hidden">
                                            <div class="h-full {{ $isDocComplete ? 'bg-emerald-500' : 'bg-primary' }} rounded-full" style="width: {{ $docPct }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-5 md:px-6">
                                <ol class="space-y-3">
                                    @foreach ($documentTypes as $docType)
                                        @php
                                            $docFiles = $documentsByType->get($docType->id, collect())->filter(fn ($d) => $d->status !== 'archived');
                                            $hasDoc   = $docFiles->isNotEmpty();
                                        @endphp
                                        <li class="flex items-start gap-3 rounded-lg border {{ $hasDoc ? 'border-gov-300/60 bg-gov-50/40 dark:border-gov-800/30 dark:bg-gov-900/10' : 'border-surface-dim bg-surface-container-low/40' }} p-3 dark:border-gray-700">
                                            <span class="mt-0.5 flex h-7 w-7 flex-none items-center justify-center rounded-full {{ $hasDoc ? 'bg-gov-100 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300' : 'bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500' }}">
                                                @if ($hasDoc)
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                @else
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg>
                                                @endif
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-body-sm font-semibold {{ $hasDoc ? 'text-on-surface dark:text-gray-100' : 'text-secondary dark:text-gray-400' }}">
                                                    {{ $loop->iteration }}. {{ $docType->name }}
                                                    @if ($docType->is_conditional)<span class="ms-1.5 inline-flex items-center gap-0.5 text-[10px] font-bold text-accent-600 bg-accent-50 px-1.5 py-0.5 rounded">{{ __('training.conditional') }}</span>@endif
                                                </p>
                                                @if ($hasDoc)
                                                    <div class="mt-1.5 flex flex-wrap gap-2">
                                                        @foreach ($docFiles as $docFile)
                                                        <div class="inline-flex items-center gap-2 bg-surface-container-lowest border border-surface-dim rounded px-2.5 py-1.5">
                                                            <span class="text-[11px] text-secondary font-mono">{{ $docFile->original_name }}</span>
                                                            <span class="text-[10px] text-outline">{{ $fmtSize($docFile->size) }}</span>
                                                            <a href="{{ route('training.documents.download', $docFile) }}" class="ms-1 text-primary hover:text-primary-container transition-colors" title="{{ __('training.download_label') }}">
                                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                                            </a>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="text-[11px] text-red-500 dark:text-red-400 mt-0.5">{{ __('training.documents_not_uploaded') }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                        @endif

                        {{-- Timeline --}}
                        @if ($tr->events->isNotEmpty())
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden" x-data="{ open: false }">
                            <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between gap-3 p-5 md:px-6 pb-4 border-b border-surface-dim hover:bg-surface-container dark:hover:bg-gray-700/40 transition-colors text-start">
                                <div>
                                    <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">history</span>
                                        {{ __('training.timeline_title') }}
                                    </h3>
                                    <p class="text-xs text-secondary mt-0.5">{{ __('training.timeline_subtitle') }}</p>
                                </div>
                                <span class="text-[11px] text-secondary font-semibold flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">receipt_long</span>
                                    {{ __('training.events_count', ['n' => $tr->events->count()]) }}
                                </span>
                                <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open" x-cloak class="p-5 md:p-6 pt-5">
                                <div class="relative ps-6 sm:ps-8 space-y-6 before:absolute before:start-3 sm:before:start-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                                    @foreach ($tr->events->reverse()->values() as $event)
                                        @php [$chipCls, $icon, $chip, $rowBg] = $eventChip($event->type); @endphp
                                        <div class="relative">
                                            <div class="absolute -start-6 sm:-start-8 top-0 w-6 sm:w-8 flex items-center justify-center">
                                                <div class="w-5 h-5 rounded-full {{ $chipCls }} flex items-center justify-center ring-4 ring-surface-container-lowest shadow-xs">
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
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>{{-- /left col --}}

                    {{-- Right column --}}
                    <div class="lg:col-span-4 space-y-6">

                        {{-- Status summary card --}}
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg p-5 shadow-sm space-y-3">
                            <div class="flex items-center gap-2.5">
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full {{ $statusPillCls }}">
                                    <span class="material-symbols-outlined text-lg" aria-hidden="true">{{ $statusPillIcon }}</span>
                                </span>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-secondary">{{ __('training.idx_status') }}</p>
                                    <p class="text-sm font-extrabold text-on-surface">{{ $tr->statusLabel() }}</p>
                                </div>
                            </div>
                            <div class="border-t border-surface-dim pt-3 space-y-2 text-xs">
                                <div class="flex justify-between gap-2">
                                    <span class="text-secondary">{{ __('training.print_ref_label') }}</span>
                                    <span class="font-mono font-bold text-on-surface" dir="ltr">{{ $viewRef }}</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-secondary">{{ __('training.idx_submitted') }}</span>
                                    <span class="text-on-surface">{{ $tr->created_at->format('d/m/Y') }}</span>
                                </div>
                                @if ($tr->training_type)
                                <div class="flex justify-between gap-2">
                                    <span class="text-secondary">{{ __('training.training_type') }}</span>
                                    <span class="text-on-surface font-semibold">{{ __('training.type_' . $tr->training_type) }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Payment card --}}
                        @if ($tr->isPaid())
                        <div class="bg-surface-container-lowest border border-emerald-200 dark:border-emerald-800/40 rounded-lg p-5 shadow-sm">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-3 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-emerald-600 text-sm" aria-hidden="true">payments</span>
                                {{ __('training.payment_title') }}
                            </p>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between gap-2">
                                    <span class="text-secondary">{{ __('training.fee_amount') }}</span>
                                    <span class="font-mono font-bold text-on-surface" dir="ltr">{{ number_format($tr->fee_amount, 3) }} {{ __('training.fee_tnd') }}</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-secondary">{{ __('training.receipt_number') }}</span>
                                    <span class="font-mono font-bold text-on-surface">{{ $tr->receipt_number }}</span>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <span class="text-secondary">{{ __('training.paid_at') }}</span>
                                    <span class="text-on-surface">{{ $tr->paid_at->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-surface-dim">
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 dark:text-emerald-300">
                                    <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1" aria-hidden="true">check_circle</span>
                                    {{ __('training.status_payment_confirmed') }}
                                </span>
                            </div>
                        </div>
                        @elseif ($status === 'payment_pending')
                        <div class="bg-orange-50 border border-orange-200 dark:border-orange-800/40 dark:bg-orange-900/10 rounded-lg p-5 shadow-sm">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-orange-800 mb-2 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-orange-600 text-sm" aria-hidden="true">payments</span>
                                {{ __('training.payment_title') }}
                            </p>
                            <p class="text-xs text-orange-700 leading-relaxed">{{ __('training.payment_pending_banner') }}</p>
                        </div>
                        @endif

                        {{-- Certificate card --}}
                        @if ($tr->cert_status)
                        <div class="bg-surface-container-lowest border border-teal-200 dark:border-teal-800/40 rounded-lg p-5 shadow-sm">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-3 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-teal-600 text-sm" aria-hidden="true">workspace_premium</span>
                                {{ __('training.certificate_title') }}
                            </p>
                            <div class="space-y-2 text-xs">
                                @if ($tr->certificate_number)
                                <div class="flex justify-between gap-2">
                                    <span class="text-secondary">{{ __('training.certificate_number') }}</span>
                                    <span class="font-mono font-bold text-on-surface">{{ $tr->certificate_number }}</span>
                                </div>
                                @endif
                                @if ($tr->valid_until)
                                <div class="flex justify-between gap-2">
                                    <span class="text-secondary">{{ __('training.valid_until') }}</span>
                                    <span class="text-on-surface">{{ $tr->valid_until->format('d/m/Y') }}</span>
                                </div>
                                @endif
                                @if ($tr->delivery_method)
                                <div class="flex justify-between gap-2">
                                    <span class="text-secondary">{{ __('training.delivery_title') }}</span>
                                    <span class="text-on-surface">{{ __('training.delivery_method_' . $tr->delivery_method) }}</span>
                                </div>
                                @endif
                            </div>
                            <div class="mt-3 pt-3 border-t border-surface-dim">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-teal-100 text-teal-800 text-[11px] font-bold dark:bg-teal-900/40 dark:text-teal-300">
                                    {{ __('training.cert_status_' . $tr->cert_status) }}
                                </span>
                            </div>
                            @if ($tr->cert_status === 'ready' && $tr->isPickup())
                                <p class="mt-3 text-[11px] text-secondary leading-relaxed">{{ __('training.approved_next_hint') }}</p>
                            @endif
                        </div>
                        @endif

                    </div>{{-- /right col --}}
                </div>

            </div>{{-- /.dossier-screen --}}

            {{-- ═══ PRINT FICHE ═══ --}}
            <div class="print-sheet">
                <div class="fiche">
                    <div class="fiche-band">
                        <img src="/images/logo.png" alt="" class="fiche-band-logo">
                        <div>
                            <div class="fiche-band-org">{{ __('training.print_header_service', ['place' => $tr->governorate?->name ?? '—']) }}</div>
                            <div class="fiche-band-sub">{{ __('training.card_title') }}</div>
                        </div>
                        <div class="fiche-band-ref">
                            <div class="fiche-band-ref-label">{{ __('training.print_ref_label') }}</div>
                            <div class="fiche-band-ref-value" dir="ltr">{{ $viewRef }}</div>
                        </div>
                    </div>

                    <div class="fiche-title-row">
                        <div class="fiche-title">{{ __('training.print_title') }}</div>
                        <div class="fiche-date">{{ __('training.print_edited_on') }}: {{ $printDate }}</div>
                    </div>

                    <table class="fiche-table">
                        <tbody>
                            <tr><th>{{ __('training.nom_prenom') }}</th><td>{{ $tr->nom_prenom ?? '—' }}</td></tr>
                            <tr><th>{{ __('training.cin') }}</th><td>{{ $tr->cin ?? '—' }}</td></tr>
                            <tr><th>{{ __('training.phone') }}</th><td dir="ltr">{{ $tr->phone ?? '—' }}</td></tr>
                            <tr><th>{{ __('training.code_structure') }}</th><td>{{ $tr->governorate?->name ?? '—' }}</td></tr>
                            <tr><th>{{ __('training.delegation') }}</th><td>{{ $tr->delegation?->name ?? '—' }}</td></tr>
                            <tr><th>{{ __('training.training_type') }}</th><td>{{ $tr->training_type ? __('training.type_' . $tr->training_type) : '—' }}</td></tr>
                            <tr><th>{{ __('training.training_kind') }}</th><td>{{ $tr->training_kind ? __('training.kind_' . $tr->training_kind) : '—' }}</td></tr>
                            <tr><th>{{ __('training.idx_status') }}</th><td>{{ $tr->statusLabel() }}</td></tr>
                        </tbody>
                    </table>

                    @if ($tr->isPaid())
                    <table class="fiche-table">
                        <thead><tr><th colspan="2" style="background:#1a1d29;color:#fff;">{{ __('training.payment_title') }}</th></tr></thead>
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
                        @if ($tr->certificate_number)<p><strong>{{ __('training.certificate_number') }}:</strong> {{ $tr->certificate_number }}</p>@endif
                        @if ($tr->valid_until)<p><strong>{{ __('training.valid_until') }}:</strong> {{ $tr->valid_until->format('d/m/Y') }}</p>@endif
                        <p><strong>{{ __('training.idx_status') }}:</strong> {{ __('training.cert_status_' . $tr->cert_status) }}</p>
                    </div>
                    @endif

                    <div class="fiche-sign-row">
                        <div class="fiche-sign-col">
                            <div class="fiche-sign-name">{{ __('training.print_sign_applicant') ?? 'إمضاء طالب الطلب' }}</div>
                            <div class="fiche-sign-line">{{ $tr->nom_prenom }}</div>
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
