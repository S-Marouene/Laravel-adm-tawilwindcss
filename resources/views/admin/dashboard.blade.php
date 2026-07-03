@extends('admin.layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Overview of your application.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="ynex-stat-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-lg flex items-center justify-center" style="background: rgba(132, 90, 223, 0.12);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #845ADF;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Users</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $total_users }}</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="mt-3 inline-flex items-center text-xs font-medium" style="color: #845ADF;">
                Manage Users
                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="ynex-stat-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-lg flex items-center justify-center" style="background: rgba(22, 163, 74, 0.12);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #16A34A;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Roles</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $total_roles }}</p>
            </div>
            <a href="{{ route('admin.roles.index') }}" class="mt-3 inline-flex items-center text-xs font-medium" style="color: #16A34A;">
                Manage Roles
                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="ynex-stat-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-lg flex items-center justify-center" style="background: rgba(217, 119, 6, 0.12);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #D97706;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Permissions</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $total_permissions }}</p>
            </div>
            <a href="{{ route('admin.permissions.index') }}" class="mt-3 inline-flex items-center text-xs font-medium" style="color: #D97706;">
                Manage Permissions
                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="ynex-stat-card">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-lg flex items-center justify-center" style="background: rgba(59, 130, 246, 0.12);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #3B82F6;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Activity Today</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $activity_counts['today'] }}</p>
            </div>
            <div class="mt-3 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                <span>Week: <strong class="text-gray-700 dark:text-gray-300">{{ $activity_counts['week'] }}</strong></span>
                <span>Month: <strong class="text-gray-700 dark:text-gray-300">{{ $activity_counts['month'] }}</strong></span>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Users -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8">
        <!-- Recent Activity -->
        <div class="ynex-card overflow-hidden">
            <div class="ynex-card-header">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #16A34A;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Recent Activity
                    </h2>
                    <a href="{{ route('admin.activity-logs.index') }}" class="text-xs font-medium" style="color: #845ADF;">View All</a>
                </div>
            </div>
            <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recent_activities as $log)
                    <div class="px-5 py-3.5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            @if($log->user)
                                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, #845ADF, #6B3FAF);">
                                    <span class="text-xs font-semibold text-white">{{ substr($log->user->name, 0, 1) }}</span>
                                </div>
                            @else
                                <div class="w-8 h-8 bg-gray-100 dark:bg-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $log->description }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium flex-shrink-0 ml-2 {{ $log->badgeColor() }}">
                            {{ ucfirst($log->type) }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                        <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm">No recent activity.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Users -->
        <div class="ynex-card overflow-hidden">
            <div class="ynex-card-header">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #845ADF;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                        </svg>
                        Recent Users
                    </h2>
                    <a href="{{ route('admin.users.index') }}" class="text-xs font-medium" style="color: #845ADF;">View All</a>
                </div>
            </div>
            <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recent_users as $user)
                    <div class="px-5 py-3.5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, #845ADF, #6B3FAF);">
                                <span class="text-xs font-semibold text-white">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($user->is_admin)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background: rgba(132, 90, 223, 0.1); color: #845ADF;">Admin</span>
                            @endif
                            @foreach($user->roles as $role)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background: rgba(132, 90, 223, 0.08); color: #6B3FAF;">{{ $role->name }}</span>
                            @endforeach
                            <span class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                        <p class="text-sm">No users found.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
