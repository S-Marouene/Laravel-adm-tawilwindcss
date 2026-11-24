@php
    $tr     = $trainingRequest;
    $status = $tr->status;
    $viewRef = $tr->reference ?: \App\Support\Reference::provisional('training');

    // Workflow flags
    $canAdvance         = in_array($status, ['completed', 'needs_correction']);
    $canCorrect         = in_array($status, ['completed', 'under_consultation', 'needs_correction']);
    $canApprove         = $status === 'under_consultation';
    $canCancel          = in_array($status, ['completed', 'under_consultation', 'needs_correction', 'payment_pending', 'payment_confirmed', 'scheduled']);
    $canRecordPayment   = in_array($status, ['payment_pending', 'payment_confirmed', 'scheduled']);
    $canSchedule        = $status === 'payment_confirmed';
    $canStart           = $status === 'scheduled';
    $canAttendance      = $status === 'in_progress';
    $canResult          = $status === 'in_progress';
    $canIssueCert       = $status === 'passed' && ! $tr->hasCertificate();
    $canUpdateCert      = $status === 'passed' && $tr->hasCertificate() && ! in_array($tr->cert_status, ['collected', 'post_delivered']);
    $canRevokeCert      = $tr->hasCertificate() && ! in_array($tr->cert_status, ['collected', 'post_delivered']);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface">{{ __('training.card_title') }}</h2>
                <p class="mt-0.5 text-sm text-secondary" dir="ltr">{{ $viewRef }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Top toolbar --}}
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.training.index') }}" class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-3 rounded transition-colors">
                    <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180">arrow_back</span>
                    {{ __('training.admin_back_to_list') }}
                </a>
                @if ($canAdvance)
                    <form method="POST" action="{{ route('admin.training.advance', $tr) }}" data-confirm data-confirm-style="warning" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded shadow-sm">
                            <span class="material-symbols-outlined text-base">sync_alt</span>{{ __('training.admin_advance_btn') }}
                        </button>
                    </form>
                @endif
                @if ($canApprove)
                    <form method="POST" action="{{ route('admin.training.approve', $tr) }}" data-confirm data-confirm-style="success" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2 px-4 rounded shadow-sm">
                            <span class="material-symbols-outlined text-base">task_alt</span>{{ __('training.admin_approve_btn') }}
                        </button>
                    </form>
                @endif
                @if ($canStart)
                    <form method="POST" action="{{ route('admin.training.start', $tr) }}" data-confirm data-confirm-style="success" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold py-2 px-4 rounded shadow-sm">
                            <span class="material-symbols-outlined text-base">play_circle</span>{{ __('training.admin_start_btn') }}
                        </button>
                    </form>
                @endif
                <div class="ms-auto">
                    <button onclick="window.print()" class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded">
                        <span class="material-symbols-outlined text-base">print</span>
                    </button>
                </div>
            </div>

            {{-- Key info chips --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                @foreach ([
                    ['label' => __('training.nom_prenom'),  'value' => $tr->nom_prenom],
                    ['label' => __('training.cin'),         'value' => $tr->cin],
                    ['label' => __('training.phone'),       'value' => $tr->phone],
                    ['label' => __('training.training_type'), 'value' => $tr->training_type ? __('training.type_' . $tr->training_type) : '—'],
                    ['label' => __('training.idx_status'),  'value' => __('training.status_' . str_replace('-','_',$status))],
                ] as $chip)
                <div class="bg-surface-container-low border border-surface-dim rounded p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-1">{{ $chip['label'] }}</p>
                    <p class="text-sm font-bold text-on-surface">{{ $chip['value'] }}</p>
                </div>
                @endforeach
            </div>

            {{-- Centralized formation info (prévention 1/2 + BNS) --}}
            @if ($tr->isCentralized())
                @php
                    $sess = $tr->plannedSession;
                    $isWait = $tr->isWaitListed();
                @endphp
                <div class="mb-6 rounded-lg border @if($isWait) border-amber-300 bg-amber-50 dark:border-amber-700/50 dark:bg-amber-900/20 @else border-primary/30 bg-primary/5 dark:border-primary/40 dark:bg-primary/10 @endif p-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-[22px] @if($isWait) text-amber-600 @else text-primary @endif mt-px shrink-0" aria-hidden="true">hub</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold uppercase tracking-wider @if($isWait) text-amber-800 dark:text-amber-300 @else text-primary @endif mb-1">{{ __('training.centralized_title') }}</p>
                            <p class="text-sm text-on-surface dark:text-gray-200 mb-1">{{ __('training.centralized_hint_admin') }}</p>
                            @if ($isWait)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-amber-200 text-amber-900 text-xs font-bold dark:bg-amber-900/40 dark:text-amber-200">
                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">hourglass_top</span>{{ __('training.waitlist_badge') }}
                                </span>
                            @else
                                <div class="text-sm text-on-surface dark:text-gray-100">
                                    <span class="font-semibold">{{ __('training.chosen_session') }}:</span>
                                    <span dir="ltr">{{ $sess->starts_at->format('d/m/Y H:i') }}@if($sess->ends_at) — {{ $sess->ends_at->format('d/m/Y H:i') }}@endif</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Left column --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Request details --}}
                    <div class="bg-surface-container-low border border-surface-dim rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-base">badge</span>
                                {{ __('training.section_applicant') }}
                            </h3>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ([
                                ['l' => __('training.code_structure'), 'v' => $tr->governorate?->name],
                                ['l' => __('training.delegation'),     'v' => $tr->delegation?->name],
                                ['l' => __('training.training_kind'),  'v' => $tr->training_kind ? __('training.kind_' . $tr->training_kind) : null],
                                ['l' => __('training.notes'),          'v' => $tr->notes],
                            ] as $row)
                                @if ($row['v'])
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-0.5">{{ $row['l'] }}</p>
                                    <p class="text-sm text-on-surface">{{ $row['v'] }}</p>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Payment info --}}
                    @if ($tr->isPaid())
                    <div class="bg-surface-container-low border border-surface-dim rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-base">payments</span>
                                {{ __('training.payment_title') }}
                            </h3>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                            <div><p class="font-bold uppercase text-secondary mb-0.5">{{ __('training.fee_amount') }}</p><p class="font-mono font-bold text-on-surface" dir="ltr">{{ number_format($tr->fee_amount, 3) }} {{ __('training.fee_tnd') }}</p></div>
                            <div><p class="font-bold uppercase text-secondary mb-0.5">{{ __('training.receipt_number') }}</p><p class="font-mono font-bold text-on-surface">{{ $tr->receipt_number }}</p></div>
                            <div><p class="font-bold uppercase text-secondary mb-0.5">{{ __('training.paid_at') }}</p><p class="text-on-surface">{{ $tr->paid_at->format('d/m/Y H:i') }}</p></div>
                        </div>
                    </div>
                    @endif

                    {{-- Schedule + attendance --}}
                    @if ($tr->training_start_date)
                    <div class="bg-surface-container-low border border-surface-dim rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-base">calendar_today</span>
                                {{ __('training.schedule_title') }}
                            </h3>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                            <div><p class="font-bold uppercase text-secondary mb-0.5">{{ __('training.training_start_date') }}</p><p class="text-on-surface">{{ $tr->training_start_date->format('d/m/Y') }}</p></div>
                            @if ($tr->training_start_time)<div><p class="font-bold uppercase text-secondary mb-0.5">{{ __('training.training_start_time') }}</p><p class="text-on-surface" dir="ltr">{{ $tr->training_start_time->format('H:i') }}</p></div>@endif
                            @if ($tr->training_duration_days)<div><p class="font-bold uppercase text-secondary mb-0.5">{{ __('training.training_duration_days') }}</p><p class="text-on-surface">{{ $tr->training_duration_days }}</p></div>@endif
                        </div>
                        @if ($tr->sessions->isNotEmpty())
                        <div class="p-4 border-t border-surface-dim">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-secondary mb-2">{{ __('training.attendance_title') }}</p>
                            <div class="space-y-2">
                                @foreach ($tr->sessions as $session)
                                    @php $cls = match($session->attendance) { 'present' => 'bg-emerald-50 border-emerald-200 text-emerald-800', 'excused' => 'bg-blue-50 border-blue-200 text-blue-800', default => 'bg-red-50 border-red-200 text-red-800' }; @endphp
                                    <div class="flex items-center justify-between gap-2 rounded border {{ $cls }} px-3 py-2 text-xs">
                                        <span class="font-bold">{{ $session->session_date->format('d/m/Y') }}</span>
                                        <span>{{ __('training.attendance_' . $session->attendance) }}</span>
                                        @if ($session->note)<span class="text-secondary truncate max-w-[200px]">{{ $session->note }}</span>@endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Result --}}
                    @if ($tr->result)
                    <div class="bg-surface-container-low border border-surface-dim rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-base">grading</span>
                                {{ __('training.result_title') }}
                            </h3>
                        </div>
                        <div class="p-4">
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded {{ $tr->result === 'passed' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }} text-sm font-bold">
                                <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1">{{ $tr->result === 'passed' ? 'workspace_premium' : 'cancel' }}</span>
                                {{ __('training.result_' . $tr->result) }}
                            </span>
                            @if ($tr->result_note)<p class="mt-3 text-sm text-on-surface whitespace-pre-line">{{ $tr->result_note }}</p>@endif
                        </div>
                    </div>
                    @endif

                    {{-- Certificate --}}
                    @if ($tr->hasCertificate())
                    <div class="bg-surface-container-low border border-teal-200 rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-teal-600 text-base">workspace_premium</span>
                                {{ __('training.certificate_title') }}
                            </h3>
                        </div>
                        <div class="p-4 grid grid-cols-2 gap-4 text-xs">
                            <div><p class="font-bold uppercase text-secondary mb-0.5">{{ __('training.certificate_number') }}</p><p class="font-mono font-bold text-on-surface">{{ $tr->certificate_number }}</p></div>
                            @if ($tr->valid_until)<div><p class="font-bold uppercase text-secondary mb-0.5">{{ __('training.valid_until') }}</p><p class="text-on-surface">{{ $tr->valid_until->format('d/m/Y') }}</p></div>@endif
                            <div><p class="font-bold uppercase text-secondary mb-0.5">{{ __('training.idx_status') }}</p>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-teal-100 text-teal-800 font-bold">{{ __('training.cert_status_' . $tr->cert_status) }}</span>
                            </div>
                            <div><p class="font-bold uppercase text-secondary mb-0.5">{{ __('training.delivery_title') }}</p><p class="text-on-surface">{{ $tr->delivery_method ? __('training.delivery_method_' . $tr->delivery_method) : '—' }}</p></div>
                        </div>
                        {{-- Update certificate state --}}
                        @if ($canUpdateCert)
                        <div class="p-4 border-t border-surface-dim">
                            <form method="POST" action="{{ route('admin.training.certificate.update', $tr) }}" class="flex flex-wrap items-center gap-2">
                                @csrf @method('PUT')
                                <input type="hidden" name="action" value="{{ $tr->isPickup() ? 'collected' : 'sent' }}">
                                @if ($tr->isPost() && $tr->cert_status === \App\Models\TrainingRequest::CERT_STATUS_READY)
                                    <input type="text" name="post_tracking" value="{{ old('post_tracking', $tr->post_tracking) }}" placeholder="{{ __('training.certificate_number') }}" class="h-9 px-3 text-xs rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none">
                                @endif
                                <button type="submit" class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold py-2 px-4 rounded">
                                    <span class="material-symbols-outlined text-sm">send</span>
                                    {{ $tr->isPickup() ? __('training.cert_status_collected') : __('training.cert_status_post_sent') }}
                                </button>
                                @if ($tr->isPost() && $tr->cert_status === \App\Models\TrainingRequest::CERT_STATUS_POST_SENT)
                                    <form method="POST" action="{{ route('admin.training.certificate.update', $tr) }}" class="inline">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="action" value="delivered">
                                        <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2 px-4 rounded">
                                            {{ __('training.cert_status_post_delivered') }}
                                        </button>
                                    </form>
                                @endif
                            </form>
                        </div>
                        @endif
                        @if ($canRevokeCert)
                        <div class="p-4 border-t border-surface-dim">
                            <form method="POST" action="{{ route('admin.training.certificate.destroy', $tr) }}" data-confirm data-confirm-style="danger">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800 transition">{{ __('training.admin_cert_revoked') }}</button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Timeline --}}
                    @if ($tr->events->isNotEmpty())
                    <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-3 p-5 border-b border-surface-dim hover:bg-surface-container transition-colors text-start">
                            <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-2xl">history</span>{{ __('training.timeline_title') }}
                            </h3>
                            <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="p-5">
                            <div class="relative ps-6 sm:ps-8 space-y-4 before:absolute before:start-3 sm:before:start-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                                @foreach ($tr->events->reverse()->values() as $event)
                                <div class="relative">
                                    <div class="absolute -start-6 sm:-start-8 top-0 w-6 sm:w-8 flex items-center justify-center">
                                        <div class="w-5 h-5 rounded-full bg-primary/20 flex items-center justify-center ring-4 ring-surface-container-lowest">
                                            <span class="w-2 h-2 rounded-full bg-primary"></span>
                                        </div>
                                    </div>
                                    <div class="bg-surface-container-low/60 border border-surface-dim rounded-lg p-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                                            <span class="text-xs font-bold text-on-surface">{{ __('training.tl_' . $event->type) }}</span>
                                            <time class="text-xs font-mono text-secondary" dir="ltr">{{ $event->created_at->format('d/m/Y H:i') }}</time>
                                        </div>
                                        @if ($event->note)<p class="text-xs text-secondary"><span class="font-bold text-on-surface">{{ __('training.timeline_note_label') }}</span> {{ $event->note }}</p>@endif
                                        @if (is_array($event->data) && !empty($event->data))
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                @foreach ($event->data as $k => $v)
                                                    @if ($v && !is_array($v))
                                                    <span class="text-[10px] bg-surface-container px-1.5 py-0.5 rounded font-mono text-secondary">{{ $k }}: {{ $v }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Right sidebar — actions --}}
                <div class="lg:col-span-1 space-y-6" id="admin-actions-panel">

                    {{-- Documents --}}
                    <div class="bg-surface-container-low border border-surface-dim rounded-lg overflow-hidden">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-base">attach_file</span>
                                {{ __('training.documents_title') }}
                            </h3>
                        </div>
                        <div class="p-4 space-y-3">
                            @forelse ($documentGroups as $group)
                                <div class="rounded border border-surface-dim overflow-hidden">
                                    <div class="flex items-center justify-between gap-2 px-2.5 py-2 border-b border-surface-dim bg-surface-container-low">
                                        <div class="min-w-0">
                                            <p class="truncate text-[11px] font-bold text-on-surface">{{ $group['name'] ?: __('training.participants_section') }}</p>
                                            @if ($group['cin'])
                                                <p class="text-[10px] text-secondary" dir="ltr">{{ __('training.cin') }} : {{ $group['cin'] }}</p>
                                            @endif
                                        </div>
                                        @if ($group['training_kind'] === \App\Models\TrainingRequest::KIND_RECYCLING)
                                            <span class="shrink-0 rounded border border-amber-200 bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:border-amber-800/40 dark:bg-amber-900/30 dark:text-amber-300">{{ __('training.kind_recycling') }}</span>
                                        @endif
                                    </div>
                                    <div class="p-2 space-y-2">
                                        @foreach ($group['documents'] as $requirement)
                                            @php $doc = $uploadedDocs->get($requirement['key']); @endphp
                                            <div class="p-2 rounded border {{ $doc && $doc->status === 'uploaded' ? 'border-emerald-200 bg-emerald-50/60' : 'border-surface-dim bg-surface-container-low' }} flex items-center justify-between gap-2">
                                                <p class="text-xs font-bold {{ $doc && $doc->status === 'uploaded' ? 'text-emerald-700' : 'text-secondary' }} truncate">{{ $requirement['name'] }}</p>
                                                @if ($doc && $doc->status === 'uploaded')
                                                    <a href="{{ route('admin.training.documents.download', $doc) }}" class="w-6 h-6 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                                        <span class="material-symbols-outlined text-xs">download</span>
                                                    </a>
                                                @else
                                                    <span class="text-[10px] text-red-500">{{ __('training.documents_not_uploaded') }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-secondary text-center py-4">{{ __('training.admin_no_doctypes') }}</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Action panels --}}
                    <div class="bg-surface-container-low border border-surface-dim rounded-lg overflow-hidden" x-data="{ fixOpen: false, cancelOpen: false, payOpen: false, schedOpen: false, attendOpen: false, resultOpen: false, certOpen: false }">
                        <div class="p-4 border-b border-surface-dim">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-secondary">{{ __('training.admin_actions_title') }}</h3>
                        </div>
                        <div class="p-4 space-y-3">

                            {{-- Advance --}}
                            @if ($canAdvance)
                            <form method="POST" action="{{ route('admin.training.advance', $tr) }}" data-confirm data-confirm-style="warning">
                                @csrf
                                <button type="submit" class="w-full flex items-center justify-between gap-2 bg-blue-50 text-blue-700 border border-blue-200 rounded p-3 text-xs font-semibold hover:bg-blue-100 transition-colors">
                                    <span>{{ __('training.admin_advance_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]">sync_alt</span>
                                </button>
                            </form>
                            @endif

                            {{-- Correction (expandable) --}}
                            @if ($canCorrect)
                            <div>
                                <button type="button" @click="fixOpen = !fixOpen; cancelOpen = false"
                                    class="w-full flex items-center justify-between gap-2 bg-amber-50 text-amber-700 border border-amber-200 rounded p-3 text-xs font-semibold hover:bg-amber-100 transition-colors">
                                    <span x-text="fixOpen ? '{{ __('training.admin_hide_note') }}' : '{{ __('training.admin_correction_btn') }}'">{{ __('training.admin_correction_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]">edit_note</span>
                                </button>
                                <div x-show="fixOpen" x-cloak class="mt-2 rounded border border-amber-200 bg-amber-50 p-4 space-y-3">
                                    <form method="POST" action="{{ route('admin.training.correction', $tr) }}">
                                        @csrf
                                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-amber-900 mb-1.5">{{ __('training.admin_note_to_applicant') }} <span class="text-red-500">*</span></label>
                                        <x-admin.bilingual-note name="admin_note" :rows="3" required
                                            placeholder_fr="{{ __('training.note_fix_placeholder', [], 'fr') }}"
                                            placeholder_ar="{{ __('training.note_fix_placeholder', [], 'ar') }}" />
                                        <div class="mt-3">
                                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-amber-800 mb-1.5">{{ __('training.section_training') }}</p>
                                            <div class="grid grid-cols-2 gap-1">
                                                @foreach (['cin','nom_prenom','phone','governorate_id','delegation_id','training_type','training_kind','notes'] as $fk)
                                                    <label class="flex items-center gap-1.5 text-[11px] text-amber-800 cursor-pointer">
                                                        <input type="checkbox" name="fields_to_fix[]" value="{{ $fk }}" class="rounded text-amber-600">
                                                        {{ __('training.' . $fk) }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                        @if (!empty($documentGroups))
                                        <div class="mt-3 space-y-2">
                                            @foreach ($documentGroups as $group)
                                                <div class="rounded border border-amber-200/70 p-2">
                                                    <p class="mb-1 text-[10px] font-extrabold uppercase tracking-wide text-amber-900">{{ $group['name'] ?: __('training.participants_section') }}</p>
                                                    @foreach ($group['documents'] as $requirement)
                                                        <label class="flex items-center gap-1.5 text-[11px] text-amber-800 cursor-pointer">
                                                            <input type="checkbox" name="docs_to_fix[]" value="{{ $requirement['key'] }}" class="rounded text-amber-600">
                                                            {{ $requirement['name'] }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                        @endif
                                        <button type="submit" class="mt-3 w-full flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold py-2.5 px-4 rounded">
                                            <span class="material-symbols-outlined text-sm">send</span>{{ __('training.admin_send_correction') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif

                            {{-- Approve --}}
                            @if ($canApprove)
                            <form method="POST" action="{{ route('admin.training.approve', $tr) }}" data-confirm data-confirm-style="success">
                                @csrf
                                <button type="submit" class="w-full flex items-center justify-between gap-2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded p-3 text-xs font-semibold hover:bg-emerald-100 transition-colors">
                                    <span>{{ __('training.admin_approve_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]">task_alt</span>
                                </button>
                            </form>
                            @endif

                            {{-- Record Payment (expandable) --}}
                            @if ($canRecordPayment)
                            <div>
                                <button type="button" @click="payOpen = !payOpen"
                                    class="w-full flex items-center justify-between gap-2 bg-orange-50 text-orange-700 border border-orange-200 rounded p-3 text-xs font-semibold hover:bg-orange-100 transition-colors">
                                    <span x-text="payOpen ? '{{ __('training.admin_hide_note') }}' : '{{ __('training.admin_payment_btn') }}'">{{ __('training.admin_payment_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]">payments</span>
                                </button>
                                <div x-show="payOpen" x-cloak class="mt-2 rounded border border-orange-200 bg-orange-50 p-4">
                                    <form method="POST" action="{{ route('admin.training.payment', $tr) }}" class="space-y-3">
                                        @csrf
                                        <div class="space-y-1">
                                            <label class="block text-[11px] font-bold uppercase text-orange-900">{{ __('training.fee_amount') }} <span class="text-red-500">*</span></label>
                                            <input type="number" name="fee_amount" step="0.001" min="0" value="{{ old('fee_amount', $tr->fee_amount) }}"
                                                class="block w-full h-9 px-3 text-sm rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none" required>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-[11px] font-bold uppercase text-orange-900">{{ __('training.receipt_number') }} <span class="text-red-500">*</span></label>
                                            <input type="text" name="receipt_number" value="{{ old('receipt_number', $tr->receipt_number) }}"
                                                class="block w-full h-9 px-3 text-sm rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none" required>
                                        </div>
                                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold py-2.5 px-4 rounded">
                                            <span class="material-symbols-outlined text-sm">save</span>{{ __('training.admin_payment_btn') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif

                            {{-- Schedule (expandable) --}}
                            @if ($canSchedule)
                            <div>
                                <button type="button" @click="schedOpen = !schedOpen"
                                    class="w-full flex items-center justify-between gap-2 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded p-3 text-xs font-semibold hover:bg-indigo-100 transition-colors">
                                    <span x-text="schedOpen ? '{{ __('training.admin_hide_note') }}' : '{{ __('training.admin_schedule_btn') }}'">{{ __('training.admin_schedule_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                </button>
                                <div x-show="schedOpen" x-cloak class="mt-2 rounded border border-indigo-200 bg-indigo-50 p-4">
                                    <form method="POST" action="{{ route('admin.training.schedule', $tr) }}" class="space-y-3">
                                        @csrf
                                        <div class="space-y-1">
                                            <label class="block text-[11px] font-bold uppercase text-indigo-900">{{ __('training.training_start_date') }} <span class="text-red-500">*</span></label>
                                            <input type="date" name="training_start_date" value="{{ old('training_start_date') }}" min="{{ now()->format('Y-m-d') }}"
                                                class="block w-full h-9 px-3 text-sm rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none" required>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-[11px] font-bold uppercase text-indigo-900">{{ __('training.training_start_time') }}</label>
                                            <input type="time" name="training_start_time" value="{{ old('training_start_time') }}"
                                                class="block w-full h-9 px-3 text-sm rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-[11px] font-bold uppercase text-indigo-900">{{ __('training.training_duration_days') }} <span class="text-red-500">*</span></label>
                                            <input type="number" name="training_duration_days" min="1" max="30" value="{{ old('training_duration_days', \App\Models\TrainingRequest::durationWeeks($tr->training_type ?? '') * 5) }}"
                                                class="block w-full h-9 px-3 text-sm rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none" required>
                                        </div>
                                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2.5 px-4 rounded">
                                            <span class="material-symbols-outlined text-sm">save</span>{{ __('training.admin_schedule_btn') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif

                            {{-- Start --}}
                            @if ($canStart)
                            <form method="POST" action="{{ route('admin.training.start', $tr) }}" data-confirm data-confirm-style="success">
                                @csrf
                                <button type="submit" class="w-full flex items-center justify-between gap-2 bg-sky-50 text-sky-700 border border-sky-200 rounded p-3 text-xs font-semibold hover:bg-sky-100 transition-colors">
                                    <span>{{ __('training.admin_start_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]">play_circle</span>
                                </button>
                            </form>
                            @endif

                            {{-- Attendance (expandable) --}}
                            @if ($canAttendance)
                            <div>
                                <button type="button" @click="attendOpen = !attendOpen"
                                    class="w-full flex items-center justify-between gap-2 bg-sky-50 text-sky-700 border border-sky-200 rounded p-3 text-xs font-semibold hover:bg-sky-100 transition-colors">
                                    <span x-text="attendOpen ? '{{ __('training.admin_hide_note') }}' : '{{ __('training.admin_attendance_btn') }}'">{{ __('training.admin_attendance_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]">how_to_reg</span>
                                </button>
                                <div x-show="attendOpen" x-cloak class="mt-2 rounded border border-sky-200 bg-sky-50 p-4">
                                    <form method="POST" action="{{ route('admin.training.attendance', $tr) }}" class="space-y-3">
                                        @csrf
                                        <div class="space-y-1">
                                            <label class="block text-[11px] font-bold uppercase text-sky-900">{{ __('training.session_date') }} <span class="text-red-500">*</span></label>
                                            <input type="date" name="session_date" value="{{ old('session_date', now()->format('Y-m-d')) }}"
                                                class="block w-full h-9 px-3 text-sm rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none" required>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-[11px] font-bold uppercase text-sky-900">{{ __('training.attendance') }} <span class="text-red-500">*</span></label>
                                            <select name="attendance" class="block w-full h-9 px-3 text-sm rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none" required>
                                                @foreach (['present','absent','excused'] as $att)
                                                    <option value="{{ $att }}">{{ __('training.attendance_' . $att) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold py-2.5 px-4 rounded">
                                            <span class="material-symbols-outlined text-sm">save</span>{{ __('training.admin_attendance_btn') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif

                            {{-- Result (expandable) --}}
                            @if ($canResult)
                            <div>
                                <button type="button" @click="resultOpen = !resultOpen"
                                    class="w-full flex items-center justify-between gap-2 bg-purple-50 text-purple-700 border border-purple-200 rounded p-3 text-xs font-semibold hover:bg-purple-100 transition-colors">
                                    <span x-text="resultOpen ? '{{ __('training.admin_hide_note') }}' : '{{ __('training.admin_result_btn') }}'">{{ __('training.admin_result_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]">grading</span>
                                </button>
                                <div x-show="resultOpen" x-cloak class="mt-2 rounded border border-purple-200 bg-purple-50 p-4">
                                    <form method="POST" action="{{ route('admin.training.result', $tr) }}" class="space-y-3">
                                        @csrf
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach (['passed','failed'] as $res)
                                                <label class="flex items-center gap-2 rounded border border-surface-dim bg-surface-container px-3 py-2 cursor-pointer has-[:checked]:border-primary/60 has-[:checked]:bg-primary/5">
                                                    <input type="radio" name="result" value="{{ $res }}" @checked($res === 'passed') class="h-4 w-4 text-primary">
                                                    <span class="text-xs font-semibold">{{ __('training.result_' . $res) }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <x-admin.bilingual-note name="result_note" :rows="2"
                                            placeholder_fr="{{ __('training.note_fix_placeholder', [], 'fr') }}"
                                            placeholder_ar="{{ __('training.note_fix_placeholder', [], 'ar') }}" />
                                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2.5 px-4 rounded">
                                            <span class="material-symbols-outlined text-sm">grading</span>{{ __('training.admin_result_btn') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif

                            {{-- Issue Certificate (expandable) --}}
                            @if ($canIssueCert)
                            <div>
                                <button type="button" @click="certOpen = !certOpen"
                                    class="w-full flex items-center justify-between gap-2 bg-teal-50 text-teal-700 border border-teal-200 rounded p-3 text-xs font-semibold hover:bg-teal-100 transition-colors">
                                    <span x-text="certOpen ? '{{ __('training.admin_hide_note') }}' : '{{ __('training.admin_issue_cert_btn') }}'">{{ __('training.admin_issue_cert_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]">workspace_premium</span>
                                </button>
                                <div x-show="certOpen" x-cloak class="mt-2 rounded border border-teal-200 bg-teal-50 p-4" x-data="{ method: 'pickup' }">
                                    <form method="POST" action="{{ route('admin.training.certificate.store', $tr) }}" class="space-y-3">
                                        @csrf
                                        <div class="grid grid-cols-2 gap-2">
                                            @foreach (['pickup' => 'delivery_method_pickup', 'post' => 'delivery_method_post'] as $m => $lk)
                                                <label class="flex items-center gap-2 rounded border border-surface-dim bg-surface-container px-3 py-2 cursor-pointer has-[:checked]:border-teal-500/60 has-[:checked]:bg-teal-50">
                                                    <input type="radio" name="delivery_method" value="{{ $m }}" @checked($m === 'pickup') @click="method = '{{ $m }}'" class="h-4 w-4 text-teal-600">
                                                    <span class="text-xs font-semibold">{{ __('training.' . $lk) }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <div x-show="method === 'pickup'" class="space-y-2">
                                            <select name="pickup_governorate_id" class="block w-full h-9 px-3 text-xs rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none">
                                                <option value="">{{ __('training.code_structure_select') }}</option>
                                                @foreach ($governorates as $gov)
                                                    <option value="{{ $gov->id }}">{{ $gov->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div x-show="method === 'post'" class="space-y-2">
                                            <input type="text" name="post_recipient" placeholder="{{ __('training.nom_prenom') }}" class="block w-full h-9 px-3 text-xs rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none">
                                            <input type="text" name="post_phone" placeholder="{{ __('training.phone') }}" class="block w-full h-9 px-3 text-xs rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none">
                                            <textarea name="post_address" rows="2" placeholder="{{ __('training.notes') }}" class="block w-full px-3 py-2 text-xs rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none"></textarea>
                                        </div>
                                        <input type="date" name="valid_until" class="block w-full h-9 px-3 text-xs rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:outline-none" placeholder="{{ __('training.valid_until') }}">
                                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold py-2.5 px-4 rounded">
                                            <span class="material-symbols-outlined text-sm">workspace_premium</span>{{ __('training.admin_issue_cert_btn') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif

                            {{-- Cancel (expandable) --}}
                            @if ($canCancel)
                            <div>
                                <button type="button" @click="cancelOpen = !cancelOpen; fixOpen = false"
                                    class="w-full flex items-center justify-between gap-2 bg-red-50 text-red-700 border border-red-200 rounded p-3 text-xs font-semibold hover:bg-red-100 transition-colors">
                                    <span x-text="cancelOpen ? '{{ __('training.admin_hide_note') }}' : '{{ __('training.admin_cancel_btn') }}'">{{ __('training.admin_cancel_btn') }}</span>
                                    <span class="material-symbols-outlined text-[14px]">cancel</span>
                                </button>
                                <div x-show="cancelOpen" x-cloak class="mt-2 rounded border border-red-200 bg-red-50 p-4">
                                    <form method="POST" action="{{ route('admin.training.cancel', $tr) }}">
                                        @csrf
                                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-red-800 mb-1.5">{{ __('training.admin_cancel_reason') }} <span class="text-red-500">*</span></label>
                                        <x-admin.bilingual-note name="admin_note" :rows="3" required
                                            placeholder_fr="{{ __('training.note_cancel_placeholder', [], 'fr') }}"
                                            placeholder_ar="{{ __('training.note_cancel_placeholder', [], 'ar') }}" />
                                        <button type="submit" class="mt-3 w-full flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 px-4 rounded">
                                            <span class="material-symbols-outlined text-sm">cancel</span>{{ __('training.admin_confirm_cancel') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
