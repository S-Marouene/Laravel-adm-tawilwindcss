@extends('admin.layouts.admin')
@section('title', __('preventive_presence.card_title') . ' — Request #' . $requestItem->id)

@php
    $status = $requestItem->status;
    $statusConfig = [
        'completed'          => ['label' => 'Under Review',       'dot' => 'bg-blue-500',    'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
        'under_consultation' => ['label' => 'Under Consultation', 'dot' => 'bg-purple-500',  'badge' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
        'needs_correction'   => ['label' => 'Needs Correction',   'dot' => 'bg-red-500',     'badge' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'],
        'approved'           => ['label' => 'Approved',           'dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
        'served'             => ['label' => 'Service Performed',  'dot' => 'bg-teal-500',    'badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'],
        'cancelled'          => ['label' => 'Cancelled',          'dot' => 'bg-gray-400',    'badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'],
    ];
    $sc = $statusConfig[$status] ?? ['label' => ucfirst($status), 'dot' => 'bg-gray-400', 'badge' => 'bg-gray-100 text-gray-600'];
@endphp

@section('content')
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('preventive_presence.card_title') }} — Request #{{ $requestItem->id }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('preventive_presence.event_label') }}: {{ $requestItem->event_title }} — {{ $requestItem->created_at->format('d M Y') }}</p>
    </div>
    <a href="{{ route('admin.preventive-presence.index') }}"
       class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to list
    </a>
</div>

@if (session('success'))
    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-[1fr,340px]">

    {{-- ─── Left column ─── --}}
    <div class="space-y-6">

        {{-- Current status card --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-100 dark:border-gray-700/60">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold {{ $sc['badge'] }}">
                        <span class="h-2 w-2 rounded-full {{ $sc['dot'] }}"></span>
                        {{ $sc['label'] }}
                    </span>
                    @if ($requestItem->reviewed_at)
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            by {{ $requestItem->reviewer?->name ?? 'Admin' }} · {{ $requestItem->reviewed_at->format('d M Y, H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Admin note display (needs_correction / cancelled) --}}
            @if ($requestItem->admin_note)
                <div class="px-6 py-4 {{ $status === 'needs_correction' ? 'bg-red-50 dark:bg-red-900/10' : ($status === 'cancelled' ? 'bg-gray-50 dark:bg-gray-700/20' : '') }}">
                    <p class="text-xs font-semibold uppercase tracking-wide {{ $status === 'needs_correction' ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }} mb-2">
                        Admin Note (visible to applicant)
                    </p>
                    <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $requestItem->admin_note }}</p>
                </div>
            @endif

            {{-- Under consultation phone highlight --}}
            @if ($status === 'under_consultation')
                <div class="flex items-center gap-3 px-6 py-3 bg-purple-50 dark:bg-purple-900/10">
                    <svg class="h-4 w-4 text-purple-500 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span class="text-sm text-purple-700 dark:text-purple-300">
                        Applicant's phone: <strong class="font-mono">{{ $requestItem->phone ?? 'Not provided' }}</strong>
                        <span class="ms-2 text-xs text-purple-500">– Keep this available</span>
                    </span>
                </div>
            @endif
        </div>

        {{-- Applicant info --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Applicant Information</h2>
            </div>
            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Full Name</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $requestItem->nom_prenom }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">CIN</p>
                    <p class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $requestItem->cin }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Phone</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $requestItem->phone ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Account Email</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $requestItem->user?->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Governorate</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $requestItem->governorate?->name_fr ?? $requestItem->governorate?->name_ar ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Delegation</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $requestItem->delegation?->name_fr ?? $requestItem->delegation?->name_ar ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Event info --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('preventive_presence.event_section') }}</h2>
            </div>
            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.event_title') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $requestItem->event_title ?? '—' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.event_address') }}</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $requestItem->event_address ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.event_date') }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                        @if ($requestItem->event_date)
                            {{ $requestItem->event_date->format('d/m/Y') }}@if($requestItem->event_end_date) &rarr; {{ $requestItem->event_end_date->format('d/m/Y') }}@endif
                        @else
                            —
                        @endif
                        @if ($requestItem->event_time)
                            <span class="text-gray-500">· {{ $requestItem->event_time->format('H:i') }}</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.expected_attendance') }}</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $requestItem->expected_attendance ?? '—' }}</p>
                </div>
                @if ($requestItem->notes)
                    <div class="sm:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.notes') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $requestItem->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Payment --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('preventive_presence.payment_title') }}</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('preventive_presence.payment_fee_info') }}</p>
                </div>
                @if ($requestItem->isPaid())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        {{ __('preventive_presence.payment_status_paid') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        {{ __('preventive_presence.payment_status_unpaid') }}
                    </span>
                @endif
            </div>
            @if ($requestItem->isPaid())
                <div class="grid gap-4 px-6 py-5 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.fee_amount') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ number_format((float) $requestItem->fee_amount, 3) }} DT</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.receipt_number') }}</p>
                        <p class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $requestItem->receipt_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.paid_at') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $requestItem->paid_at?->format('d M Y') }}</p>
                    </div>
                </div>
            @elseif (in_array($status, ['completed', 'under_consultation', 'needs_correction', 'cancelled']))
                <div class="px-6 py-4 text-sm text-gray-400 dark:text-gray-500">
                    Fee payment becomes applicable once the request is approved.
                </div>
            @endif
        </div>

        {{-- Service performed (إسداء الخدمة) --}}
        @if (in_array($status, ['approved', 'served']) || $requestItem->service_date)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('preventive_presence.service_title') }}</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('preventive_presence.index_title') }}</p>
                </div>
                @if ($requestItem->isServed())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-teal-100 px-2.5 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-900/40 dark:text-teal-300">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        {{ __('preventive_presence.service_status_done') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                        {{ __('preventive_presence.service_status_pending') }}
                    </span>
                @endif
            </div>
            @if ($requestItem->service_date || $requestItem->service_team || $requestItem->service_agents || $requestItem->service_note)
                <div class="grid gap-4 px-6 py-5 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.service_date') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $requestItem->service_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.service_team') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $requestItem->service_team ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.service_agents') }}</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $requestItem->service_agents ?? '—' }}</p>
                    </div>
                    @if ($requestItem->service_note)
                        <div class="sm:col-span-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('preventive_presence.service_note') }}</p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $requestItem->service_note }}</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="px-6 py-4 text-sm text-gray-400 dark:text-gray-500">
                    Use the panel on the right to record the service once it has been performed.
                </div>
            @endif
        </div>
        @endif

        {{-- Documents --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Uploaded Documents</h2>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $uploadedDocs->count() }} / {{ $documentTypes->count() }} uploaded</p>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @forelse ($documentTypes as $docType)
                    @php $doc = $uploadedDocs->get($docType->id); @endphp
                    <div class="flex items-center justify-between gap-4 px-6 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full {{ $doc ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' }}">
                                @if ($doc)
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @endif
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $docType->name }}</p>
                                @if ($doc)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $doc->original_name }} ({{ number_format($doc->size / 1024, 0) }} KB) — {{ $doc->updated_at->format('d M Y') }}</p>
                                @else
                                    <p class="text-xs text-red-400 dark:text-red-500">Not uploaded</p>
                                @endif
                            </div>
                        </div>
                        @if ($docType->is_conditional)
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Conditional</span>
                        @endif
                    </div>
                @empty
                    <div class="px-6 py-6 text-center text-sm text-gray-400">No document types configured.</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ─── Right sidebar: actions + timeline ─── --}}
    <div class="space-y-5">

        {{-- Record payment + perform the service (only when approved) --}}
        @can('manage preventive-presence')
        @if ($status === 'approved')
        <div class="rounded-2xl border border-amber-200 bg-white shadow-sm dark:border-amber-800/40 dark:bg-gray-800" x-data="{ open: {{ $requestItem->isPaid() ? 'false' : 'true' }} }">
            <div class="border-b border-amber-100 dark:border-amber-800/30 px-5 py-4 flex items-center justify-between cursor-pointer" @click="open = !open">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-amber-600 dark:text-amber-400 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">{{ __('preventive_presence.payment_title') }}</h3>
                </div>
                <svg class="h-4 w-4 text-amber-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="px-5 py-4">
                <form method="POST" action="{{ route('admin.preventive-presence.payment', $requestItem) }}">
                    @csrf
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('preventive_presence.fee_amount') }} <span class="text-red-500">*</span></label>
                            <input type="number" name="fee_amount" step="0.001" min="0" required
                                value="{{ old('fee_amount', $requestItem->fee_amount) }}"
                                placeholder="{{ __('preventive_presence.fee_amount_placeholder') }}"
                                class="w-full text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('preventive_presence.receipt_number') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="receipt_number" maxlength="50" required
                                value="{{ old('receipt_number', $requestItem->receipt_number) }}"
                                placeholder="{{ __('preventive_presence.receipt_number_placeholder') }}"
                                class="w-full text-sm">
                        </div>
                        <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ $requestItem->isPaid() ? __('action.save') : __('preventive_presence.payment_title') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Serve the service --}}
        <div class="rounded-2xl border border-teal-200 bg-white shadow-sm dark:border-teal-800/40 dark:bg-gray-800" x-data="{ open: {{ $requestItem->service_date ? 'false' : 'true' }} }">
            <div class="border-b border-teal-100 dark:border-teal-800/30 px-5 py-4 flex items-center justify-between cursor-pointer" @click="open = !open">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-teal-600 dark:text-teal-400 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-teal-800 dark:text-teal-300">{{ __('preventive_presence.service_title') }}</h3>
                </div>
                <svg class="h-4 w-4 text-teal-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="px-5 py-4">
                @if (! $requestItem->isPaid())
                    <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800 dark:border-amber-800/30 dark:bg-amber-900/10 dark:text-amber-300">
                        <svg class="h-4 w-4 flex-none mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        Record the fee receipt before marking the service as performed.
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.preventive-presence.serve', $requestItem) }}" onsubmit="return confirm('Mark the preventive presence service as performed? The applicant will see the details.')">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('preventive_presence.service_date') }} <span class="text-red-500">*</span></label>
                                <input type="date" name="service_date" required
                                    value="{{ old('service_date', $requestItem->event_date?->format('Y-m-d')) }}"
                                    class="w-full text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('preventive_presence.service_team') }}</label>
                                <input type="text" name="service_team" maxlength="255"
                                    value="{{ old('service_team') }}"
                                    placeholder="Assigned unit / commander..."
                                    class="w-full text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('preventive_presence.service_agents') }}</label>
                                <input type="number" name="service_agents" min="1" max="999"
                                    value="{{ old('service_agents') }}"
                                    class="w-full text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ __('preventive_presence.service_note') }}</label>
                                <textarea name="service_note" rows="3" maxlength="2000"></textarea>
                            </div>
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Mark Service Performed
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
        @endif
        @endcan

        {{-- Actions --}}
        @can('manage preventive-presence')
        @if ($requestItem->isActionable())
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Actions</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Move this request to the next step</p>
            </div>
            <div class="px-5 py-4 space-y-3">

                {{-- Advance to Processing --}}
                @if (in_array($status, ['completed', 'needs_correction']))
                    <form method="POST" action="{{ route('admin.preventive-presence.advance', $requestItem) }}"
                          onsubmit="return confirm('Advance this request to processing? The applicant will see a notice to keep their phone available.')">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Advance to Processing
                        </button>
                    </form>
                @endif

                {{-- Approve --}}
                @if (in_array($status, ['completed', 'under_consultation']))
                    <form method="POST" action="{{ route('admin.preventive-presence.approve', $requestItem) }}"
                          onsubmit="return confirm('Approve this request? Next step: record the fee receipt, then mark the service as performed.')">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Approve Request
                        </button>
                    </form>
                @endif

                {{-- Request Correction --}}
                <div x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-800 transition hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300 dark:hover:bg-amber-900/40 focus:outline-none">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span x-text="open ? 'Hide' : 'Request Correction'">Request Correction</span>
                        <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-3">
                        <form method="POST" action="{{ route('admin.preventive-presence.correction', $requestItem) }}">
                            @csrf
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Note to applicant <span class="text-red-500">*</span></label>
                            <textarea name="admin_note" rows="5" required maxlength="2000"
                                placeholder="Describe what needs to be fixed (e.g. missing document, wrong file, incorrect data)...">{{ old('admin_note') }}</textarea>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">This note will be shown to the applicant with a "Fix & Resubmit" button.</p>
                            <button type="submit" class="mt-3 w-full inline-flex items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                Send Correction Request
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Cancel --}}
                <div x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40 focus:outline-none">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="open ? 'Hide' : 'Cancel Request'">Cancel Request</span>
                        <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-3">
                        <form method="POST" action="{{ route('admin.preventive-presence.cancel', $requestItem) }}"
                              onsubmit="return confirm('Cancel this request? The applicant will be notified.')">
                            @csrf
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Cancellation reason <span class="text-red-500">*</span></label>
                            <textarea name="admin_note" rows="4" required maxlength="2000"
                                placeholder="Explain why this request is being cancelled...">{{ old('admin_note') }}</textarea>
                            <button type="submit" class="mt-3 w-full inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Confirm Cancellation
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
        @elseif ($status === 'approved')
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 dark:border-emerald-800/40 dark:bg-emerald-900/10 px-5 py-4 text-center">
                <svg class="h-8 w-8 text-emerald-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Request Approved</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Record the fee receipt, then perform the service.</p>
            </div>
        @elseif ($status === 'served')
            <div class="rounded-2xl border border-teal-200 bg-teal-50 dark:border-teal-800/40 dark:bg-teal-900/10 px-5 py-4 text-center">
                <svg class="h-8 w-8 text-teal-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-semibold text-teal-800 dark:text-teal-200">Service Performed</p>
                <p class="text-xs text-teal-600 dark:text-teal-400 mt-1">This request has been fully completed.</p>
            </div>
        @elseif ($status === 'cancelled')
            <div class="rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/20 px-5 py-4 text-center">
                <svg class="h-8 w-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Request Cancelled</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">This request has been closed.</p>
            </div>
        @endif
        @endcan

        {{-- Timeline — full audit log (every event) --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">History & Timeline</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $requestItem->events->count() }} recorded events</p>
            </div>
            <div class="px-5 py-4">
                @forelse ($requestItem->events as $event)
                    @php
                        $isCorrection = $event->type === 'correction_requested';
                        $isCancelled  = $event->type === 'cancelled';
                        $isRejected   = $event->type === 'cancelled';
                        $isApproved   = in_array($event->type, ['approved', 'advanced_to_consultation']);
                    @endphp

                    @if (! $loop->first)
                        <div class="ms-[3px] my-1.5 w-0.5 h-5 bg-gray-200 dark:bg-gray-600"></div>
                    @endif

                    <div class="flex items-start gap-3" @if ($isCorrection || $isRejected)
                            x-data="{ open: false }" @endif>
                        <span class="mt-1 flex-none h-2 w-2 rounded-full {{ $event->typeDot() }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 flex-wrap">
                                {{ $event->typeLabel() }}
                                @if ($isApproved)
                                    <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Accepted</span>
                                @endif
                                @if ($isRejected)
                                    <span class="rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-red-700 dark:bg-red-900/40 dark:text-red-300">Rejected</span>
                                @endif
                                @if ($isCorrection)
                                    <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Needs correction</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $event->actor?->name ?? 'System' }}
                                · {{ $event->created_at->format('d M Y, H:i') }}
                                ({{ $event->created_at->diffForHumans() }})
                            </p>

                            {{-- Extra details (payment / service) --}}
                            @if ($detailsText = $event->detailsText())
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300 font-medium">{{ $detailsText }}</p>
                            @endif

                            {{-- Correction note / rejection reason — expandable with full detail --}}
                            @if ($event->note)
                                @if ($isCorrection || $isCancelled)
                                    <button type="button" @click="open = !open"
                                        class="mt-1.5 inline-flex items-center gap-1.5 rounded-lg border text-xs font-semibold px-2.5 py-1.5 transition focus:outline-none {{ $isCorrection ? 'border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300 dark:hover:bg-amber-900/40' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40' }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                        </svg>
                                        <span x-text="open ? 'Hide' : 'View'">{{ $isCorrection ? 'correction note' : 'reason' }}</span>
                                    </button>
                                    <div x-show="open" x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 -translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        class="mt-2 rounded-lg px-3 py-2 text-xs {{ $isCorrection ? 'border border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-700/50 dark:bg-amber-900/10 dark:text-amber-200' : 'border border-red-200 bg-red-50 text-red-800 dark:border-red-700/50 dark:bg-red-900/10 dark:text-red-200' }}">
                                        <p class="font-semibold mb-1">{{ $isCorrection ? 'Note sent to applicant:' : 'Reason given to applicant:' }}</p>
                                        <p class="whitespace-pre-line">{{ $event->note }}</p>
                                    </div>
                                @else
                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $event->note }}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-2 text-center text-sm text-gray-400">No events recorded yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Quick info --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 px-5 py-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Quick Info</h3>
            <dl class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Request ID</dt>
                    <dd class="font-mono font-semibold text-gray-900 dark:text-gray-100">#{{ $requestItem->id }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Step reached</dt>
                    <dd class="font-semibold text-gray-900 dark:text-gray-100">Step {{ $requestItem->current_step }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Docs uploaded</dt>
                    <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $uploadedDocs->count() }} / {{ $documentTypes->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Submitted</dt>
                    <dd class="text-gray-700 dark:text-gray-300">{{ $requestItem->created_at->diffForHumans() }}</dd>
                </div>
            </dl>
        </div>

    </div>{{-- /right sidebar --}}

</div>
@endsection
