@extends('admin.layouts.admin')

@section('title', 'Users')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">Users</h1>
            <p class="text-sm text-secondary dark:text-gray-400 mt-1">Manage all registered users.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="admin-add-button px-4 py-2 text-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add User
        </a>
    </div>

    <div class="admin-table-card">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Roles</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Territory</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Services</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Access</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-secondary dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-surface-container dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="admin-avatar w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-semibold text-white">{{ substr($user->name, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-on-surface dark:text-gray-100">{{ $user->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary dark:text-gray-400">{{ $user->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="admin-badge inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gov-50 dark:bg-gov-900/40 text-gov-700 dark:text-gov-300">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-xs text-outline">No roles</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-secondary dark:text-gray-400 max-w-[180px]">
                                @if($user->adminScope() === 'all')
                                    <span class="admin-badge inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gov-50 dark:bg-gov-900/40 text-gov-700 dark:text-gov-300">
                                        <span class="material-symbols-outlined text-[12px] me-1">public</span>
                                        Tous les territoires
                                    </span>
                                @elseif($user->territories->isEmpty())
                                    <span class="text-xs text-outline italic">—</span>
                                @else
                                    @php
                                        $allLabels  = $user->territories->map(fn($t) => $t->label());
                                        $firstLabel = $allLabels->first();
                                        $extra      = $allLabels->count() - 1;
                                        $fullTitle  = $allLabels->join(', ');
                                    @endphp
                                    <div class="flex flex-col gap-1 group relative">
                                        {{-- First badge — truncated --}}
                                        <span class="admin-badge inline-flex items-center px-2 py-0.5 text-xs font-medium bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 max-w-[160px] truncate" title="{{ $firstLabel }}">
                                            {{ $firstLabel }}
                                        </span>
                                        {{-- "+N more" pill that reveals a popover on hover --}}
                                        @if($extra > 0)
                                            <span class="relative w-fit">
                                                <span class="cursor-pointer admin-badge inline-flex items-center gap-0.5 px-2 py-0.5 text-xs font-semibold bg-surface-container dark:bg-gray-700 text-secondary dark:text-gray-400 hover:bg-surface-container-high dark:hover:bg-gray-600 transition-colors select-none">
                                                    +{{ $extra }} {{ $extra === 1 ? 'autre' : 'autres' }}…
                                                </span>
                                                {{-- Full list tooltip shown on hover --}}
                                                <span class="pointer-events-none absolute start-0 top-full mt-1 z-30 hidden group-hover:block w-max max-w-[260px] rounded-lg border border-surface-dim dark:border-gray-600 bg-surface-container-lowest dark:bg-gray-800 px-3 py-2 text-xs text-on-surface dark:text-gray-200 shadow-xl leading-relaxed whitespace-normal break-words">
                                                    {{ $fullTitle }}
                                                </span>
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->services as $service)
                                        <span class="admin-badge inline-flex items-center px-2 py-0.5 text-xs font-medium bg-sky-50 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300">{{ $service->name }}</span>
                                    @empty
                                        <span class="text-xs text-outline">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->isAdministrator())
                                    <span class="admin-badge inline-flex items-center px-2.5 py-0.5 text-xs font-medium bg-gov-50 dark:bg-gov-900/45 text-gov-700 dark:text-gov-300">Admin</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-container dark:bg-gray-700 text-on-surface dark:text-gray-300">User</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary dark:text-gray-400">{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.users.edit', $user) }}" class="admin-action-link mr-3">Edit</a>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline"
                                      data-confirm data-confirm-style="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-danger-link">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-secondary dark:text-gray-400">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-surface-dim dark:border-gray-700">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
