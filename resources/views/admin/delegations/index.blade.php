@extends('admin.layouts.admin')
@section('title', __('admin.delegations'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('admin.delegations') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('admin.delegations_desc') }}</p>
    </div>
    <a href="{{ route('admin.delegations.create') }}" class="admin-add-button px-4 py-2 text-sm">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        {{ __('action.add') }}
    </a>
</div>
<form method="GET" class="mb-4 flex gap-3">
    <select name="governorate_id" class="px-4 py-2 text-sm bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl dark:text-gray-100">
        <option value="">{{ __('admin.all_governorates') }}</option>
        @foreach($governorates as $id => $name)
        <option value="{{ $id }}" {{ request('governorate_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('action.search') }}" class="flex-1 px-4 py-2 text-sm bg-white dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
    <button type="submit" class="px-4 py-2 text-sm font-semibold bg-gov-600 text-white rounded-xl hover:bg-gov-700">{{ __('action.search') }}</button>
</form>
<div class="admin-table-card">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('admin.governorate') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('admin.name_ar') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('admin.name_fr') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('admin.name_en') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('admin.actions') }}</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($delegations as $del)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $del->governorate?->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $del->name_ar }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $del->name_fr }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $del->name_en }}</td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('admin.delegations.edit', $del) }}" class="admin-action-link mr-3">{{ __('action.edit') }}</a>
                        <form action="{{ route('admin.delegations.destroy', $del) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                            @csrf @method('DELETE')<button type="submit" class="admin-danger-link">{{ __('action.delete') }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">{{ __('admin.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($delegations->hasPages())<div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $delegations->links() }}</div>@endif
</div>
@endsection
