@extends('admin.layouts.admin')

@section('title', 'Create User')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-secondary hover:text-on-surface dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Users</a>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100 mt-2">Create User</h1>
    </div>

    <div class="max-w-2xl">
        <form action="{{ route('admin.users.store') }}" method="POST" class="admin-form-card p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full px-3 py-2 border border-surface-dim dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="w-full px-3 py-2 border border-surface-dim dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1">Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full px-3 py-2 border border-surface-dim dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="w-full px-3 py-2 border border-surface-dim dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-2">Roles</label>
                <div class="space-y-2">
                    @foreach($roles as $role)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}
                                @if($role->name === 'super-admin' && ! auth()->user()->hasRole('super-admin')) disabled title="Only a super-admin can grant this role" @endif
                                class="rounded border-surface-dim text-purple-600 shadow-sm focus:ring-purple-500">
                            <span class="ml-2 text-sm text-on-surface dark:text-gray-300">{{ $role->name }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-secondary dark:text-gray-400">
                    Territory roles: <strong>gouvernorat</strong> requires a governorate, <strong>delegation</strong> requires both governorate and delegation.
                </p>
                @error('roles')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="p-4 rounded-lg bg-surface-container dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 space-y-4">
                <div class="p-4 rounded-lg bg-surface-container dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 space-y-4">
                <p class="text-sm font-medium text-on-surface dark:text-gray-200">Assign services</p>
                <p class="text-xs text-secondary dark:text-gray-400 -mt-2">Which services this administrator handles. Required (min. one) for centrale / gouvernorat / delegation administrators; they may hold several.</p>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    @foreach($services as $service)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="services[]" value="{{ $service->id }}"
                                {{ in_array($service->id, old('services', [])) ? 'checked' : '' }}
                                class="rounded border-surface-dim text-purple-600 shadow-sm focus:ring-purple-500">
                            <span class="ml-2 text-sm text-on-surface dark:text-gray-300">{{ $service->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('services')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="p-4 rounded-lg bg-surface-container dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 space-y-4">
                <p class="text-sm font-medium text-on-surface dark:text-gray-200">Administrative Territory</p>
                <p class="text-xs text-secondary dark:text-gray-400 -mt-2">Required for gouvernorat / delegation administrators. Leave empty for centrale, admin, super-admin and reference data managers.</p>

                <div>
                    <label for="governorate_id" class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1">Governorate</label>
                    <select name="governorate_id" id="governorate_id"
                        class="w-full px-3 py-2 border border-surface-dim dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('governorate_id') border-red-500 @enderror">
                        <option value="">— None (full access) —</option>
                        @foreach($governorates as $governorate)
                            <option value="{{ $governorate->id }}" @selected(old('governorate_id', $user->governorate_id ?? null) == $governorate->id)>{{ $governorate->id }} — {{ $governorate->name }}</option>
                        @endforeach
                    </select>
                    @error('governorate_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="delegation_id" class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1">Delegation</label>
                    <select name="delegation_id" id="delegation_id"
                        class="w-full px-3 py-2 border border-surface-dim dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('delegation_id') border-red-500 @enderror">
                        <option value="">— None (whole governorate) —</option>
                        @foreach($governorates as $governorate)
                            @foreach($governorate->delegations as $delegation)
                                <option value="{{ $delegation->id }}" @selected(old('delegation_id', $user->delegation_id ?? null) == $delegation->id)>{{ $governorate->name }} → {{ $delegation->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    @error('delegation_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="is_admin" value="1" class="rounded border-surface-dim text-purple-600 shadow-sm focus:ring-purple-500">
                    <span class="ml-2 text-sm text-on-surface dark:text-gray-300">Admin privileges</span>
                </label>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm font-medium text-on-surface dark:text-gray-300 hover:text-secondary">Cancel</a>
                <button type="submit" class="admin-add-button px-4 py-2 text-sm">Create User</button>
            </div>
        </form>
    </div>
@endsection
