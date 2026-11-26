@extends('admin.layouts.admin')
@section('title', __('training.planned_title'))
@section('content')

@php
    $canCreate = auth()->user()->can(\App\Models\TrainingPlannedSession::PERM_CREATE);
    $canEdit   = auth()->user()->can(\App\Models\TrainingPlannedSession::PERM_EDIT);
    $canDelete = auth()->user()->can(\App\Models\TrainingPlannedSession::PERM_DELETE);
    $readOnly  = ! ($canCreate || $canEdit || $canDelete);
    $defaultType = \App\Models\TrainingRequest::CENTRALIZED_TYPES[0];
@endphp

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">{{ __('training.planned_title') }}</h1>
        <p class="text-sm text-secondary dark:text-gray-400 mt-1">{{ __('training.planned_desc') }}</p>
    </div>
    <a href="{{ route('admin.training.index') }}" class="px-4 py-2 text-sm font-semibold bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl hover:bg-surface-container">
        {{ __('training.planned_back_to_requests') }}
    </a>
</div>

@if($readOnly)
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/10 dark:text-amber-300">
    <span class="material-symbols-outlined text-base align-middle mr-1" aria-hidden="true">lock</span>
    {{ __('training.planned_readonly_notice') }}
</div>
@endif

@can(\App\Models\TrainingPlannedSession::PERM_CREATE)
{{-- Create form --}}
<div class="admin-table-card mb-6">
    <div class="px-6 py-4 border-b border-surface-dim dark:border-gray-700">
        <h2 class="text-sm font-bold text-on-surface dark:text-gray-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">add_circle</span>
            {{ __('training.planned_add') }}
        </h2>
    </div>
    <form id="planned-create-form" method="POST" action="{{ route('admin.training.planned.store') }}" class="p-6 grid grid-cols-1 md:grid-cols-6 gap-4">
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
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.planned_place') }}</label>
            <select name="governorate_id" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
                @foreach ($governorates as $id => $name)
                    <option value="{{ $id }}" @selected((int) $id === $defaultGovernorateId)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.planned_starts_at') }} <span class="text-red-500">*</span></label>
            <input type="date" name="starts_at" required class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.planned_ends_at') }}</label>
            <input type="date" name="ends_at" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.planned_capacity') }}</label>
            <input type="number" name="capacity" min="1" placeholder="{{ __('training.planned_capacity_unlimited') }}" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>
        <label class="flex items-end gap-2 pb-2 text-xs font-bold uppercase text-secondary cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 text-gov-600 rounded focus:ring-gov-500">
            {{ __('training.planned_active') }}
        </label>
        <div class="md:col-span-6">
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.notes') }}</label>
            <input type="text" name="notes" maxlength="1000" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>

        @if ($waitlist->isNotEmpty())
        {{-- Wait-listed requests are pre-checked and attached on creation. --}}
        <div id="create-waitlist-box" class="md:col-span-6 rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/50 dark:bg-amber-900/10">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">hourglass_top</span>
                    {{ __('training.planned_waitlist_create_title') }}
                </p>
                <span class="text-xs font-semibold text-amber-800 dark:text-amber-300">
                    <span id="create-waitlist-count">{{ $waitlistPeopleCount[$defaultType] ?? 0 }}</span>
                    {{ __('training.planned_waitlist_people') }}
                </span>
            </div>
            <p class="text-xs text-amber-900/80 dark:text-amber-200/80 mb-3">{{ __('training.planned_waitlist_create_hint') }}</p>

            @foreach ($waitlist as $type => $requests)
                <div data-create-waitlist-type="{{ $type }}" data-people-count="{{ $waitlistPeopleCount[$type] ?? $requests->count() }}" class="space-y-2 mb-3 @unless($type === $defaultType) hidden @endunless">
                    <p class="text-[11px] font-bold uppercase text-secondary">
                        {{ __('training.type_' . $type) }} — {{ $waitlistPeopleCount[$type] ?? $requests->count() }} {{ __('training.planned_waitlist_people') }}
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($requests as $tr)
                            <label class="flex items-start gap-2 rounded border border-amber-200 bg-white/70 px-3 py-2 cursor-pointer dark:border-amber-900/40 dark:bg-gray-900/30">
                                <input type="checkbox" name="assign_requests[]" value="{{ $tr->id }}" checked class="mt-0.5 h-4 w-4 text-gov-600 rounded focus:ring-gov-500" @unless($type === $defaultType) disabled @endunless>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold text-on-surface dark:text-gray-100">{{ $tr->nom_prenom }}</span>
                                    <span class="block text-[11px] text-secondary">{{ $tr->reference ?? '—' }} · {{ $tr->cin }}@if($tr->governorate) · {{ $tr->governorate->name }}@endif</span>
                                    @if ($tr->isCompany() && $tr->participants->isNotEmpty())
                                        <span class="block text-[11px] text-secondary mt-0.5">{{ $tr->participants->pluck('nom_prenom')->filter()->implode(' · ') }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <p id="create-waitlist-empty" class="hidden text-xs text-secondary">{{ __('training.planned_waitlist_none') }}</p>
        </div>
        @endif

        <div class="md:col-span-6">
            <button type="submit" class="admin-add-button px-4 py-2 text-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('action.add') }}
            </button>
        </div>
    </form>
</div>
@endcan

{{-- Sessions grouped by formation type — each type is collapsible --}}
@if ($sessionsByType->isEmpty())
    <div class="admin-table-card mb-6 px-6 py-12 text-center text-secondary dark:text-gray-400">{{ __('training.planned_none') }}</div>
@else
    <style>
        .planned-group > summary { list-style: none; }
        .planned-group > summary::-webkit-details-marker { display: none; }
        .planned-group[open] .planned-chevron { transform: rotate(180deg); }
    </style>
    @foreach ($sessionsByType as $type => $typeSessions)
        <details class="admin-table-card planned-group mb-4" @if($loop->first) open @endif>
            <summary class="px-6 py-4 flex flex-wrap items-center justify-between gap-3 cursor-pointer hover:bg-surface-container/60 dark:hover:bg-gray-700/20">
                <div class="min-w-0">
                    <p class="text-sm font-bold text-on-surface dark:text-gray-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">calendar_month</span>
                        {{ __('training.type_' . $type) }}
                    </p>
                    <p class="text-[11px] text-secondary mt-1" dir="ltr">
                        {{ $typeSessions->pluck('starts_at')->map(fn ($date) => $date->format('d/m/Y'))->implode(' · ') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="admin-badge">{{ $typeSessions->count() }} {{ __('training.planned_sessions_count') }}</span>
                    <span class="material-symbols-outlined planned-chevron text-secondary transition-transform" aria-hidden="true">expand_more</span>
                </div>
            </summary>
            <div class="border-t border-surface-dim dark:border-gray-700 overflow-x-auto">
                <table class="w-full">
                    <thead><tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('training.planned_place') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('training.planned_starts_at') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('training.planned_ends_at') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('training.idx_status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('training.planned_active') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('training.planned_seats') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.actions') }}</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($typeSessions as $s)
                            <tr class="hover:bg-surface-container dark:hover:bg-gray-700/30">
                                <td class="px-6 py-4 text-sm text-secondary dark:text-gray-300">{{ $s->placeName() }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-on-surface dark:text-gray-100" dir="ltr">{{ $s->starts_at?->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-sm text-secondary dark:text-gray-300" dir="ltr">{{ $s->ends_at?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm"><span class="admin-badge">{{ $s->statusLabel() }}</span></td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($s->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-xs font-bold dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('training.planned_active_badge') }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-200 text-gray-700 text-xs font-bold dark:bg-gray-700 dark:text-gray-300">{{ __('training.planned_inactive_badge') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="admin-badge">{{ $s->seatsTaken() }}</span> / {{ $s->capacity ?? '∞' }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    @if($canEdit)
                                        <a href="{{ route('admin.training.planned.edit', $s) }}" class="admin-action-link mr-3">{{ __('action.edit') }}</a>
                                    @endif
                                    @if($canDelete)
                                        <form action="{{ route('admin.training.planned.destroy', $s) }}" method="POST" class="inline" data-confirm data-confirm-style="danger">
                                            @csrf @method('DELETE')<button type="submit" class="admin-danger-link">{{ __('action.delete') }}</button>
                                        </form>
                                    @endif
                                    @if(! $canEdit && ! $canDelete)
                                        <span class="text-xs text-outline">{{ __('training.planned_readonly_badge') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @php
                                $enrolled        = $s->enrolledParticipants();
                                $sessionWaitlist = $s->waitlistedParticipants();
                            @endphp
                            @if ($enrolled->isNotEmpty() || $sessionWaitlist->isNotEmpty())
                                <tr class="bg-surface-container/30 dark:bg-gray-700/10">
                                    <td colspan="7" class="px-6 py-3">
                                        <div class="flex flex-wrap gap-x-6 gap-y-1.5">
                                            @foreach ($enrolled as $row)
                                                @php $p = $row['participant']; @endphp
                                                <span class="inline-flex items-center gap-1 text-xs">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    <span class="font-semibold text-on-surface dark:text-gray-100">{{ $p->nom_prenom }}</span>
                                                    <span class="text-outline">·</span>
                                                    <span class="text-secondary">{{ $p->cin }}</span>
                                                    @if ($row['request']->isCompany())<span class="text-outline">·</span><span class="text-secondary">{{ $row['request']->company_name }}</span>@endif
                                                </span>
                                            @endforeach
                                            @foreach ($sessionWaitlist as $row)
                                                @php $p = $row['participant']; @endphp
                                                <span class="inline-flex items-center gap-1 text-xs">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    <span class="font-semibold text-on-surface dark:text-gray-100">{{ $p->nom_prenom }}</span>
                                                    <span class="text-outline">·</span>
                                                    <span class="text-secondary">{{ $p->cin }}</span>
                                                    <span class="text-amber-700 dark:text-amber-400">({{ __('training.waitlist_badge') }})</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endforeach
@endif

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
                    <p class="text-xs font-bold uppercase tracking-wider text-secondary mb-2">{{ __('training.type_' . $type) }} ({{ $waitlistPeopleCount[$type] ?? $requests->count() }})</p>
                    @php
                        $sessionsForType = $sessions->where('training_type', $type)->filter(fn($s) => $s->isAssignable());
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
                                            @if(! $canEdit)
                                                <span class="text-xs text-outline">{{ __('training.planned_readonly_badge') }}</span>
                                            @elseif ($sessionsForType->isNotEmpty())
                                                <form method="POST" action="{{ route('admin.training.planned.assign') }}" class="inline-flex items-center gap-2">
                                                    @csrf
                                                    <input type="hidden" name="training_request_id" value="{{ $tr->id }}">
                                                    <select name="planned_session_id" class="text-xs px-2 py-1 rounded border border-surface-dim dark:border-gray-600 bg-surface-container-lowest dark:bg-gray-900/50">
                                                        @foreach ($sessionsForType as $sf)
                                                            <option value="{{ $sf->id }}">{{ $sf->starts_at->format('d/m/Y') }} — {{ $sf->placeName() }}</option>
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

@can(\App\Models\TrainingPlannedSession::PERM_CREATE)
@if ($waitlist->isNotEmpty())
<script>
(function () {
    var form  = document.getElementById('planned-create-form');
    var box   = document.getElementById('create-waitlist-box');
    var count = document.getElementById('create-waitlist-count');
    var empty = document.getElementById('create-waitlist-empty');
    if (!form || !box) return;

    var select = form.querySelector('select[name="training_type"]');
    var groups = Array.prototype.slice.call(box.querySelectorAll('[data-create-waitlist-type]'));

    function sync() {
        var type = select ? select.value : '';
        var people = 0;
        var visible = false;

        groups.forEach(function (group) {
            var active = group.getAttribute('data-create-waitlist-type') === type;
            group.classList.toggle('hidden', !active);
            group.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                checkbox.disabled = !active;
            });
            if (active) {
                visible = true;
                people += parseInt(group.getAttribute('data-people-count') || '0', 10);
            }
        });

        if (count) count.textContent = people;
        if (empty) empty.classList.toggle('hidden', visible);
    }

    if (select) select.addEventListener('change', sync);
    sync();
}());
</script>
@endif
@endcan

@endsection
