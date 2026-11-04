@php
/**
 * Targeted correction form — UI aligned with the create wizard (steps 1–3).
 *
 * Variables:
 *   $dossier        – PreventionDossier (loaded: governorate, delegation, buildingType, buildingActivity)
 *   $correction     – array from $dossier->lastCorrectionData()
 *   $fieldsToFix    – string[] field keys that need editing
 *   $docsToFix      – int[]   document_type_id values that need re-upload
 *   $documentTypes  – Collection<DocumentType>  (flagged ones only)
 *   $archivedDocs   – Collection<DossierDocument> (status=archived)
 *   $gouvernorats   – Collection<Governorate> with delegations eager-loaded
 *   $delegations    – flat delegations map (unused here – delegation driven by Alpine)
 *   $buildingTypes  – [id => name] array
 *   $activitiesMap  – {buildingTypeId: [{id, name, is_other}]} JSON-ready
 */

$isField = fn (string $key) => in_array($key, $fieldsToFix);

$fieldLabels = [
    'nom_prenom'           => __('prevention_request.field_name'),
    'cin'                  => __('prevention_request.field_cin'),
    'phone'                => __('prevention_request.field_phone'),
    'governorate_id'       => __('prevention_request.field_governorate'),
    'delegation_id'        => __('prevention_request.field_delegation'),
    'building_address'     => __('prevention_request.field_address'),
    'building_type_id'     => __('prevention_request.field_building'),
    'building_activity_id' => __('prevention_request.field_activity'),
];

$hasFields = ! empty($fieldsToFix);
$hasDocs   = $documentTypes->isNotEmpty();
@endphp

<x-app-layout>
<div class="py-8 sm:py-10">
<div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Breadcrumb --}}
    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-secondary overflow-x-auto whitespace-nowrap">
        <a class="hover:text-primary transition-colors flex items-center gap-1" href="{{ route('dashboard') }}">
            <span class="material-symbols-outlined text-xs" aria-hidden="true">home</span>
            {{ __('prevention_request.dashboard_title') }}
        </a>
        <span class="material-symbols-outlined text-xs text-surface-dim rtl:rotate-180" aria-hidden="true">chevron_right</span>
        <a class="hover:text-primary transition-colors" href="{{ route('prevention-requests.index') }}">{{ __('prevention_request.list_requests') }}</a>
        <span class="material-symbols-outlined text-xs text-surface-dim rtl:rotate-180" aria-hidden="true">chevron_right</span>
        <a class="hover:text-primary transition-colors" href="{{ route('prevention-requests.show', $dossier) }}">
            {{ $dossier->reference ?? __('prevention_request.reference_provisional') }}
        </a>
        <span class="material-symbols-outlined text-xs text-surface-dim rtl:rotate-180" aria-hidden="true">chevron_right</span>
        <span class="text-on-surface font-bold">{{ __('prevention_request.correction_page_title') }}</span>
    </nav>

    {{-- Full-width correction status banner (replaces stepper) --}}
    <div class="bg-surface-container-lowest rounded border border-surface-dim border-l-4 border-l-primary shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 dark:bg-primary/20">
                    <span class="material-symbols-outlined text-2xl" aria-hidden="true">edit_note</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-primary">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">sync</span>
                            {{ __('prevention_request.step_editing') }}
                        </span>
                        <span class="text-outline dark:text-gray-600 text-xs">·</span>
                        <span class="text-[11px] text-secondary dark:text-gray-400">
                            {{ __('prevention_request.correction_page_subtitle', ['round' => $correction['round'] ?? 1]) }}
                        </span>
                    </div>
                    <h1 class="text-headline-sm font-bold text-on-surface dark:text-gray-100 mt-0.5">
                        {{ __('prevention_request.correction_page_title') }}
                    </h1>

                    {{-- Admin correction note — shown right under the title so it's the first thing read --}}
                    @if (!empty($correction['note']))
                    <div class="mt-3 rounded border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-3.5 py-2.5">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                            {{ __('prevention_request.admin_note_label') }}
                        </p>
                        <p class="text-sm text-red-900 dark:text-red-200 whitespace-pre-line leading-relaxed">{{ $correction['note'] }}</p>
                    </div>
                    @endif

                    {{-- Summary chips --}}
                    @if ($hasFields || $hasDocs)
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($fieldsToFix as $fk)
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full
                                         bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700/40">
                                <span class="material-symbols-outlined text-[11px]" aria-hidden="true">edit</span>
                                {{ $fieldLabels[$fk] ?? $fk }}
                            </span>
                        @endforeach
                        @foreach ($documentTypes as $dt)
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full
                                         bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-700/40">
                                <span class="material-symbols-outlined text-[11px]" aria-hidden="true">upload_file</span>
                                {{ $dt->name }}
                            </span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            {{-- Doc progress counter --}}
            @if ($hasDocs)
            <span id="docs-progress"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gov-50 text-gov-800 text-body-sm font-semibold
                         border border-gov-300 shrink-0 dark:bg-gov-900/30 dark:text-gov-300 dark:border-gov-800">
                <span class="material-symbols-outlined filled text-[16px]" aria-hidden="true">task_alt</span>
                <span id="docs-progress-count">0/{{ $documentTypes->count() }}</span>
            </span>
            @endif
        </div>
    </div>

    {{-- ── Validation error summary ── --}}
    @if ($errors->any())
    <div class="rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800
                dark:bg-red-900/30 dark:text-red-200" role="alert">
        <p class="font-bold">{{ __('prevention_request.create_error_title') }}</p>
        <ul class="mt-1.5 list-disc space-y-1 ps-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         MAIN FORM
         ══════════════════════════════════════════════════════════════════════ --}}
    <form method="POST"
          action="{{ route('prevention-requests.correct.submit', $dossier) }}"
          enctype="multipart/form-data"
          id="correction-form"
          class="space-y-6">
        @csrf

        {{-- ── SECTION A: Read-only snapshot — collapsible ── --}}
        <div class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800"
             x-data="{ openA: false }">

            {{-- Clickable header --}}
            <button type="button" @click="openA = !openA"
                    class="w-full flex items-center gap-2.5 px-6 py-4 text-start
                           hover:bg-surface-container dark:hover:bg-gray-700/40 transition-colors rounded">
                <span class="w-7 h-7 rounded bg-surface-container dark:bg-gray-700 text-secondary flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">A</span>
                <h2 class="text-title-section text-on-surface uppercase tracking-wider flex-1">
                    {{ __('prevention_request.correction_current_values') }}
                </h2>
                <span class="text-[10px] text-outline dark:text-gray-500 hidden sm:block">— {{ __('prevention_request.correction_readonly_note') }}</span>
                <svg class="w-4 h-4 text-secondary dark:text-gray-400 shrink-0 transition-transform"
                     :class="openA ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Collapsible body --}}
            <div x-show="openA" x-cloak
                 class="px-6 pb-5 border-t border-surface-dim dark:border-gray-700">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-0 divide-y sm:divide-y-0 divide-surface-dim dark:divide-gray-700/60 pt-4">
                    @foreach ([
                        ['nom_prenom',           $dossier->nom_prenom,                                                  $fieldLabels['nom_prenom']],
                        ['cin',                  $dossier->cin,                                                          $fieldLabels['cin']],
                        ['phone',                $dossier->phone,                                                        $fieldLabels['phone']],
                        ['governorate_id',       $dossier->governorate?->name_fr ?? $dossier->governorate?->name_ar,    $fieldLabels['governorate_id']],
                        ['delegation_id',        $dossier->delegation?->name_fr ?? $dossier->delegation?->name_ar,      $fieldLabels['delegation_id']],
                        ['building_address',     $dossier->building_address,                                             $fieldLabels['building_address']],
                        ['building_type_id',     $dossier->buildingType?->name,                                          $fieldLabels['building_type_id']],
                        ['building_activity_id', $dossier->buildingActivity?->name ?? $dossier->activity_other,         $fieldLabels['building_activity_id']],
                    ] as [$key, $val, $label])
                        @if (filled($val) && ! $isField($key))
                        <div class="py-2.5">
                            <dt class="text-[10px] uppercase tracking-wide font-bold text-secondary dark:text-gray-500">{{ $label }}</dt>
                            <dd class="text-xs font-semibold text-on-surface dark:text-gray-200 mt-0.5">{{ $val }}</dd>
                        </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        </div>

        {{-- ── SECTION B: Editable fields ── --}}
        @if ($hasFields)
        <div class="bg-surface-container-lowest rounded border border-amber-200 dark:border-amber-700/50 shadow-sm dark:bg-gray-800">
            <div class="p-6 space-y-5">
                <div class="flex items-center gap-2.5 pb-3 border-b border-amber-100 dark:border-amber-800/30">
                    <span class="w-7 h-7 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">edit</span>
                    </span>
                    <h2 class="text-title-section text-on-surface uppercase tracking-wider">
                        {{ __('prevention_request.correction_fields_section') }}
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    @if ($isField('nom_prenom'))
                    <div class="space-y-1.5">
                        <label for="nom_prenom" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['nom_prenom'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <input id="nom_prenom" type="text" name="nom_prenom"
                               value="{{ old('nom_prenom', $dossier->nom_prenom) }}"
                               required maxlength="255" autocomplete="name"
                               class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gov-400 dark:focus:ring-gov-400/50 @error('nom_prenom') border-red-400 @enderror">
                        @error('nom_prenom')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    @if ($isField('cin'))
                    <div class="space-y-1.5">
                        <label for="cin" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['cin'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <input id="cin" type="text" name="cin"
                               value="{{ old('cin', $dossier->cin) }}"
                               required maxlength="20" inputmode="numeric"
                               class="block w-full h-11 px-3.5 text-body-md font-mono text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gov-400 dark:focus:ring-gov-400/50 @error('cin') border-red-400 @enderror">
                        @error('cin')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    @if ($isField('phone'))
                    <div class="space-y-1.5">
                        <label for="phone" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['phone'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <input id="phone" type="tel" name="phone"
                               value="{{ old('phone', $dossier->phone) }}"
                               required maxlength="20" inputmode="tel" dir="ltr"
                               class="num-ltr block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gov-400 dark:focus:ring-gov-400/50 @error('phone') border-red-400 @enderror">
                        @error('phone')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    @if ($isField('building_address'))
                    <div class="space-y-1.5 {{ ($isField('nom_prenom') || $isField('cin') || $isField('phone')) ? 'md:col-span-2' : '' }}">
                        <label for="building_address" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['building_address'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <input id="building_address" type="text" name="building_address"
                               value="{{ old('building_address', $dossier->building_address) }}"
                               required maxlength="500"
                               class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gov-400 dark:focus:ring-gov-400/50 @error('building_address') border-red-400 @enderror">
                        @error('building_address')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif

                </div>{{-- /grid --}}

                {{-- Governorate / Delegation cascading pair --}}
                @if ($isField('governorate_id') || $isField('delegation_id'))
                <div
                    x-data="{
                        gov: '{{ old('code_structure', $dossier->governorate_id) }}',
                        del: '{{ old('delegation_id', $dossier->delegation_id) }}',
                        delegationsMap: {{ Illuminate\Support\Js::from(
                            $gouvernorats->mapWithKeys(fn ($g) => [
                                $g->id => $g->delegations->map(fn ($d) => [
                                    'id'   => $d->id,
                                    'name' => $d->name_fr ?? $d->name_ar,
                                ])->values()->all(),
                            ])->toArray()
                        ) }}
                    }"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                    @if ($isField('governorate_id'))
                    <div class="space-y-1.5">
                        <label for="code_structure" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['governorate_id'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <select id="code_structure" name="code_structure" x-model="gov" required
                                class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('code_structure') border-red-400 @enderror">
                            <option value="">{{ __('prevention_request.code_structure_select') }}</option>
                            @foreach ($gouvernorats as $gov_item)
                                <option value="{{ $gov_item->id }}"
                                        {{ old('code_structure', $dossier->governorate_id) == $gov_item->id ? 'selected' : '' }}>
                                    {{ $gov_item->name_fr ?? $gov_item->name_ar }}
                                </option>
                            @endforeach
                        </select>
                        @error('code_structure')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    @if ($isField('delegation_id'))
                    <div class="space-y-1.5">
                        <label for="delegation_id" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['delegation_id'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <select id="delegation_id" name="delegation_id" x-model="del" required
                                class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('delegation_id') border-red-400 @enderror">
                            <option value="">{{ __('prevention_request.delegation_select') }}</option>
                            <template x-for="d in (delegationsMap[gov] || [])" :key="d.id">
                                <option :value="d.id" :selected="d.id == del" x-text="d.name"></option>
                            </template>
                        </select>
                        @error('delegation_id')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif
                </div>
                @endif

                {{-- Building type / Activity cascading pair --}}
                @if ($isField('building_type_id') || $isField('building_activity_id'))
                <div
                    x-data="{
                        buildingType: '{{ old('building_type_id', $dossier->building_type_id) }}',
                        activityId:   '{{ old('building_activity_id', $dossier->building_activity_id) }}',
                        activitiesMap: {{ Illuminate\Support\Js::from($activitiesMap) }}
                    }"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                    @if ($isField('building_type_id'))
                    <div class="space-y-1.5">
                        <label for="building_type_id" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['building_type_id'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <select id="building_type_id" name="building_type_id" x-model="buildingType" required
                                class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('building_type_id') border-red-400 @enderror">
                            <option value="">—</option>
                            @foreach ($buildingTypes as $btId => $btName)
                                <option value="{{ $btId }}" {{ old('building_type_id', $dossier->building_type_id) == $btId ? 'selected' : '' }}>{{ $btName }}</option>
                            @endforeach
                        </select>
                        @error('building_type_id')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    @if ($isField('building_activity_id'))
                    <div class="space-y-1.5">
                        <label for="building_activity_id" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['building_activity_id'] }}
                        </label>
                        <select id="building_activity_id" name="building_activity_id" x-model="activityId"
                                class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('building_activity_id') border-red-400 @enderror">
                            <option value="">—</option>
                            <template x-for="act in (activitiesMap[buildingType] || [])" :key="act.id">
                                <option :value="act.id" :selected="act.id == activityId" x-text="act.name"></option>
                            </template>
                        </select>
                        @error('building_activity_id')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror

                        <input type="text" name="activity_other"
                               value="{{ old('activity_other', $dossier->activity_other) }}"
                               placeholder="{{ __('prevention_request.activity_other_placeholder') }}"
                               maxlength="255"
                               class="mt-2 block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                               x-show="activitiesMap[buildingType]?.find(a => a.id == activityId)?.is_other"
                               x-cloak>
                    </div>
                    @endif
                </div>
                @endif

            </div>
        </div>
        @endif

        {{-- ── SECTION C: Documents to re-upload ── --}}
        @if ($hasDocs)
        <div class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="p-6 space-y-5">

                {{-- Section header --}}
                <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                    <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">C</span>
                    <h2 class="text-title-section text-on-surface uppercase tracking-wider">
                        {{ __('prevention_request.correction_docs_section') }}
                    </h2>
                </div>

                {{-- Info bar --}}
                <div class="flex items-start gap-3 rounded border border-surface-dim bg-surface-container-low p-4 text-body-sm dark:border-gray-700 dark:bg-gray-900/40" role="note">
                    <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">info</span>
                    <p class="text-secondary dark:text-gray-300">{{ __('prevention_request.documents_info') }}</p>
                </div>

                {{-- Document cards — same structure as create-step3 --}}
                <ol id="doc-list" class="space-y-3">
                    @foreach ($documentTypes as $docType)
                    @php
                        $oldDoc = $archivedDocs->filter(fn ($d) => $d->document_type_id === $docType->id)->last();
                    @endphp
                    <li class="doc-card rounded border p-4 transition border-surface-dim dark:border-gray-700"
                        data-doc-id="{{ $docType->id }}"
                        data-has-server="0">

                        <div class="flex items-start gap-3">

                            {{-- Status icon --}}
                            <span class="status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full
                                         bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </span>

                            <div class="min-w-0 flex-1">

                                {{-- Doc name + badges --}}
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-body-sm font-bold text-on-surface dark:text-gray-100">
                                        {{ $loop->iteration }}. {{ $docType->name }}
                                    </p>
                                    @if ($docType->is_conditional)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold
                                                     text-accent-600 bg-accent-50 rounded border border-accent-500/20
                                                     dark:bg-accent-900/30 dark:text-accent-500">
                                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">help</span>
                                            {{ __('prevention_request.conditional') }}
                                        </span>
                                    @endif
                                    @if ($docType->template_path)
                                        <a href="{{ route('prevention-requests.document-template.download', $docType) }}"
                                           class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold
                                                  text-primary bg-primary/10 rounded border border-primary/25 transition
                                                  hover:bg-primary/15 cursor-pointer dark:bg-primary/15 dark:border-primary/40"
                                           title="{{ __('prevention_request.download_form_hint') }}">
                                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">download</span>
                                            {{ __('prevention_request.download_form') }}
                                            <span class="text-primary/60">({{ $docType->templateExtension() }})</span>
                                        </a>
                                    @endif
                                </div>

                                {{-- Archived (old) version of this document --}}
                                @if ($oldDoc)
                                <div class="mt-2 flex items-center gap-2 rounded bg-surface-container dark:bg-gray-700/40
                                            border border-surface-dim dark:border-gray-600 px-3 py-2 text-[11px]">
                                    <span class="material-symbols-outlined text-outline dark:text-gray-500 text-sm" aria-hidden="true">inventory_2</span>
                                    <span class="text-secondary dark:text-gray-400 flex-1 truncate">
                                        {{ __('prevention_request.correction_old_file') }}:
                                        <span class="font-semibold">{{ $oldDoc->original_name }}</span>
                                    </span>
                                    <span class="text-outline dark:text-gray-500 font-mono">
                                        @php
                                            $b = $oldDoc->size;
                                            echo $b >= 1048576 ? round($b/1048576,1).' Mo' : ($b >= 1024 ? round($b/1024,1).' Ko' : $b.' o');
                                        @endphp
                                    </span>
                                    <a href="{{ route('prevention-requests.documents.download', [$dossier, $oldDoc]) }}"
                                       class="w-6 h-6 rounded bg-surface-container-low dark:bg-gray-700 text-secondary
                                              hover:bg-primary hover:text-on-primary flex items-center justify-center flex-shrink-0 transition-colors"
                                       title="{{ __('prevention_request.download') }}">
                                        <span class="material-symbols-outlined text-xs" aria-hidden="true">download</span>
                                    </a>
                                </div>
                                @endif

                                {{-- Preview list (filled by JS) --}}
                                <div class="preview-list mt-3 hidden space-y-1.5"></div>

                                {{-- Upload button --}}
                                <div class="mt-3 upload-area">
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded border border-surface-dim
                                                  bg-surface-container-lowest px-3 py-2 text-xs font-semibold text-on-surface
                                                  transition hover:bg-surface-container
                                                  dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        <span class="material-symbols-outlined text-[15px] text-primary" aria-hidden="true">upload_file</span>
                                        <span class="upload-label">{{ __('prevention_request.upload_file') }}</span>
                                        <input type="file"
                                               name="documents[{{ $docType->id }}][]"
                                               class="sr-only file-input"
                                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                               multiple>
                                    </label>
                                </div>

                            </div>
                        </div>
                    </li>
                    @endforeach
                </ol>

            </div>
        </div>
        @endif

        {{-- ── Footer bar: back + submit ── --}}
        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3
                    border-t border-surface-dim pt-4 dark:border-gray-700">
            <a href="{{ route('prevention-requests.show', $dossier) }}"
               class="inline-flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest
                      px-4 py-2.5 text-body-sm font-semibold text-on-surface transition
                      hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('prevention_request.back') }}
            </a>

            <button type="submit"
                    id="correction-submit-btn"
                    class="ui-btn-primary inline-flex items-center justify-center gap-2 px-6 py-2.5 text-body-sm
                           focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer
                           disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                    data-loading-text="{{ __('prevention_request.submitting') }}">
                <span class="material-symbols-outlined filled text-[18px]" aria-hidden="true">send</span>
                <span id="correction-submit-label">{{ __('prevention_request.correction_submit_btn') }}</span>
            </button>
        </div>

    </form>

    {{-- ── Old version card — below the form so it's accessible but never in the way ── --}}
    @include('prevention-requests.partials.old-version-card', [
        'correction'          => $correction,
        'archivedDocs'        => $archivedDocs,
        'allCorrectionRounds' => [],
        'documentTypes'       => $documentTypes,
    ])

</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     Upload JS — mirrors create-step3 exactly (cumulative staging, preview,
     card state toggle, progress counter, remove-by-index delegation)
     ══════════════════════════════════════════════════════════════════════════ --}}
@if ($hasDocs)
<script>
(function () {
    var form          = document.getElementById('correction-form');
    var btn           = document.getElementById('correction-submit-btn');
    var lbl           = document.getElementById('correction-submit-label');
    var fileInputs    = document.querySelectorAll('.file-input');
    var progressCount = document.getElementById('docs-progress-count');
    var totalDocs     = document.querySelectorAll('.doc-card').length;

    var LABEL_UPLOAD = '{{ __('prevention_request.upload_file') }}';
    var LABEL_ADD    = '{{ __('prevention_request.add_file') }}';
    var LABEL_REMOVE = '{{ __('prevention_request.remove_file') }}';

    /* ── Live counter ── */
    function updateProgress() {
        if (!progressCount) return;
        var done = 0;
        document.querySelectorAll('.doc-card').forEach(function (card) {
            var input   = card.querySelector('.file-input');
            var staged  = input && input.files ? input.files.length : 0;
            var hasSrv  = card.dataset.hasServer === '1';
            if (staged > 0 || hasSrv) done++;
        });
        progressCount.textContent = done + '/' + totalDocs;
    }

    /* ── Attach change listeners ── */
    fileInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            var files = Array.prototype.slice.call(this.files);
            var card  = this.closest('.doc-card');
            if (!card || !files.length) return;

            stagedFiles(input).push.apply(stagedFiles(input), files);
            syncInputFiles(input);
            renderPreviewList(card);
            refreshCardState(card);
        });
    });

    /* ── Cumulative staging helpers ── */
    function stagedFiles(input) {
        if (!input._staged) input._staged = [];
        return input._staged;
    }

    function syncInputFiles(input) {
        var dt = new DataTransfer();
        stagedFiles(input).forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
    }

    /* ── Card visual state ── */
    function refreshCardState(card) {
        var input       = card.querySelector('.file-input');
        var hasServer   = card.dataset.hasServer === '1';
        var stagedCount = stagedFiles(input).length;
        var statusIcon  = card.querySelector('.status-icon');
        var uploadLabel = card.querySelector('.upload-label');
        var previewList = card.querySelector('.preview-list');

        if (previewList) previewList.classList.toggle('hidden', stagedCount === 0);

        if (stagedCount === 0 && !hasServer) {
            card.classList.add('border-surface-dim');
            card.classList.remove('border-gov-300', 'bg-gov-50/30', 'dark:border-gov-800/40', 'dark:bg-gov-900/10');
            statusIcon.className = 'status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500';
            statusIcon.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>';
        } else {
            card.classList.remove('border-surface-dim');
            card.classList.add('border-gov-300', 'bg-gov-50/30', 'dark:border-gov-800/40', 'dark:bg-gov-900/10');
            statusIcon.className = 'status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-gov-100 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300';
            statusIcon.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
        }

        if (uploadLabel) {
            uploadLabel.textContent = (stagedCount > 0 || hasServer) ? LABEL_ADD : LABEL_UPLOAD;
        }

        updateProgress();
    }

    /* ── Per-file preview rows ── */
    function renderPreviewList(card) {
        var input     = card.querySelector('.file-input');
        var list      = stagedFiles(input);
        var container = card.querySelector('.preview-list');
        if (!container) return;

        container.innerHTML = '';

        list.forEach(function (file, idx) {
            var row = document.createElement('div');
            row.className = 'flex items-center gap-3 rounded-lg border border-surface-dim bg-surface-container-lowest p-2.5 dark:border-gray-700 dark:bg-gray-800/60';

            row.innerHTML =
                '<div class="staged-thumb flex h-10 w-10 flex-none items-center justify-center overflow-hidden rounded-lg bg-gov-50 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300">' +
                    '<img class="hidden h-full w-full object-cover" alt="">' +
                    '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>' +
                    '</svg>' +
                '</div>' +
                '<div class="min-w-0 flex-1">' +
                    '<p class="truncate text-xs font-semibold text-on-surface dark:text-gray-100"></p>' +
                    '<p class="text-[11px] text-outline"></p>' +
                '</div>' +
                '<span class="flex h-5 w-5 flex-none items-center justify-center rounded-full bg-gov-100 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300">' +
                    '<svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>' +
                '</span>';

            // Filename + size via textContent (XSS-safe)
            row.querySelectorAll('p')[0].textContent = file.name;
            row.querySelectorAll('p')[1].textContent = formatSize(file.size);

            // Thumbnail for images
            var badge = row.querySelector('.staged-thumb');
            var img   = badge.querySelector('img');
            if (file.type && file.type.indexOf('image/') === 0) {
                img.classList.remove('hidden');
                var reader = new FileReader();
                reader.onload = function (e) { img.src = e.target.result; };
                reader.readAsDataURL(file);
            }

            // Remove button
            var rm = document.createElement('button');
            rm.type      = 'button';
            rm.className = 'remove-file flex h-8 w-8 flex-none items-center justify-center rounded-lg text-outline transition hover:bg-red-50 hover:text-red-600 cursor-pointer dark:hover:bg-red-900/20';
            rm.title     = LABEL_REMOVE;
            rm.dataset.idx = String(idx);
            rm.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
            row.appendChild(rm);

            container.appendChild(row);
        });
    }

    /* ── Remove button (event-delegated so it survives re-renders) ── */
    var docList = document.getElementById('doc-list');
    if (docList) {
        docList.addEventListener('click', function (e) {
            var btnRemove = e.target.closest('.remove-file');
            if (!btnRemove || btnRemove.dataset.idx === undefined) return;

            var card  = btnRemove.closest('.doc-card');
            if (!card) return;

            var input = card.querySelector('.file-input');
            stagedFiles(input).splice(Number(btnRemove.dataset.idx), 1);
            syncInputFiles(input);
            renderPreviewList(card);
            refreshCardState(card);
        });
    }

    function formatSize(bytes) {
        if (bytes < 1024)    return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    /* ── Submit lock ── */
    if (form) {
        form.addEventListener('submit', function () {
            btn.disabled  = true;
            lbl.textContent = btn.dataset.loadingText;
        });
    }
}());
</script>
@endif

</x-app-layout>
