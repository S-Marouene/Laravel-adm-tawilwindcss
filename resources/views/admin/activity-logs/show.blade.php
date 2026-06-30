@extends('admin.layouts.admin')

@section('title', 'Log Detail')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.activity-logs.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to Activity Logs</a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-2">Log Detail</h1>
    </div>

    <div class="max-w-3xl">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $activityLog->badgeColor() }}">
                        {{ ucfirst($activityLog->type) }}
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $activityLog->created_at->format('F j, Y \\a\\t g:i A') }}</span>
                </div>
                <form action="{{ route('admin.activity-logs.destroy', $activityLog) }}" method="POST" onsubmit="return confirm('Delete this log entry?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                </form>
            </div>

            <!-- Details -->
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Description</h3>
                    <p class="text-base text-gray-900 dark:text-gray-100">{{ $activityLog->description }}</p>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">User</h3>
                        @if($activityLog->user)
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-indigo-500 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-semibold text-white">{{ substr($activityLog->user->name, 0, 1) }}</span>
                                </div>
                                <div class="ml-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $activityLog->user->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activityLog->user->email }}</p>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">System / Deleted user</p>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Type</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $activityLog->badgeColor() }}">
                            {{ ucfirst($activityLog->type) }}
                        </span>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">IP Address</h3>
                        <p class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $activityLog->ip_address ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">User Agent</h3>
                        <p class="text-sm text-gray-900 dark:text-gray-100 break-words">{{ $activityLog->user_agent ?? 'N/A' }}</p>
                    </div>

                    @if($activityLog->subject_type)
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Subject</h3>
                        <p class="text-sm text-gray-900 dark:text-gray-100">
                            <span class="text-gray-500">{{ class_basename($activityLog->subject_type) }}</span>
                            @if($activityLog->subject_id)
                                <span class="text-gray-400">#{{ $activityLog->subject_id }}</span>
                            @endif
                        </p>
                    </div>
                    @endif
                </div>

                @if($activityLog->properties)
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Additional Data</h3>
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                        <pre class="text-xs text-gray-700 dark:text-gray-300 font-mono whitespace-pre-wrap overflow-x-auto">{{ json_encode($activityLog->properties, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-xl">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Log ID: #{{ $activityLog->id }}</span>
                    <span>Created: {{ $activityLog->created_at->toISOString() }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
