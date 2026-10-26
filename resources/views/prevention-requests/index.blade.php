<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ __('prevention_request.index_title') }}</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('prevention_request.index_subtitle') }}</p>
            </div>
            <a href="{{ route('prevention-requests.create') }}" class="ui-btn-primary inline-flex items-center justify-center gap-2 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('prevention_request.new_request') }}</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Success flash --}}
            @if (session('success'))
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 flex-none text-emerald-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('info'))
                <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 flex-none text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ session('info') }}</span>
                    </div>
                </div>
            @endif

            {{-- Validation errors --}}
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                    <p class="font-semibold">{{ __('prevention_request.create_error_title') }}</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ── Submitted / Processed dossiers ── --}}
            @if ($submitted->isNotEmpty())
                <section class="mb-8">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('prevention_request.submitted_title') }}
                    </h3>
                    <div class="space-y-4">
                        @foreach ($submitted as $dossier)
                            @php
                                $status = $dossier->status;
                            @endphp
                            <div class="overflow-hidden rounded-2xl border
                                {{ $status === 'needs_correction' ? 'border-red-200 dark:border-red-800/50'       : '' }}
                                {{ $status === 'under_consultation' ? 'border-purple-200 dark:border-purple-800/40' : '' }}
                                {{ $status === 'approved'  ? 'border-emerald-200 dark:border-emerald-800/40'       : '' }}
                                {{ $status === 'cancelled' ? 'border-gray-300 dark:border-gray-600'               : '' }}
                                {{ in_array($status, ['completed','submitted']) ? 'border-gray-200 dark:border-gray-700' : '' }}
                                bg-white shadow-sm dark:bg-gray-800">

                                {{-- Status banner --}}
                                @if ($status === 'needs_correction')
                                    <div class="flex items-start gap-3 border-b border-red-100 bg-red-50 px-5 py-3 dark:border-red-800/30 dark:bg-red-900/20">
                                        <svg class="h-5 w-5 flex-none text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                        </svg>
                                        <p class="text-sm text-red-800 dark:text-red-200 font-semibold">
                                            {{ __('prevention_request.needs_correction_banner') }}
                                        </p>
                                    </div>
                                @elseif ($status === 'under_consultation')
                                    <div class="flex items-start gap-3 border-b border-purple-100 bg-purple-50 px-5 py-3 dark:border-purple-800/30 dark:bg-purple-900/20">
                                        <svg class="h-5 w-5 flex-none text-purple-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <p class="text-sm text-purple-800 dark:text-purple-200">
                                            <span class="font-semibold">{{ __('prevention_request.status_under_consultation') }} — </span>
                                            {{ __('prevention_request.under_consultation_banner') }}
                                        </p>
                                    </div>
                                @elseif ($status === 'approved')
                                    <div class="flex items-start gap-3 border-b border-emerald-100 bg-emerald-50 px-5 py-3 dark:border-emerald-800/30 dark:bg-emerald-900/20">
                                        <svg class="h-5 w-5 flex-none text-emerald-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm text-emerald-800 dark:text-emerald-200 font-semibold">
                                            {{ __('prevention_request.approved_banner') }}
                                        </p>
                                    </div>
                                @elseif ($status === 'cancelled')
                                    <div class="flex items-start gap-3 border-b border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-700/30">
                                        <svg class="h-5 w-5 flex-none text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 font-semibold">
                                            {{ __('prevention_request.cancelled_banner') }}
                                        </p>
                                    </div>
                                @else
                                    <div class="flex items-start gap-3 border-b border-blue-100 bg-blue-50 px-5 py-3 dark:border-blue-800/30 dark:bg-blue-900/20">
                                        <svg class="h-5 w-5 flex-none text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="text-sm text-blue-800 dark:text-blue-200">
                                            <span class="font-semibold">{{ __('prevention_request.status_under_review') }} — </span>
                                            {{ __('prevention_request.under_review_banner') }}
                                        </p>
                                    </div>
                                @endif

                                {{-- Dossier body --}}
                                <div class="px-5 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $dossier->nom_prenom }}</p>
                                                {{-- Status badge --}}
                                                @php
                                                    $badgeClass = match($status) {
                                                        'needs_correction'   => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                        'under_consultation' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                                        'approved'           => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                        'cancelled'          => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                                        'submitted'          => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                                                        default              => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                    };
                                                    $dotClass = match($status) {
                                                        'needs_correction'   => 'bg-red-500',
                                                        'under_consultation' => 'bg-purple-500',
                                                        'approved'           => 'bg-emerald-500',
                                                        'cancelled'          => 'bg-gray-400',
                                                        'submitted'          => 'bg-teal-500',
                                                        default              => 'bg-blue-500',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                                    <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                                                    {{ __('prevention_request.status_' . str_replace('-', '_', $dossier->status)) }}
                                                </span>
                                            </div>

                                            {{-- Dossier meta --}}
                                            <div class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-xs text-gray-500 dark:text-gray-400 sm:grid-cols-3">
                                                <span><span class="font-medium text-gray-700 dark:text-gray-300">CIN:</span> {{ $dossier->cin }}</span>
                                                @if ($dossier->phone)
                                                    <span><span class="font-medium text-gray-700 dark:text-gray-300">{{ __('prevention_request.phone') }}:</span> {{ $dossier->phone }}</span>
                                                @endif
                                                @if ($dossier->governorate)
                                                    <span>{{ $dossier->governorate->name }}</span>
                                                @endif
                                                @if ($dossier->delegation)
                                                    <span>{{ $dossier->delegation->name }}</span>
                                                @endif
                                                @if ($dossier->buildingType)
                                                    <span>{{ $dossier->buildingType->name }}</span>
                                                @endif
                                                <span>{{ $dossier->created_at->format('d/m/Y') }}</span>
                                            </div>

                                            {{-- Admin note (shown for needs_correction and cancelled) --}}
                                            @if ($dossier->admin_note && in_array($status, ['needs_correction', 'cancelled']))
                                                <div class="mt-3 rounded-xl border {{ $status === 'needs_correction' ? 'border-red-200 bg-red-50 dark:border-red-800/30 dark:bg-red-900/10' : 'border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700/30' }} px-4 py-3">
                                                    <p class="text-xs font-semibold {{ $status === 'needs_correction' ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-400' }} mb-1">
                                                        {{ __('prevention_request.admin_note_label') }}
                                                    </p>
                                                    <p class="text-sm {{ $status === 'needs_correction' ? 'text-red-800 dark:text-red-200' : 'text-gray-700 dark:text-gray-300' }} whitespace-pre-line">{{ $dossier->admin_note }}</p>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Fix & Resubmit button --}}
                                        @if ($status === 'needs_correction')
                                            <div class="flex-none">
                                                <form method="POST" action="{{ route('prevention-requests.resubmit', $dossier) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/40">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                        {{ __('prevention_request.fix_and_resubmit') }}
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Appointments / RDV (shown for approved dossiers) --}}
                                @if ($dossier->appointments->isNotEmpty())
                                    <div class="border-t border-gray-100 dark:border-gray-700/50 mt-4 pt-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ __('prevention_request.appointments_title') }}
                                        </p>
                                        <div class="space-y-3">
                                            @foreach ($dossier->appointments as $i => $appt)
                                                <div class="rounded-xl border px-4 py-3
                                                    {{ $appt->isPassed() ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800/30 dark:bg-emerald-900/10' : '' }}
                                                    {{ $appt->isFailed() ? 'border-red-200 bg-red-50 dark:border-red-800/30 dark:bg-red-900/10' : '' }}
                                                    {{ $appt->isPending() ? 'border-blue-200 bg-blue-50 dark:border-blue-800/30 dark:bg-blue-900/10' : '' }}">
                                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                                        <span class="text-xs font-semibold
                                                            {{ $appt->isPassed() ? 'text-gray-700 dark:text-gray-200' : '' }}
                                                            {{ $appt->isFailed() ? 'text-gray-700 dark:text-gray-200' : '' }}
                                                            {{ $appt->isPending() ? 'text-gray-700 dark:text-gray-200' : '' }}">
                                                            {{ __('prevention_request.appointment_visit_number', ['num' => $i + 1]) }}
                                                            — {{ $appt->visit_date->format('d M Y') }} {{ __('at') }} {{ substr($appt->visit_time, 0, 5) }}
                                                        </span>
                                                        @if ($appt->isPassed())
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                                {{ __('prevention_request.appointment_passed') }}
                                                            </span>
                                                        @elseif ($appt->isFailed())
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                {{ __('prevention_request.appointment_failed') }}
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                                                {{ __('prevention_request.appointment_pending') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if ($appt->location)
                                                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                            <svg class="h-3 w-3 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                            {{ $appt->location }}
                                                        </p>
                                                    @endif
                                                    @if ($appt->note)
                                                        <div class="mt-2 border-t {{ $appt->isFailed() ? 'border-red-200 dark:border-red-800/30' : 'border-gray-200 dark:border-gray-600' }} pt-2">
                                                            <p class="text-xs font-semibold {{ $appt->isFailed() ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }} mb-0.5">
                                                                {{ __('prevention_request.appointment_note_label') }}
                                                            </p>
                                                            <p class="text-xs {{ $appt->isFailed() ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-300' }} whitespace-pre-line">{{ $appt->note }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ── Local Drafts (incomplete dossiers) ── --}}
            @if ($drafts->isNotEmpty())
                <section class="mb-8 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 shadow-sm dark:border-amber-800/40 dark:bg-amber-900/10">
                    <div class="flex items-center gap-3 border-b border-amber-200 px-6 py-4 dark:border-amber-800/40">
                        <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">{{ __('prevention_request.drafts_title') }}</h3>
                        <span class="ms-auto rounded-full bg-amber-200 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-800/40 dark:text-amber-300">{{ $drafts->count() }}</span>
                    </div>
                    <div class="divide-y divide-amber-100 dark:divide-amber-800/20">
                        @foreach ($drafts as $draft)
                            <div class="flex items-center justify-between gap-4 px-6 py-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $draft->nom_prenom }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $draft->cin }}
                                        @if($draft->phone) &bull; {{ $draft->phone }} @endif
                                        &bull; {{ $draft->governorate?->name }} &bull; {{ $draft->delegation?->name }}
                                    </p>
                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                                        {{ __('prevention_request.draft_step', ['step' => $draft->current_step]) }} &bull; {{ $draft->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 flex-none">
                                    @if ($draft->current_step === 1)
                                        <a href="{{ route('prevention-requests.step2.show', $draft) }}" class="inline-flex items-center gap-1 rounded-lg border border-gov-300 bg-gov-50 px-3 py-1.5 text-xs font-semibold text-gov-700 transition hover:bg-gov-100 dark:border-gov-700 dark:bg-gov-900/30 dark:text-gov-300">
                                            {{ __('prevention_request.continue') }} &rarr;
                                        </a>
                                    @elseif ($draft->current_step >= 2)
                                        <a href="{{ route('prevention-requests.step3.show', $draft) }}" class="inline-flex items-center gap-1 rounded-lg border border-gov-300 bg-gov-50 px-3 py-1.5 text-xs font-semibold text-gov-700 transition hover:bg-gov-100 dark:border-gov-700 dark:bg-gov-900/30 dark:text-gov-300">
                                            {{ __('prevention_request.continue') }} &rarr;
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('prevention-requests.destroy', $draft->id) }}" onsubmit="return confirm('{{ __('prevention_request.delete_confirm') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                                            {{ __('prevention_request.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Empty state --}}
            @if ($drafts->isEmpty() && $submitted->isEmpty())
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                        <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('prevention_request.no_requests') }}</p>
                        <a href="{{ route('prevention-requests.create') }}" class="mt-4 ui-btn-primary inline-flex items-center justify-center gap-2 px-4 py-2 text-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('prevention_request.new_request') }}
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
