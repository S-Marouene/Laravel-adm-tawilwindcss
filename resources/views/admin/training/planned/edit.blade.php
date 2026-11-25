@extends('admin.layouts.admin')
@section('title', __('training.planned_edit'))
@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">{{ __('training.planned_edit') }}</h1>
    <p class="text-sm text-secondary dark:text-gray-400 mt-1">{{ $session->typeLabel() }} — <span dir="ltr">{{ $session->starts_at->format('d/m/Y') }}</span></p>
</div>

<div class="admin-table-card">
    <form method="POST" action="{{ route('admin.training.planned.update', $session) }}" class="p-6 grid grid-cols-1 md:grid-cols-6 gap-4">
        @csrf @method('PUT')
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.training_type') }} <span class="text-red-500">*</span></label>
            <select name="training_type" required class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
                @foreach (\App\Models\TrainingRequest::CENTRALIZED_TYPES as $t)
                    <option value="{{ $t }}" @selected($session->training_type === $t)>{{ __('training.type_' . $t) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.planned_place') }}</label>
            <select name="governorate_id" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
                @foreach ($governorates as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($session->governorate_id ?? $defaultGovernorateId) === (int) $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.planned_starts_at') }} <span class="text-red-500">*</span></label>
            <input type="date" name="starts_at" value="{{ $session->starts_at?->format('Y-m-d') }}" required class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.planned_ends_at') }}</label>
            <input type="date" name="ends_at" value="{{ $session->ends_at?->format('Y-m-d') }}" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.planned_capacity') }}</label>
            <input type="number" name="capacity" min="1" value="{{ $session->capacity }}" placeholder="{{ __('training.planned_capacity_unlimited') }}" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.idx_status') }}</label>
            <select name="status" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
                @foreach (['planned','started','completed','cancelled'] as $st)
                    <option value="{{ $st }}" @selected($session->status === $st)>{{ __('training.planned_status_' . $st) }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-4">
            <label class="block text-xs font-bold uppercase text-secondary mb-1">{{ __('training.notes') }}</label>
            <input type="text" name="notes" maxlength="1000" value="{{ $session->notes }}" class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
        </div>
        <label class="md:col-span-2 flex items-end gap-2 pb-2 text-xs font-bold uppercase text-secondary cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($session->is_active) class="h-4 w-4 text-gov-600 rounded focus:ring-gov-500">
            {{ __('training.planned_active') }}
        </label>
        <div class="md:col-span-6 flex items-center gap-3">
            <button type="submit" class="px-4 py-2 text-sm font-semibold bg-gov-600 text-white rounded-xl hover:bg-gov-700">{{ __('action.save') }}</button>
            <a href="{{ route('admin.training.planned.index') }}" class="px-4 py-2 text-sm font-semibold bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl">{{ __('training.cancel') }}</a>
        </div>
    </form>
</div>

@endsection
