@extends('admin.layouts.admin')
@section('title', __('works_notification.index_title'))

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">{{ __('works_notification.index_title') }}</h1>
        <p class="text-sm text-secondary dark:text-gray-400 mt-1">{{ __('works_notification.index_subtitle') }}</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="mb-5 flex flex-wrap gap-3">
    <input type="text" name="q" value="{{ request('q') }}"
        placeholder="{{ __('works_notification.search_placeholder') }}"
        class="flex-1 min-w-48 px-4 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:text-gray-100">
    <select name="status" class="px-4 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:text-gray-100">
        <option value="">{{ __('works_notification.idx_all_statuses') }}</option>
        <option value="completed"          {{ request('status') === 'completed'          ? 'selected' : '' }}>{{ __('works_notification.status_completed') }}</option>
        <option value="under_consultation" {{ request('status') === 'under_consultation' ? 'selected' : '' }}>{{ __('works_notification.status_under_consultation') }}</option>
        <option value="needs_correction"   {{ request('status') === 'needs_correction'   ? 'selected' : '' }}>{{ __('works_notification.status_needs_correction') }}</option>
        <option value="approved"           {{ request('status') === 'approved'           ? 'selected' : '' }}>{{ __('works_notification.status_approved') }}</option>
        <option value="cancelled"          {{ request('status') === 'cancelled'          ? 'selected' : '' }}>{{ __('works_notification.status_cancelled') }}</option>
    </select>
    <button type="submit" class="px-4 py-2 text-sm font-semibold bg-gov-600 text-white rounded-xl hover:bg-gov-700">{{ __('works_notification.idx_search') }}</button>
    @if(request('q') || request('status'))
        <a href="{{ route('admin.building-works-declarations.index') }}" class="px-4 py-2 text-sm font-semibold border border-surface-dim dark:border-gray-600 rounded-xl text-secondary dark:text-gray-300 hover:bg-surface-container dark:hover:bg-gray-700">{{ __('works_notification.idx_reset') }}</a>
    @endif
</form>

<div class="admin-table-card">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('works_notification.idx_col_reference') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('works_notification.idx_col_applicant') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('works_notification.owner_cin') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('works_notification.phone') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('works_notification.event_label') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('works_notification.idx_col_location') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('works_notification.idx_col_status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('works_notification.idx_col_submitted') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($notifications as $notification)
                    @php
                        $badgeConfig = match($notification->status) {
                            'under_consultation' => [
                                'dot' => 'bg-purple-500',
                                'text' => __('works_notification.status_under_consultation'),
                                'cls' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                            ],
                            'needs_correction' => [
                                'dot' => 'bg-amber-500',
                                'text' => __('works_notification.status_needs_correction'),
                                'cls' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            ],
                            'approved' => [
                                'dot' => 'bg-emerald-500',
                                'text' => __('works_notification.status_approved'),
                                'cls' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            ],
                            'cancelled' => [
                                'dot' => 'bg-gray-400',
                                'text' => __('works_notification.status_cancelled'),
                                'cls' => 'bg-surface-container text-secondary dark:bg-gray-700 dark:text-gray-400',
                            ],
                            default => [
                                'dot' => 'bg-blue-500',
                                'text' => __('works_notification.status_under_review'),
                                'cls' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                            ],
                        };
                    @endphp
                    <tr class="hover:bg-surface-container dark:hover:bg-gray-700/30 transition-colors">

                        {{-- Reference --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($notification->reference)
                                <span class="font-mono text-xs font-bold text-primary dark:text-primary/80" dir="ltr">{{ $notification->reference }}</span>
                            @else
                                <span class="text-xs text-outline">—</span>
                            @endif
                        </td>

                        {{-- Applicant --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <p class="text-sm font-semibold text-on-surface dark:text-gray-100">
                                    {{ $notification->owner_name ?: $notification->company_name }}
                                </p>
                                <p class="text-xs text-secondary dark:text-gray-400">{{ $notification->user?->email }}</p>
                                @if ($notification->isMoral())
                                    <span class="inline-flex items-center gap-0.5 rounded bg-surface-container px-1.5 py-0.5 text-[10px] font-bold text-secondary dark:bg-gray-700 dark:text-gray-400">
                                        <span class="material-symbols-outlined text-[11px]" aria-hidden="true">business</span>
                                        {{ __('works_notification.owner_moral') }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- CIN --}}
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-on-surface dark:text-gray-300">
                            {{ $notification->owner_cin ?? '—' }}
                        </td>

                        {{-- Phone --}}
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-on-surface dark:text-gray-300">
                            <span class="num-ltr">{{ $notification->phone ?? '—' }}</span>
                        </td>

                        {{-- Works type + start date --}}
                        <td class="px-6 py-4 text-sm text-on-surface dark:text-gray-300 max-w-[200px]">
                            @if($notification->works_type)
                                <span class="truncate block" title="{{ $notification->works_type }}">{{ $notification->works_type }}</span>
                            @else
                                <span class="text-outline">—</span>
                            @endif
                            @if($notification->works_start_date)
                                <span class="text-xs text-outline block" dir="ltr">{{ $notification->works_start_date->format('d/m/Y') }}</span>
                            @endif
                        </td>

                        {{-- Location --}}
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary dark:text-gray-400">
                            {{ $notification->governorate?->name_fr ?? $notification->governorate?->name_ar ?? '—' }}
                            @if($notification->delegation)
                                <br><span class="text-xs">{{ $notification->delegation?->name_fr ?? $notification->delegation?->name_ar }}</span>
                            @endif
                        </td>

                        {{-- Status badge --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeConfig['cls'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $badgeConfig['dot'] }}"></span>
                                {{ $badgeConfig['text'] }}
                            </span>
                            @if($notification->admin_note && $notification->status === 'needs_correction')
                                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400 truncate max-w-[160px]"
                                   title="{{ $notification->admin_note }}">
                                    ⚠ {{ Str::limit($notification->admin_note, 40) }}
                                </p>
                            @endif
                        </td>

                        {{-- Submitted date --}}
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary dark:text-gray-400">
                            {{ $notification->created_at->format('d M Y') }}
                        </td>

                        {{-- Action --}}
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('admin.building-works-declarations.show', $notification) }}"
                               class="admin-action-link">{{ __('action.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-2 text-secondary dark:text-gray-400">
                                <span class="material-symbols-outlined text-4xl text-outline" aria-hidden="true">engineering</span>
                                <p class="text-sm font-medium">{{ __('admin.no_data') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($notifications->hasPages())
        <div class="px-6 py-4 border-t border-surface-dim dark:border-gray-700">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
