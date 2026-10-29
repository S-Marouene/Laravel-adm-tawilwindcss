{{--_partial: services multi + types de bâtiment concernés + formulaire téléchargeable
    Expects: $serviceOptions, $buildingTypes, optional $documentType--}}
@php
    $docType = $documentType ?? null;
    $selectedServiceKeys = old('service_keys', $docType ? $docType->serviceKeys() : [\App\Models\DocumentType::SERVICE_PREVENTION]);
    $selectedBuildingTypeIds = old('building_types', $docType ? $docType->buildingTypes->pluck('id')->all() : []);
@endphp

{{-- 1. Services concernés (choix multiple) --}}
<div>
    <label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">
        {{ __('admin.services') }}
    </label>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-xl border border-surface-dim dark:border-gray-600 bg-surface-container-low px-4 py-3 dark:bg-gray-900/50">
        @foreach ($serviceOptions as $key => $label)
            <label class="flex items-center gap-2 text-sm text-on-surface dark:text-gray-200 cursor-pointer">
                <input type="checkbox" name="service_keys[]" value="{{ $key }}"
                       @checked(in_array($key, $selectedServiceKeys))
                       class="js-service-check rounded border-surface-dim dark:border-gray-600 text-gov-600 focus:ring-gov-500/30 dark:bg-gray-700">
                {{ $label }}
            </label>
        @endforeach
    </div>
</div>

{{-- 2. Types de bâtiment concernés (uniquement quand « Prevention » est coché) --}}
<div id="building-types-block">
    <label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">
        {{ __('admin.building_types') }}
    </label>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-xl border border-surface-dim dark:border-gray-600 bg-surface-container-low px-4 py-3 dark:bg-gray-900/50">
        @forelse ($buildingTypes as $typeId => $typeName)
            <label class="flex items-center gap-2 text-sm text-on-surface dark:text-gray-200 cursor-pointer">
                <input
                    type="checkbox"
                    name="building_types[]"
                    value="{{ $typeId }}"
                    @checked(in_array($typeId, $selectedBuildingTypeIds))
                    class="rounded border-surface-dim dark:border-gray-600 text-gov-600 focus:ring-gov-500/30 dark:bg-gray-700"
                >
                {{ $typeName }}
            </label>
        @empty
            <span class="text-sm text-secondary dark:text-gray-400">{{ __('admin.no_data') }}</span>
        @endforelse
    </div>
    <p class="mt-1.5 text-xs text-secondary dark:text-gray-400">{{ __('admin.building_types_all_hint') }}</p>
</div>

{{-- 3. Formulaire officiel téléchargeable --}}
<div>
    <label class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">
        {{ __('admin.form_template') }}
    </label>

    {{-- Modèle déjà enregistré --}}
    @if ($docType && $docType->template_path)
        <div class="flex items-center justify-between gap-3 rounded-xl border border-surface-dim bg-surface-container-low px-3.5 py-2.5 mb-2 dark:border-gray-600 dark:bg-gray-900/40">
            <div class="min-w-0 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[20px]" aria-hidden="true">description</span>
                <span class="text-sm text-on-surface dark:text-gray-200 truncate">{{ $docType->template_name ?: basename($docType->template_path) }}</span>
                <span class="admin-badge bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 shrink-0">{{ $docType->templateExtension() }}</span>
            </div>
            <a href="{{ route('admin.document-types.template.download', $docType) }}"
               class="admin-action-link shrink-0">{{ __('action.download') }}</a>
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-secondary dark:text-gray-400 mb-2 cursor-pointer">
            <input type="checkbox" name="remove_template" value="1" class="rounded border-surface-dim dark:border-gray-600 text-gov-600 focus:ring-gov-500/30 dark:bg-gray-700">
            {{ __('admin.form_template_remove') }}
        </label>
    @endif

    <input
        type="file"
        name="template_file"
        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
        class="block w-full text-sm text-secondary dark:text-gray-300 border border-surface-dim dark:border-gray-600 rounded-xl bg-surface-container-lowest dark:bg-gray-900/50 px-3.5 py-2.5 file:mr-3 file:rounded-lg file:border-0 file:bg-gov-600 file:px-3 file:py-1.5 file:text-white file:text-xs file:font-semibold hover:file:bg-gov-700"
    >
    <p class="mt-1.5 text-xs text-secondary dark:text-gray-400">{{ __('admin.form_template_hint') }}</p>
</div>
