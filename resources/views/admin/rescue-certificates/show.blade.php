@extends('admin.layouts.admin')
@section('title', __('rescue_certificate.card_title') . ' — Request #' . $certificate->id)

@php
    $status = $certificate->status;
    $statusConfig = [
        'completed'          => ['label' => 'Under Review',       'dot' => 'bg-blue-500',    'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
        'under_consultation' => ['label' => 'Verifying Rescue Operation', 'dot' => 'bg-purple-500',  'badge' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
        'needs_correction'   => ['label' => 'Needs Correction',   'dot' => 'bg-red-500',     'badge' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'],
        'approved'           => ['label' => 'Approved',           'dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
        'served'             => ['label' => 'Certificate Delivered',  'dot' => 'bg-teal-500',    'badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'],
        'cancelled'          => ['label' => 'Cancelled',          'dot' => 'bg-gray-400',    'badge' => 'bg-surface-container text-secondary dark:bg-gray-700 dark:text-gray-400'],
    ];
    $sc = $statusConfig[$status] ?? ['label' => ucfirst($status), 'dot' => 'bg-gray-400', 'badge' => 'bg-surface-container text-secondary'];
@endphp

@section('content')
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.card_title') }} — Request #{{ $certificate->id }}</h1>
        <p class="text-sm text-secondary dark:text-gray-400 mt-1">{{ __('rescue_certificate.incident_type') }}: {{ $certificate->incidentTypeLabel() }} — {{ $certificate->created_at->format('d M Y') }}</p>
    </div>
    <a href="{{ route('admin.rescue-certificates.index') }}"
       class="inline-flex items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2 text-sm font-medium text-on-surface transition hover:bg-surface-container dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
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
        <div class="overflow-hidden rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-surface-container-high dark:border-gray-700/60">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold {{ $sc['badge'] }}">
                        <span class="h-2 w-2 rounded-full {{ $sc['dot'] }}"></span>
                        {{ $sc['label'] }}
                    </span>
                    @if ($certificate->reviewed_at)
                        <span class="text-xs text-outline dark:text-gray-500">
                            by {{ $certificate->reviewer?->name ?? 'Admin' }} · {{ $certificate->reviewed_at->format('d M Y, H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Admin note display (needs_correction / cancelled) --}}
            @if ($certificate->admin_note)
                <div class="px-6 py-4 {{ $status === 'needs_correction' ? 'bg-red-50 dark:bg-red-900/10' : ($status === 'cancelled' ? 'bg-surface-container dark:bg-gray-700/20' : '') }}">
                    <p class="text-xs font-semibold uppercase tracking-wide {{ $status === 'needs_correction' ? 'text-red-600 dark:text-red-400' : 'text-secondary dark:text-gray-400' }} mb-2">
                        Admin Note (visible to applicant)
                    </p>
                    <p class="text-sm text-on-surface dark:text-gray-200 whitespace-pre-line">{{ $certificate->admin_note }}</p>
                </div>
            @endif

            {{-- Under consultation phone highlight --}}
            @if ($status === 'under_consultation')
                <div class="flex items-center gap-3 px-6 py-3 bg-purple-50 dark:bg-purple-900/10">
                    <svg class="h-4 w-4 text-purple-500 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span class="text-sm text-purple-700 dark:text-purple-300">
                        Applicant's phone: <strong class="font-mono">{{ $certificate->phone ?? 'Not provided' }}</strong>
                        <span class="ms-2 text-xs text-purple-500">– Keep this available</span>
                    </span>
                </div>
            @endif
        </div>

        {{-- Applicant info --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">Applicant Information</h2>
            </div>
            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Full Name</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $certificate->nom_prenom }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">CIN</p>
                    <p class="mt-1 text-sm font-mono text-on-surface dark:text-gray-100">{{ $certificate->cin }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Phone</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $certificate->phone ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Account Email</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $certificate->user?->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Governorate</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $certificate->governorate?->name_fr ?? $certificate->governorate?->name_ar ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Delegation</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $certificate->delegation?->name_fr ?? $certificate->delegation?->name_ar ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Rescue operation info --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.incident_section') }}</h2>
            </div>
            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('rescue_certificate.incident_type') }}</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $certificate->incidentTypeLabel() }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('rescue_certificate.incident_address') }}</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $certificate->incident_address ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('rescue_certificate.incident_date') }}</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">
                        @if ($certificate->incident_date)
                            {{ $certificate->incident_date->format('d/m/Y') }}
                            @if ($certificate->incident_time) <span class="text-secondary">· {{ $certificate->incident_time->format('H:i') }}</span> @endif
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('rescue_certificate.victim_status') }}</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">
                        @if ($certificate->victim_is_minor)
                            {{ __('rescue_certificate.victim_is_minor') }}
                            — {{ __('rescue_certificate.parent_name') }}: {{ $certificate->parent_name }} ({{ __('rescue_certificate.parent_cin') }}: {{ $certificate->parent_cin }})
                        @elseif ($certificate->spouse_case)
                            {{ __('rescue_certificate.spouse_case') }}
                        @else
                            {{ __('rescue_certificate.victim_normal') }}
                        @endif
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('rescue_certificate.incident_description') }}</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100 whitespace-pre-line">{{ $certificate->incident_description ?? '—' }}</p>
                </div>
                @if ($certificate->notes)
                    <div class="sm:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('rescue_certificate.notes') }}</p>
                        <p class="mt-1 text-sm text-on-surface dark:text-gray-100 whitespace-pre-line">{{ $certificate->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Free procedure (مجاني) --}}
        <div class="rounded-2xl border border-emerald-200 bg-surface-container-lowest shadow-sm dark:border-emerald-800/40 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-3 border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                <div>
                    <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.fees_title') }}</h2>
                    <p class="mt-0.5 text-xs text-secondary dark:text-gray-400">{{ __('rescue_certificate.fees_info') }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    {{ __('rescue_certificate.free') }}
                </span>
            </div>
        </div>

        -- Certificate delivery (تسليم الشهادة) --}}
        @if (in_array($status, ['approved', 'served']) || $certificate->delivered_at)
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.delivery_title') }}</h2>
                    <p class="mt-0.5 text-xs text-secondary dark:text-gray-400">{{ __('rescue_certificate.delivery_subtitle') }}</p>
                </div>
                @if ($certificate->isDelivered())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-teal-100 px-2.5 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-900/40 dark:text-teal-300">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        {{ __('rescue_certificate.delivery_status_done') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container px-2.5 py-1 text-xs font-semibold text-secondary dark:bg-gray-700 dark:text-gray-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                        {{ __('rescue_certificate.delivery_status_pending') }}
                    </span>
                @endif
            </div>
            @if ($certificate->delivered_at || $certificate->certificate_number || $certificate->delivery_date)
                <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('rescue_certificate.certificate_number') }}</p>
                        <p class="mt-1 text-sm font-mono font-semibold text-on-surface dark:text-gray-100">{{ $certificate->certificate_number ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('rescue_certificate.delivery_date') }}</p>
                        <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $certificate->delivery_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    @if ($certificate->delivery_note)
                        <div class="sm:col-span-2">
                            <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('rescue_certificate.delivery_note') }}</p>
                            <p class="mt-1 text-sm text-on-surface dark:text-gray-100 whitespace-pre-line">{{ $certificate->delivery_note }}</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="px-6 py-4 text-sm text-outline dark:text-gray-500">
                    {{ __('rescue_certificate.delivery_hint_panel') }}
                </div>
            @endif
        </div>
        @endif

        {{-- Documents --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">Uploaded Documents</h2>
                <p class="mt-0.5 text-xs text-secondary dark:text-gray-400">{{ $uploadedDocs->count() }} / {{ $documentTypes->count() }} uploaded</p>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @forelse ($documentTypes as $docType)
                    @php $doc = $uploadedDocs->get($docType->id); @endphp
                    <div class="flex items-center justify-between gap-4 px-6 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full {{ $doc ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500' }}">
                                @if ($doc)
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @endif
                            </span>
                            <div>
                                <p class="text-sm font-medium text-on-surface dark:text-gray-100">{{ $docType->name }}</p>
                                @if ($doc)
                                    <p class="text-xs text-secondary dark:text-gray-400">{{ $doc->original_name }} ({{ number_format($doc->size / 1024, 0) }} KB) — {{ $doc->updated_at->format('d M Y') }}</p>
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
                    <div class="px-6 py-6 text-center text-sm text-outline">No document types configured.</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ─── Right sidebar: actions + timeline ─── --}}
    <div class="space-y-5">

        {{-- Deliver the certificate (only when approved) --}}
        @can('manage rescue-certificates')
        @if ($status === 'approved')
        <div class="rounded-2xl border border-teal-200 bg-surface-container-lowest shadow-sm dark:border-teal-800/40 dark:bg-gray-800" x-data="{ open: {{ $certificate->delivered_at ? 'false' : 'true' }} }">
            <div class="border-b border-teal-100 dark:border-teal-800/30 px-5 py-4 flex items-center justify-between cursor-pointer" @click="open = !open">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-teal-600 dark:text-teal-400 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-teal-800 dark:text-teal-300">{{ __('rescue_certificate.delivery_title') }}</h3>
                </div>
                <svg class="h-4 w-4 text-teal-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="px-5 py-4">
                <p class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:border-emerald-800/30 dark:bg-emerald-900/10 dark:text-emerald-300">
                    {{ __('rescue_certificate.free_info') }}
                </p>
                <form method="POST" action="{{ route('admin.rescue-certificates.deliver', $certificate) }}" onsubmit="return confirm('Deliver the rescue & relief certificate? The applicant will see the details.')">
                    @csrf
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">{{ __('rescue_certificate.certificate_number') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="certificate_number" maxlength="50" required
                                value="{{ old('certificate_number') }}"
                                class="w-full text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">{{ __('rescue_certificate.delivery_date') }} <span class="text-red-500">*</span></label>
                            <input type="date" name="delivery_date" required
                                value="{{ old('delivery_date', now()->format('Y-m-d')) }}"
                                class="w-full text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">{{ __('rescue_certificate.delivery_note') }}</label>
                            <textarea name="delivery_note" rows="3" maxlength="2000"></textarea>
                        </div>
                        <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            {{ __('rescue_certificate.delivery_deliver_btn') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
        @endcan

        {{-- Actions --}}
        @can('manage rescue-certificates')
        @if ($certificate->isActionable())
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-on-surface dark:text-gray-100">Actions</h3>
                <p class="text-xs text-secondary dark:text-gray-400 mt-0.5">Move this request to the next step</p>
            </div>
            <div class="px-5 py-4 space-y-3">

                {{-- Advance to Processing --}}
                @if (in_array($status, ['completed', 'needs_correction']))
                    <form method="POST" action="{{ route('admin.rescue-certificates.advance', $certificate) }}"
                          onsubmit="return confirm('Advance this request? You must verify the data against the rescue operation records. The applicant will be notified by phone.')">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Advance to Processing
                        </button>
                    </form>
                @endif

                {{-- Approve --}}
                @if (in_array($status, ['completed', 'under_consultation']))
                    <form method="POST" action="{{ route('admin.rescue-certificates.approve', $certificate) }}"
                          onsubmit="return confirm('Approve this request? Next step: deliver the certificate when the applicant collects it (free procedure).')">
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
                        <form method="POST" action="{{ route('admin.rescue-certificates.correction', $certificate) }}">
                            @csrf
                            <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1.5">Note to applicant <span class="text-red-500">*</span></label>
                            <textarea name="admin_note" rows="5" required maxlength="2000"
                                placeholder="Describe what needs to be fixed (e.g. missing document, wrong file, incorrect data)...">{{ old('admin_note') }}</textarea>
                            <p class="mt-1 text-xs text-outline dark:text-gray-500">This note will be shown to the applicant with a "Fix & Resubmit" button.</p>
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
                        <form method="POST" action="{{ route('admin.rescue-certificates.cancel', $certificate) }}"
                              onsubmit="return confirm('Cancel this request? The applicant will be notified.')">
                            @csrf
                            <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1.5">Cancellation reason <span class="text-red-500">*</span></label>
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
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">The procedure is free. Deliver the certificate when the applicant collects it.</p>
            </div>
        @elseif ($status === 'served')
            <div class="rounded-2xl border border-teal-200 bg-teal-50 dark:border-teal-800/40 dark:bg-teal-900/10 px-5 py-4 text-center">
                <svg class="h-8 w-8 text-teal-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-semibold text-teal-800 dark:text-teal-200">Certificate Delivered</p>
                <p class="text-xs text-teal-600 dark:text-teal-400 mt-1">This request has been fully completed.</p>
            </div>
        @elseif ($status === 'cancelled')
            <div class="rounded-2xl border border-surface-dim bg-surface-container dark:border-gray-700 dark:bg-gray-700/20 px-5 py-4 text-center">
                <svg class="h-8 w-8 text-outline mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-semibold text-on-surface dark:text-gray-300">Request Cancelled</p>
                <p class="text-xs text-secondary dark:text-gray-400 mt-1">This request has been closed.</p>
            </div>
        @endif
        @endcan

        {{-- Timeline — full audit log (every event) --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-on-surface dark:text-gray-100">History & Timeline</h3>
                <p class="text-xs text-secondary dark:text-gray-400 mt-0.5">{{ $certificate->events->count() }} recorded events</p>
            </div>
            <div class="px-5 py-4">
                @forelse ($certificate->events as $event)
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
                            <p class="text-xs font-semibold text-on-surface dark:text-gray-100 flex items-center gap-2 flex-wrap">
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
                            <p class="text-xs text-secondary dark:text-gray-400">
                                {{ $event->actor?->name ?? 'System' }}
                                · {{ $event->created_at->format('d M Y, H:i') }}
                                ({{ $event->created_at->diffForHumans() }})
                            </p>

                            {{-- Extra details (payment / service) --}}
                            @if ($detailsText = $event->detailsText())
                                <p class="mt-1 text-xs text-secondary dark:text-gray-300 font-medium">{{ $detailsText }}</p>
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
                                    <p class="mt-1 text-xs text-secondary dark:text-gray-300 whitespace-pre-line">{{ $event->note }}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-2 text-center text-sm text-outline">No events recorded yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Quick info --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800 px-5 py-4">
            <h3 class="text-sm font-semibold text-on-surface dark:text-gray-100 mb-3">Quick Info</h3>
            <dl class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">Request ID</dt>
                    <dd class="font-mono font-semibold text-on-surface dark:text-gray-100">#{{ $certificate->id }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">Step reached</dt>
                    <dd class="font-semibold text-on-surface dark:text-gray-100">Step {{ $certificate->current_step }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">Docs uploaded</dt>
                    <dd class="font-semibold text-on-surface dark:text-gray-100">{{ $uploadedDocs->count() }} / {{ $documentTypes->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">Submitted</dt>
                    <dd class="text-on-surface dark:text-gray-300">{{ $certificate->created_at->diffForHumans() }}</dd>
                </div>
            </dl>
        </div>

    </div>{{-- /right sidebar --}}

</div>
@endsection
