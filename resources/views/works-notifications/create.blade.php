@php
    $isEditing   = isset($BuildingWorksDeclaration);
    $formAction  = $isEditing
        ? route('building-works-declarations.step1.update', $BuildingWorksDeclaration)
        : route('building-works-declarations.step1.store');

    $valOwnerType = old('owner_type', $isEditing ? $BuildingWorksDeclaration->owner_type : 'natural');
    $valOwnerName = old('owner_name', $isEditing ? $BuildingWorksDeclaration->owner_name : '');
    $valOwnerCin  = old('owner_cin', $isEditing ? $BuildingWorksDeclaration->owner_cin : '');
    $valCompany   = old('company_name', $isEditing ? $BuildingWorksDeclaration->company_name : '');
    $valJort      = old('jort_reference', $isEditing ? $BuildingWorksDeclaration->jort_reference : '');
    $valPhone     = old('phone', $isEditing ? $BuildingWorksDeclaration->phone : '');
    $valGov       = old('governorate_id', $isEditing ? $BuildingWorksDeclaration->governorate_id : '');
    $valDelId     = old('delegation_id', $isEditing ? $BuildingWorksDeclaration->delegation_id : '');
    $valPropAddr  = old('property_address', $isEditing ? $BuildingWorksDeclaration->property_address : '');
    $valWorksType = old('works_type', $isEditing ? $BuildingWorksDeclaration->works_type : '');
    $valWorksDate = old('works_start_date', $isEditing && $BuildingWorksDeclaration->works_start_date ? $BuildingWorksDeclaration->works_start_date->format('Y-m-d') : '');
    $valWorksDesc = old('works_description', $isEditing ? $BuildingWorksDeclaration->works_description : '');
    $valNotes     = old('notes', $isEditing ? $BuildingWorksDeclaration->notes : '');

    $isMoral = ($valOwnerType === 'moral');

    // ── Stepper data ──
    $wizardSteps = [
        [
            'state'   => 'active',
            'title'   => __('works_notification.step_one_title'),
            'editing' => $isEditing,
        ],
        [
            'state'   => $isEditing ? 'done' : 'upcoming',
            'title'   => __('works_notification.step_two_title'),
            'data'    => [],
            'editUrl' => $isEditing ? route('building-works-declarations.step2.show', $BuildingWorksDeclaration) : null,
            'editLabel' => __('works_notification.step_two_title'),
        ],
    ];

    $showForm = $isEditing || $errors->any() || !empty(old());
@endphp

<x-app-layout>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Service details tabs (hidden when editing an existing draft) --}}
            @unless ($isEditing)
                <x-onpc.works-service-details-tabs :drafts="$drafts ?? collect()" />
            @endunless

            {{-- Form section (revealed by tab button or shown directly when editing) --}}
            <section id="works-form-section" class="space-y-6 @unless($showForm) hidden @endunless">

                {{-- Horizontal stepper --}}
                <x-onpc.dossier-stepper :steps="$wizardSteps" />

                {{-- Step status banner --}}
                <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div>
                        <span class="text-label-caps text-primary uppercase tracking-wider">
                            {{ __('works_notification.step_of', ['step' => 1, 'total' => 2]) }}
                            @if ($isEditing) · {{ __('works_notification.step_editing') }} @endif
                        </span>
                        <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('works_notification.step_heading') }} — {{ __('works_notification.step_one_title') }}</h2>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-surface-container text-body-sm text-outline font-medium border border-surface-dim shrink-0 dark:text-gray-400">
                        <span class="text-red-500 font-bold" aria-hidden="true">*</span>
                        {{ __('works_notification.required_note') }}
                    </span>
                </div>

                <form method="POST" action="{{ $formAction }}" id="works-create-form"
                      class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800"
                      x-data="{ ownerType: @js($valOwnerType) }">
                    @csrf
                    @if ($isEditing) @method('PUT') @endif

                    {{-- Validation errors --}}
                    <div class="p-6 pb-0">
                        @if ($errors->any())
                            <div class="rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                                <p class="font-bold">{{ __('works_notification.create_error_title') }}</p>
                                <ul class="mt-1.5 list-disc space-y-1 ps-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    {{-- =====================================================
                         SECTION A — IDENTITÉ DU PROPRIÉTAIRE
                         ===================================================== --}}
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">A</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">{{ __('works_notification.section_owner') }}</h3>
                        </div>

                        {{-- Owner type toggle --}}
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition hover:border-primary/40 dark:border-gray-700 dark:bg-gray-900/50 has-[:checked]:border-primary/60 has-[:checked]:bg-primary/5">
                                <input type="radio" name="owner_type" value="natural"
                                    @checked($valOwnerType === 'natural')
                                    @click="ownerType = 'natural'"
                                    class="h-4 w-4 rounded-full border-surface-dim text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-800">
                                <span>
                                    <span class="block text-body-sm font-semibold text-on-surface dark:text-gray-100">{{ __('works_notification.owner_natural') }}</span>
                                    <span class="block text-xs text-secondary dark:text-gray-400 mt-0.5">{{ __('works_notification.owner_natural_hint') }}</span>
                                </span>
                            </label>
                            <label class="flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition hover:border-primary/40 dark:border-gray-700 dark:bg-gray-900/50 has-[:checked]:border-primary/60 has-[:checked]:bg-primary/5">
                                <input type="radio" name="owner_type" value="moral"
                                    @checked($valOwnerType === 'moral')
                                    @click="ownerType = 'moral'"
                                    class="h-4 w-4 rounded-full border-surface-dim text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-800">
                                <span>
                                    <span class="block text-body-sm font-semibold text-on-surface dark:text-gray-100">{{ __('works_notification.owner_moral') }}</span>
                                    <span class="block text-xs text-secondary dark:text-gray-400 mt-0.5">{{ __('works_notification.owner_moral_hint') }}</span>
                                </span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            {{-- Owner name (natural person) --}}
                            <div class="space-y-1.5" x-show="ownerType === 'natural'" x-cloak>
                                <label for="owner_name" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('works_notification.owner_name') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input id="owner_name" name="owner_name" type="text" maxlength="255"
                                    value="{{ $valOwnerName }}"
                                    placeholder="{{ __('works_notification.owner_name_placeholder') }}"
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('owner_name') border-red-400 @enderror">
                                @error('owner_name')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>

                            {{-- Company name (legal entity) --}}
                            <div class="space-y-1.5" x-show="ownerType === 'moral'" x-cloak>
                                <label for="company_name" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('works_notification.company_name') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input id="company_name" name="company_name" type="text" maxlength="255"
                                    value="{{ $valCompany }}"
                                    placeholder="{{ __('works_notification.company_name_placeholder') }}"
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('company_name') border-red-400 @enderror">
                                @error('company_name')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>

                            {{-- CIN --}}
                            <div class="space-y-1.5">
                                <label for="owner_cin" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('works_notification.owner_cin') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input id="owner_cin" name="owner_cin" type="text" inputmode="numeric" maxlength="20"
                                    value="{{ $valOwnerCin }}"
                                    placeholder="{{ __('works_notification.cin_placeholder') }}" required
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('owner_cin') border-red-400 @enderror">
                                <p class="text-xs text-secondary dark:text-gray-500">{{ __('works_notification.owner_cin_hint') }}</p>
                                @error('owner_cin')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>

                            {{-- Phone --}}
                            <div class="space-y-1.5">
                                <label for="phone" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('works_notification.phone') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input id="phone" name="phone" type="tel" maxlength="20" inputmode="tel" dir="ltr"
                                    value="{{ $valPhone }}"
                                    placeholder="{{ __('works_notification.phone_placeholder') }}" required
                                    class="num-ltr block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('phone') border-red-400 @enderror">
                                <p class="text-xs text-secondary dark:text-gray-500 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm text-outline" aria-hidden="true">info</span>
                                    {{ __('works_notification.phone_notification_hint') }}
                                </p>
                                @error('phone')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- JORT reference (legal entity only) --}}
                        <div class="space-y-1.5" x-show="ownerType === 'moral'" x-cloak>
                            <label for="jort_reference" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                {{ __('works_notification.jort_reference') }}
                            </label>
                            <input id="jort_reference" name="jort_reference" type="text" maxlength="100" dir="ltr"
                                value="{{ $valJort }}"
                                placeholder="{{ __('works_notification.jort_reference_placeholder') }}"
                                class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('jort_reference') border-red-400 @enderror">
                            <p class="text-xs text-secondary dark:text-gray-500">{{ __('works_notification.jort_reference_hint') }}</p>
                            @error('jort_reference')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Dashed separator --}}
                    <div class="mx-6 border-t border-dashed border-surface-dim dark:border-gray-700"></div>

                    {{-- =====================================================
                         SECTION B — BÂTIMENT ET LOCALISATION
                         ===================================================== --}}
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">B</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">{{ __('works_notification.section_location') }}</h3>
                        </div>

                        {{-- Governorate + Delegation (searchable dropdowns) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Governorate searchable dropdown --}}
                            <div class="space-y-1.5" id="gov-field">
                                <label id="gov-label" class="block text-body-sm font-bold text-on-surface dark:text-gray-200" for="gov-trigger">
                                    {{ __('works_notification.code_structure') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input type="hidden" name="governorate_id" id="gov-hidden" value="{{ $valGov }}">
                                <div class="relative" id="gov-wrap">
                                    <button type="button" id="gov-trigger"
                                        class="flex w-full items-center justify-between rounded border border-surface-dim bg-surface-container-lowest px-3.5 h-11 text-body-md shadow-sm transition focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 @error('governorate_id') border-red-400 @enderror"
                                        aria-haspopup="listbox" aria-expanded="false" aria-labelledby="gov-label">
                                        <span id="gov-trigger-label" class="truncate text-outline dark:text-gray-500">{{ __('works_notification.code_structure_select') }}</span>
                                        <svg class="h-4 w-4 flex-none text-outline transition-transform" id="gov-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div id="gov-panel" class="absolute z-50 mt-1 hidden w-full overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-lg dark:border-gray-700 dark:bg-gray-800" role="listbox">
                                        <div class="border-b border-surface-container-high px-3 py-2 dark:border-gray-700">
                                            <div class="relative">
                                                <span class="material-symbols-outlined absolute start-2.5 top-2.5 text-body-sm text-outline" aria-hidden="true">search</span>
                                                <input type="text" id="gov-search" autocomplete="off"
                                                    class="block w-full rounded bg-surface-container py-1.5 ps-9 pe-3 text-body-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                                    placeholder="{{ __('works_notification.search_placeholder') }}">
                                            </div>
                                        </div>
                                        <ul id="gov-list" class="max-h-56 overflow-y-auto py-1 text-body-sm" role="listbox"></ul>
                                    </div>
                                </div>
                                <p id="gov-inline-error" class="text-xs text-red-600 dark:text-red-400 hidden" role="alert">{{ __('works_notification.select_gov_del') }}</p>
                                @error('governorate_id')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>

                            {{-- Delegation searchable dropdown --}}
                            <div class="space-y-1.5" id="del-field">
                                <label id="del-label" class="block text-body-sm font-bold text-on-surface dark:text-gray-200" for="del-trigger">
                                    {{ __('works_notification.delegation') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input type="hidden" name="delegation_id" id="del-hidden" value="{{ $valDelId }}">
                                <div class="relative" id="del-wrap">
                                    <button type="button" id="del-trigger" disabled
                                        class="flex w-full items-center justify-between rounded border border-surface-dim bg-surface-container-lowest px-3.5 h-11 text-body-md shadow-sm transition focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 @error('delegation_id') border-red-400 @enderror"
                                        aria-haspopup="listbox" aria-expanded="false" aria-labelledby="del-label">
                                        <span id="del-trigger-label" class="truncate text-outline dark:text-gray-500">{{ __('works_notification.delegation_select') }}</span>
                                        <svg class="h-4 w-4 flex-none text-outline transition-transform" id="del-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div id="del-panel" class="absolute z-40 mt-1 hidden w-full overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-lg dark:border-gray-700 dark:bg-gray-800" role="listbox">
                                        <div class="border-b border-surface-container-high px-3 py-2 dark:border-gray-700">
                                            <div class="relative">
                                                <span class="material-symbols-outlined absolute start-2.5 top-2.5 text-body-sm text-outline" aria-hidden="true">search</span>
                                                <input type="text" id="del-search" autocomplete="off"
                                                    class="block w-full rounded bg-surface-container py-1.5 ps-9 pe-3 text-body-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                                    placeholder="{{ __('works_notification.search_placeholder') }}">
                                            </div>
                                        </div>
                                        <ul id="del-list" class="max-h-56 overflow-y-auto py-1 text-body-sm" role="listbox"></ul>
                                    </div>
                                </div>
                                <p id="del-inline-error" class="text-xs text-red-600 dark:text-red-400 hidden" role="alert">{{ __('works_notification.delegation_empty') }}</p>
                                @error('delegation_id')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Property address --}}
                        <div class="space-y-1.5">
                            <label for="property_address" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                {{ __('works_notification.property_address') }} <span class="text-primary" aria-hidden="true">*</span>
                            </label>
                            <div class="relative">
                                <span class="material-symbols-outlined filled absolute start-3 top-3 text-lg text-outline" aria-hidden="true">location_on</span>
                                <input id="property_address" name="property_address" type="text" maxlength="500"
                                    value="{{ $valPropAddr }}"
                                    placeholder="{{ __('works_notification.property_address_placeholder') }}" required
                                    class="ps-10 block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('property_address') border-red-400 @enderror">
                            </div>
                            @error('property_address')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Dashed separator --}}
                    <div class="mx-6 border-t border-dashed border-surface-dim dark:border-gray-700"></div>

                    {{-- =====================================================
                         SECTION C — NATURE ET DESCRIPTION DES TRAVAUX
                         ===================================================== --}}
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">C</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">{{ __('works_notification.section_works') }}</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Works type --}}
                            <div class="space-y-1.5">
                                <label for="works_type" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('works_notification.works_type') }}
                                </label>
                                <input id="works_type" name="works_type" type="text" maxlength="100"
                                    value="{{ $valWorksType }}"
                                    placeholder="{{ __('works_notification.works_type_placeholder') }}"
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('works_type') border-red-400 @enderror">
                                @error('works_type')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>

                            {{-- Works start date --}}
                            <div class="space-y-1.5">
                                <label for="works_start_date" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('works_notification.works_start_date') }}
                                </label>
                                <input id="works_start_date" name="works_start_date" type="date"
                                    value="{{ $valWorksDate }}"
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('works_start_date') border-red-400 @enderror">
                                @error('works_start_date')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Works description --}}
                        <div class="space-y-1.5">
                            <label for="works_description" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                {{ __('works_notification.works_description') }} <span class="text-primary" aria-hidden="true">*</span>
                            </label>
                            <textarea id="works_description" name="works_description" rows="4" maxlength="5000" required
                                placeholder="{{ __('works_notification.works_description_placeholder') }}"
                                class="block w-full px-3.5 py-2.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('works_description') border-red-400 @enderror">{{ $valWorksDesc }}</textarea>
                            @error('works_description')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                        </div>

                        {{-- Notes --}}
                        <div class="space-y-1.5">
                            <label for="notes" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                {{ __('works_notification.notes') }}
                            </label>
                            <textarea id="notes" name="notes" rows="3" maxlength="3000"
                                placeholder="{{ __('works_notification.notes_placeholder') }}"
                                class="block w-full px-3.5 py-2.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('notes') border-red-400 @enderror">{{ $valNotes }}</textarea>
                            @error('notes')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Form footer --}}
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim px-6 py-5 dark:border-gray-700">
                        <a href="{{ $isEditing ? route('building-works-declarations.step2.show', $BuildingWorksDeclaration) : route('building-works-declarations.index') }}"
                           class="inline-flex items-center justify-center rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            {{ __('works_notification.cancel') }}
                        </a>
                        <button type="submit" id="works-submit-btn"
                            class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                            data-loading-text="{{ __('works_notification.submitting') }}">
                            <span id="works-submit-label">{{ $isEditing ? __('works_notification.save_and_continue') : __('works_notification.continue') }}</span>
                            <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </form>

            </section>{{-- /#works-form-section --}}
        </div>
    </div>

    <script>
    (function () {
        /* ── Reveal form when user clicks "create new" in the tabs ── */
        var formSection = document.getElementById('works-form-section');
        Array.prototype.slice.call(document.querySelectorAll('[data-start-new-works]')).forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!formSection) return;
                formSection.classList.remove('hidden');
                formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                var firstField = document.getElementById('owner_cin');
                if (firstField) setTimeout(function () { firstField.focus({ preventScroll: true }); }, 350);
            });
        });

        /* ── Data ── */
        var gouvernorats   = @json($gouvernorats);
        var delegationsMap = @json($delegations);
        var initGovId      = @json((string) $valGov);
        var initDelId      = @json((string) $valDelId);
        var noResults      = @json(__('works_notification.activity_no_results'));
        var govSelectLbl   = @json(__('works_notification.code_structure_select'));
        var delSelectLbl   = @json(__('works_notification.delegation_select'));
        var delEmptyLbl    = @json(__('works_notification.delegation_empty'));

        /* ── DOM ── */
        var govHidden  = document.getElementById('gov-hidden');
        var govTrigger = document.getElementById('gov-trigger');
        var govTrigLbl = document.getElementById('gov-trigger-label');
        var govChevron = document.getElementById('gov-chevron');
        var govPanel   = document.getElementById('gov-panel');
        var govSearch  = document.getElementById('gov-search');
        var govList    = document.getElementById('gov-list');

        var delHidden  = document.getElementById('del-hidden');
        var delTrigger = document.getElementById('del-trigger');
        var delTrigLbl = document.getElementById('del-trigger-label');
        var delChevron = document.getElementById('del-chevron');
        var delPanel   = document.getElementById('del-panel');
        var delSearch  = document.getElementById('del-search');
        var delList    = document.getElementById('del-list');

        var submitBtn = document.getElementById('works-submit-btn');
        var submitLbl = document.getElementById('works-submit-label');

        var govOpen = false, delOpen = false;
        var govItems = [], delItems = [];

        /* ── Helpers ── */
        function buildItems(source) {
            if (Array.isArray(source)) return source;
            return Object.keys(source).map(function (k) { return { id: k, name: source[k] }; });
        }

        function renderList(ul, items, selectedId, onSelect) {
            ul.innerHTML = '';
            if (!items.length) {
                var li = document.createElement('li');
                li.className = 'px-4 py-2 text-outline text-body-sm italic';
                li.textContent = noResults;
                ul.appendChild(li);
                return;
            }
            items.forEach(function (item) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                var isSel = String(item.id) === String(selectedId);
                li.setAttribute('aria-selected', isSel ? 'true' : 'false');
                li.className = 'flex cursor-pointer items-center gap-2 px-4 py-2 text-body-sm transition ' +
                    (isSel ? 'bg-gov-50 font-semibold text-gov-700 dark:bg-gov-900/30 dark:text-gov-300'
                           : 'text-on-surface hover:bg-surface-container dark:text-gray-200 dark:hover:bg-gray-700');
                li.innerHTML = isSel
                    ? '<svg class="h-3.5 w-3.5 flex-none text-gov-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>' + item.name + '</span>'
                    : '<span class="h-3.5 w-3.5 flex-none"></span><span>' + item.name + '</span>';
                li.addEventListener('mousedown', function (e) { e.preventDefault(); onSelect(item); });
                ul.appendChild(li);
            });
        }

        function openPanel(panel, chevron, search, setOpen) {
            panel.classList.remove('hidden');
            chevron.style.transform = 'rotate(180deg)';
            search.value = '';
            setOpen(true);
            search.focus();
        }
        function closePanel(panel, chevron, setOpen) {
            panel.classList.add('hidden');
            chevron.style.transform = '';
            setOpen(false);
        }

        /* ── Governorate ── */
        govItems = buildItems(gouvernorats);

        function renderGovList(filter) {
            var filtered = filter ? govItems.filter(function (i) { return i.name.toLowerCase().includes(filter.toLowerCase()); }) : govItems;
            renderList(govList, filtered, govHidden.value, function (item) {
                govHidden.value = item.id;
                govTrigLbl.textContent = item.name;
                govTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
                closePanel(govPanel, govChevron, function (v) { govOpen = v; });
                loadDelegations(item.id);
            });
        }

        govTrigger.addEventListener('click', function () {
            if (govOpen) { closePanel(govPanel, govChevron, function (v) { govOpen = v; }); return; }
            openPanel(govPanel, govChevron, govSearch, function (v) { govOpen = v; });
            renderGovList('');
        });
        govSearch.addEventListener('input', function () { renderGovList(this.value); });
        document.addEventListener('click', function (e) {
            if (govOpen && !document.getElementById('gov-wrap').contains(e.target))
                closePanel(govPanel, govChevron, function (v) { govOpen = v; });
        });

        /* ── Delegation ── */
        function loadDelegations(govId) {
            var list = (delegationsMap[String(govId)] || []);
            delItems = Array.isArray(list) ? list : buildItems(list);
            delHidden.value = '';
            delTrigLbl.textContent = delSelectLbl;
            delTrigLbl.classList.add('text-outline', 'dark:text-gray-500');
            delTrigger.disabled = (delItems.length === 0);
        }

        function renderDelList(filter) {
            var filtered = filter ? delItems.filter(function (i) { return i.name.toLowerCase().includes(filter.toLowerCase()); }) : delItems;
            renderList(delList, filtered, delHidden.value, function (item) {
                delHidden.value = item.id;
                delTrigLbl.textContent = item.name;
                delTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
                closePanel(delPanel, delChevron, function (v) { delOpen = v; });
            });
        }

        delTrigger.addEventListener('click', function () {
            if (delOpen) { closePanel(delPanel, delChevron, function (v) { delOpen = v; }); return; }
            openPanel(delPanel, delChevron, delSearch, function (v) { delOpen = v; });
            renderDelList('');
        });
        delSearch.addEventListener('input', function () { renderDelList(this.value); });
        document.addEventListener('click', function (e) {
            if (delOpen && !document.getElementById('del-wrap').contains(e.target))
                closePanel(delPanel, delChevron, function (v) { delOpen = v; });
        });

        /* ── Pre-fill on edit mode ── */
        (function init() {
            if (initGovId) {
                var gov = govItems.find(function (i) { return String(i.id) === String(initGovId); });
                if (gov) {
                    govHidden.value = gov.id;
                    govTrigLbl.textContent = gov.name;
                    govTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
                    loadDelegations(gov.id);
                    if (initDelId) {
                        var del = delItems.find(function (i) { return String(i.id) === String(initDelId); });
                        if (del) {
                            delHidden.value = del.id;
                            delTrigLbl.textContent = del.name;
                            delTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
                        }
                    }
                }
            }
        }());

        /* ── Loading state on submit ── */
        document.getElementById('works-create-form').addEventListener('submit', function () {
            if (submitBtn) submitBtn.disabled = true;
            if (submitLbl) submitLbl.textContent = submitBtn.dataset.loadingText || '...';
        });
    }());
    </script>
</x-app-layout>
