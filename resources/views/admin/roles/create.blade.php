@extends('admin.layouts.admin')

@section('title', 'Create Role')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.roles.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Roles</a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">Create Role</h1>
    </div>

    <div class="max-w-2xl">
        <form action="{{ route('admin.roles.store') }}" method="POST" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('name') border-red-500 @enderror"
                    placeholder="e.g., editor, moderator">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Permissions</label>
                @foreach($permissions as $group => $groupPermissions)
                    <div class="mb-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">{{ $group }}</h4>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($groupPermissions as $permission)
                                <label class="inline-flex items-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-500">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">Create Role</button>
            </div>
        </form>
    </div>
@endsection
