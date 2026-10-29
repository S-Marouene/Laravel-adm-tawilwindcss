@extends('admin.layouts.admin')
@section('title', 'Prevention Dossier #' . $preventionDossier->id)

@php
    $status = $preventionDossier->status;
    $statusConfig = [
        'completed'          => ['label' => 'Under Review',       'dot' => 'bg-blue-500',    'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
        'submitted'          => ['label' => 'Submitted',          'dot' => 'bg-teal-500',    'badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'],
        'under_consultation' => ['label' => 'Approved — Awaiting RDV', 'dot' => 'bg-purple-500',  'badge' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
        'needs_correction'   => ['label' => 'Needs Correction',   'dot' => 'bg-red-500',     'badge' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'],
        'approved'           => ['label' => 'Approved',           'dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
        'cancelled'          => ['label' => 'Cancelled',          'dot' => 'bg-gray-400',    'badge' => 'bg-surface-container text-secondary dark:bg-gray-700 dark:text-gray-400'],
    ];
    $sc = $statusConfig[$status] ?? ['label' => ucfirst($status), 'dot' => 'bg-gray-400', 'badge' => 'bg-surface-container text-secondary'];
@endphp

@section('content')
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">Dossier #{{ $preventionDossier->id }}</h1>
        <p class="text-sm text-secondary dark:text-gray-400 mt-1">Prevention certificate request — {{ $preventionDossier->created_at->format('d M Y') }}</p>
    </div>
    <a href="{{ route('admin.prevention-dossiers.index') }}"
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
                    @if ($preventionDossier->reviewed_at)
                        <span class="text-xs text-outline dark:text-gray-500">
                            by {{ $preventionDossier->reviewer?->name ?? 'Admin' }} · {{ $preventionDossier->reviewed_at->format('d M Y, H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Admin note display (needs_correction / cancelled) --}}
            @if ($preventionDossier->admin_note)
                <div class="px-6 py-4 {{ $status === 'needs_correction' ? 'bg-red-50 dark:bg-red-900/10' : ($status === 'cancelled' ? 'bg-surface-container dark:bg-gray-700/20' : '') }}">
                    <p class="text-xs font-semibold uppercase tracking-wide {{ $status === 'needs_correction' ? 'text-red-600 dark:text-red-400' : 'text-secondary dark:text-gray-400' }} mb-2">
                        Admin Note (visible to applicant)
                    </p>
                    <p class="text-sm text-on-surface dark:text-gray-200 whitespace-pre-line">{{ $preventionDossier->admin_note }}</p>
                </div>
            @endif

            {{-- Under consultation phone highlight --}}
            @if ($status === 'under_consultation')
                <div class="flex items-center gap-3 px-6 py-3 bg-purple-50 dark:bg-purple-900/10">
                    <svg class="h-4 w-4 text-purple-500 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span class="text-sm text-purple-700 dark:text-purple-300">
                        Applicant's phone: <strong class="font-mono">{{ $preventionDossier->phone ?? 'Not provided' }}</strong>
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
                    <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $preventionDossier->nom_prenom }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">CIN</p>
                    <p class="mt-1 text-sm font-mono text-on-surface dark:text-gray-100">{{ $preventionDossier->cin }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Phone</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $preventionDossier->phone ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Account Email</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $preventionDossier->user?->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Governorate</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $preventionDossier->governorate?->name_fr ?? $preventionDossier->governorate?->name_ar ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Delegation</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $preventionDossier->delegation?->name_fr ?? $preventionDossier->delegation?->name_ar ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Building info --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">Building Information</h2>
            </div>
            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Building Type</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $preventionDossier->buildingType?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Activity</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">
                        {{ $preventionDossier->buildingActivity?->name ?? $preventionDossier->activity_other ?? '—' }}
                    </p>
                </div>
                @if ($preventionDossier->num_demande)
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Request Number</p>
                        <p class="mt-1 text-sm font-mono text-on-surface dark:text-gray-100">{{ $preventionDossier->num_demande }}</p>
                    </div>
                @endif
                @if ($preventionDossier->api_reference)
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">API Reference</p>
                        <p class="mt-1 text-sm font-mono text-on-surface dark:text-gray-100">{{ $preventionDossier->api_reference }}</p>
                    </div>
                @endif
            </div>
        </div>

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

        {{-- Appointments / RDV --}}
        @if ($preventionDossier->appointments->isNotEmpty() || $preventionDossier->status === 'approved')
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">Site Visits (Rendez-vous)</h2>
                    <p class="mt-0.5 text-xs text-secondary dark:text-gray-400">
                        {{ $preventionDossier->appointments->count() }} visit(s) scheduled
                        @if ($preventionDossier->building_address)
                            · Visit at: <span class="font-semibold text-on-surface dark:text-gray-300">{{ $preventionDossier->building_address }}</span>
                        @endif
                    </p>
                </div>
            </div>

            @if ($preventionDossier->appointments->isEmpty())
                <div class="px-6 py-8 text-center">
                    <svg class="h-8 w-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-outline">No visits scheduled yet. Use the panel on the right to schedule the first one.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach ($preventionDossier->appointments as $i => $appt)
                        <div class="px-6 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    {{-- Visit number + status icon --}}
                                    <div class="flex-none flex flex-col items-center gap-1">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold
                                            {{ $appt->isPassed() ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                                            {{ $appt->isFailed() ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : '' }}
                                            {{ $appt->isPending() ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}">
                                            #{{ $i + 1 }}
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        {{-- Status badge + datetime --}}
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @if ($appt->isPassed())
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                    Passed
                                                </span>
                                            @elseif ($appt->isFailed())
                                                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    Failed
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                                    Scheduled
                                                </span>
                                            @endif
                                            <span class="text-sm font-semibold text-on-surface dark:text-gray-100">
                                                {{ $appt->visit_date->format('d M Y') }} at {{ substr($appt->visit_time, 0, 5) }}
                                            </span>
                                        </div>

                                        @if ($appt->scheduler)
                                            <p class="mt-1 text-xs text-outline dark:text-gray-500">Scheduled by {{ $appt->scheduler->name }}</p>
                                        @endif

                                        {{-- Inspector note (failed visits) --}}
                                        @if ($appt->note)
                                            <div class="mt-2 rounded-lg border border-red-200 bg-red-50 dark:border-red-800/30 dark:bg-red-900/10 px-3 py-2">
                                                <p class="text-xs font-semibold text-red-600 dark:text-red-400 mb-0.5">Inspector note:</p>
                                                <p class="text-xs text-red-800 dark:text-red-200 whitespace-pre-line">{{ $appt->note }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Actions: update result or delete --}}
                                @can('manage prevention-dossiers')
                                <div class="flex-none" x-data="{ openResult: false }">
                                    @if ($appt->isPending())
                                        <div class="flex items-center gap-2">
                                            <button type="button" @click="openResult = !openResult"
                                                class="inline-flex items-center gap-1 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-1.5 text-xs font-semibold text-on-surface hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                                Record Result
                                                <svg class="h-3 w-3 transition-transform" :class="openResult ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <form method="POST" action="{{ route('admin.prevention-dossiers.appointments.destroy', [$preventionDossier, $appt]) }}"
                                                  onsubmit="return confirm('Remove this appointment?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                        {{-- Result form --}}
                                        <div x-show="openResult"
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             class="mt-3 w-64">
                                            <form method="POST" action="{{ route('admin.prevention-dossiers.appointments.update', [$preventionDossier, $appt]) }}">
                                                @csrf @method('PUT')
                                                <div class="flex gap-2 mb-2">
                                                    <label class="flex-1 flex items-center gap-1.5 cursor-pointer rounded-lg border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20">
                                                        <input type="radio" name="status" value="passed" class="sr-only" required>
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                        Passed
                                                    </label>
                                                    <label class="flex-1 flex items-center gap-1.5 cursor-pointer rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-300 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20">
                                                        <input type="radio" name="status" value="failed" class="sr-only" required>
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        Failed
                                                    </label>
                                                </div>
                                                <textarea name="note" rows="3" placeholder="Inspector note (required if failed)..." class="text-xs"></textarea>
                                                <button type="submit" class="mt-2 w-full rounded-lg bg-gov-600 px-3 py-2 text-xs font-semibold text-white hover:bg-gov-700">
                                                    Save Result
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

    </div>

    {{-- ─── Right sidebar: actions + timeline ─── --}}
    <div class="space-y-5">

        {{-- Schedule Rendez-vous (available once the dossier is approved via consultation) --}}
        @can('manage prevention-dossiers')
        @if (in_array($status, ['under_consultation', 'approved']))
        <div class="rounded-2xl border border-emerald-200 bg-surface-container-lowest shadow-sm dark:border-emerald-800/40 dark:bg-gray-800" x-data="{ open: {{ $preventionDossier->appointments->isEmpty() ? 'true' : 'false' }} }">
            <div class="border-b border-emerald-100 dark:border-emerald-800/30 px-5 py-4 flex items-center justify-between cursor-pointer" @click="open = !open">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Schedule a Visit (RDV)</h3>
                </div>
                <svg class="h-4 w-4 text-emerald-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="px-5 py-4">
                <form method="POST" action="{{ route('admin.prevention-dossiers.appointments.store', $preventionDossier) }}">
                    @csrf
                    <div class="space-y-3">
                        {{-- The visit happens at the building — address comes from the dossier --}}
                        <div class="flex items-start gap-1.5 text-xs text-emerald-700 dark:text-emerald-300">
                            <svg class="h-3.5 w-3.5 flex-none mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="flex items-start gap-1">
                                Visit takes place at the building:
                                <strong class="font-semibold break-all">{{ $preventionDossier->building_address ?? '—' }}</strong>
                            </span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">Visit Date <span class="text-red-500">*</span></label>
                            <input type="date" name="visit_date" required
                                min="{{ date('Y-m-d') }}"
                                value="{{ old('visit_date') }}"
                                class="w-full text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">Visit Time <span class="text-red-500">*</span></label>
                            <input type="time" name="visit_time" required
                                value="{{ old('visit_time') }}"
                                class="w-full text-sm">
                        </div>
                        <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Schedule Visit
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
        @endcan

        {{-- Prevention certificate (issued after a passed RDV) --}}
        @can('manage prevention-dossiers')
        @if ($preventionDossier->hasPassedAppointment() || $preventionDossier->certificate)
        <div class="rounded-2xl border border-emerald-200 bg-surface-container-lowest shadow-sm dark:border-emerald-800/40 dark:bg-gray-800"
            x-data="{ open: false }">

            <div class="border-b border-emerald-100 dark:border-emerald-800/30 px-5 py-4 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 3h10a2 2 0 012 2v16l-7-3-7 3V5a2 2 0 012-2z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Prevention Certificate</h3>
                </div>
                @if ($preventionDossier->certificate)
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold
                        {{ $preventionDossier->certificate->isCollected() ? 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' }}">
                        {{ $preventionDossier->certificate->statusLabel() }}
                    </span>
                @endif
            </div>

            @if ($preventionDossier->certificate)
                @php $cert = $preventionDossier->certificate; @endphp
                <div class="px-5 py-4 space-y-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">Reference</p>
                        <p class="text-sm font-mono font-bold text-emerald-700 dark:text-emerald-300">{{ $cert->reference }}</p>
                    </div>
                    <dl class="space-y-2 text-xs">
                        <div class="flex justify-between gap-2">
                            <dt class="text-secondary dark:text-gray-400">Issued</dt>
                            <dd class="font-semibold text-on-surface dark:text-gray-100">{{ $cert->issued_at->format('d M Y') }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-secondary dark:text-gray-400">Valid until</dt>
                            <dd class="font-semibold text-on-surface dark:text-gray-100">{{ $cert->valid_until ? $cert->valid_until->format('d M Y') : '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-secondary dark:text-gray-400">Pickup gouvernorat</dt>
                            <dd class="text-end font-semibold text-on-surface dark:text-gray-100">
                                {{ $cert->governorate?->name_fr ?? $cert->governorate?->name_ar ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-secondary dark:text-gray-400">Pickup delegation</dt>
                            <dd class="text-end font-semibold text-on-surface dark:text-gray-100">
                                {{ $cert->delegation?->name_fr ?? $cert->delegation?->name_ar ?? '— All delegations —' }}
                            </dd>
                        </div>
                        @if ($cert->collected_at)
                            <div class="flex justify-between gap-2">
                                <dt class="text-secondary dark:text-gray-400">Collected on</dt>
                                <dd class="font-semibold text-on-surface dark:text-gray-100">{{ $cert->collected_at->format('d M Y, H:i') }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($cert->note)
                        <div class="rounded-lg border border-surface-dim bg-surface-container dark:border-gray-600 dark:bg-gray-700/30 px-2.5 py-2">
                            <p class="text-xs font-semibold text-secondary dark:text-gray-400 mb-0.5">Internal note</p>
                            <p class="text-xs text-secondary dark:text-gray-300 whitespace-pre-line">{{ $cert->note }}</p>
                        </div>
                    @endif

                    <div class="space-y-2 pt-1">
                        @if ($cert->isReady())
                            <form method="POST" action="{{ route('admin.prevention-dossiers.certificate.update', $preventionDossier) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="action" value="collected">
                                <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/40">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Mark as Collected
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.prevention-dossiers.certificate.update', $preventionDossier) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="action" value="ready">
                                <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-teal-300 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-700 transition hover:bg-teal-100 dark:border-teal-700 dark:bg-teal-900/20 dark:text-teal-300">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                    Mark as Ready (un-collect)
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                <div class="px-5 py-4">
                    <p class="mb-3 flex items-start gap-1.5 text-xs text-emerald-700 dark:text-emerald-300">
                        <svg class="h-3.5 w-3.5 flex-none mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>A visit was marked as <strong>passed</strong>. You can now issue the certificate — the applicant will see where to pick it up.</span>
                    </p>
                    <button type="button" @click="open = !open"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/40">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Issue Certificate
                    </button>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mt-4">
                        <form method="POST" action="{{ route('admin.prevention-dossiers.certificate.store', $preventionDossier) }}">
                            @csrf
                            <div class="space-y-3">
                        {{-- Gouvernorat (required) + Delegation (optional, filtered by gouvernorat) --}}
                        @php
                            $certDelegationsMap = $governorates
                                ->mapWithKeys(fn ($g) => [
                                    $g->id => ['name' => $g->name_fr ?? $g->name_ar, 'delegations' => $g->delegations->map(fn ($d) => ['id' => $d->id, 'name' => $d->name_fr ?? $d->name_ar])->values()->all()],
                                ])
                                ->toArray();
                        @endphp
                        <div x-data="{
                                gov: {{ old('governorate_id', $preventionDossier->governorate_id) ?? 'null' }},
                                del: {{ old('delegation_id') ? old('delegation_id') : 'null' }},
                                map: {{ \Illuminate\Support\Js::from($certDelegationsMap) }}
                            }">
                            <div>
                                <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">Gouvernorat <span class="text-red-500">*</span></label>
                                <select name="governorate_id" required x-model="gov"
                                    class="w-full text-sm">
                                    <option value="">Select gouvernorat…</option>
                                    <template x-for="(entry, govId) in map" :key="govId">
                                        <option :value="govId" x-text="entry.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="mt-3">
                                <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">Pickup delegation <span class="text-outline font-normal">(optional)</span></label>
                                <select name="delegation_id" x-model="del" class="w-full text-sm">
                                    <option value="">— All delegations —</option>
                                    <template x-for="d in (map[gov] ? map[gov].delegations : [])" :key="d.id">
                                        <option :value="d.id" x-text="d.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                                <div>
                                    <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">Valid until (optional)</label>
                                    <input type="date" name="valid_until" min="{{ date('Y-m-d') }}" value="{{ old('valid_until') }}" class="w-full text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">Internal note (optional)</label>
                                    <textarea name="note" rows="2" maxlength="2000" placeholder="Internal note (not visible to the applicant)..." class="text-xs">{{ old('note') }}</textarea>
                                </div>
                                <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/40">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Confirm &amp; Issue
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
        @endif
        @endcan

        {{-- Actions --}}
        @can('manage prevention-dossiers')
        @if ($preventionDossier->isActionable())
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-on-surface dark:text-gray-100">Actions</h3>
                <p class="text-xs text-secondary dark:text-gray-400 mt-0.5">Move this dossier to the next step</p>
            </div>
            <div class="px-5 py-4 space-y-3">

                {{-- Advance to Consultation --}}
                @if (in_array($status, ['completed', 'submitted', 'needs_correction']))
                    <form method="POST" action="{{ route('admin.prevention-dossiers.advance', $preventionDossier) }}"
                          onsubmit="return confirm('Advance to consultation? This validates the dossier as approved — the applicant is notified and you can then schedule an RDV.')">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Advance to Consultation
                        </button>
                    </form>
                @endif

                {{-- Approve --}}
                @if (in_array($status, ['completed', 'submitted', 'under_consultation']))
                    <form method="POST" action="{{ route('admin.prevention-dossiers.approve', $preventionDossier) }}"
                          onsubmit="return confirm('Approve this dossier? This action marks it as fully resolved.')">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Approve Dossier
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
                        <form method="POST" action="{{ route('admin.prevention-dossiers.correction', $preventionDossier) }}">
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
                        <span x-text="open ? 'Hide' : 'Cancel Dossier'">Cancel Dossier</span>
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
                        <form method="POST" action="{{ route('admin.prevention-dossiers.cancel', $preventionDossier) }}"
                              onsubmit="return confirm('Cancel this dossier? The applicant will be notified.')">
                            @csrf
                            <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1.5">Cancellation reason <span class="text-red-500">*</span></label>
                            <textarea name="admin_note" rows="4" required maxlength="2000"
                                placeholder="Explain why this dossier is being cancelled...">{{ old('admin_note') }}</textarea>
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
                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Dossier Approved</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">No further actions needed.</p>
            </div>
        @elseif ($status === 'cancelled')
            <div class="rounded-2xl border border-surface-dim bg-surface-container dark:border-gray-700 dark:bg-gray-700/20 px-5 py-4 text-center">
                <svg class="h-8 w-8 text-outline mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-semibold text-on-surface dark:text-gray-300">Dossier Cancelled</p>
                <p class="text-xs text-secondary dark:text-gray-400 mt-1">This dossier has been closed.</p>
            </div>
        @endif
        @endcan

        {{-- Timeline — full audit history (corrections, approvals, RDV, etc.) --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-on-surface dark:text-gray-100">Timeline</h3>
                <p class="text-xs text-secondary dark:text-gray-400 mt-0.5">Complete history of this dossier</p>
            </div>
            <div class="px-5 py-4">
                {{-- Legacy fallback: dossiers created before the events table — show their origin from created_at --}}
                @if ($preventionDossier->events->isEmpty())
                    <div class="flex items-start gap-3">
                        <span class="mt-1 flex-none h-2 w-2 rounded-full bg-teal-500"></span>
                        <div>
                            <p class="text-xs font-semibold text-on-surface dark:text-gray-100">Submitted by applicant</p>
                            <p class="text-xs text-secondary dark:text-gray-400">{{ $preventionDossier->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                @endif

                {{-- All recorded events, in chronological order --}}
                @foreach ($preventionDossier->events as $event)
                    @if (! $loop->first)
                        <div class="ms-[3px] my-1.5 w-0.5 h-5 bg-gray-200 dark:bg-gray-600"></div>
                    @endif
                    <div class="flex items-start gap-3">
                        <span class="mt-1 flex-none h-2 w-2 rounded-full {{ $event->typeDot() }}"></span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-on-surface dark:text-gray-100">{{ $event->typeLabel() }}</p>
                            <p class="text-xs text-secondary dark:text-gray-400">
                                {{ $event->actor?->name ?? 'System' }} · {{ $event->created_at->format('d M Y, H:i') }}
                            </p>

                            {{-- RDV details (date / time / location) --}}
                            @if ($event->detailsText())
                                <p class="mt-1 text-xs inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                                    <svg class="h-3 w-3 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ $event->detailsText() }}
                                </p>
                            @endif

                            {{-- Note attached to the event (correction note, fail reason, cancellation reason...) --}}
                            @if ($event->note)
                                <div class="mt-1.5 rounded-lg border bg-surface-container px-2.5 py-2 dark:bg-gray-700/30 {{ str_contains($event->type, 'failed') || str_contains($event->type, 'cancelled') ? 'border-red-200 dark:border-red-800/30' : 'border-surface-dim dark:border-gray-600' }}">
                                    <p class="text-xs font-semibold text-secondary dark:text-gray-400 mb-0.5">Note</p>
                                    <p class="text-xs text-secondary dark:text-gray-300 whitespace-pre-line">{{ $event->note }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick info --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800 px-5 py-4">
            <h3 class="text-sm font-semibold text-on-surface dark:text-gray-100 mb-3">Quick Info</h3>
            <dl class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">Dossier ID</dt>
                    <dd class="font-mono font-semibold text-on-surface dark:text-gray-100">#{{ $preventionDossier->id }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">Step reached</dt>
                    <dd class="font-semibold text-on-surface dark:text-gray-100">Step {{ $preventionDossier->current_step }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">Docs uploaded</dt>
                    <dd class="font-semibold text-on-surface dark:text-gray-100">{{ $uploadedDocs->count() }} / {{ $documentTypes->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">Submitted</dt>
                    <dd class="text-on-surface dark:text-gray-300">{{ $preventionDossier->created_at->diffForHumans() }}</dd>
                </div>
            </dl>
        </div>

    </div>{{-- /right sidebar --}}

</div>
@endsection
