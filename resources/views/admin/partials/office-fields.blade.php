@php
    $model = $model ?? null;
    $fieldClass = 'block w-full px-4 py-2.5 text-sm text-on-surface dark:text-gray-100 bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 focus:outline-none transition-all duration-200';
@endphp

{{-- Pickup office details for the prevention certificate --}}
<div class="col-span-full rounded-xl border border-surface-dim dark:border-gray-600 bg-surface-container-low/50 dark:bg-gray-800/40 p-4">
    <div class="flex items-start gap-2.5 mb-3">
        <span class="material-symbols-outlined text-gov-600 text-xl mt-0.5" aria-hidden="true">storefront</span>
        <div>
            <p class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ __('admin.office_details') }}</p>
            <p class="text-xs text-secondary mt-0.5">{{ __('admin.office_details_desc') }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">{{ __('admin.office_address') }}</label>
            <input type="text" name="office_address" value="{{ old('office_address', $model?->office_address ?? '') }}" class="{{ $fieldClass }}">
            @error('office_address')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">{{ __('admin.office_phone') }}</label>
            <input type="text" name="office_phone" value="{{ old('office_phone', $model?->office_phone ?? '') }}" maxlength="40" dir="ltr" class="{{ $fieldClass }}">
            @error('office_phone')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">{{ __('admin.office_hours') }}</label>
            <input type="text" name="office_hours" value="{{ old('office_hours', $model?->office_hours ?? '') }}" maxlength="255" class="{{ $fieldClass }}">
            @error('office_hours')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
