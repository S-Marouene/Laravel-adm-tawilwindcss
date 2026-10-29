@php
    $status = $dossier->status;
    $badgeClass = match($status) {
        'needs_correction'   => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        'under_consultation' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        'approved'           => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'cancelled'          => 'bg-surface-container text-secondary dark:bg-gray-700 dark:text-gray-400',
        'submitted'          => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
        'draft'              => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        default              => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    };
    $dotClass = match($status) {
        'needs_correction'   => 'bg-red-500',
        'under_consultation' => 'bg-purple-500',
        'approved'           => 'bg-emerald-500',
        'cancelled'          => 'bg-gray-400',
        'submitted'          => 'bg-teal-500',
        'draft'              => 'bg-amber-500',
        default              => 'bg-blue-500',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface dark:text-gray-100">{{ __('prevention_request.details_title') }}</h2>
                <p class="mt-0.5 text-sm text-secondary dark:text-gray-400">
                    {{ __('prevention_request.card_title') }} — {{ __('prevention_request.id') }} #{{ $dossier->id }}
                </p>
            </div>
            <a href="{{ route('prevention-requests.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2 text-sm font-medium text-on-surface transition hover:bg-surface-container dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('prevention_request.back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ─── Status header card ─── --}}
            <div class="overflow-hidden rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-surface-container-high dark:border-gray-700/60">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold {{ $badgeClass }}">
                        <span class="h-2 w-2 rounded-full {{ $dotClass }}"></span>
                        {{ __('prevention_request.status_' . str_replace('-', '_', $dossier->status)) }}
                    </span>
                    <span class="text-xs text-secondary dark:text-gray-400">
                        {{ __('prevention_request.date_demande') }}: {{ $dossier->created_at->format('d/m/Y H:i') }}
                    </span>
                </div>

                {{-- Status banner --}}
                @if ($status === 'needs_correction')
                    <div class="flex items-start gap-3 px-6 py-3 bg-red-50 dark:bg-red-900/20">
                        <svg class="h-5 w-5 flex-none text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <p class="text-sm text-red-800 dark:text-red-200 font-semibold">
                            {{ __('prevention_request.needs_correction_banner') }}
                        </p>
                    </div>
                @elseif ($status === 'under_consultation')
                    <div class="flex items-start gap-3 px-6 py-3 bg-purple-50 dark:bg-purple-900/20">
                        <svg class="h-5 w-5 flex-none text-purple-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <p class="text-sm text-purple-800 dark:text-purple-200">
                            <span class="font-semibold">{{ __('prevention_request.status_under_consultation') }} — </span>
                            {{ __('prevention_request.under_consultation_banner') }}
                        </p>
                    </div>
                @elseif ($status === 'approved')
                    <div class="flex items-start gap-3 px-6 py-3 bg-emerald-50 dark:bg-emerald-900/20">
                        <svg class="h-5 w-5 flex-none text-emerald-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-emerald-800 dark:text-emerald-200 font-semibold">
                            {{ __('prevention_request.approved_banner') }}
                        </p>
                    </div>
                @elseif ($status === 'cancelled')
                    <div class="flex items-start gap-3 px-6 py-3 bg-surface-container dark:bg-gray-700/30">
                        <svg class="h-5 w-5 flex-none text-outline mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-secondary dark:text-gray-300 font-semibold">
                            {{ __('prevention_request.cancelled_banner') }}
                        </p>
                    </div>
                @else
                    <div class="flex items-start gap-3 px-6 py-3 bg-blue-50 dark:bg-blue-900/20">
                        <svg class="h-5 w-5 flex-none text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <span class="font-semibold">{{ __('prevention_request.status_under_review') }} — </span>
                            {{ __('prevention_request.under_review_banner') }}
                        </p>
                    </div>
                @endif

                {{-- Admin note (visible for correction / cancellation) --}}
                @if ($dossier->admin_note && in_array($status, ['needs_correction', 'cancelled']))
                    <div class="px-6 py-4 border-t border-surface-container-high dark:border-gray-700/60">
                        <div class="rounded-xl border px-4 py-3 {{ $status === 'needs_correction' ? 'border-red-200 bg-red-50 dark:border-red-800/30 dark:bg-red-900/10' : 'border-surface-dim bg-surface-container dark:border-gray-600 dark:bg-gray-700/30' }}">
                            <p class="text-xs font-semibold mb-1 {{ $status === 'needs_correction' ? 'text-red-700 dark:text-red-300' : 'text-secondary dark:text-gray-400' }}">
                                {{ __('prevention_request.admin_note_label') }}
                            </p>
                            <p class="text-sm {{ $status === 'needs_correction' ? 'text-red-800 dark:text-red-200' : 'text-on-surface dark:text-gray-300' }} whitespace-pre-line">{{ $dossier->admin_note }}</p>
                        </div>

                        @if ($status === 'needs_correction')
                            <form class="mt-4" method="POST" action="{{ route('prevention-requests.resubmit', $dossier) }}">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/40">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    {{ __('prevention_request.fix_and_resubmit') }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ─── Certificate ready — pickup at delegation ─── --}}
            @if ($dossier->certificate)
                @php $cert = $dossier->certificate; @endphp
                <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-surface-container-lowest shadow-sm dark:border-emerald-800/40 dark:bg-gray-800">
                    <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-emerald-100 bg-emerald-50 dark:border-emerald-800/30 dark:bg-emerald-900/20">
                        <div class="flex items-center gap-2.5">
                            <svg class="h-5 w-5 flex-none text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-base font-semibold text-emerald-800 dark:text-emerald-200">{{ __('prevention_request.certificate_available_title') }}</h3>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold
                            {{ $cert->isCollected() ? 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                            {{ $cert->isCollected() ? __('prevention_request.certificate_status_collected') : __('prevention_request.certificate_status_ready') }}
                        </span>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-sm {{ $cert->isCollected() ? 'text-teal-800 dark:text-teal-200' : 'text-emerald-800 dark:text-emerald-200' }}">
                            {{ $cert->isCollected() ? __('prevention_request.certificate_collected_banner') : __('prevention_request.certificate_ready_banner') }}
                        </p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-surface-dim bg-surface-container px-4 py-3 dark:border-gray-600 dark:bg-gray-700/30">
                                <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.certificate_ref') }}</p>
                                <p class="mt-1 text-sm font-mono font-bold text-on-surface dark:text-gray-100">{{ $cert->reference }}</p>
                            </div>
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-800/30 dark:bg-emerald-900/10 sm:col-span-2">
                                <div class="flex items-start gap-2">
                                    <svg class="h-4 w-4 mt-0.5 flex-none text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <div>
                                        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">
                                            {{ __('prevention_request.certificate_pickup_text', ['place' => $cert->delegation?->name ?? $cert->governorate?->name ?? '—']) }}
                                        </p>
                                        @if ($cert->governorate && $cert->governorate?->name !== $cert->delegation?->name)
                                            <p class="mt-0.5 text-xs font-medium text-emerald-700/80 dark:text-emerald-300/80">
                                                {{ __('prevention_request.certificate_brigade', ['name' => $cert->governorate->name]) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.certificate_issued_on') }}</p>
                                <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $cert->issued_at->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.certificate_valid_until') }}</p>
                                <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $cert->valid_until ? $cert->valid_until->format('d/m/Y') : '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ─── Applicant information ─── --}}
            <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('prevention_request.applicant_info') }}</h3>
                </div>
                <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.nom_prenom') }}</p>
                        <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $dossier->nom_prenom }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.cin') }}</p>
                        <p class="mt-1 text-sm font-mono font-semibold text-on-surface dark:text-gray-100">{{ $dossier->cin }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.phone') }}</p>
                        <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100"><span class="num-ltr">{{ $dossier->phone ?? '—' }}</span></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.email') }}</p>
                        <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $dossier->user?->email }}</p>
                    </div>
                    @if ($dossier->num_demande)
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.num_demande') }}</p>
                            <p class="mt-1 text-sm font-mono font-semibold text-on-surface dark:text-gray-100">{{ $dossier->num_demande }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.date_demande') }}</p>
                        <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $dossier->created_at->format('d/m/Y — H:i') }}</p>
                    </div>
                </div>
            </div>

            {{-- ─── Building details ─── --}}
            <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('prevention_request.building_info') }}</h3>
                </div>
                <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.code_structure') }}</p>
                        <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $dossier->governorate?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.delegation') }}</p>
                        <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $dossier->delegation?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.building_type') }}</p>
                        <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $dossier->buildingType?->name ?? '—' }}</p>
                    </div>
                    @if ($dossier->buildingActivity?->name || $dossier->activity_other)
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.building_activity') }}</p>
                            <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">
                                {{ $dossier->buildingActivity?->name ?? $dossier->activity_other }}
                            </p>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('prevention_request.building_address') }}</p>
                        <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100 flex items-start gap-1.5">
                            <svg class="h-4 w-4 mt-0.5 flex-none text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $dossier->building_address ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- ─── My documents ─── --}}
            <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('prevention_request.documents_title') }}</h3>
                    <p class="mt-0.5 text-xs text-secondary dark:text-gray-400">{{ $documents->count() }} / {{ $documentTypes->count() }}</p>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse ($documentTypes as $docType)
                        @php $doc = $documents->get($docType->id); @endphp
                        <div class="flex items-center justify-between gap-4 px-6 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full {{ $doc ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500' }}">
                                    @if ($doc)
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-on-surface dark:text-gray-100">{{ $docType->name }}</p>
                                    @if ($doc)
                                        <p class="text-xs text-secondary dark:text-gray-400 truncate">{{ $doc->original_name }} ({{ number_format($doc->size / 1024, 0) }} KB)</p>
                                    @else
                                        <p class="text-xs text-outline dark:text-gray-500">{{ __('prevention_request.not_uploaded') }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-none">
                                @if ($docType->is_conditional)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('prevention_request.conditional') }}</span>
                                @endif
                                @if ($doc)
                                    <a href="{{ route('prevention-requests.documents.download', [$dossier, $doc]) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-1.5 text-xs font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        {{ __('prevention_request.download') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-6 text-center text-sm text-outline dark:text-gray-500">—</div>
                    @endforelse
                </div>
            </div>

            {{-- ─── Site visits (Rendez-vous) ─── --}}
            @if ($dossier->appointments->isNotEmpty())
                <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-on-surface dark:text-gray-100 flex items-center gap-2">
                            <svg class="h-4 w-4 text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ __('prevention_request.appointments_title') }}
                        </h3>
                        @if ($dossier->building_address)
                            <p class="mt-1 text-xs text-secondary dark:text-gray-400 flex items-center gap-1">
                                <svg class="h-3.5 w-3.5 flex-none text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ __('prevention_request.rdv_building_label') }}: <span class="font-semibold text-on-surface dark:text-gray-300">{{ $dossier->building_address }}</span>
                            </p>
                        @endif
                    </div>
                    <div class="space-y-3 px-6 py-4">
                        @foreach ($dossier->appointments as $i => $appt)
                            <div class="rounded-xl border px-4 py-3
                                {{ $appt->isPassed() ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800/30 dark:bg-emerald-900/10' : '' }}
                                {{ $appt->isFailed() ? 'border-red-200 bg-red-50 dark:border-red-800/30 dark:bg-red-900/10' : '' }}
                                {{ $appt->isPending() ? 'border-blue-200 bg-blue-50 dark:border-blue-800/30 dark:bg-blue-900/10' : '' }}">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <span class="text-xs font-semibold text-on-surface dark:text-gray-200">
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
                                @if ($appt->note)
                                    <div class="mt-2 border-t {{ $appt->isFailed() ? 'border-red-200 dark:border-red-800/30' : 'border-surface-dim dark:border-gray-600' }} pt-2">
                                        <p class="text-xs font-semibold {{ $appt->isFailed() ? 'text-red-600 dark:text-red-400' : 'text-secondary dark:text-gray-400' }} mb-0.5">
                                            {{ __('prevention_request.appointment_note_label') }}
                                        </p>
                                        <p class="text-xs {{ $appt->isFailed() ? 'text-red-700 dark:text-red-300' : 'text-secondary dark:text-gray-300' }} whitespace-pre-line">{{ $appt->note }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ─── Timeline — follow-up history ─── --}}
            @if ($dossier->events->isNotEmpty())
                <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-surface-dim px-5 py-4 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ __('prevention_request.timeline_title') }}</h3>
                        <p class="mt-0.5 text-xs text-secondary dark:text-gray-400">{{ __('prevention_request.timeline_subtitle') }}</p>
                    </div>
                    <div class="px-8 py-4">
                        @foreach ($dossier->events as $event)
                            @php
                                $isFailedEvent = str_contains($event->type, 'failed') || str_contains($event->type, 'cancelled');
                            @endphp
                            @if (! $loop->first)
                                <div class="ms-[3px] my-1.5 w-0.5 h-5 bg-gray-200 dark:bg-gray-600"></div>
                            @endif
                            <div class="flex items-start gap-3">
                                <span class="mt-1 flex-none h-2 w-2 rounded-full {{ $event->typeDot() }}"></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold {{ $isFailedEvent ? 'text-red-700 dark:text-red-300' : 'text-on-surface dark:text-gray-100' }}">
                                        {{ __('prevention_request.timeline_' . $event->type) }}
                                    </p>
                                    <p class="text-xs text-secondary dark:text-gray-400">
                                        {{ in_array($event->type, ['submitted', 'resubmitted']) ? __('prevention_request.timeline_actor_you') : __('prevention_request.timeline_actor_admin') }}
                                        · {{ $event->created_at->format('d/m/Y H:i') }}
                                    </p>

                                                {{-- RDV details --}}
                                                @if (in_array($event->type, ['appointment_scheduled', 'appointment_passed', 'appointment_failed', 'appointment_deleted']) && is_array($event->data) && ! empty($event->data['visit_date']))
                                                    <p class="mt-1 text-xs inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                                                        <svg class="h-3 w-3 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                        {{ __('prevention_request.timeline_visit_label') }} {{ \Illuminate\Support\Carbon::parse($event->data['visit_date'])->format('d/m/Y') }} {{ __('at') }} {{ substr((string) ($event->data['visit_time'] ?? ''), 0, 5) }}
                                                    </p>
                                                @endif

                                                {{-- Certificate details --}}
                                                @if (in_array($event->type, ['certificate_issued', 'certificate_collected', 'certificate_deleted']) && is_array($event->data) && ! empty($event->data['reference']))
                                                    <p class="mt-1 text-xs inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                                        <svg class="h-3 w-3 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 3h10a2 2 0 012 2v16l-7-3-7 3V5a2 2 0 012-2z"/></svg>
                                                        {{ __('prevention_request.timeline_certificate_label') }} {{ $event->data['reference'] }}
                                                    </p>
                                                @endif

                                                @if ($event->note)
                                                    <div class="mt-1.5 rounded-lg border bg-surface-container px-2.5 py-2 dark:bg-gray-700/30 {{ $isFailedEvent ? 'border-red-200 dark:border-red-800/30' : 'border-surface-dim dark:border-gray-600' }}">
                                                        <p class="text-xs font-semibold text-secondary dark:text-gray-400 mb-0.5">{{ __('prevention_request.timeline_note_label') }}</p>
                                                        <p class="text-xs text-secondary dark:text-gray-300 whitespace-pre-line">{{ $event->note }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

        </div>
    </div>
</x-app-layout>
