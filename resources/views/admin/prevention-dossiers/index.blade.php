@extends('admin.layouts.admin')
@section('title', __('prevention.idx_title'))

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">{{ __('prevention.idx_title') }}</h1>
        <p class="text-sm text-secondary dark:text-gray-400 mt-1">{{ __('prevention.idx_sub') }}</p>
    </div>
</div>

{{-- Filtres --}}
<form method="GET" class="mb-5 flex flex-wrap gap-3">
    <input type="text" name="q" value="{{ request('q') }}"
        placeholder="{{ __('prevention.idx_search_placeholder') }}"
        class="flex-1 min-w-48 px-4 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:text-gray-100">
    <select name="status" class="px-4 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:text-gray-100">
        <option value="">{{ __('prevention.idx_all_statuses') }}</option>
        <option value="completed"          {{ request('status') === 'completed'          ? 'selected' : '' }}>{{ __('prevention.idx_status_under_review') }}</option>
        <option value="submitted"          {{ request('status') === 'submitted'          ? 'selected' : '' }}>{{ __('prevention.idx_status_submitted_api') }}</option>
        <option value="under_consultation" {{ request('status') === 'under_consultation' ? 'selected' : '' }}>{{ __('prevention.idx_status_await_rdv') }}</option>
        <option value="needs_correction"   {{ request('status') === 'needs_correction'   ? 'selected' : '' }}>{{ __('prevention.st_needs_correction') }}</option>
        <option value="waiting_delivery"   {{ request('status') === 'waiting_delivery'   ? 'selected' : '' }}>{{ __('prevention.idx_status_all_ok') }}</option>
        <option value="approved"           {{ request('status') === 'approved'           ? 'selected' : '' }}>{{ __('prevention.st_approved') }}</option>
        <option value="cancelled"          {{ request('status') === 'cancelled'          ? 'selected' : '' }}>{{ __('prevention.st_cancelled') }}</option>
    </select>
    <button type="submit" class="px-4 py-2 text-sm font-semibold bg-gov-600 text-white rounded-xl hover:bg-gov-700">{{ __('prevention.idx_search') }}</button>
    @if(request('q') || request('status'))
        <a href="{{ route('admin.prevention-dossiers.index') }}" class="px-4 py-2 text-sm font-semibold border border-surface-dim dark:border-gray-600 rounded-xl text-secondary dark:text-gray-300 hover:bg-surface-container dark:hover:bg-gray-700">{{ __('prevention.idx_reset') }}</a>
    @endif
</form>

@if (session('success'))
    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
        {{ session('success') }}
    </div>
@endif

<div class="admin-table-card">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('prevention.idx_applicant') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('prevention.f_cin') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('prevention.idx_phone') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('prevention.idx_location') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('prevention.idx_status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('prevention.idx_submitted') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">{{ __('prevention.idx_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($dossiers as $dossier)
                    @php
                        $badgeConfig = match($dossier->status) {
                            'under_consultation' => ['dot' => 'bg-purple-500',  'text' => __('prevention.idx_status_await_rdv'), 'cls' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
                            'submitted'          => ['dot' => 'bg-teal-500',    'text' => __('prevention.st_submitted'),         'cls' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'],
                            'needs_correction'   => ['dot' => 'bg-amber-500',   'text' => __('prevention.st_needs_correction'),  'cls' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
                            'approved'           => ['dot' => 'bg-emerald-500', 'text' => __('prevention.st_approved'),          'cls' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
                            'waiting_delivery'   => ['dot' => 'bg-emerald-500', 'text' => __('prevention.idx_status_all_ok'),    'cls' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
                            'cancelled'          => ['dot' => 'bg-gray-400',    'text' => __('prevention.st_cancelled'),         'cls' => 'bg-surface-container text-secondary dark:bg-gray-700 dark:text-gray-400'],
                            default              => ['dot' => 'bg-blue-500',    'text' => __('prevention.idx_status_under_review'), 'cls' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
                        };
                    @endphp
                    <tr class="hover:bg-surface-container dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <p class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ $dossier->nom_prenom }}</p>
                                <p class="text-xs text-secondary dark:text-gray-400">{{ $dossier->user?->email }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-on-surface dark:text-gray-300">{{ $dossier->cin }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-on-surface dark:text-gray-300"><span class="num-ltr">{{ $dossier->phone ?? '—' }}</span></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary dark:text-gray-400">
                            {{ $dossier->governorate?->name_fr ?? $dossier->governorate?->name_ar ?? '—' }}
                            @if($dossier->delegation)
                                <br><span class="text-xs">{{ $dossier->delegation?->name_fr ?? $dossier->delegation?->name_ar }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeConfig['cls'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $badgeConfig['dot'] }}"></span>
                                {{ $badgeConfig['text'] }}
                            </span>
                            @if($dossier->admin_note && $dossier->status === 'needs_correction')
                                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400 truncate max-w-[160px]" title="{{ $dossier->admin_note }}">
                                    ⚠ {{ Str::limit($dossier->admin_note, 40) }}
                                </p>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary dark:text-gray-400">
                            {{ $dossier->created_at->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('admin.prevention-dossiers.show', $dossier) }}"
                               class="admin-action-link">{{ __('prevention.idx_view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-secondary dark:text-gray-400">{{ __('prevention.idx_none') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($dossiers->hasPages())
        <div class="px-6 py-4 border-t border-surface-dim dark:border-gray-700">
            {{ $dossiers->links() }}
        </div>
    @endif
</div>
@endsection
