<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface dark:text-gray-100">{{ __('training.index_title') }}</h2>
                <p class="mt-0.5 text-sm text-secondary dark:text-gray-400">{{ __('training.index_subtitle') }}</p>
            </div>
            <a href="{{ route('training.create') }}" class="ui-btn-primary inline-flex items-center justify-center gap-2 px-4 py-2 text-sm focus:outline-none">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('training.new_request') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                    <ul class="list-disc space-y-1 ps-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @if ($submitted->isNotEmpty())
                <section class="mb-8">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-secondary">{{ __('training.submitted_title') }}</h3>
                    <div class="space-y-4">
                        @foreach ($submitted as $tr)
                            @php
                                $status = $tr->status;
                                $badgeClass = match($status) {
                                    'needs_correction'   => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    'under_consultation' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                    'payment_pending'    => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                    'payment_confirmed'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                    'scheduled'          => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                    'in_progress'        => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                    'passed'             => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'failed'             => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    'served'             => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                                    'cancelled'          => 'bg-surface-container text-secondary dark:bg-gray-700 dark:text-gray-400',
                                    default              => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                };
                                $bannerBg = match($status) {
                                    'needs_correction'   => 'border-red-100 bg-red-50 dark:border-red-800/30 dark:bg-red-900/20',
                                    'payment_pending'    => 'border-orange-100 bg-orange-50 dark:border-orange-800/30 dark:bg-orange-900/20',
                                    'passed'             => 'border-emerald-100 bg-emerald-50 dark:border-emerald-800/30 dark:bg-emerald-900/20',
                                    'failed'             => 'border-red-100 bg-red-50 dark:border-red-800/30 dark:bg-red-900/20',
                                    'served'             => 'border-teal-100 bg-teal-50 dark:border-teal-800/30 dark:bg-teal-900/20',
                                    'cancelled'          => 'border-surface-dim bg-surface-container dark:border-gray-600 dark:bg-gray-700/30',
                                    default              => 'border-blue-100 bg-blue-50 dark:border-blue-800/30 dark:bg-blue-900/20',
                                };
                                $bannerKey = 'training.' . $status . '_banner';
                            @endphp
                            <a href="{{ route('training.show', $tr) }}"
                               class="block overflow-hidden rounded-2xl border transition hover:shadow-md focus:outline-none bg-surface-container-lowest shadow-sm dark:bg-gray-800
                               {{ $status === 'needs_correction' ? 'border-red-200 dark:border-red-800/50' : 'border-surface-dim dark:border-gray-700' }}">
                                <div class="flex items-start gap-3 border-b {{ $bannerBg }} px-5 py-3">
                                    <p class="text-sm text-on-surface dark:text-gray-200">{{ __($bannerKey) }}</p>
                                </div>
                                <div class="px-5 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ $tr->nom_prenom }}</p>
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                                    {{ __('training.status_' . str_replace('-','_',$status)) }}
                                                </span>
                                            </div>
                                            <div class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-xs text-secondary sm:grid-cols-3">
                                                @if ($tr->training_type)<span>{{ __('training.type_' . $tr->training_type) }}</span>@endif
                                                @if ($tr->training_kind)<span>{{ __('training.kind_' . $tr->training_kind) }}</span>@endif
                                                @if ($tr->governorate)<span>{{ $tr->governorate->name }}</span>@endif
                                                <span>{{ $tr->created_at->format('d/m/Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative z-10 mt-4 flex items-center gap-2">
                                        <a href="{{ route('training.show', $tr) }}" class="inline-flex items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-on-surface transition hover:bg-surface-container">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            {{ __('training.view_details') }}
                                        </a>
                                        @if ($status === 'needs_correction')
                                            <form method="POST" action="{{ route('training.resubmit', $tr) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                                                    {{ __('training.fix_and_resubmit') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($drafts->isNotEmpty())
                <section class="mb-8 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 shadow-sm dark:border-amber-800/40 dark:bg-amber-900/10">
                    <div class="flex items-center gap-3 border-b border-amber-200 px-6 py-4 dark:border-amber-800/40">
                        <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">{{ __('training.drafts_title') }}</h3>
                        <span class="ms-auto rounded-full bg-amber-200 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-800/40 dark:text-amber-300">{{ $drafts->count() }}</span>
                    </div>
                    <div class="divide-y divide-amber-100 dark:divide-amber-800/20">
                        @foreach ($drafts as $draft)
                            <div class="flex items-center justify-between gap-4 px-6 py-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-on-surface truncate">{{ $draft->nom_prenom ?: '—' }}</p>
                                    <p class="text-xs text-secondary mt-0.5">
                                        @if ($draft->training_type){{ __('training.type_' . $draft->training_type) }} &bull; @endif
                                        {{ $draft->created_at->diffForHumans() }}
                                    </p>
                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">{{ __('training.draft_step', ['step' => $draft->current_step]) }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-none">
                                    <a href="{{ $draft->current_step >= 3
                                            ? route('training.step3.show', $draft)
                                            : ($draft->current_step >= 2
                                                ? route('training.step2.show', $draft)
                                                : route('training.step1.edit', $draft)) }}" class="inline-flex items-center gap-1 rounded-lg border border-gov-300 bg-gov-50 px-3 py-1.5 text-xs font-semibold text-gov-700 transition hover:bg-gov-100 dark:border-gov-700 dark:bg-gov-900/30 dark:text-gov-300">
                                        {{ __('training.continue') }} &rarr;
                                    </a>
                                    <form method="POST" action="{{ route('training.destroy', $draft) }}" data-confirm data-confirm-style="danger">
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

            @if ($drafts->isEmpty() && $submitted->isEmpty())
                <div class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                        <span class="material-symbols-outlined text-5xl text-outline" aria-hidden="true">school</span>
                        <p class="mt-4 text-sm font-medium text-secondary">{{ __('training.no_requests') }}</p>
                        <a href="{{ route('training.create') }}" class="mt-4 ui-btn-primary inline-flex items-center justify-center gap-2 px-4 py-2 text-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('training.new_request') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
