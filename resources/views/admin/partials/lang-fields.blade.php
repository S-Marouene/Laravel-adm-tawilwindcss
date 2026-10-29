@php
    $model = $model ?? null;
    $isLong = $long ?? false;
    $fieldClass = 'block w-full px-4 py-2.5 text-sm text-on-surface dark:text-gray-100 bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 focus:outline-none transition-all duration-200';
    $textareaClass = $fieldClass . ' rows-3';
@endphp

<div>
    <label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">{{ __('admin.name_ar') }} <span class="text-xs text-outline">(عربي)</span></label>
    @if($isLong)
        <textarea name="name_ar" rows="2" required class="{{ $fieldClass }}">{{ old('name_ar', $model?->name_ar ?? '') }}</textarea>
    @else
        <input type="text" name="name_ar" value="{{ old('name_ar', $model?->name_ar ?? '') }}" required class="{{ $fieldClass }}">
    @endif
    @error('name_ar')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">{{ __('admin.name_fr') }} <span class="text-xs text-outline">(Français)</span></label>
    @if($isLong)
        <textarea name="name_fr" rows="2" required class="{{ $fieldClass }}">{{ old('name_fr', $model?->name_fr ?? '') }}</textarea>
    @else
        <input type="text" name="name_fr" value="{{ old('name_fr', $model?->name_fr ?? '') }}" required class="{{ $fieldClass }}">
    @endif
    @error('name_fr')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">{{ __('admin.name_en') }} <span class="text-xs text-outline">(English)</span></label>
    @if($isLong)
        <textarea name="name_en" rows="2" required class="{{ $fieldClass }}">{{ old('name_en', $model?->name_en ?? '') }}</textarea>
    @else
        <input type="text" name="name_en" value="{{ old('name_en', $model?->name_en ?? '') }}" required class="{{ $fieldClass }}">
    @endif
    @error('name_en')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
</div>
