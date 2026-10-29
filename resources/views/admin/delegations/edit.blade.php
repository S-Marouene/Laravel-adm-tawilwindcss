@extends('admin.layouts.admin')
@section('title', __('admin.delegations') . ' — ' . __('action.edit'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.delegations.index') }}" class="text-sm text-secondary hover:text-gov-600">&larr; {{ __('action.back') }}</a></div>
<div class="admin-form-card">
    <div class="px-6 py-4 border-b border-surface-dim dark:border-gray-700"><h2 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ __('action.edit') }}</h2></div>
    <form method="POST" action="{{ route('admin.delegations.update', $delegation) }}" class="p-6 space-y-5">
        @csrf @method('PUT')
        <div><label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">{{ __('admin.governorate') }}</label>
            <select name="governorate_id" required class="block w-full px-4 py-2.5 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
                @foreach($governorates as $id => $name)<option value="{{ $id }}" {{ old('governorate_id', $delegation->governorate_id) == $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach
            </select>@error('governorate_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        @include('admin.partials.lang-fields', ['model' => $delegation])
        <div class="flex justify-end"><button type="submit" class="admin-add-button px-6 py-2.5 text-sm">{{ __('action.save') }}</button></div>
    </form>
</div>
@endsection
