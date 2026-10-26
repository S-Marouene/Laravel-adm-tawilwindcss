@extends('admin.layouts.admin')
@section('title', 'Prevention Dossier #' . $preventionDossier->id)

@php
    $status = $preventionDossier->status;
    $statusConfig = [
        'completed'          => ['label' => 'Under Review',       'dot' => 'bg-blue-500',    'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
        'submitted'          => ['label' => 'Submitted',          'dot' => 'bg-teal-500',    'badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'],
        'under_consultation' => ['label' => 'Under Consultation', 'dot' => 'bg-purple-500',  'badge' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
        'needs_correction'   => ['label' => 'Needs Correction',   'dot' => 'bg-red-500',     'badge' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'],
        'approved'           => ['label' => 'Approved',           'dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
        'cancelled'          => ['label' => 'Cancelled',          'dot' => 'bg-gray-400',    'badge' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'],
    ];
    $sc = $statusConfig[$status] ?? ['label' => ucfirst($status), 'dot' => 'bg-gray-400', 'badge' => 'bg-gray-100 text-gray-600'];
@endphp

@section('content')
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dossier #{{ $preventionDossier->id }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Prevention certificate request — {{ $preventionDossier->created_at->format('d M Y') }}</p>
    </div>
    <a href="{{ route('admin.prevention-dossiers.index') }}"
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
                    @if ($preventionDossier->reviewed_at)
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            by {{ $preventionDossier->reviewer?->name ?? 'Admin' }} · {{ $preventionDossier->reviewed_at->format('d M Y, H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Admin note display (needs_correction / cancelled) --}}
            @if ($preventionDossier->admin_note)
                <div class="px-6 py-4 {{ $status === 'needs_correction' ? 'bg-red-50 dark:bg-red-900/10' : ($status === 'cancelled' ? 'bg-gray-50 dark:bg-gray-700/20' : '') }}">
                    <p class="text-xs font-semibold uppercase tracking-wide {{ $status === 'needs_correction' ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }} mb-2">
                        Admin Note (visible to applicant)
                    </p>
                    <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $preventionDossier->admin_note }}</p>
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
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Applicant Information</h2>
            </div>
            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Full Name</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $preventionDossier->nom_prenom }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">CIN</p>
                    <p class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $preventionDossier->cin }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Phone</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $preventionDossier->phone ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Account Email</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $preventionDossier->user?->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Governorate</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $preventionDossier->governorate?->name_fr ?? $preventionDossier->governorate?->name_ar ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Delegation</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $preventionDossier->delegation?->name_fr ?? $preventionDossier->delegation?->name_ar ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Building info --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Building Information</h2>
            </div>
            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Building Type</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $preventionDossier->buildingType?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Activity</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        {{ $preventionDossier->buildingActivity?->name ?? $preventionDossier->activity_other ?? '—' }}
                    </p>
                </div>
                @if ($preventionDossier->num_demande)
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Request Number</p>
                        <p class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $preventionDossier->num_demande }}</p>
                    </div>
                @endif
                @if ($preventionDossier->api_reference)
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">API Reference</p>
                        <p class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $preventionDossier->api_reference }}</p>
                    </div>
                @endif
            </div>
        </div>

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

        {{-- Appointments / RDV --}}
        @if ($preventionDossier->appointments->isNotEmpty() || $preventionDossier->status === 'approved')
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Site Visits (Rendez-vous)</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $preventionDossier->appointments->count() }} visit(s) scheduled</p>
                </div>
            </div>

            @if ($preventionDossier->appointments->isEmpty())
                <div class="px-6 py-8 text-center">
                    <svg class="h-8 w-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-gray-400">No visits scheduled yet. Use the panel on the right to schedule the first one.</p>
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
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $appt->visit_date->format('d M Y') }} at {{ substr($appt->visit_time, 0, 5) }}
                                            </span>
                                        </div>

                                        @if ($appt->location)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                <svg class="h-3 w-3 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                {{ $appt->location }}
                                            </p>
                                        @endif

                                        @if ($appt->scheduler)
                                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Scheduled by {{ $appt->scheduler->name }}</p>
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
                                                class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
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

        {{-- Schedule Rendez-vous (only when approved) --}}
        @can('manage prevention-dossiers')
        @if ($status === 'approved')
        <div class="rounded-2xl border border-emerald-200 bg-white shadow-sm dark:border-emerald-800/40 dark:bg-gray-800" x-data="{ open: {{ $preventionDossier->appointments->isEmpty() ? 'true' : 'false' }} }">
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
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Visit Date <span class="text-red-500">*</span></label>
                            <input type="date" name="visit_date" required
                                min="{{ date('Y-m-d') }}"
                                value="{{ old('visit_date') }}"
                                class="w-full text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Visit Time <span class="text-red-500">*</span></label>
                            <input type="time" name="visit_time" required
                                value="{{ old('visit_time') }}"
                                class="w-full text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Location / Address</label>
                            <input type="text" name="location" maxlength="500"
                                value="{{ old('location') }}"
                                placeholder="Building address or reference..."
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

        {{-- Actions --}}
        @can('manage prevention-dossiers')
        @if ($preventionDossier->isActionable())
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Actions</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Move this dossier to the next step</p>
            </div>
            <div class="px-5 py-4 space-y-3">

                {{-- Advance to Consultation --}}
                @if (in_array($status, ['completed', 'submitted', 'needs_correction']))
                    <form method="POST" action="{{ route('admin.prevention-dossiers.advance', $preventionDossier) }}"
                          onsubmit="return confirm('Advance to consultation? The applicant will see a notice to keep their phone available.')">
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
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5">Cancellation reason <span class="text-red-500">*</span></label>
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
            <div class="rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/20 px-5 py-4 text-center">
                <svg class="h-8 w-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Dossier Cancelled</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">This dossier has been closed.</p>
            </div>
        @endif
        @endcan

        {{-- Timeline --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Timeline</h3>
            </div>
            <div class="px-5 py-4">
                {{-- Submitted --}}
                <div class="flex items-start gap-3">
                    <span class="mt-1 flex-none h-2 w-2 rounded-full bg-emerald-500"></span>
                    <div>
                        <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">Submitted by applicant</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $preventionDossier->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>

                @if ($preventionDossier->reviewed_at && $status !== 'completed')
                    <div class="ms-[3px] my-1.5 w-0.5 h-5 bg-gray-200 dark:bg-gray-600"></div>
                    @php
                        $timelineDot = match($status) {
                            'under_consultation' => 'bg-purple-500',
                            'needs_correction'   => 'bg-amber-500',
                            'approved'           => 'bg-emerald-500',
                            'cancelled'          => 'bg-red-400',
                            default              => 'bg-blue-500',
                        };
                        $timelineLabel = match($status) {
                            'under_consultation' => 'Advanced to Consultation',
                            'needs_correction'   => 'Correction Requested',
                            'approved'           => 'Approved',
                            'cancelled'          => 'Cancelled',
                            default              => 'Status updated',
                        };
                    @endphp
                    <div class="flex items-start gap-3">
                        <span class="mt-1 flex-none h-2 w-2 rounded-full {{ $timelineDot }}"></span>
                        <div>
                            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $timelineLabel }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $preventionDossier->reviewer?->name ?? 'Admin' }} · {{ $preventionDossier->reviewed_at->format('d M Y, H:i') }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick info --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 px-5 py-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Quick Info</h3>
            <dl class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Dossier ID</dt>
                    <dd class="font-mono font-semibold text-gray-900 dark:text-gray-100">#{{ $preventionDossier->id }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Step reached</dt>
                    <dd class="font-semibold text-gray-900 dark:text-gray-100">Step {{ $preventionDossier->current_step }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Docs uploaded</dt>
                    <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $uploadedDocs->count() }} / {{ $documentTypes->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Submitted</dt>
                    <dd class="text-gray-700 dark:text-gray-300">{{ $preventionDossier->created_at->diffForHumans() }}</dd>
                </div>
            </dl>
        </div>

    </div>{{-- /right sidebar --}}

</div>
@endsection
