@extends('admin.layouts.admin')
@section('title', __('admin.building_activities') . ' — ' . __('action.add'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.building-activities.index') }}" class="text-sm text-gray-500 hover:text-gov-600">&larr; {{ __('action.back') }}</a></div>
<div class="admin-form-card">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('action.add') }}</h2></div>
    <form method="POST" action="{{ route('admin.building-activities.store') }}" class="p-6 space-y-5">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('admin.building_type') }}</label>
            <select name="building_type_id" required class="block w-full px-4 py-2.5 text-sm bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
                <option value="">{{ __('admin.select_type') }}</option>
                @foreach($buildingTypes as $id => $name)<option value="{{ $id }}" {{ old('building_type_id') == $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach
            </select>@error('building_type_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        @include('admin.partials.lang-fields')
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('admin.sort_order') }}</label><input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="block w-full px-4 py-2.5 text-sm bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100"></div>
        <div class="flex justify-end"><button type="submit" class="admin-add-button px-6 py-2.5 text-sm">{{ __('action.save') }}</button></div>
    </form>
</div>
@endsection
