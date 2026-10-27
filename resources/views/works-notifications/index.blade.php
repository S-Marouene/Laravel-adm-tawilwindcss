<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ __('works_notification.index_title') }}</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('works_notification.index_subtitle') }}</p>
            </div>
            <a href="{{ route('works-notifications.create') }}" class="ui-btn-primary inline-flex items-center justify-center gap-2 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('works_notification.new_request') }}</span>
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
                    <p class="font-semibold">{{ __('works_notification.create_error_title') }}</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ── Submitted / Processed requests ── --}}
            @if ($submitted->isNotEmpty())
                <section class="mb-8">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('works_notification.submitted_title') }}
                    </h3>
                    <div class="space-y-4">
                        @foreach ($submitted as $worksNotification)
                            @php
                                $status = $worksNotification->status;
                            @endphp
                            <div class="overflow-hidden rounded-2xl border
                                {{ $status === 'needs_correction' ? 'border-red-200 dark:border-red-800/50'       : '' }}
                                {{ $status === 'under_consultation' ? 'border-purple-200 dark:border-purple-800/40' : '' }}
                                {{ $status === 'approved'  ? 'border-emerald-200 dark:border-emerald-800/40'       : '' }}
                                {{ $status === 'cancelled' ? 'border-gray-300 dark:border-gray-600'               : '' }}
                                {{ $status === 'completed' ? 'border-gray-200 dark:border-gray-700'               : '' }}
                                bg-white shadow-sm dark:bg-gray-800">

                                {{-- Status banner --}}
                                @if ($status === 'needs_correction')
                                    <div class="flex items-start gap-3 border-b border-red-100 bg-red-50 px-5 py-3 dark:border-red-800/30 dark:bg-red-900/20">
                                        <svg class="h-5 w-5 flex-none text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                        </svg>
                                        <p class="text-sm text-red-800 dark:text-red-200 font-semibold">
                                            {{ __('works_notification.needs_correction_banner') }}
                                        </p>
                                    </div>
                                @elseif ($status === 'under_consultation')
                                    <div class="flex items-start gap-3 border-b border-purple-100 bg-purple-50 px-5 py-3 dark:border-purple-800/30 dark:bg-purple-900/20">
                                        <svg class="h-5 w-5 flex-none text-purple-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <p class="text-sm text-purple-800 dark:text-purple-200">
                                            <span class="font-semibold">{{ __('works_notification.status_under_consultation') }} — </span>
                                            {{ __('works_notification.under_consultation_banner') }}
                                        </p>
                                    </div>
                                @elseif ($status === 'approved')
                                    <div class="flex items-start gap-3 border-b border-emerald-100 bg-emerald-50 px-5 py-3 dark:border-emerald-800/30 dark:bg-emerald-900/20">
                                        <svg class="h-5 w-5 flex-none text-emerald-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm text-emerald-800 dark:text-emerald-200 font-semibold">
                                            {{ __('works_notification.approved_banner') }}
                                        </p>
                                    </div>
                                @elseif ($status === 'cancelled')
                                    <div class="flex items-start gap-3 border-b border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-700/30">
                                        <svg class="h-5 w-5 flex-none text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 font-semibold">
                                            {{ __('works_notification.cancelled_banner') }}
                                        </p>
                                    </div>
                                @else
                                    <div class="flex items-start gap-3 border-b border-blue-100 bg-blue-50 px-5 py-3 dark:border-blue-800/30 dark:bg-blue-900/20">
                                        <svg class="h-5 w-5 flex-none text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="text-sm text-blue-800 dark:text-blue-200">
                                            <span class="font-semibold">{{ __('works_notification.status_under_review') }} — </span>
                                            {{ __('works_notification.under_review_banner') }}
                                        </p>
                                    </div>
                                @endif

                                {{-- Request body --}}
                                <div class="px-5 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $worksNotification->owner_name ?: $worksNotification->company_name }}
                                                </p>
                                                {{-- Status badge --}}
                                                @php
                                                    $badgeClass = match($status) {
                                                        'needs_correction'   => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                        'under_consultation' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                                        'approved'           => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                        'cancelled'          => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                                        default              => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                    };
                                                    $dotClass = match($status) {
                                                        'needs_correction'   => 'bg-red-500',
                                                        'under_consultation' => 'bg-purple-500',
                                                        'approved'           => 'bg-emerald-500',
                                                        'cancelled'          => 'bg-gray-400',
                                                        default              => 'bg-blue-500',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                                    <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                                                    {{ __('works_notification.status_' . str_replace('-', '_', $status)) }}
                                                </span>
                                            </div>

                                            {{-- Request meta --}}
                                            <div class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-xs text-gray-500 dark:text-gray-400 sm:grid-cols-3">
                                                <span><span class="font-medium text-gray-700 dark:text-gray-300">{{ __('works_notification.owner_cin') }}:</span> {{ $worksNotification->owner_cin }}</span>
                                                @if ($worksNotification->phone)
                                                    <span><span class="font-medium text-gray-700 dark:text-gray-300">{{ __('works_notification.phone') }}:</span> {{ $worksNotification->phone }}</span>
                                                @endif
                                                @if ($worksNotification->works_type)
                                                    <span class="truncate"><span class="font-medium text-gray-700 dark:text-gray-300">{{ __('works_notification.works_type') }}:</span> {{ $worksNotification->works_type }}</span>
                                                @endif
                                                @if ($worksNotification->works_start_date)
                                                    <span>{{ $worksNotification->works_start_date->format('d/m/Y') }}</span>
                                                @endif
                                                @if ($worksNotification->governorate)
                                                    <span>{{ $worksNotification->governorate->name }}</span>
                                                @endif
                                                @if ($worksNotification->delegation)
                                                    <span>{{ $worksNotification->delegation->name }}</span>
                                                @endif
                                                <span>{{ $worksNotification->created_at->format('d/m/Y') }}</span>
                                            </div>

                                            {{-- Admin note (shown for needs_correction and cancelled) --}}
                                            @if ($worksNotification->admin_note && in_array($status, ['needs_correction', 'cancelled']))
                                                <div class="mt-3 rounded-xl border {{ $status === 'needs_correction' ? 'border-red-200 bg-red-50 dark:border-red-800/30 dark:bg-red-900/10' : 'border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700/30' }} px-4 py-3">
                                                    <p class="text-xs font-semibold {{ $status === 'needs_correction' ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-400' }} mb-1">
                                                        {{ __('works_notification.admin_note_label') }}
                                                    </p>
                                                    <p class="text-sm {{ $status === 'needs_correction' ? 'text-red-800 dark:text-red-200' : 'text-gray-700 dark:text-gray-300' }} whitespace-pre-line">{{ $worksNotification->admin_note }}</p>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Fix & Resubmit button --}}
                                        @if ($status === 'needs_correction')
                                            <div class="flex-none">
                                                <form method="POST" action="{{ route('works-notifications.resubmit', $worksNotification) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/40">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                        {{ __('works_notification.fix_and_resubmit') }}
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Approved info: free procedure, phone notification --}}
                                    @if ($status === 'approved')
                                        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 dark:border-emerald-800/30 dark:bg-emerald-900/10">
                                            <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 mb-1 flex items-center gap-1.5">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                {{ __('works_notification.approved_phone_notice') }}
                                            </p>
                                            <p class="text-xs text-emerald-700 dark:text-emerald-300">{{ __('works_notification.free_info') }}</p>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ── Local Drafts (incomplete requests) ── --}}
            @if ($drafts->isNotEmpty())
                <section class="mb-8 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 shadow-sm dark:border-amber-800/40 dark:bg-amber-900/10">
                    <div class="flex items-center gap-3 border-b border-amber-200 px-6 py-4 dark:border-amber-800/40">
                        <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">{{ __('works_notification.drafts_title') }}</h3>
                        <span class="ms-auto rounded-full bg-amber-200 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-800/40 dark:text-amber-300">{{ $drafts->count() }}</span>
                    </div>
                    <div class="divide-y divide-amber-100 dark:divide-amber-800/20">
                        @foreach ($drafts as $draft)
                            <div class="flex items-center justify-between gap-4 px-6 py-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $draft->owner_name ?: $draft->company_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $draft->owner_cin }}
                                        @if($draft->phone) &bull; {{ $draft->phone }} @endif
                                        &bull; {{ $draft->governorate?->name }} &bull; {{ $draft->delegation?->name }}
                                    </p>
                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                                        {{ __('works_notification.draft_step', ['step' => $draft->current_step]) }} &bull; {{ $draft->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 flex-none">
                                    <a href="{{ route('works-notifications.step2.show', $draft) }}" class="inline-flex items-center gap-1 rounded-lg border border-gov-300 bg-gov-50 px-3 py-1.5 text-xs font-semibold text-gov-700 transition hover:bg-gov-100 dark:border-gov-700 dark:bg-gov-900/30 dark:text-gov-300">
                                        {{ __('works_notification.continue') }} &rarr;
                                    </a>
                                    <form method="POST" action="{{ route('works-notifications.destroy', $draft) }}" onsubmit="return confirm('{{ __('works_notification.delete_confirm') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                                            {{ __('action.delete') }}
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
                        <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('works_notification.no_requests') }}</p>
                        <a href="{{ route('works-notifications.create') }}" class="mt-4 ui-btn-primary inline-flex items-center justify-center gap-2 px-4 py-2 text-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('works_notification.new_request') }}
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
