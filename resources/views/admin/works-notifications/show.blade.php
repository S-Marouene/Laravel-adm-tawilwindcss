@extends('admin.layouts.admin')
@section('title', __('works_notification.card_title') . ' — ' . __('works_notification.admin_request_id') . ' #' . $notification->id)

@php
    $status = $notification->status;
    $statusConfig = [
        'completed'          => ['label' => __('works_notification.status_completed'),          'dot' => 'bg-blue-500',    'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
        'under_consultation' => ['label' => __('works_notification.status_under_consultation'), 'dot' => 'bg-purple-500',  'badge' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
        'needs_correction'   => ['label' => __('works_notification.status_needs_correction'),   'dot' => 'bg-red-500',     'badge' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'],
        'approved'           => ['label' => __('works_notification.status_approved'),           'dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
        'cancelled'          => ['label' => __('works_notification.status_cancelled'),          'dot' => 'bg-gray-400',    'badge' => 'bg-surface-container text-secondary dark:bg-gray-700 dark:text-gray-400'],
    ];
    $sc = $statusConfig[$status] ?? ['label' => ucfirst($status), 'dot' => 'bg-gray-400', 'badge' => 'bg-surface-container text-secondary'];
@endphp

@section('content')
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">
            {{ __('works_notification.card_title') }}
            <span class="text-secondary font-normal text-xl">— {{ __('works_notification.admin_request_id') }} #{{ $notification->id }}</span>
        </h1>
        <p class="text-sm text-secondary dark:text-gray-400 mt-1">
            @if($notification->reference)
                <span class="font-mono font-bold" dir="ltr">{{ $notification->reference }}</span> —
            @endif
            @if($notification->works_type) {{ $notification->works_type }} — @endif
            {{ $notification->created_at->format('d M Y') }}
        </p>
    </div>
    <a href="{{ route('admin.building-works-declarations.index') }}"
       class="inline-flex items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2 text-sm font-medium text-on-surface transition hover:bg-surface-container dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
        <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ __('works_notification.admin_back_to_list') }}
    </a>
</div>

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
                    @if ($notification->reviewed_at)
                        <span class="text-xs text-outline dark:text-gray-500">
                            {{ $notification->reviewer?->name ?? __('admin.admin') }} · {{ $notification->reviewed_at->format('d M Y, H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Admin note (needs_correction / cancelled) --}}
            @if ($notification->admin_note)
                <div class="px-6 py-4 {{ $status === 'needs_correction' ? 'bg-red-50 dark:bg-red-900/10' : ($status === 'cancelled' ? 'bg-surface-container dark:bg-gray-700/20' : '') }}">
                    <p class="text-xs font-semibold uppercase tracking-wide {{ $status === 'needs_correction' ? 'text-red-600 dark:text-red-400' : 'text-secondary dark:text-gray-400' }} mb-2">
                        {{ __('works_notification.admin_note_visible') }}
                    </p>
                    <p class="text-sm text-on-surface dark:text-gray-200 whitespace-pre-line">{{ $notification->admin_note }}</p>
                </div>
            @endif

            {{-- Under consultation phone highlight --}}
            @if ($status === 'under_consultation')
                <div class="flex items-center gap-3 px-6 py-3 bg-purple-50 dark:bg-purple-900/10">
                    <svg class="h-4 w-4 text-purple-500 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span class="text-sm text-purple-700 dark:text-purple-300">
                        {{ __('works_notification.phone') }} : <strong class="font-mono num-ltr">{{ $notification->phone ?? '—' }}</strong>
                        <span class="ms-2 text-xs text-purple-500">{{ __('works_notification.admin_phone_keep') }}</span>
                    </span>
                </div>
            @endif
        </div>

        {{-- Applicant & building info --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('works_notification.admin_applicant_info') }}</h2>
            </div>
            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.owner_type_label') }}</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">
                        {{ $notification->isMoral() ? __('works_notification.owner_moral') : __('works_notification.owner_natural') }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">
                        {{ $notification->isMoral() ? __('works_notification.company_name') : __('works_notification.owner_name') }}
                    </p>
                    <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $notification->owner_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.owner_cin') }}</p>
                    <p class="mt-1 text-sm font-mono text-on-surface dark:text-gray-100">{{ $notification->owner_cin ?? '—' }}</p>
                </div>
                @if ($notification->isMoral() && $notification->jort_reference)
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.jort_reference') }}</p>
                        <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $notification->jort_reference }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.phone') }}</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100"><span class="num-ltr">{{ $notification->phone ?? '—' }}</span></p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.admin_account_email') }}</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $notification->user?->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.code_structure') }}</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $notification->governorate?->name_fr ?? $notification->governorate?->name_ar ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.delegation') }}</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $notification->delegation?->name_fr ?? $notification->delegation?->name_ar ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Works details --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('works_notification.works_section') }}</h2>
            </div>
            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.works_type') }}</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $notification->works_type ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.property_address') }}</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100">{{ $notification->property_address ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.works_start_date') }}</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface dark:text-gray-100">
                        {{ $notification->works_start_date?->format('d/m/Y') ?? '—' }}
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.works_description') }}</p>
                    <p class="mt-1 text-sm text-on-surface dark:text-gray-100 whitespace-pre-line">{{ $notification->works_description ?? '—' }}</p>
                </div>
                @if ($notification->notes)
                    <div class="sm:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-outline dark:text-gray-500">{{ __('works_notification.notes') }}</p>
                        <p class="mt-1 text-sm text-on-surface dark:text-gray-100 whitespace-pre-line">{{ $notification->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Free procedure --}}
        <div class="rounded-2xl border border-emerald-200 bg-surface-container-lowest shadow-sm dark:border-emerald-800/40 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-3 border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                <div>
                    <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('works_notification.fees_title') }}</h2>
                    <p class="mt-0.5 text-xs text-secondary dark:text-gray-400">{{ __('works_notification.fees_info') }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    {{ __('works_notification.free') }}
                </span>
            </div>
        </div>

        {{-- Documents --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('works_notification.documents_title') }}</h2>
                <p class="mt-0.5 text-xs text-secondary dark:text-gray-400">
                    {{ $documentsByType->flatten()->count() }} {{ __('works_notification.admin_docs_active') }}
                    — {{ $archivedDocs->count() }} {{ __('works_notification.admin_docs_archived') }}
                </p>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @forelse ($documentTypes as $docType)
                    @php $files = $documentsByType->get($docType->id, collect()); @endphp
                    <div class="px-6 py-3">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full {{ $files->isNotEmpty() ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500' }}">
                                    @if ($files->isNotEmpty())
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        <span class="material-symbols-outlined text-sm" aria-hidden="true">upload_file</span>
                                    @endif
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-on-surface dark:text-gray-100">{{ $docType->name }}</p>
                                    @if ($files->isEmpty())
                                        <p class="text-xs text-red-400 dark:text-red-500">{{ __('works_notification.admin_doc_not_uploaded') }}</p>
                                    @elseif ($files->count() > 1)
                                        <p class="text-xs text-secondary dark:text-gray-400">{{ $files->count() }} {{ __('works_notification.admin_doc_files_count') }}</p>
                                    @endif
                                </div>
                            </div>
                            @if ($docType->is_conditional)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('works_notification.admin_doc_conditional') }}</span>
                            @endif
                        </div>
                        @if ($files->isNotEmpty())
                        <div class="mt-2 ms-11 space-y-1.5">
                            @foreach ($files as $doc)
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-low px-3 py-2">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="material-symbols-outlined {{ str_contains((string) $doc->mime_type, 'pdf') ? 'text-red-500' : 'text-blue-500' }} text-base shrink-0" aria-hidden="true">{{ str_contains((string) $doc->mime_type, 'pdf') ? 'picture_as_pdf' : 'description' }}</span>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-on-surface dark:text-gray-100 truncate">{{ $doc->original_name }}</p>
                                            <p class="text-[10px] text-secondary dark:text-gray-400">{{ number_format($doc->size / 1024, 0) }} KB — {{ $doc->updated_at->format('d M Y, H:i') }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.building-works-declarations.documents.download', $doc) }}"
                                       class="w-7 h-7 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-600 hover:text-white flex items-center justify-center shrink-0 transition-colors"
                                       title="{{ __('works_notification.download_label') }}">
                                        <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                @empty
                    <div class="px-6 py-6 text-center text-sm text-outline">{{ __('works_notification.admin_no_doctypes') }}</div>
                @endforelse
            </div>
            {{-- Archived versions --}}
            @if ($archivedDocs->isNotEmpty())
            <div class="border-t border-surface-dim px-6 py-4 dark:border-gray-700" x-data="{ archOpen: false }">
                <button type="button" @click="archOpen = !archOpen" class="w-full flex items-center gap-2 text-start">
                    <span class="material-symbols-outlined text-base text-secondary" aria-hidden="true">inventory_2</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-secondary flex-1">{{ __('works_notification.admin_archived_versions') }} ({{ $archivedDocs->count() }})</span>
                    <svg class="w-4 h-4 text-secondary transition-transform" :class="archOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="archOpen" x-cloak class="mt-3 space-y-1.5">
                    @foreach ($archivedDocs as $doc)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-low px-3 py-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="material-symbols-outlined text-outline text-base shrink-0" aria-hidden="true">inventory_2</span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-secondary truncate">{{ $doc->original_name }}</p>
                                    <p class="text-[10px] text-outline">{{ $doc->documentType?->name }} — {{ __('works_notification.admin_archived_on') }} {{ $doc->updated_at->format('d M Y') }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.building-works-declarations.documents.download', $doc) }}"
                               class="w-7 h-7 rounded bg-surface-container text-secondary hover:bg-primary hover:text-on-primary flex items-center justify-center shrink-0 transition-colors"
                               title="{{ __('works_notification.download_label') }}">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

    </div>

    {{-- ─── Right sidebar: actions + timeline ─── --}}
    <div class="space-y-5">

        {{-- Actions --}}
        @can('manage building-works-declarations')
        @if ($notification->isActionable())
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ __('works_notification.admin_actions_title') }}</h3>
                <p class="text-xs text-secondary dark:text-gray-400 mt-0.5">{{ __('works_notification.admin_actions_subtitle') }}</p>
            </div>
            <div class="px-5 py-4 space-y-3">

                {{-- Advance to consultation --}}
                @if (in_array($status, ['completed', 'needs_correction']))
                    <form method="POST" action="{{ route('admin.building-works-declarations.advance', $notification) }}"
                          data-confirm data-confirm-style="info"
                          data-confirm-title="{{ __('works_notification.admin_advance_confirm') }}"
                          data-confirm-ok="{{ __('works_notification.admin_advance_ok') }}">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('works_notification.admin_advance_btn') }}
                        </button>
                    </form>
                @endif

                {{-- Approve --}}
                @if (in_array($status, ['completed', 'under_consultation']))
                    <form method="POST" action="{{ route('admin.building-works-declarations.approve', $notification) }}"
                          data-confirm data-confirm-style="success"
                          data-confirm-title="{{ __('works_notification.admin_approve_confirm') }}"
                          data-confirm-ok="{{ __('works_notification.admin_approve_ok') }}">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('works_notification.admin_approve_btn') }}
                        </button>
                    </form>
                @endif

                {{-- Request Correction --}}
                <div x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-800 transition hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300 dark:hover:bg-amber-900/40 focus:outline-none">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span x-text="open ? '{{ __('works_notification.admin_correction_hide') }}' : '{{ __('works_notification.admin_correction_btn') }}'">{{ __('works_notification.admin_correction_btn') }}</span>
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
                        <form method="POST" action="{{ route('admin.building-works-declarations.correction', $notification) }}">
                            @csrf
                            <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1.5">
                                {{ __('works_notification.admin_note_to_applicant') }} <span class="text-red-500">*</span>
                            </label>
                            <x-admin.bilingual-note name="admin_note" :rows="3" required
                                placeholder-key="works_notification.note_fix_placeholder" />

                            {{-- Docs to fix --}}
                            @if ($documentTypes->isNotEmpty())
                            <p class="mt-4 mb-2 flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-secondary dark:text-gray-400">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">upload_file</span>
                                {{ __('works_notification.correction_docs_section') }}
                            </p>
                            <div class="grid gap-1.5 mb-4">
                                @foreach ($documentTypes as $docType)
                                <label class="group flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-lowest px-3 py-2 transition
                                              hover:border-amber-400/70 hover:bg-surface-container
                                              has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/80 has-[:checked]:shadow-sm
                                              dark:border-gray-600 dark:bg-gray-800/50
                                              dark:has-[:checked]:border-amber-500/70 dark:has-[:checked]:bg-amber-900/20">
                                    <input type="checkbox" name="docs_to_fix[]" value="{{ $docType->id }}"
                                           class="h-4 w-4 shrink-0 cursor-pointer rounded border-surface-dim text-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                           {{ in_array($docType->id, old('docs_to_fix', [])) ? 'checked' : '' }}>
                                    <span class="material-symbols-outlined text-sm text-secondary group-has-[:checked]:text-amber-700" aria-hidden="true">description</span>
                                    <span class="flex-1 text-xs font-semibold text-on-surface dark:text-gray-200">{{ $docType->name }}</span>
                                </label>
                                @endforeach
                            </div>
                            @endif

                            {{-- Fields to fix --}}
                            @php
                                $fixableFields = [
                                    'owner_cin'         => ['label' => __('works_notification.owner_cin'),         'icon' => 'badge'],
                                    'owner_name'        => ['label' => __('works_notification.owner_name'),        'icon' => 'person'],
                                    'company_name'      => ['label' => __('works_notification.company_name'),      'icon' => 'business'],
                                    'jort_reference'    => ['label' => __('works_notification.jort_reference'),    'icon' => 'article'],
                                    'phone'             => ['label' => __('works_notification.phone'),             'icon' => 'call'],
                                    'governorate_id'    => ['label' => __('works_notification.code_structure'),    'icon' => 'public'],
                                    'delegation_id'     => ['label' => __('works_notification.delegation'),        'icon' => 'account_balance'],
                                    'property_address'  => ['label' => __('works_notification.property_address'),  'icon' => 'location_on'],
                                    'works_type'        => ['label' => __('works_notification.works_type'),        'icon' => 'category'],
                                    'works_start_date'  => ['label' => __('works_notification.works_start_date'),  'icon' => 'event'],
                                    'works_description' => ['label' => __('works_notification.works_description'), 'icon' => 'subject'],
                                    'notes'             => ['label' => __('works_notification.notes'),             'icon' => 'edit_note'],
                                ];
                                $currentFieldVals = [
                                    'owner_cin'         => $notification->owner_cin,
                                    'owner_name'        => $notification->owner_name,
                                    'company_name'      => $notification->company_name,
                                    'jort_reference'    => $notification->jort_reference,
                                    'phone'             => $notification->phone,
                                    'governorate_id'    => $notification->governorate?->name_fr ?? $notification->governorate?->name_ar,
                                    'delegation_id'     => $notification->delegation?->name_fr ?? $notification->delegation?->name_ar,
                                    'property_address'  => str($notification->property_address)->limit(40),
                                    'works_type'        => $notification->works_type,
                                    'works_start_date'  => $notification->works_start_date?->format('d/m/Y'),
                                    'works_description' => str($notification->works_description)->limit(40),
                                    'notes'             => str($notification->notes)->limit(40),
                                ];
                            @endphp
                            <p class="mb-2 flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">fact_check</span>
                                <span>{{ __('works_notification.admin_fields_to_fix') }}</span>
                                <span class="font-normal normal-case tracking-normal text-secondary dark:text-gray-500">{{ __('works_notification.admin_select_what_fix') }}</span>
                            </p>
                            <div class="grid gap-1.5">
                                @foreach ($fixableFields as $fieldKey => $fieldDef)
                                <label class="group flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-lowest px-3 py-2 transition
                                              hover:border-amber-400/70 hover:bg-surface-container
                                              has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/80 has-[:checked]:shadow-sm
                                              dark:border-gray-600 dark:bg-gray-800/50
                                              dark:has-[:checked]:border-amber-500/70 dark:has-[:checked]:bg-amber-900/20">
                                    <input type="checkbox" name="fields_to_fix[]" value="{{ $fieldKey }}"
                                           class="h-4 w-4 shrink-0 cursor-pointer rounded border-surface-dim text-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                           {{ in_array($fieldKey, old('fields_to_fix', [])) ? 'checked' : '' }}>
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-surface-container text-secondary transition
                                                 group-has-[:checked]:bg-amber-100 group-has-[:checked]:text-amber-700
                                                 dark:bg-gray-700 dark:text-gray-300" aria-hidden="true">
                                        <span class="material-symbols-outlined text-sm">{{ $fieldDef['icon'] }}</span>
                                    </span>
                                    <span class="flex-1 text-xs font-semibold text-on-surface dark:text-gray-200">{{ $fieldDef['label'] }}</span>
                                    @if ($currentFieldVals[$fieldKey])
                                        <span class="truncate max-w-28 text-[10px] text-outline dark:text-gray-500" title="{{ $currentFieldVals[$fieldKey] }}">{{ $currentFieldVals[$fieldKey] }}</span>
                                    @endif
                                </label>
                                @endforeach
                            </div>

                            <p class="mt-1 text-xs text-outline dark:text-gray-500">{{ __('works_notification.admin_note_visible') }}</p>
                            <button type="submit" class="mt-3 w-full inline-flex items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                {{ __('works_notification.admin_send_correction') }}
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Reject --}}
                <div x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40 focus:outline-none">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="open ? '{{ __('works_notification.admin_cancel_hide') }}' : '{{ __('works_notification.admin_cancel_btn') }}'">{{ __('works_notification.admin_cancel_btn') }}</span>
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
                        <form method="POST" action="{{ route('admin.building-works-declarations.cancel', $notification) }}"
                              data-confirm data-confirm-style="danger"
                              data-confirm-title="{{ __('works_notification.admin_cancel_confirm') }}"
                              data-confirm-focus-cancel="false">
                            @csrf
                            <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1.5">
                                {{ __('works_notification.admin_cancel_reason') }} <span class="text-red-500">*</span>
                            </label>
                            <x-admin.bilingual-note name="admin_note" :rows="3" required
                                placeholder-key="works_notification.note_cancel_placeholder" />
                            <button type="submit" class="mt-3 w-full inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/40">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ __('works_notification.admin_confirm_cancel') }}
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
        @elseif ($status === 'approved')
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 dark:border-emerald-800/40 dark:bg-emerald-900/10 px-5 py-4 text-center">
                <span class="material-symbols-outlined text-3xl text-emerald-500 mx-auto mb-2 block" aria-hidden="true">task_alt</span>
                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">{{ __('works_notification.status_approved') }}</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">{{ __('works_notification.admin_approved_final') }}</p>
            </div>
        @elseif ($status === 'cancelled')
            <div class="rounded-2xl border border-surface-dim bg-surface-container dark:border-gray-700 dark:bg-gray-700/20 px-5 py-4 text-center">
                <span class="material-symbols-outlined text-3xl text-outline mx-auto mb-2 block" aria-hidden="true">cancel</span>
                <p class="text-sm font-semibold text-on-surface dark:text-gray-300">{{ __('works_notification.status_cancelled') }}</p>
                <p class="text-xs text-secondary dark:text-gray-400 mt-1">{{ __('works_notification.admin_cancelled_final') }}</p>
            </div>
        @endif
        @endcan

        {{-- Timeline --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-surface-dim px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ __('works_notification.admin_history_title') }}</h3>
                <p class="text-xs text-secondary dark:text-gray-400 mt-0.5">{{ $notification->events->count() }} {{ __('works_notification.timeline_events_count') }}</p>
            </div>
            <div class="px-5 py-4">
                @forelse ($notification->events as $event)
                    @php
                        $isCorrection = $event->type === 'correction_requested';
                        $isCancelled  = $event->type === 'cancelled';
                        $isApproved   = in_array($event->type, ['approved', 'advanced_to_consultation']);
                    @endphp

                    @if (!$loop->first)
                        <div class="ms-[3px] my-1.5 w-0.5 h-5 bg-gray-200 dark:bg-gray-600"></div>
                    @endif

                    <div class="flex items-start gap-3" @if ($isCorrection || $isCancelled) x-data="{ open: false }" @endif>
                        <span class="mt-1 flex-none h-2 w-2 rounded-full {{ $event->typeDot() }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-on-surface dark:text-gray-100 flex items-center gap-2 flex-wrap">
                                {{ $event->typeLabel() }}
                                @if ($isApproved)
                                    <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('works_notification.chip_favorable') }}</span>
                                @endif
                                @if ($isCancelled)
                                    <span class="rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-red-700 dark:bg-red-900/40 dark:text-red-300">{{ __('works_notification.chip_alert') }}</span>
                                @endif
                                @if ($isCorrection)
                                    <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ __('works_notification.chip_correction') }}</span>
                                @endif
                            </p>
                            <p class="text-xs text-secondary dark:text-gray-400">
                                {{ $event->actor?->name ?? __('works_notification.system_actor') }}
                                · {{ $event->created_at->format('d M Y, H:i') }}
                                ({{ $event->created_at->diffForHumans() }})
                            </p>

                            @if ($event->note)
                                @if ($isCorrection || $isCancelled)
                                    <button type="button" @click="open = !open"
                                        class="mt-1.5 inline-flex items-center gap-1.5 rounded-lg border text-xs font-semibold px-2.5 py-1.5 transition focus:outline-none {{ $isCorrection ? 'border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300 dark:hover:bg-amber-900/40' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40' }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                        </svg>
                                        <span x-text="open ? '{{ __('action.hide') }}' : '{{ __('action.view') }}'">{{ __('action.view') }}</span>
                                    </button>
                                    <div x-show="open" x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 -translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        class="mt-2 rounded-lg px-3 py-2 text-xs {{ $isCorrection ? 'border border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-700/50 dark:bg-amber-900/10 dark:text-amber-200' : 'border border-red-200 bg-red-50 text-red-800 dark:border-red-700/50 dark:bg-red-900/10 dark:text-red-200' }}">
                                        <p class="font-semibold mb-1">{{ $isCorrection ? __('works_notification.admin_note_to_applicant') : __('works_notification.admin_cancel_reason') }} :</p>
                                        <p class="whitespace-pre-line">{{ $event->note }}</p>
                                    </div>
                                @else
                                    <p class="mt-1 text-xs text-secondary dark:text-gray-300 whitespace-pre-line">{{ $event->note }}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-2 text-center text-sm text-outline">{{ __('works_notification.admin_no_events') }}</div>
                @endforelse
            </div>
        </div>

        {{-- Quick info --}}
        <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800 px-5 py-4">
            <h3 class="text-sm font-semibold text-on-surface dark:text-gray-100 mb-3">{{ __('works_notification.admin_quick_info') }}</h3>
            <dl class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">{{ __('works_notification.admin_request_id') }}</dt>
                    <dd class="font-mono font-semibold text-on-surface dark:text-gray-100" dir="ltr">
                        {{ $notification->reference ?? ('#' . $notification->id) }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">{{ __('works_notification.admin_step_reached') }}</dt>
                    <dd class="font-semibold text-on-surface dark:text-gray-100">{{ $notification->current_step }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">{{ __('works_notification.admin_docs_count') }}</dt>
                    <dd class="font-semibold text-on-surface dark:text-gray-100">{{ $documentsByType->flatten()->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-secondary dark:text-gray-400">{{ __('works_notification.deposited_on', ['date' => $notification->created_at->format('d/m/Y')]) }}</dt>
                    <dd class="text-on-surface dark:text-gray-300">{{ $notification->created_at->diffForHumans() }}</dd>
                </div>
            </dl>
        </div>

    </div>{{-- /right sidebar --}}

</div>
@endsection
