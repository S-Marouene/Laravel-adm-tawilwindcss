@extends('admin.layouts.admin')

@section('title', 'Create User')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Users</a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">Create User</h1>
    </div>

    <div class="max-w-2xl">
        <form action="{{ route('admin.users.store') }}" method="POST" class="admin-form-card p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-gray-100 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roles</label>
                <div class="space-y-2">
                    @foreach($roles as $role)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $role->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="is_admin" value="1" class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Admin privileges</span>
                </label>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-500">Cancel</a>
                <button type="submit" class="admin-add-button px-4 py-2 text-sm">Create User</button>
            </div>
        </form>
    </div>
@endsection
