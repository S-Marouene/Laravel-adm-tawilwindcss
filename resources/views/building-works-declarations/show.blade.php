@php
    $notification->loadMissing('user');
    $status  = $notification->status;
    $viewRef = $notification->reference ?: \App\Support\Reference::provisional('works');

    /* ── Audit-log event proofs ── */
    $advancedEvent = $notification->events->last(fn ($e) => $e->type === 'advanced_to_consultation');
    $approvedEvent = $notification->events->last(fn ($e) => $e->type === 'approved');

    /* ── Stepper 3 steps: deposit → study (15 days) → approved ── */
    $currentStep = match ($status) {
        'draft'              => 1,
        'completed'          => 2,
        'under_consultation' => 2,
        'needs_correction'   => 2,
        'approved'           => 4, // sentinel: all steps done
        'cancelled'          => match (true) {
            $approvedEvent !== null  => 4,
            $advancedEvent !== null
                || $notification->events->contains(fn ($e) => $e->type === 'correction_requested') => 2,
            default => 2,
        },
        default => null,
    };
    $isCancelled = $status === 'cancelled';

    $stepDates = [];
    $stepDates[1] = $notification->created_at;
    $stepDates[2] = $advancedEvent?->created_at;
    $stepDates[3] = $approvedEvent?->created_at;

    $steps = [];
    $hadCorrection = $notification->events->contains(fn ($e) => $e->type === 'correction_requested');
    $cancelledAt   = $isCancelled ? min($currentStep, 3) : null;

    foreach ([1, 2, 3] as $i) {
        if ($isCancelled) {
            if ($i < $cancelledAt)       $state = 'done';
            elseif ($i === $cancelledAt) $state = 'cancelled';
            else                         $state = 'pending';
        } else {
            if ($currentStep === 4 || $i < $currentStep) $state = 'done';
            elseif ($i === $currentStep)                 $state = $status === 'needs_correction' ? 'error' : 'current';
            else                                         $state = 'pending';
        }
        if ($state === 'done' && $i === 2 && $hadCorrection) $state = 'warning';

        $sub = match (true) {
            $state === 'cancelled'            => __('building-works-declarations.stepper_cancelled'),
            $state === 'warning' && $i === 2  => __('building-works-declarations.stepper_had_correction'),
            $status === 'needs_correction' && $state === 'error' => __('building-works-declarations.stepper_correction'),
            $state === 'done'                 => __('building-works-declarations.stepper_done'),
            $state === 'pending'              => __('building-works-declarations.stepper_pending'),
            $i === 1                          => __('building-works-declarations.draft_notice'),
            $i === 2 && $status === 'completed'          => __('building-works-declarations.stepper_verify_pending'),
            $i === 2 && $status === 'under_consultation' => __('building-works-declarations.stepper_verify_waiting'),
            $i === 3                          => __('building-works-declarations.stepper_approved'),
            default                           => __('building-works-declarations.stepper_current'),
        };
        $alert  = in_array($state, ['error', 'cancelled']);
        $steps[] = [
            'num' => $i, 'title' => __('building-works-declarations.stepper_' . $i),
            'sub' => $sub, 'state' => $state, 'alert' => $alert,
        ];
    }

    $doneSteps = collect($steps)->whereIn('state', ['done', 'warning'])->count();
    $progress  = (int) round($doneSteps / 3 * 100);

    /* ── Status pill ── */
    [$statusPillCls, $statusPillIcon] = match ($status) {
        'draft', 'completed'  => ['bg-surface-container-high text-secondary', 'edit_note'],
        'under_consultation'  => ['bg-blue-100 text-blue-800', 'manage_search'],
        'needs_correction'    => ['bg-red-100 text-red-800', 'error'],
        'approved'            => ['bg-emerald-100 text-emerald-800', 'task_alt'],
        'cancelled'           => ['bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300', 'cancel'],
        default               => ['bg-surface-container-high text-secondary', 'info'],
    };

    /* ── Hero alert type ── */
    $heroAlert = match (true) {
        $status === 'cancelled'          => 'error',
        $status === 'draft'              => 'draft',
        $status === 'needs_correction'   => 'error',
        $status === 'under_consultation' => 'info',
        $status === 'approved'           => 'success',
        default                          => 'neutral', // completed
    };

    /* ── Documents completeness ── */
    $activeDocuments = $notification->documents->filter(fn ($d) => $d->status !== 'archived');
    $documentsByType = $activeDocuments->groupBy('document_type_id');
    $fmtSize = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1) . ' Mo' : number_format($b / 1024, 0) . ' Ko';
    $uploadedTypes = $documentsByType->filter(fn ($files, $typeId) => $documentTypes->contains('id', $typeId) && $files->isNotEmpty())->count();
    $docPct = $documentTypes->count() > 0 ? (int) round(min($uploadedTypes, $documentTypes->count()) / $documentTypes->count() * 100) : 0;
    $correction = $notification->lastCorrectionData();

    /* ── Timeline chip helper ── */
    $eventChip = fn ($type) => match (true) {
        in_array($type, ['submitted', 'resubmitted'])
            => ['bg-secondary-container text-on-secondary-container', 'post_add', __('building-works-declarations.chip_depot'), 'bg-surface-dim text-secondary'],
        $type === 'advanced_to_consultation'
            => ['bg-violet-600 text-white', 'sync_alt', __('building-works-declarations.chip_phase'), 'bg-surface-dim text-secondary'],
        $type === 'correction_requested'
            => ['bg-amber-500 text-white', 'edit_note', __('building-works-declarations.chip_correction'), 'bg-amber-100 text-amber-800'],
        $type === 'approved'
            => ['bg-emerald-600 text-white', 'task_alt', __('building-works-declarations.chip_favorable'), 'bg-emerald-100 text-emerald-800'],
        default
            => ['bg-red-600 text-white', 'error', __('building-works-declarations.chip_alert'), 'bg-red-100 text-red-700'],
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
    .fiche { font-family: var(--font-sans,'Roboto',Arial,sans-serif); font-size: 11.5px; line-height: 1.5; color: #1a1d29; background: #fff; }
    .fiche-band { background: #9e001f; color: #fff; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; gap: 16px; border-radius: 3px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .fiche-band-logo { width: 46px; height: 46px; object-fit: contain; border-radius: 4px; background: #fff; padding: 3px; }
    .fiche-band-org { font-size: 14.5px; font-weight: 800; letter-spacing: .02em; line-height: 1.25; }
    .fiche-band-ref { text-align: end; line-height: 1.3; }
    .fiche-band-ref-label { font-size: 9px; text-transform: uppercase; letter-spacing: .14em; opacity: .85; font-weight: 600; }
    .fiche-band-ref-value { font-family: 'Roboto Mono',Menlo,Consolas,monospace; font-size: 15px; font-weight: 700; letter-spacing: .03em; }
    .fiche-title-row { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin: 14px 0 16px; padding-bottom: 8px; border-bottom: 2px solid #1a1d29; }
    .fiche-title { font-size: 15px; font-weight: 800; letter-spacing: .01em; }
    .fiche-date { font-size: 9.5px; color: #6b7280; text-align: end; line-height: 1.4; }
    .fiche-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .fiche-table th, .fiche-table td { border: 1px solid #c7cad1; padding: 6px 9px; text-align: start; vertical-align: top; font-size: 11px; }
    .fiche-table th { background: #f4f4f7; font-weight: 700; width: 34%; }
    .fiche-sign-row { display: flex; gap: 28px; margin: 6px 0 10px; align-items: flex-end; }
    .fiche-sign-col { flex: 1; text-align: center; }
    .fiche-sign-name { font-size: 10.5px; font-weight: 700; margin-bottom: 46px; text-transform: uppercase; letter-spacing: .04em; }
    .fiche-sign-line { border-top: 1px solid #6b7280; margin: 0 8px; padding-top: 3px; font-size: 9px; color: #6b7280; }
    .fiche-foot { margin-top: 10px; padding-top: 7px; border-top: 1px solid #c7cad1; display: flex; justify-content: space-between; gap: 12px; font-size: 9px; color: #6b7280; }
</style>

<x-app-layout>
    <main class="flex-1 w-full mx-auto sm:px-6 lg:px-8 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-0">

            {{-- ══════════════ SCREEN CONTENT ══════════════ --}}
            <div class="dossier-screen no-print">

                {{-- ── Needs correction banner ── --}}
                @if ($status === 'needs_correction')
                <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden mb-6 no-print">
                    <div class="border-s-4 border-s-red-600 p-5">
                        <div class="flex items-start gap-3.5 mb-3">
                            <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">assignment_late</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('building-works-declarations.correction_title') }}</h2>
                                    <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded">{{ __('building-works-declarations.chip_alert') }}</span>
                                </div>
                            </div>
                        </div>
                        @if ($notification->admin_note)
                        <div class="rounded border border-red-200 bg-red-50 dark:bg-red-900/10 px-4 py-3 mb-4">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                                {{ __('building-works-declarations.admin_note_label') }}
                            </p>
                            <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $notification->admin_note }}</p>
                        </div>
                        @endif
                        @php
                            $worksFieldLabels = [
                                'owner_cin'         => __('building-works-declarations.owner_cin'),
                                'owner_name'        => __('building-works-declarations.owner_name'),
                                'company_name'      => __('building-works-declarations.company_name'),
                                'jort_reference'    => __('building-works-declarations.jort_reference'),
                                'phone'             => __('building-works-declarations.phone'),
                                'governorate_id'    => __('building-works-declarations.code_structure'),
                                'delegation_id'     => __('building-works-declarations.delegation'),
                                'property_address'  => __('building-works-declarations.property_address'),
                                'works_type'        => __('building-works-declarations.works_type'),
                                'works_start_date'  => __('building-works-declarations.works_start_date'),
                                'works_description' => __('building-works-declarations.works_description'),
                                'notes'             => __('building-works-declarations.notes'),
                            ];
                            $correctionDocs = ! empty($correction['docs_to_fix'])
                                ? $documentTypes->filter(fn ($t) => in_array($t->id, $correction['docs_to_fix']))
                                : collect();
                        @endphp
                        @if (!empty($correction['fields_to_fix']) || $correctionDocs->isNotEmpty())
                        <div class="mb-4 flex flex-wrap gap-1.5">
                            @foreach ($correction['fields_to_fix'] ?? [] as $fk)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700/40">
                                    <span class="material-symbols-outlined text-[11px]" aria-hidden="true">edit</span>
                                    {{ $worksFieldLabels[$fk] ?? $fk }}
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
                        <a href="{{ route('building-works-declarations.correct', $notification) }}"
                           class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">edit_note</span>
                            {{ __('building-works-declarations.fix_and_resubmit') }}
                        </a>
                    </div>
                </section>
                @endif

                {{-- ── Draft continue banner ── --}}
                @if ($status === 'draft')
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden mb-6 no-print">
                    <div class="border-s-4 border-s-red-600 p-5 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl" aria-hidden="true">edit_note</span>
                            </div>
                            <div>
                                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('building-works-declarations.status_draft') }}</h2>
                                <p class="text-body-sm text-secondary mt-0.5">{{ __('building-works-declarations.draft_notice') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('building-works-declarations.step2.show', $notification) }}"
                            class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">play_arrow</span>
                            {{ __('building-works-declarations.continue') }}
                        </a>
                    </div>
                </section>
                @endif

                {{-- ══════════════ STEPPER ══════════════ --}}
                <section class="bg-surface-container-lowest border {{ $isCancelled ? 'border-red-200 dark:border-red-800/40' : 'border-surface-dim' }} rounded-lg shadow-sm mb-6 overflow-hidden">
                    @if ($isCancelled)
                    <div class="border-b border-red-200 dark:border-red-800/40 bg-red-50 dark:bg-red-900/20 border-s-4 border-s-red-600 p-5 md:px-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">cancel</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('building-works-declarations.cancelled_banner') }}</h3>
                                <p class="text-body-sm text-secondary mt-1">{{ __('building-works-declarations.status_cancelled') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="p-5 md:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-surface-dim">
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">route</span>
                                {{ __('building-works-declarations.stepper_title') }}
                            </h3>
                            <div class="flex items-center gap-3 flex-1 sm:flex-none sm:min-w-64">
                                @if ($isCancelled)
                                <span class="text-xs font-bold text-white bg-red-600 px-2 py-0.5 rounded whitespace-nowrap">{{ __('building-works-declarations.stepper_cancelled') }}</span>
                                @else
                                <span class="text-xs font-bold text-primary bg-primary-fixed/40 px-2 py-0.5 rounded whitespace-nowrap">
                                    {{ __('building-works-declarations.phase_instruction', ['step' => $currentStep ? min($currentStep, 3) : 1]) }}
                                </span>
                                @endif
                                <div class="hidden sm:block flex-1 h-1.5 bg-surface-dim/50 rounded-full overflow-hidden">
                                    <div class="h-full {{ $isCancelled ? 'bg-red-500' : 'bg-gradient-to-r from-primary-container to-primary' }} rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                                </div>
                                <span class="text-[11px] font-mono font-bold text-secondary hidden sm:inline" dir="ltr">{{ $progress }}%</span>
                            </div>
                        </div>

                        {{-- Steps connected by a horizontal track --}}
                        <div class="relative grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-3">
                            <div class="hidden md:block absolute top-5 [inset-inline-start:10%] [inset-inline-end:10%] h-0.5 bg-surface-dim" aria-hidden="true"></div>

                            @foreach ($steps as $step)
                                <div class="relative flex md:flex-col items-start md:items-center gap-3 md:gap-2 md:text-center">
                                    @if ($step['state'] === 'done')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center shadow-sm ring-4 ring-primary/20">
                                            <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1" aria-hidden="true">check</span>
                                        </span>
                                    @elseif ($step['state'] === 'warning')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center shadow-sm ring-4 ring-primary/20">
                                            <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1" aria-hidden="true">check</span>
                                        </span>
                                    @elseif ($step['state'] === 'current')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center text-sm font-black shadow-sm ring-4 ring-primary/20 animate-pulse">
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
                                                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse" aria-hidden="true"></span>
                                            @elseif ($step['state'] === 'error' || $step['state'] === 'cancelled')
                                                <span class="text-[10px] uppercase font-black text-red-800 bg-red-100 px-1.5 py-px rounded">{{ __('building-works-declarations.chip_alert') }}</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] mt-0.5 leading-snug {{ $step['alert'] ? 'text-red-700 font-medium' : ($step['state'] === 'current' ? 'text-primary font-medium' : ($step['state'] === 'done' || $step['state'] === 'warning' ? 'text-primary/80 font-medium' : 'text-secondary')) }}">
                                            {{ $step['sub'] }}
                                        </p>
                                        <p class="text-[10px] mt-1 font-semibold {{ in_array($step['state'], ['done','warning']) ? 'text-primary/80' : ($step['state'] === 'current' ? 'text-primary' : 'text-secondary') }}" dir="ltr">
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

                {{-- ══════════════ HERO — reference + status + key facts ══════════════ --}}
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm mb-6 overflow-hidden">
                    <div class="h-1 bg-gradient-to-r from-primary via-primary-container/70 to-transparent" aria-hidden="true"></div>

                    <div class="p-5 md:p-6">
                        {{-- Top row: title + actions --}}
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                            <div class="min-w-0">
                                <h1 class="text-headline-md font-headline-md text-on-surface tracking-tight leading-tight" dir="ltr">
                                    {{ __('building-works-declarations.card_title') }} · <span class="font-mono">{{ $viewRef }}</span>
                                </h1>
                                <p class="text-xs text-secondary mt-1 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">calendar_today</span>
                                    {{ __('building-works-declarations.deposited_on', ['date' => $notification->created_at->isoFormat('D MMMM YYYY')]) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2.5 shrink-0">
                                <span class="inline-flex items-center gap-1.5 text-label-caps font-label-caps px-2.5 py-1 rounded font-bold {{ $statusPillCls }}">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $statusPillIcon }}</span>
                                    {{ __('building-works-declarations.status_' . $status) }}
                                </span>
                                <a href="{{ route('building-works-declarations.index') }}"
                                    class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-2.5 rounded transition-colors">
                                    <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                                </a>
                                <button type="button" onclick="window.print()"
                                    class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                                </button>
                            </div>
                        </div>

                        {{-- Key facts grid --}}
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">fact_check</span>
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-secondary">{{ __('building-works-declarations.details_title') }}</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                            @php
                                $ownerLabel = $notification->isMoral() ? __('building-works-declarations.company_name') : __('building-works-declarations.owner_name');
                                $ownerValue = $notification->isMoral() ? $notification->company_name : $notification->owner_name;
                                $keyFacts = [
                                    ['badge', $ownerLabel, $ownerValue, false],
                                    ['fingerprint', __('building-works-declarations.owner_cin'), $notification->owner_cin, true],
                                    ['call', __('building-works-declarations.phone'), $notification->phone, true],
                                    ['mail', __('rescue_certificate.email'), $notification->user?->email, true],
                                    ['account_balance', __('building-works-declarations.code_structure'), $notification->governorate?->name, false],
                                    ['location_city', __('building-works-declarations.delegation'), $notification->delegation?->name, false],
                                    ['construction', __('building-works-declarations.works_type'), $notification->works_type, false],
                                    ['place', __('building-works-declarations.property_address'), $notification->property_address, false],
                                ];
                            @endphp
                            @foreach ($keyFacts as [$icon, $label, $val, $isLtr])
                                @if (filled($val))
                                <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                    <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">{{ $icon }}</span>
                                    <div class="min-w-0">
                                        <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ $label }}</span>
                                        <span class="block text-xs font-bold text-on-surface truncate {{ $isLtr ? 'num-ltr font-mono' : '' }}" {{ $isLtr ? 'dir="ltr"' : '' }} title="{{ $val }}">{{ $val }}</span>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>

                        {{-- Admin note (if present and not in correction state) --}}
                        @if ($notification->admin_note && $status !== 'needs_correction')
                        <div class="mt-4 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-3">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-1 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">sticky_note_2</span>
                                {{ __('building-works-declarations.admin_note_label') }}
                            </p>
                            <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $notification->admin_note }}</p>
                        </div>
                        @endif

                        <div class="h-2"></div>

                        {{-- Contextual status alert --}}
                        @if ($heroAlert === 'info')
                            <div class="flex items-start gap-2.5 bg-blue-50 border border-blue-200 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-blue-600 text-base mt-px" aria-hidden="true">manage_search</span>
                                <div class="font-bold text-blue-900 leading-relaxed">{{ __('building-works-declarations.under_consultation_banner') }}</div>
                            </div>
                        @elseif ($heroAlert === 'success')
                            <div class="flex items-start gap-2.5 bg-emerald-50 border border-emerald-200 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-emerald-600 text-base mt-px" aria-hidden="true">task_alt</span>
                                <div class="font-bold text-emerald-900 leading-relaxed">{{ __('building-works-declarations.approved_banner') }}</div>
                            </div>
                        @elseif ($heroAlert === 'error')
                            <div class="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">notification_important</span>
                                <div class="font-bold text-red-900 leading-relaxed">
                                    {{ $status === 'needs_correction' ? __('building-works-declarations.needs_correction_banner') : __('building-works-declarations.cancelled_banner') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'draft')
                            <div class="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">edit_note</span>
                                <div class="font-bold text-red-900 leading-relaxed">{{ __('building-works-declarations.draft_notice') }}</div>
                            </div>
                        @else
                            <div class="flex items-start gap-2.5 bg-surface-container-low border border-surface-dim rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-secondary text-base mt-px" aria-hidden="true">hourglass_top</span>
                                <div class="font-bold text-on-surface leading-relaxed">{{ __('building-works-declarations.under_review_banner') }}</div>
                            </div>
                        @endif

                        {{-- Procedure info pills --}}
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-400">
                                <span class="material-symbols-outlined text-xs" aria-hidden="true">verified</span>
                                {{ __('building-works-declarations.free_info') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-surface-dim bg-surface-container-low px-2.5 py-1 text-[11px] font-semibold text-secondary">
                                <span class="material-symbols-outlined text-xs" aria-hidden="true">schedule</span>
                                {{ __('building-works-declarations.delay_info') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-surface-dim bg-surface-container-low px-2.5 py-1 text-[11px] font-semibold text-secondary">
                                <span class="material-symbols-outlined text-xs" aria-hidden="true">call</span>
                                {{ __('building-works-declarations.phone_followup') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-surface-dim bg-surface-container-low px-2.5 py-1 text-[11px] font-semibold text-secondary">
                                <span class="material-symbols-outlined text-xs" aria-hidden="true">all_inclusive</span>
                                {{ __('building-works-declarations.validity_info') }}
                            </span>
                        </div>
                    </div>
                </section>

                {{-- ══════════════ WORKS DESCRIPTION ══════════════ --}}
                @if (filled($notification->works_description) || filled($notification->works_start_date) || filled($notification->notes))
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm mb-6 overflow-hidden">
                    <div class="border-s-4 border-s-primary p-5 space-y-3">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">construction</span>
                            {{ __('building-works-declarations.section_works') }}
                        </h3>
                        @if (filled($notification->works_start_date))
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-secondary font-semibold">{{ __('building-works-declarations.works_start_date') }} :</span>
                            <span class="font-bold text-on-surface" dir="ltr">{{ $notification->works_start_date->format('d/m/Y') }}</span>
                        </div>
                        @endif
                        @if (filled($notification->works_description))
                        <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $notification->works_description }}</p>
                        @endif
                        @if (filled($notification->notes))
                        <div class="rounded-lg border border-surface-dim bg-surface-container-low px-4 py-3 mt-2">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-1">{{ __('building-works-declarations.notes') }}</p>
                            <p class="text-body-sm text-secondary whitespace-pre-line leading-relaxed">{{ $notification->notes }}</p>
                        </div>
                        @endif
                    </div>
                </section>
                @endif

                {{-- ══════════════ JORT reference (moral person) ══════════════ --}}
                @if ($notification->isMoral() && filled($notification->jort_reference))
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm mb-6 overflow-hidden">
                    <div class="p-5 flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-xl mt-px shrink-0" aria-hidden="true">article</span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-0.5">{{ __('building-works-declarations.jort_reference') }}</p>
                            <p class="text-body-sm font-semibold text-on-surface" dir="ltr">{{ $notification->jort_reference }}</p>
                        </div>
                    </div>
                </section>
                @endif

                {{-- ══════════════ DOCUMENTS ══════════════ --}}
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm mb-6 overflow-hidden">
                    <div class="border-b border-surface-dim px-5 py-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">folder_open</span>
                                {{ __('building-works-declarations.documents_title') }}
                            </h3>
                            <p class="text-xs text-secondary mt-0.5">
                                {{ $activeDocuments->count() }} {{ __('building-works-declarations.files_uploaded') }}
                                @if ($archivedDocuments->isNotEmpty()) — {{ $archivedDocuments->count() }} {{ __('building-works-declarations.files_archived') }} @endif
                            </p>
                        </div>
                        {{-- completeness ring --}}
                        @if ($documentTypes->count() > 0)
                        <div class="flex items-center gap-2 shrink-0">
                            <div class="h-2 w-24 bg-surface-dim rounded-full overflow-hidden">
                                <div class="h-full {{ $docPct >= 100 ? 'bg-emerald-500' : 'bg-primary' }} rounded-full transition-all" style="width: {{ $docPct }}%"></div>
                            </div>
                            <span class="text-[11px] font-mono font-bold {{ $docPct >= 100 ? 'text-emerald-600' : 'text-secondary' }}" dir="ltr">{{ $uploadedTypes }}/{{ $documentTypes->count() }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="divide-y divide-surface-dim dark:divide-gray-700/50">
                        @forelse ($documentTypes as $docType)
                            @php $files = $documentsByType->get($docType->id, collect()); @endphp
                            <div class="px-5 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-7 w-7 flex-none items-center justify-center rounded-full {{ $files->isNotEmpty() ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500' }}">
                                            @if ($files->isNotEmpty())
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            @else
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">upload_file</span>
                                            @endif
                                        </span>
                                        <div>
                                            <p class="text-xs font-semibold text-on-surface dark:text-gray-100">{{ $docType->name }}</p>
                                            @if ($files->isEmpty())
                                                <p class="text-[10px] text-outline dark:text-gray-500">{{ __('building-works-declarations.documents_not_uploaded') }}</p>
                                            @elseif ($files->count() > 1)
                                                <p class="text-[10px] text-secondary">{{ $files->count() }} {{ __('building-works-declarations.files_count') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($docType->is_conditional)
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('building-works-declarations.document_conditional') }}</span>
                                    @endif
                                </div>
                                @if ($files->isNotEmpty())
                                <div class="mt-2 ms-10 space-y-1.5">
                                    @foreach ($files as $doc)
                                        <div class="flex items-center justify-between gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-low px-3 py-2">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="material-symbols-outlined {{ str_contains((string) $doc->mime_type, 'pdf') ? 'text-red-500' : 'text-blue-500' }} text-base shrink-0" aria-hidden="true">{{ str_contains((string) $doc->mime_type, 'pdf') ? 'picture_as_pdf' : 'description' }}</span>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-semibold text-on-surface truncate">{{ $doc->original_name }}</p>
                                                    <p class="text-[10px] text-secondary">{{ $fmtSize($doc->size) }} — {{ $doc->created_at->format('d/m/Y') }}</p>
                                                </div>
                                            </div>
                                            <a href="{{ route('building-works-declarations.documents.download', [$notification, $doc]) }}"
                                               class="w-7 h-7 rounded bg-primary/10 text-primary hover:bg-primary hover:text-on-primary flex items-center justify-center shrink-0 transition-colors"
                                               title="{{ __('building-works-declarations.download_label') }}">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-xs text-outline">{{ __('building-works-declarations.admin_no_doctypes') }}</div>
                        @endforelse
                    </div>

                    {{-- Archived document versions --}}
                    @if ($archivedDocuments->isNotEmpty())
                    <div class="border-t border-surface-dim px-5 py-4" x-data="{ archOpen: false }">
                        <button type="button" @click="archOpen = !archOpen" class="w-full flex items-center gap-2 text-start">
                            <span class="material-symbols-outlined text-base text-secondary" aria-hidden="true">inventory_2</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-secondary flex-1">{{ __('building-works-declarations.archive_title') }} ({{ $archivedDocuments->count() }})</span>
                            <svg class="w-4 h-4 text-secondary transition-transform" :class="archOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="archOpen" x-cloak class="mt-3">
                            <p class="text-xs text-secondary mb-2 leading-relaxed">{{ __('building-works-declarations.archive_desc') }}</p>
                            <div class="space-y-1.5">
                                @foreach ($archivedDocuments as $doc)
                                    <div class="flex items-center justify-between gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-low px-3 py-2">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="material-symbols-outlined text-outline text-base shrink-0" aria-hidden="true">inventory_2</span>
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-secondary truncate">{{ $doc->original_name }}</p>
                                                <p class="text-[10px] text-outline">{{ $doc->documentType?->name }} — {{ $doc->created_at->format('d/m/Y') }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ route('building-works-declarations.documents.download', [$notification, $doc]) }}"
                                           class="w-7 h-7 rounded bg-surface-container text-secondary hover:bg-primary hover:text-on-primary flex items-center justify-center shrink-0 transition-colors"
                                           title="{{ __('building-works-declarations.download_label') }}">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </section>

                {{-- ══════════════ CORRECTION HISTORY ══════════════ --}}
                @if (!empty($allCorrectionRounds))
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm mb-6 overflow-hidden" x-data="{ histOpen: false }">
                    <button type="button" @click="histOpen = !histOpen" class="w-full flex items-center gap-3 px-5 py-4 text-start border-b border-surface-dim hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined text-amber-600 text-base" aria-hidden="true">history</span>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">{{ __('building-works-declarations.timeline_title') }} ({{ count($allCorrectionRounds) }})</span>
                        <svg class="w-4 h-4 text-secondary transition-transform" :class="histOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="histOpen" x-cloak class="divide-y divide-surface-dim">
                        @foreach ($allCorrectionRounds as $round)
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700 mb-2 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">sync_problem</span>
                                {{ __('building-works-declarations.old_version_title', ['round' => $round['round']]) }}
                                @if ($round['date'])
                                    <span class="font-normal normal-case tracking-normal text-secondary" dir="ltr">{{ $round['date']->format('d/m/Y') }}</span>
                                @endif
                            </p>
                            @if ($round['note'])
                                <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs mb-3">
                                    <p class="font-bold text-amber-900 mb-1">{{ __('building-works-declarations.admin_note_label') }}</p>
                                    <p class="text-amber-800 whitespace-pre-line">{{ is_array($round['note']) ? ($round['note'][app()->getLocale()] ?? $round['note']['fr'] ?? $round['note']['ar'] ?? '') : $round['note'] }}</p>
                                </div>
                            @endif
                            @if (!empty($round['previous']))
                                <div class="rounded border border-surface-dim bg-surface-container-low px-3 py-2 text-xs space-y-1">
                                    <p class="font-bold text-secondary">{{ __('building-works-declarations.previous_values') }}</p>
                                    @foreach ($round['previous'] as $field => $val)
                                        @if (filled($val))
                                        <div class="flex gap-2">
                                            <span class="text-secondary min-w-[80px] shrink-0">{{ $worksFieldLabels[$field] ?? $field }}</span>
                                            <span class="font-semibold text-on-surface">{{ $val }}</span>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            @if (!empty($round['archived_files']))
                                <div class="mt-2 space-y-1">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-secondary">{{ __('building-works-declarations.archived_files_title') }}</p>
                                    @foreach ($round['archived_files'] as $af)
                                        <div class="flex items-center gap-2 text-xs text-secondary">
                                            <span class="material-symbols-outlined text-outline text-sm" aria-hidden="true">inventory_2</span>
                                            <span class="truncate">{{ $af['file'] }}</span>
                                            <span class="text-outline shrink-0">{{ $af['type'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- ══════════════ TIMELINE ══════════════ --}}
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden mb-6">
                    <div class="border-b border-surface-dim px-5 py-4 flex items-center justify-between gap-3">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">timeline</span>
                            {{ __('building-works-declarations.timeline_title') }}
                        </h3>
                        <span class="text-[11px] font-semibold text-secondary">{{ $notification->events->count() }} {{ __('building-works-declarations.timeline_events_count') }}</span>
                    </div>
                    <div class="px-5 py-4">
                        @forelse ($notification->events as $event)
                            @php [$chipBg, $chipIcon, $chipLabel, $badgeCls] = $eventChip($event->type); @endphp
                            @if (!$loop->first)
                                <div class="ms-[3px] my-1.5 w-0.5 h-5 bg-surface-dim dark:bg-gray-600"></div>
                            @endif
                            <div class="flex items-start gap-3" @if (in_array($event->type, ['correction_requested','cancelled'])) x-data="{ open: false }" @endif>
                                <span class="mt-1 flex-none w-2 h-2 rounded-full {{ $event->typeDot() }}"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5 mb-0.5">
                                        <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-bold {{ $chipBg }}">
                                            <span class="material-symbols-outlined text-[11px]" aria-hidden="true">{{ $chipIcon }}</span>
                                            {{ $chipLabel }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold {{ $badgeCls }}">
                                            {{ $event->typeLabel() }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-secondary">
                                        {{ $event->actor?->name ?? __('building-works-declarations.system_actor') }}
                                        · {{ $event->created_at->format('d/m/Y, H:i') }}
                                        <span class="opacity-60">({{ $event->created_at->diffForHumans() }})</span>
                                    </p>
                                    @if ($event->note && in_array($event->type, ['correction_requested','cancelled']))
                                        <button type="button" @click="open = !open"
                                            class="mt-1.5 inline-flex items-center gap-1.5 rounded-lg border text-xs font-semibold px-2.5 py-1.5 transition focus:outline-none {{ $event->type === 'correction_requested' ? 'border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100' }}">
                                            <span class="material-symbols-outlined text-xs" aria-hidden="true">chat</span>
                                            <span x-text="open ? '{{ __('action.hide') }}' : '{{ __('action.view') }}'"></span>
                                        </button>
                                        <div x-show="open" x-cloak x-transition class="mt-2 rounded-lg px-3 py-2 text-xs {{ $event->type === 'correction_requested' ? 'border border-amber-200 bg-amber-50 text-amber-800' : 'border border-red-200 bg-red-50 text-red-800' }}">
                                            <p class="font-semibold mb-1">{{ __('building-works-declarations.admin_note_label') }}</p>
                                            <p class="whitespace-pre-line">{{ $event->note }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-outline text-center py-4">{{ __('building-works-declarations.timeline_empty') }}</p>
                        @endforelse
                    </div>
                </section>

            </div>{{-- /.dossier-screen --}}

            {{-- ══════════════ PRINT SHEET ══════════════ --}}
            <div class="print-sheet" aria-hidden="true">
                <div class="fiche">
                    <div class="fiche-band">
                        <div>
                            <div class="fiche-band-org">{{ config('app.name', 'ONPC') }}</div>
                            <div class="fiche-band-sub">{{ __('building-works-declarations.index_title') }}</div>
                        </div>
                        <div class="fiche-band-ref">
                            <div class="fiche-band-ref-label">{{ __('building-works-declarations.card_title') }}</div>
                            <div class="fiche-band-ref-value" dir="ltr">{{ $viewRef }}</div>
                        </div>
                    </div>
                    <div class="fiche-title-row">
                        <div class="fiche-title">{{ __('building-works-declarations.card_title') }}</div>
                        <div class="fiche-date">{{ $printDate }}</div>
                    </div>
                    <table class="fiche-table">
                        <tbody>
                            <tr><th>{{ __('building-works-declarations.owner_type_label') }}</th><td>{{ $notification->isMoral() ? __('building-works-declarations.owner_moral') : __('building-works-declarations.owner_natural') }}</td></tr>
                            <tr><th>{{ $notification->isMoral() ? __('building-works-declarations.company_name') : __('building-works-declarations.owner_name') }}</th><td>{{ $notification->owner_name ?? '—' }}</td></tr>
                            <tr><th>{{ __('building-works-declarations.owner_cin') }}</th><td>{{ $notification->owner_cin ?? '—' }}</td></tr>
                            <tr><th>{{ __('building-works-declarations.phone') }}</th><td>{{ $notification->phone ?? '—' }}</td></tr>
                            <tr><th>{{ __('building-works-declarations.code_structure') }}</th><td>{{ $notification->governorate?->name ?? '—' }}</td></tr>
                            <tr><th>{{ __('building-works-declarations.delegation') }}</th><td>{{ $notification->delegation?->name ?? '—' }}</td></tr>
                            <tr><th>{{ __('building-works-declarations.property_address') }}</th><td>{{ $notification->property_address ?? '—' }}</td></tr>
                            <tr><th>{{ __('building-works-declarations.works_type') }}</th><td>{{ $notification->works_type ?? '—' }}</td></tr>
                            <tr><th>{{ __('building-works-declarations.works_start_date') }}</th><td>{{ $notification->works_start_date?->format('d/m/Y') ?? '—' }}</td></tr>
                            <tr><th>{{ __('building-works-declarations.works_description') }}</th><td style="white-space:pre-line;">{{ $notification->works_description ?? '—' }}</td></tr>
                            @if ($notification->notes)<tr><th>{{ __('building-works-declarations.notes') }}</th><td>{{ $notification->notes }}</td></tr>@endif
                            <tr><th>{{ __('building-works-declarations.free') }}</th><td>{{ __('building-works-declarations.free_info') }}</td></tr>
                        </tbody>
                    </table>
                    <div class="fiche-sign-row">
                        <div class="fiche-sign-col">
                            <div class="fiche-sign-name">{{ __('building-works-declarations.print_applicant') }}</div>
                            <div class="fiche-sign-line">{{ __('building-works-declarations.print_signature') }}</div>
                        </div>
                        <div class="fiche-sign-col">
                            <div class="fiche-sign-name">{{ __('building-works-declarations.print_admin') }}</div>
                            <div class="fiche-sign-line">{{ __('building-works-declarations.print_stamp') }}</div>
                        </div>
                    </div>
                    <div class="fiche-foot">
                        <span>{{ __('building-works-declarations.print_footer') }}</span>
                        <span dir="ltr">{{ $viewRef }}</span>
                    </div>
                </div>
            </div>

        </div>
    </main>
</x-app-layout>
