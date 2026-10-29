@extends('admin.layouts.admin')
@section('title', __('admin.building_types'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">{{ __('admin.building_types') }}</h1>
        <p class="text-sm text-secondary dark:text-gray-400 mt-1">{{ __('admin.building_types_desc') }}</p>
    </div>
    <a href="{{ route('admin.building-types.create') }}" class="admin-add-button px-4 py-2 text-sm">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        {{ __('action.add') }}
    </a>
</div>
<form method="GET" class="mb-4 flex gap-3">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('action.search') }}" class="flex-1 px-4 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
    <button type="submit" class="px-4 py-2 text-sm font-semibold bg-gov-600 text-white rounded-xl hover:bg-gov-700">{{ __('action.search') }}</button>
</form>
<div class="admin-table-card">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.sort_order') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.name_ar') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.name_fr') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.name_en') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.has_activities') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.activities_count') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.actions') }}</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($buildingTypes as $type)
                <tr class="hover:bg-surface-container dark:hover:bg-gray-700/30">
                    <td class="px-6 py-4 text-sm text-secondary">{{ $type->sort_order }}</td>
                    <td class="px-6 py-4 text-sm text-secondary dark:text-gray-300">{{ $type->name_ar }}</td>
                    <td class="px-6 py-4 text-sm text-secondary dark:text-gray-300">{{ $type->name_fr }}</td>
                    <td class="px-6 py-4 text-sm text-secondary dark:text-gray-300">{{ $type->name_en }}</td>
                    <td class="px-6 py-4 text-sm">@if($type->has_activities)<span class="admin-badge bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">{{ __('admin.yes') }}</span>@else<span class="text-outline">{{ __('admin.no') }}</span>@endif</td>
                    <td class="px-6 py-4 text-sm"><span class="admin-badge">{{ $type->activities_count }}</span></td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('admin.building-types.edit', $type) }}" class="admin-action-link mr-3">{{ __('action.edit') }}</a>
                        <form action="{{ route('admin.building-types.destroy', $type) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                            @csrf @method('DELETE')<button type="submit" class="admin-danger-link">{{ __('action.delete') }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-secondary dark:text-gray-400">{{ __('admin.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($buildingTypes->hasPages())<div class="px-6 py-4 border-t border-surface-dim dark:border-gray-700">{{ $buildingTypes->links() }}</div>@endif
</div>
@endsection
