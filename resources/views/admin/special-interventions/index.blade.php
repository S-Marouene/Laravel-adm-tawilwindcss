<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface dark:text-gray-100">{{ __('special_intervention.index_title') }}</h2>
                <p class="mt-0.5 text-sm text-secondary dark:text-gray-400">{{ __('special_intervention.index_subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Search + filters --}}
            <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3 items-end">
                <div class="relative flex-1 sm:w-80">
                    <span class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none text-outline">
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">search</span>
                    </span>
                    <input type="search" name="q" value="{{ request('q') }}"
                           placeholder="{{ __('special_intervention.idx_search') }}"
                           class="block w-full pl-9 pr-3.5 py-2.5 text-body-md text-on-surface placeholder:text-secondary/70
                           bg-surface-container-lowest border border-surface-dim rounded hover:border-surface-container-highest
                           focus:ring-0 focus:border-primary dark:focus:border-primary focus:outline-none transition-colors duration-150">
                </div>

                <div class="relative flex-1 sm:w-64">
                    <span class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none text-outline">
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">flag</span>
                    </span>
                    <select name="status"
                            class="block w-full pl-9 pr-3.5 py-2.5 text-body-sm text-on-surface bg-surface-container-lowest border border-surface-dim rounded hover:border-surface-container-highest focus:ring-0 focus:border-primary dark:focus:border-primary focus:outline-none transition-colors">
                        <option value="">{{ __('special_intervention.idx_all_statuses') }}</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('special_intervention.status_draft') }}</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('special_intervention.status_completed') }}</option>
                        <option value="under_consultation" {{ request('status') === 'under_consultation' ? 'selected' : '' }}>{{ __('special_intervention.status_under_consultation') }}</option>
                        <option value="needs_correction" {{ request('status') === 'needs_correction' ? 'selected' : '' }}>{{ __('special_intervention.status_needs_correction') }}</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('special_intervention.status_approved') }}</option>
                        <option value="served" {{ request('status') === 'served' ? 'selected' : '' }}>{{ __('special_intervention.status_served') }}</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('special_intervention.status_cancelled') }}</option>
                    </select>
                </div>

                <button type="submit"
                    class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary px-4 py-2.5 text-xs font-bold rounded shadow-sm transition-colors">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">search</span>
                    {{ __('special_intervention.idx_search') }}
                </button>

                <a href="{{ route('admin.special-interventions.index') }}"
                    class="inline-flex items-center gap-2 text-xs font-semibold text-secondary hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">refresh</span>
                    {{ __('special_intervention.idx_reset') }}
                </a>
            </form>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-surface-dim">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-secondary">{{ __('special_intervention.idx_submitted') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-secondary">{{ __('special_intervention.idx_applicant') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-secondary">{{ __('special_intervention.idx_location') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-secondary">{{ __('special_intervention.idx_status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-secondary">{{ __('special_intervention.total_amount') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-secondary">{{ __('special_intervention.idx_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-container-lowest divide-y divide-surface-dim">
                        @foreach ($interventions as $intervention)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-on-surface">{{ $intervention->created_at->format('d/m/Y H:i') }}</div>
                                <div class="text-xs text-secondary num-ltr" dir="ltr">#{{ $intervention->id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-on-surface">{{ $intervention->nom_prenom }}</div>
                                <div class="text-xs text-secondary">{{ $intervention->cin }}</div>
                                <div class="text-xs text-secondary">{{ $intervention->phone }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-on-surface">{{ $intervention->governorate?->name ?? '—' }}</div>
                                <div class="text-xs text-secondary">{{ $intervention->delegation?->name ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $intervention->status === 'needs_correction'   ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : '' }}
                                    {{ $intervention->status === 'under_consultation' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : '' }}
                                    {{ $intervention->status === 'approved'           ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : '' }}
                                    {{ $intervention->status === 'served'             ? 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300' : '' }}
                                    {{ $intervention->status === 'cancelled'          ? 'bg-surface-container text-secondary dark:bg-gray-700 dark:text-gray-400' : '' }}
                                    {{ $intervention->status === 'completed'          ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : '' }}">
                                    <span class="h-1.5 w-1.5 rounded-full
                                        {{ $intervention->status === 'needs_correction'   ? 'bg-red-500' : '' }}
                                        {{ $intervention->status === 'under_consultation' ? 'bg-purple-500' : '' }}
                                        {{ $intervention->status === 'approved'           ? 'bg-emerald-500' : '' }}
                                        {{ $intervention->status === 'served'             ? 'bg-teal-500' : '' }}
                                        {{ $intervention->status === 'cancelled'          ? 'bg-gray-400' : '' }}
                                        {{ $intervention->status === 'completed'          ? 'bg-blue-500' : '' }}"></span>
                                    {{ __('special_intervention.status_' . str_replace('-', '_', $intervention->status)) }}
                                </span>
                                @if ($intervention->admin_note && in_array($intervention->status, ['needs_correction', 'cancelled']))
                                    <div class="mt-1 text-[10px] text-secondary/80">{{ __('special_intervention.admin_note_label') }} {{ \Illuminate\Support\Str::limit($intervention->admin_note, 60) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-medium text-on-surface num-ltr" dir="ltr">
                                    {{ number_format($intervention->total_amount, 0) }} {{ __('special_intervention.currency_tnd') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.special-interventions.show', $intervention) }}"
                                       class="w-7 h-7 rounded bg-surface-container-low hover:bg-surface-container text-on-surface flex items-center justify-center shrink-0 transition-colors"
                                       title="{{ __('special_intervention.view_details') }}">
                                        <span class="material-symbols-outlined text-[14px]" aria-hidden="true">visibility</span>
                                    </a>

                                    {{-- Workflow actions --}}
                                    @php
                                        $status = $intervention->status;
                                        $canAdvance = in_array($status, ['completed', 'needs_correction']);
                                        $canApprove = $status === 'under_consultation';
                                        $canCorrect = $status === 'completed' || $status === 'under_consultation' || $status === 'needs_correction';
                                        $canCancel  = $status === 'completed' || $status === 'under_consultation' || $status === 'needs_correction';
                                        $canServe   = $status === 'approved';
                                    @endphp

                                    @if ($canAdvance)
                                        <form method="POST" action="{{ route('admin.special-interventions.advance', $intervention) }}"
                                              data-confirm data-confirm-style="warning"
                                              class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="w-7 h-7 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 flex items-center justify-center shrink-0 transition-colors"
                                                title="{{ __('special_intervention.admin_advance_btn') }}">
                                                <span class="material-symbols-outlined text-[14px]" aria-hidden="true">sync_alt</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if ($canCorrect)
                                        <form method="POST" action="{{ route('admin.special-interventions.correction', $intervention) }}"
                                              data-confirm data-confirm-style="warning"
                                              class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="w-7 h-7 rounded bg-amber-50 text-amber-700 hover:bg-amber-100 flex items-center justify-center shrink-0 transition-colors"
                                                title="{{ __('special_intervention.admin_correction_btn') }}">
                                                <span class="material-symbols-outlined text-[14px]" aria-hidden="true">edit_note</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if ($canApprove)
                                        <form method="POST" action="{{ route('admin.special-interventions.approve', $intervention) }}"
                                              data-confirm data-confirm-style="success"
                                              class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="w-7 h-7 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 flex items-center justify-center shrink-0 transition-colors"
                                                title="{{ __('special_intervention.admin_approve_btn') }}">
                                                <span class="material-symbols-outlined text-[14px]" aria-hidden="true">task_alt</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if ($canServe)
                                        <form method="POST" action="{{ route('admin.special-interventions.serve', $intervention) }}"
                                              data-confirm data-confirm-style="success"
                                              class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="w-7 h-7 rounded bg-teal-50 text-teal-700 hover:bg-teal-100 flex items-center justify-center shrink-0 transition-colors"
                                                title="{{ __('special_intervention.admin_serve_btn') }}">
                                                <span class="material-symbols-outlined text-[14px]" aria-hidden="true">handshake</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if ($canCancel)
                                        <form method="POST" action="{{ route('admin.special-interventions.cancel', $intervention) }}"
                                              data-confirm data-confirm-style="danger"
                                              class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="w-7 h-7 rounded bg-red-50 text-red-700 hover:bg-red-100 flex items-center justify-center shrink-0 transition-colors"
                                                title="{{ __('special_intervention.admin_cancel_btn') }}">
                                                <span class="material-symbols-outlined text-[14px]" aria-hidden="true">cancel</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            {{ $interventions->links() }}

        </div>
    </div>
</x-app-layout>
