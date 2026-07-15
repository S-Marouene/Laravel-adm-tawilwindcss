@extends('admin.layouts.admin')

@section('title', 'Create Permission')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.permissions.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Permissions</a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">Create Permission</h1>
    </div>

    <div class="max-w-xl">
        <form action="{{ route('admin.permissions.store') }}" method="POST" class="admin-form-card p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Permission Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('name') border-red-500 @enderror"
                    placeholder="e.g., create posts, edit users">
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="guard_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Guard</label>
                <select name="guard_name" id="guard_name"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm">
                    <option value="web">web</option>
                    <option value="api">api</option>
                </select>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.permissions.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-500">Cancel</a>
                <button type="submit" class="admin-add-button px-4 py-2 text-sm">Create Permission</button>
            </div>
        </form>
    </div>
@endsection
