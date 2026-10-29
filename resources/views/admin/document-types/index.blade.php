@extends('admin.layouts.admin')
@section('title', __('admin.document_types'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">{{ __('admin.document_types') }}</h1>
        <p class="text-sm text-secondary dark:text-gray-400 mt-1">{{ __('admin.document_types_desc') }}</p>
    </div>
    <a href="{{ route('admin.document-types.create') }}" class="admin-add-button px-4 py-2 text-sm">
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
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">#</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.document_name') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.services') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.building_types_short') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.form_template_short') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-secondary dark:text-gray-400 uppercase">{{ __('admin.actions') }}</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($documentTypes as $doc)
                <tr class="hover:bg-surface-container dark:hover:bg-gray-700/30">
                    <td class="px-6 py-4 text-sm text-secondary">{{ $doc->sort_order }}</td>
                    <td class="px-6 py-4 text-sm max-w-xs">
                        <p class="font-semibold text-on-surface dark:text-gray-100">{{ $doc->name }}</p>
                        <p class="text-xs text-secondary dark:text-gray-400 truncate">{{ $doc->name_fr }}</p>
                        @if($doc->is_conditional)
                            <span class="admin-badge bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 mt-1">{{ __('admin.conditional') }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                        @forelse ($doc->serviceKeys() as $serviceKey)
                            <span class="admin-badge {{ $serviceKey === \App\Models\DocumentType::SERVICE_PREVENTIVE_PRESENCE ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' : 'bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300' }} mr-1.5 mb-1 inline-block">
                                {{ match ($serviceKey) {
                                    \App\Models\DocumentType::SERVICE_PREVENTION => __('admin.service_labels.prevention'),
                                    \App\Models\DocumentType::SERVICE_PREVENTIVE_PRESENCE => __('admin.service_labels.preventive-presence'),
                                    \App\Models\DocumentType::SERVICE_RESCUE_CERTIFICATE => __('rescue_certificate.index_title'),
                                    \App\Models\DocumentType::SERVICE_WORKS_NOTIFICATION => __('works_notification.index_title'),
                                    default => $serviceKey,
                                } }}
                            </span>
                        @empty
                            <span class="text-outline">—</span>
                        @endforelse
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($doc->buildingTypes->isNotEmpty())
                            <a href="{{ route('admin.document-types.edit', $doc) }}"
                               class="admin-badge bg-surface-container-high dark:bg-gray-700 text-secondary dark:text-gray-300 hover:text-gov-700 dark:hover:text-gov-300"
                               title="{{ $doc->buildingTypes->pluck('name')->implode('، ') }}">
                                <span class="material-symbols-outlined align-middle text-[13px]" aria-hidden="true">apartment</span> {{ $doc->buildingTypes->count() }}
                            </a>
                        @else
                            <span class="text-outline" title="{{ __('admin.building_scope_none') }}">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($doc->template_path)
                            <a href="{{ route('admin.document-types.template.download', $doc) }}"
                               class="admin-badge bg-gov-50 dark:bg-gov-900/40 text-gov-700 dark:text-gov-300 hover:text-gov-800"
                               title="{{ $doc->template_name }}">
                                <span class="material-symbols-outlined align-middle text-[13px]" aria-hidden="true">description</span> {{ $doc->templateExtension() }}
                            </a>
                        @else
                            <span class="text-outline">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('admin.document-types.edit', $doc) }}" class="admin-action-link mr-3">{{ __('action.edit') }}</a>
                        <form action="{{ route('admin.document-types.destroy', $doc) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                            @csrf @method('DELETE')<button type="submit" class="admin-danger-link">{{ __('action.delete') }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-secondary dark:text-gray-400">{{ __('admin.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($documentTypes->hasPages())<div class="px-6 py-4 border-t border-surface-dim dark:border-gray-700">{{ $documentTypes->links() }}</div>@endif
</div>
@endsection
