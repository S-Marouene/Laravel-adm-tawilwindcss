@extends('admin.layouts.admin')
@section('title', __('admin.building_types') . ' — ' . __('action.edit'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.building-types.index') }}" class="text-sm text-secondary hover:text-gov-600">&larr; {{ __('action.back') }}</a></div>
<div class="admin-form-card">
    <div class="px-6 py-4 border-b border-surface-dim dark:border-gray-700"><h2 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('action.edit') }}</h2></div>
    <form method="POST" action="{{ route('admin.building-types.update', $buildingType) }}" class="p-6 space-y-5">
        @csrf @method('PUT')
        @include('admin.partials.lang-fields', ['model' => $buildingType])
        <div><label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">{{ __('admin.has_activities') }}</label>
            <label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" name="has_activities" value="1" class="sr-only peer" {{ old('has_activities', $buildingType->has_activities) ? 'checked' : '' }}><div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-surface-container-lowest after:border-surface-dim after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gov-600"></div></label></div>
        <div><label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">{{ __('admin.sort_order') }}</label><input type="number" name="sort_order" value="{{ old('sort_order', $buildingType->sort_order) }}" class="block w-full px-4 py-2.5 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100"></div>
        <div class="flex justify-end"><button type="submit" class="admin-add-button px-6 py-2.5 text-sm">{{ __('action.save') }}</button></div>
    </form>
</div>
@endsection
