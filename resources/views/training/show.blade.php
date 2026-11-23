@php
    $tr     = $trainingRequest;
    $status = $tr->status;
    $viewRef = $tr->reference ?: \App\Support\Reference::provisional('training');

    $isCancelled = $status === 'cancelled';

    // Timeline events
    $eventChip = fn ($type) => match (true) {
        in_array($type, ['submitted', 'resubmitted'])       => ['bg-secondary-container text-on-secondary-container', 'post_add', __('training.chip_depot')],
        $type === 'advanced_to_consultation'                => ['bg-violet-600 text-white', 'sync_alt', __('training.chip_phase')],
        $type === 'correction_requested'                    => ['bg-amber-500 text-white', 'edit_note', __('training.chip_correction')],
        $type === 'approved'                                => ['bg-blue-600 text-white', 'task_alt', __('training.chip_phase')],
        $type === 'payment_recorded'                        => ['bg-orange-500 text-white', 'payments', __('training.chip_payment')],
        $type === 'scheduled'                               => ['bg-indigo-600 text-white', 'calendar_today', __('training.chip_schedule')],
        $type === 'started'                                 => ['bg-sky-600 text-white', 'play_circle', __('training.chip_session')],
        $type === 'attendance_recorded'                     => ['bg-sky-500 text-white', 'how_to_reg', __('training.chip_session')],
        $type === 'result_recorded'                         => ['bg-purple-600 text-white', 'grading', __('training.chip_result')],
        in_array($type, ['certificate_issued', 'certificate_collected', 'certificate_post_sent', 'certificate_post_delivered']) => ['bg-teal-600 text-white', 'workspace_premium', __('training.chip_certificate')],
        $type === 'cancelled'                               => ['bg-red-600 text-white', 'cancel', __('training.chip_alert')],
        default                                             => ['bg-gray-500 text-white', 'info', __('training.chip_alert')],
    };
@endphp

<x-app-layout>
    <main class="flex-1 w-full mx-auto sm:px-6 lg:px-8 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-0">

            {{-- Back + Reference header --}}
            <div class="flex items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <a href="{{ route('training.index') }}" class="flex items-center gap-2 text-sm text-secondary hover:text-on-surface transition">
                        <span class="material-symbols-outlined text-base rtl:rotate-180" aria-hidden="true">arrow_back</span>
                        {{ __('training.my_requests') }}
                    </a>
                    <span class="text-outline" aria-hidden="true">/</span>
                    <span class="text-sm font-mono font-bold text-on-surface" dir="ltr">{{ $viewRef }}</span>
                </div>
                <button onclick="window.print()" class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                </button>
            </div>

            {{-- Correction banner --}}
            @if ($status === 'needs_correction')
                <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden mb-6">
                    <div class="border-s-4 border-s-red-600 p-5">
                        <div class="flex items-start gap-3.5 mb-3">
                            <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">assignment_late</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.correction_title') }}</h2>
                            </div>
                        </div>
                        @if ($tr->admin_note)
                            <div class="rounded border border-red-200 bg-red-50 dark:bg-red-900/10 px-4 py-3 mb-4">
                                <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 mb-1">{{ __('training.admin_note_label') }}</p>
                                <p class="text-body-sm text-on-surface whitespace-pre-line">{{ $tr->admin_note }}</p>
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

            {{-- Status banner --}}
            @php
                $bannerKey = 'training.' . $status . '_banner';
                $bannerColor = match($status) {
                    'payment_pending' => ['border-orange-200 dark:border-orange-800/40', 'border-s-orange-500', 'bg-orange-50 dark:bg-orange-900/10'],
                    'payment_confirmed','scheduled' => ['border-blue-200 dark:border-blue-800/40', 'border-s-blue-500', 'bg-blue-50 dark:bg-blue-900/10'],
                    'in_progress' => ['border-sky-200 dark:border-sky-800/40', 'border-s-sky-500', 'bg-sky-50 dark:bg-sky-900/10'],
                    'passed','served' => ['border-teal-200 dark:border-teal-800/40', 'border-s-teal-500', 'bg-teal-50 dark:bg-teal-900/10'],
                    'failed','cancelled' => ['border-red-200 dark:border-red-800/40', 'border-s-red-500', 'bg-red-50 dark:bg-red-900/10'],
                    default => ['border-surface-dim dark:border-gray-700', 'border-s-secondary', 'bg-surface-container dark:bg-gray-900/40'],
                };
            @endphp
            @if (!in_array($status, ['draft', 'needs_correction']))
                <section class="bg-surface-container-lowest border {{ $bannerColor[0] }} rounded-lg shadow-sm overflow-hidden mb-6">
                    <div class="border-s-4 {{ $bannerColor[1] }} {{ $bannerColor[2] }} p-5">
                        <p class="text-body-sm text-on-surface leading-relaxed">{{ __($bannerKey) }}</p>
                    </div>
                </section>
            @endif

            {{-- Main content --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">

                    {{-- Request details --}}
                    <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">badge</span>
                                {{ __('training.section_applicant') }}
                            </h3>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ([
                                ['label' => __('training.nom_prenom'),  'value' => $tr->nom_prenom],
                                ['label' => __('training.cin'),         'value' => $tr->cin],
                                ['label' => __('training.phone'),       'value' => $tr->phone],
                                ['label' => __('training.code_structure'), 'value' => $tr->governorate?->name],
                                ['label' => __('training.delegation'),  'value' => $tr->delegation?->name],
                                ['label' => __('training.training_type'), 'value' => $tr->training_type ? __('training.type_' . $tr->training_type) : null],
                                ['label' => __('training.training_kind'), 'value' => $tr->training_kind ? __('training.kind_' . $tr->training_kind) : null],
                            ] as $row)
                                @if ($row['value'])
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-0.5">{{ $row['label'] }}</p>
                                    <p class="text-sm text-on-surface">{{ $row['value'] }}</p>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Schedule --}}
                    @if ($tr->training_start_date)
                    <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">calendar_today</span>
                                {{ __('training.schedule_title') }}
                            </h3>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-0.5">{{ __('training.training_start_date') }}</p>
                                <p class="text-sm text-on-surface">{{ $tr->training_start_date->format('d/m/Y') }}</p>
                            </div>
                            @if ($tr->training_start_time)
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-0.5">{{ __('training.training_start_time') }}</p>
                                <p class="text-sm text-on-surface" dir="ltr">{{ $tr->training_start_time->format('H:i') }}</p>
                            </div>
                            @endif
                            @if ($tr->training_duration_days)
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-0.5">{{ __('training.training_duration_days') }}</p>
                                <p class="text-sm text-on-surface" dir="ltr">{{ $tr->training_duration_days }}</p>
                            </div>
                            @endif
                        </div>
                        {{-- Attendance --}}
                        @if ($tr->sessions->isNotEmpty())
                        <div class="p-4 border-t border-surface-dim">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-2">{{ __('training.attendance_title') }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tr->sessions as $session)
                                    @php
                                        $cls = match($session->attendance) {
                                            'present' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            'excused' => 'bg-blue-100 text-blue-800 border-blue-200',
                                            default   => 'bg-red-100 text-red-800 border-red-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[11px] font-bold {{ $cls }}">
                                        {{ $session->session_date->format('d/m') }}
                                        · {{ __('training.attendance_' . $session->attendance) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Result --}}
                    @if ($tr->result)
                    <div class="bg-surface-container-lowest border {{ $tr->result === 'passed' ? 'border-emerald-200 dark:border-emerald-800/40' : 'border-red-200 dark:border-red-800/40' }} rounded-lg shadow-sm overflow-hidden">
                        <div class="border-s-4 {{ $tr->result === 'passed' ? 'border-s-emerald-500' : 'border-s-red-500' }} p-5">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="material-symbols-outlined text-{{ $tr->result === 'passed' ? 'emerald' : 'red' }}-600 text-2xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">
                                    {{ $tr->result === 'passed' ? 'workspace_premium' : 'cancel' }}
                                </span>
                                <h3 class="text-sm font-extrabold text-on-surface uppercase">{{ __('training.result_' . $tr->result) }}</h3>
                            </div>
                            @if ($tr->result_note)
                                <p class="text-body-sm text-on-surface whitespace-pre-line">{{ $tr->result_note }}</p>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Timeline --}}
                    @if ($tr->events->isNotEmpty())
                    <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden" x-data="{ open: false }">
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center justify-between gap-3 p-5 border-b border-surface-dim hover:bg-surface-container transition-colors text-start">
                            <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">history</span>
                                {{ __('training.timeline_title') }}
                            </h3>
                            <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="p-5">
                            <div class="relative ps-6 sm:ps-8 space-y-6 before:absolute before:start-3 sm:before:start-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                                @foreach ($tr->events->reverse()->values() as $event)
                                    @php [$chipCls, $icon, $chip] = $eventChip($event->type); @endphp
                                    <div class="relative">
                                        <div class="absolute -start-6 sm:-start-8 top-0 w-6 sm:w-8 flex items-center justify-center">
                                            <div class="w-5 h-5 rounded-full {{ $chipCls }} flex items-center justify-center ring-4 ring-surface-container-lowest shadow-xs">
                                                <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $icon }}</span>
                                            </div>
                                        </div>
                                        <div class="bg-surface-container-low/60 border border-surface-dim rounded-lg p-4">
                                            <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold {{ $chipCls }} px-2 py-0.5 rounded uppercase">{{ $chip }}</span>
                                                    <span class="text-xs font-semibold text-on-surface">{{ in_array($event->type, ['submitted','resubmitted']) ? __('training.timeline_actor_you') : __('training.timeline_actor_admin') }}</span>
                                                </div>
                                                <time class="text-xs font-mono font-medium text-secondary" dir="ltr">{{ $event->created_at->format('d/m/Y H:i') }}</time>
                                            </div>
                                            <h5 class="text-xs font-bold text-on-surface mb-1">{{ __('training.tl_' . $event->type) }}</h5>
                                            @if ($event->note)
                                                <p class="text-body-sm text-secondary"><span class="font-bold text-on-surface">{{ __('training.timeline_note_label') }}</span> {{ $event->note }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Right sidebar --}}
                <div class="lg:col-span-1 space-y-6">

                    {{-- Status card --}}
                    <div class="bg-surface-container-lowest border border-surface-dim rounded-lg p-5 space-y-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-secondary">{{ __('training.idx_status') }}</p>
                        <p class="text-sm font-bold text-on-surface">{{ $tr->statusLabel() }}</p>
                        <p class="text-[11px] text-secondary" dir="ltr">{{ $viewRef }}</p>
                    </div>

                    {{-- Payment info --}}
                    @if ($tr->isPaid())
                    <div class="bg-surface-container-lowest border border-emerald-200 rounded-lg p-5 dark:border-emerald-800/40">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-3">{{ __('training.payment_title') }}</p>
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
                    </div>
                    @elseif (in_array($status, ['payment_pending']))
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-5 dark:border-orange-800/40 dark:bg-orange-900/10">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-orange-800 mb-2">{{ __('training.payment_title') }}</p>
                        <p class="text-xs text-orange-700 leading-relaxed">{{ __('training.payment_pending_banner') }}</p>
                    </div>
                    @endif

                    {{-- Certificate delivery info --}}
                    @if ($tr->cert_status)
                    <div class="bg-surface-container-lowest border border-teal-200 rounded-lg p-5 dark:border-teal-800/40">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-3">{{ __('training.certificate_title') }}</p>
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
                            <div class="mt-2 pt-2 border-t border-surface-dim">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-teal-100 text-teal-800 text-[11px] font-bold">
                                    {{ __('training.cert_status_' . $tr->cert_status) }}
                                </span>
                            </div>
                        </div>
                        @if ($tr->cert_status === 'ready' && $tr->isPickup())
                            <p class="mt-3 text-[11px] text-secondary leading-relaxed">{{ __('training.approved_next_hint') }}</p>
                        @endif
                    </div>
                    @endif

                    {{-- Documents --}}
                    @if ($documentTypes->isNotEmpty())
                    <div class="bg-surface-container-lowest border border-surface-dim rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary">{{ __('training.documents_title') }}</h3>
                        </div>
                        <div class="p-4 space-y-2">
                            @foreach ($documentTypes as $docType)
                                @php $doc = $trainingRequest->documents->where('document_type_id', $docType->id)->where('status', '!=', 'archived')->first(); @endphp
                                <div class="flex items-center justify-between gap-2 p-2 rounded border {{ $doc ? 'border-emerald-200 bg-emerald-50/60' : 'border-surface-dim bg-surface-container-low' }}">
                                    <p class="text-xs font-bold {{ $doc ? 'text-emerald-700' : 'text-secondary' }} truncate">{{ $docType->name }}</p>
                                    @if ($doc)
                                        <a href="{{ route('training.documents.download', $doc) }}" class="w-6 h-6 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 transition-colors">
                                            <span class="material-symbols-outlined text-xs" aria-hidden="true">download</span>
                                        </a>
                                    @else
                                        <span class="text-[10px] text-red-500">{{ __('training.documents_not_uploaded') }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </main>
</x-app-layout>
