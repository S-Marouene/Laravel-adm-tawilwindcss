<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface dark:text-gray-100">{{ __('training.index_title') }}</h2>
                <p class="mt-0.5 text-sm text-secondary dark:text-gray-400">{{ __('training.index_subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Filters --}}
            <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3 items-end">
                <div class="relative flex-1 sm:w-80">
                    <span class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none text-outline"><span class="material-symbols-outlined text-[16px]">search</span></span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('training.idx_search') }}"
                           class="block w-full pl-9 pr-3.5 py-2.5 text-body-md text-on-surface placeholder:text-secondary/70 bg-surface-container-lowest border border-surface-dim rounded hover:border-surface-container-highest focus:ring-0 focus:border-primary focus:outline-none transition-colors">
                </div>
                <div class="relative flex-1 sm:w-64">
                    <span class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none text-outline"><span class="material-symbols-outlined text-[16px]">flag</span></span>
                    <select name="status" class="block w-full pl-9 pr-3.5 py-2.5 text-body-sm text-on-surface bg-surface-container-lowest border border-surface-dim rounded focus:ring-0 focus:border-primary focus:outline-none transition-colors">
                        <option value="">{{ __('training.idx_all_statuses') }}</option>
                        @foreach (['completed','under_consultation','needs_correction','payment_pending','payment_confirmed','scheduled','in_progress','passed','failed','served','cancelled'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ __('training.status_' . $s) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary px-4 py-2.5 text-xs font-bold rounded shadow-sm transition-colors">
                    <span class="material-symbols-outlined text-base">search</span>{{ __('training.idx_search') }}
                </button>
                <a href="{{ route('admin.training.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-secondary hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined text-base">refresh</span>{{ __('training.idx_reset') }}
                </a>
            </form>

            {{-- Table --}}
            <div class="overflow-x-auto rounded-lg border border-surface-dim dark:border-gray-700">
                <table class="min-w-full divide-y divide-surface-dim dark:divide-gray-700">
                    <thead class="bg-surface-container-low dark:bg-gray-900/40">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-secondary">{{ __('training.idx_submitted') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-secondary">{{ __('training.idx_applicant') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-secondary">{{ __('training.idx_type') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-secondary">{{ __('training.idx_location') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-secondary">{{ __('training.idx_status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-secondary">{{ __('training.idx_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-container-lowest divide-y divide-surface-dim dark:bg-gray-900/20 dark:divide-gray-700/50">
                        @forelse ($requests as $tr)
                        <tr class="hover:bg-surface-container/40 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-on-surface">{{ $tr->created_at->format('d/m/Y H:i') }}</div>
                                <div class="text-xs text-secondary num-ltr" dir="ltr">{{ $tr->reference ?: '#' . $tr->id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-on-surface">{{ $tr->nom_prenom }}</div>
                                <div class="text-xs text-secondary">{{ $tr->cin }}</div>
                                <div class="text-xs text-secondary">{{ $tr->phone }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-on-surface">{{ $tr->training_type ? __('training.type_' . $tr->training_type) : '—' }}</div>
                                <div class="text-xs text-secondary">{{ $tr->training_kind ? __('training.kind_' . $tr->training_kind) : '—' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-on-surface">{{ $tr->governorate?->name ?? '—' }}</div>
                                <div class="text-xs text-secondary">{{ $tr->delegation?->name ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $sCls = match($tr->status) {
                                        'needs_correction'   => 'bg-red-100 text-red-700',
                                        'under_consultation' => 'bg-purple-100 text-purple-700',
                                        'payment_pending'    => 'bg-orange-100 text-orange-700',
                                        'payment_confirmed'  => 'bg-blue-100 text-blue-700',
                                        'scheduled'          => 'bg-indigo-100 text-indigo-700',
                                        'in_progress'        => 'bg-sky-100 text-sky-700',
                                        'passed'             => 'bg-emerald-100 text-emerald-700',
                                        'failed'             => 'bg-red-100 text-red-700',
                                        'served'             => 'bg-teal-100 text-teal-700',
                                        'cancelled'          => 'bg-surface-container text-secondary',
                                        default              => 'bg-blue-100 text-blue-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $sCls }}">
                                    {{ __('training.status_' . str_replace('-', '_', $tr->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.training.show', $tr) }}" class="w-7 h-7 rounded bg-surface-container-low hover:bg-surface-container text-on-surface flex items-center justify-center shrink-0 transition-colors ms-auto" title="{{ __('training.view_details') }}">
                                    <span class="material-symbols-outlined text-[14px]">visibility</span>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-secondary text-sm">{{ __('training.no_requests') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
