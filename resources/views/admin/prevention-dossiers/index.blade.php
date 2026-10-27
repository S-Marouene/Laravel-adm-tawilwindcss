@extends('admin.layouts.admin')
@section('title', 'Prevention Dossiers')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Prevention Dossiers</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Review and manage submitted prevention certificate requests.</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="mb-5 flex flex-wrap gap-3">
    <input type="text" name="q" value="{{ request('q') }}"
        placeholder="Search by name, CIN or phone..."
        class="flex-1 min-w-48 px-4 py-2 text-sm bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:text-gray-100">
    <select name="status" class="px-4 py-2 text-sm bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:text-gray-100">
        <option value="">All statuses</option>
        <option value="completed"          {{ request('status') === 'completed'          ? 'selected' : '' }}>Under Review</option>
        <option value="submitted"          {{ request('status') === 'submitted'          ? 'selected' : '' }}>Submitted to API</option>
        <option value="under_consultation" {{ request('status') === 'under_consultation' ? 'selected' : '' }}>Approved — Awaiting RDV</option>
        <option value="needs_correction"   {{ request('status') === 'needs_correction'   ? 'selected' : '' }}>Needs Correction</option>
        <option value="approved"           {{ request('status') === 'approved'           ? 'selected' : '' }}>Approved</option>
        <option value="cancelled"          {{ request('status') === 'cancelled'          ? 'selected' : '' }}>Cancelled</option>
    </select>
    <button type="submit" class="px-4 py-2 text-sm font-semibold bg-gov-600 text-white rounded-xl hover:bg-gov-700">Search</button>
    @if(request('q') || request('status'))
        <a href="{{ route('admin.prevention-dossiers.index') }}" class="px-4 py-2 text-sm font-semibold border border-gray-300 dark:border-gray-600 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Reset</a>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Applicant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">CIN</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Submitted</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($dossiers as $dossier)
                    @php
                        $badgeConfig = match($dossier->status) {
                            'under_consultation' => ['dot' => 'bg-purple-500',  'text' => 'Approved — Awaiting RDV', 'cls' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
                            'submitted'          => ['dot' => 'bg-teal-500',    'text' => 'Submitted',          'cls' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'],
                            'needs_correction'   => ['dot' => 'bg-amber-500',   'text' => 'Needs Correction',   'cls' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
                            'approved'           => ['dot' => 'bg-emerald-500', 'text' => 'Approved',           'cls' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
                            'cancelled'          => ['dot' => 'bg-gray-400',    'text' => 'Cancelled',          'cls' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'],
                            default              => ['dot' => 'bg-blue-500',    'text' => 'Under Review',       'cls' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $dossier->nom_prenom }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $dossier->user?->email }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-700 dark:text-gray-300">{{ $dossier->cin }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $dossier->phone ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $dossier->created_at->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('admin.prevention-dossiers.show', $dossier) }}"
                               class="admin-action-link">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No dossiers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($dossiers->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $dossiers->links() }}
        </div>
    @endif
</div>
@endsection
