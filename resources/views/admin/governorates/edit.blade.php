@extends('admin.layouts.admin')
@section('title', __('admin.governorates') . ' — ' . __('action.edit'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.governorates.index') }}" class="text-sm text-gray-500 hover:text-gov-600">&larr; {{ __('action.back') }}</a></div>
<div class="admin-form-card">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"><h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('action.edit') }} — ID {{ $governorate->id }}</h2></div>
    <form method="POST" action="{{ route('admin.governorates.update', $governorate) }}" class="p-6 space-y-5">
        @csrf @method('PUT')
        @include('admin.partials.lang-fields', ['model' => $governorate])
        <div class="flex justify-end"><button type="submit" class="admin-add-button px-6 py-2.5 text-sm">{{ __('action.save') }}</button></div>
    </form>
</div>
@endsection
