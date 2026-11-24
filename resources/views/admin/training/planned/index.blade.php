@extends('admin.layouts.admin')
@section('title', __('training.planned_title'))
@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">{{ __('training.planned_title') }}</h1>
        <p class="text-sm text-secondary dark:text-gray-400 mt-1">{{ __('training.planned_desc') }}</p>
    </div>
    <a href="{{ route('admin.training.index') }}" class="px-4 py-2 text-sm font-semibold bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl hover:bg-surface-container">
        {{ __('training.planned_back_to_requests') }}
    </a>
</div>

{{-- Create form --}}
<div class="admin-table-card mb-6">
    <div class="px-6 py-4 border-b border-surface-dim dark:border-gray-700">
        <h2 class="text-sm font-bold text-on-surface dark:text-gray-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">add_circle</span>
            {{ __('training.planned_add') }}
        </h2>
    </div>
    <form method="POST" action="{{ route('admin.training.planned.store') }}" class="p-6 grid grid-cols-1 md:grid-cols-5 gap-4">
        @csrf
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.training_type') }} <span class="text-red-500">*</span></label>
            <select name="training_type" required class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
                @foreach (\App\Models\TrainingRequest::CENTRALIZED_TYPES as $t)
                    <option value="{{ $t }}">{{ __('training.type_' . $t) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.planned_starts_at') }} <span class="text-red-500">*</span></label>
            <input type="datetime-local" name="starts_at" required class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.planned_ends_at') }}</label>
            <input type="datetime-local" name="ends_at" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.planned_capacity') }}</label>
            <input type="number" name="capacity" min="1" placeholder="{{ __('training.seats_unlimited') }}" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>
        <div class="md:col-span-5">
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.notes') }}</label>
            <input type="text" name="notes" maxlength="1000" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>
        <div class="md:col-span-5">
            <button type="submit" class="admin-add-button px-4 py-2 text-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('action.add') }}
            </button>
        </div>
    </form>
</div>

{{-- Sessions table --}}
<div class="admin-table-card mb-6">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('training.training_type') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('training.planned_starts_at') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('training.planned_ends_at') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('training.idx_status') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('training.planned_seats') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.actions') }}</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($sessions as $s)
                <tr class="hover:bg-surface-container dark:hover:bg-gray-700/30">
                    <td class="px-6 py-4 text-sm font-semibold text-on-surface dark:text-gray-100">{{ $s->typeLabel() }}</td>
                    <td class="px-6 py-4 text-sm text-secondary dark:text-gray-300" dir="ltr">{{ $s->starts_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4 text-sm text-secondary dark:text-gray-300" dir="ltr">{{ $s->ends_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="admin-badge">{{ $s->statusLabel() }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <span class="admin-badge">{{ $s->requests_count }}</span>
                        / {{ $s->capacity ?? '∞' }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('admin.training.planned.edit', $s) }}" class="admin-action-link mr-3">{{ __('action.edit') }}</a>
                        <form action="{{ route('admin.training.planned.destroy', $s) }}" method="POST" class="inline" data-confirm data-confirm-style="danger">
                            @csrf @method('DELETE')<button type="submit" class="admin-danger-link">{{ __('action.delete') }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-secondary dark:text-gray-400">{{ __('training.planned_none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Wait-list per type --}}
@if ($waitlist->isNotEmpty())
    <div class="admin-table-card">
        <div class="px-6 py-4 border-b border-surface-dim dark:border-gray-700">
            <h2 class="text-sm font-bold text-on-surface dark:text-gray-100 flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600">hourglass_top</span>
                {{ __('training.waitlist_title') }}
            </h2>
        </div>
        <div class="p-6 space-y-6">
            @foreach ($waitlist as $type => $requests)
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-secondary mb-2">{{ __('training.type_' . $type) }} ({{ $requests->count() }})</p>
                    @php
                        $sessionsForType = $sessions->where('training_type', $type)->filter(fn($s) => $s->isSelectable());
                    @endphp
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead><tr class="text-left text-[11px] uppercase text-secondary">
                                <th class="py-2 pr-4">{{ __('training.reference') }}</th>
                                <th class="py-2 pr-4">{{ __('training.nom_prenom') }}</th>
                                <th class="py-2 pr-4">{{ __('training.code_structure') }}</th>
                                <th class="py-2 pr-4">{{ __('training.created_at') }}</th>
                                <th class="py-2 pr-4">{{ __('training.planned_assign') }}</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($requests as $tr)
                                    <tr>
                                        <td class="py-2 pr-4 text-xs font-mono text-secondary">{{ $tr->reference ?? '—' }}</td>
                                        <td class="py-2 pr-4 text-xs text-on-surface dark:text-gray-200">{{ $tr->nom_prenom }}</td>
                                        <td class="py-2 pr-4 text-xs text-secondary">{{ $tr->governorate?->name }}</td>
                                        <td class="py-2 pr-4 text-xs text-secondary" dir="ltr">{{ $tr->created_at?->format('d/m/Y') }}</td>
                                        <td class="py-2 pr-4">
                                            @if ($sessionsForType->isNotEmpty())
                                                <form method="POST" action="{{ route('admin.training.planned.assign') }}" class="inline-flex items-center gap-2">
                                                    @csrf
                                                    <input type="hidden" name="training_request_id" value="{{ $tr->id }}">
                                                    <select name="planned_session_id" class="text-xs px-2 py-1 rounded border border-surface-dim dark:border-gray-600 bg-surface-container-lowest dark:bg-gray-900/50">
                                                        @foreach ($sessionsForType as $sf)
                                                            <option value="{{ $sf->id }}">{{ $sf->starts_at->format('d/m/Y H:i') }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="px-3 py-1 text-xs font-semibold bg-gov-600 text-white rounded hover:bg-gov-700">{{ __('training.planned_assign') }}</button>
                                                </form>
                                            @else
                                                <span class="text-xs text-outline">{{ __('training.planned_no_selectable') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@endsection
