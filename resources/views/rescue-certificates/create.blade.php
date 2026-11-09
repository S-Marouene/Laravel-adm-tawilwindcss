@php
    $isEditing = isset($rescueCertificate);
    $formAction = $isEditing
        ? route('rescue-certificates.step1.update', $rescueCertificate)
        : route('rescue-certificates.step1.store');
    $valCin    = old('cin',      $isEditing ? $rescueCertificate->cin      : '');
    $valNom    = old('nom_prenom', $isEditing ? $rescueCertificate->nom_prenom : '');
    $valPhone  = old('phone',    $isEditing ? $rescueCertificate->phone    : '');
    $valGov    = old('governorate_id', $isEditing ? $rescueCertificate->governorate_id : '');
    $valDelId  = old('delegation_id', $isEditing ? $rescueCertificate->delegation_id : '');
    $valMinor  = old('victim_is_minor', $isEditing ? $rescueCertificate->victim_is_minor : false);
    $valParentName = old('parent_name', $isEditing ? $rescueCertificate->parent_name : '');
    $valParentCin  = old('parent_cin', $isEditing ? $rescueCertificate->parent_cin : '');
    $valSpouse = old('spouse_case', $isEditing ? $rescueCertificate->spouse_case : false);
    $valIncAddr = old('incident_address', $isEditing ? $rescueCertificate->incident_address : '');
    $valIncType = old('incident_type', $isEditing ? $rescueCertificate->incident_type : '');
    $valIncDate = old('incident_date', $isEditing && $rescueCertificate->incident_date ? $rescueCertificate->incident_date->format('Y-m-d') : '');
    $valIncTime = old('incident_time', $isEditing && $rescueCertificate->incident_time ? $rescueCertificate->incident_time->format('H:i') : '');
    $valIncDesc = old('incident_description', $isEditing ? $rescueCertificate->incident_description : '');
    $incidentTypes = \App\Models\RescueCertificate::TYPES;
    $valNotes  = old('notes', $isEditing ? $rescueCertificate->notes : '');

    // ── Récapitulatif des étapes pour la barre de progression ──
    $wizardSteps = [
        [
            'state'   => 'active',
            'title'   => __('rescue_certificate.step_one_title'),
            'editing' => $isEditing,
        ],
        [
            'state'   => $isEditing ? 'done' : 'upcoming',
            'title'   => __('rescue_certificate.step_two_title'),
            'data'    => [],
            'editUrl' => $isEditing ? route('rescue-certificates.step2.show', $rescueCertificate) : null,
            'editLabel' => __('rescue_certificate.step_two_title'),
        ],
    ];

    $showForm = $isEditing || $errors->any() || !empty(old());
@endphp

<x-app-layout>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Carte à onglets — détails officiels de la démarche
                 (masquée pendant l'édition d'un dossier existant) --}}
            @unless ($isEditing)
                <x-onpc.rescue-service-details-tabs :drafts="$drafts ?? collect()" />
            @endunless

            {{-- ================================================================
                 FORMULAIRE (stepper + bandeau + formulaire)
                 Masqué par défaut : révélé quand l'utilisateur clique sur
                 « Créer une nouvelle demande », reprend un brouillon, ou après
                 une erreur de validation.
                 ================================================================ --}}
            <section id="rescue-form-section" class="space-y-6 @unless($showForm) hidden @endunless">

                {{-- Barre de progression horizontale --}}
                <x-onpc.dossier-stepper :steps="$wizardSteps" />

                {{-- Bandeau de statut de l'étape (filet rouge ONPC) --}}
                <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div>
                        <span class="text-label-caps text-primary uppercase tracking-wider">
                            {{ __('rescue_certificate.step_of', ['step' => 1, 'total' => 2]) }}
                            @if ($isEditing) · {{ __('rescue_certificate.step_editing') }} @endif
                        </span>
                        <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('rescue_certificate.step_heading') }} — {{ __('rescue_certificate.step_one_title') }}</h2>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-surface-container text-body-sm text-outline font-medium border border-surface-dim shrink-0 dark:text-gray-400">
                        <span class="text-red-500 font-bold" aria-hidden="true">*</span>
                        {{ __('rescue_certificate.required_note') }}
                    </span>
                </div>

                <form method="POST" action="{{ $formAction }}" id="rescue-create-form"
                      class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800"
                      x-data="{ minor: @if ($valMinor === '1' || $valMinor === true || $valMinor === 1 || $valMinor) true @else false @endif }">
                    @csrf
                    @if ($isEditing) @method('PUT') @endif

                    {{-- ⚠️ Erreurs de validation --}}
                    <div class="p-6 pb-0">
                        @if ($errors->any())
                            <div class="rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                                <p class="font-bold">{{ __('rescue_certificate.create_error_title') }}</p>
                                <ul class="mt-1.5 list-disc space-y-1 ps-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    {{-- =====================================================
                         SECTION A — IDENTITÉ DE LA VICTIME
                         ===================================================== --}}
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">A</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">
                                {{ __('rescue_certificate.section_victim') }}
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- CIN --}}
                            <div class="space-y-1.5">
                                <label for="cin" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('rescue_certificate.cin') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input id="cin" name="cin" type="text" inputmode="numeric" maxlength="20"
                                    value="{{ $valCin }}" placeholder="{{ __('rescue_certificate.cin_placeholder') }}" required
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('cin') border-red-400 @enderror">
                                @error('cin')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>

                            {{-- Nom et prénom --}}
                            <div class="space-y-1.5">
                                <label for="nom_prenom" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('rescue_certificate.nom_prenom') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input id="nom_prenom" name="nom_prenom" type="text" maxlength="255"
                                    value="{{ $valNom }}" placeholder="{{ __('rescue_certificate.nom_prenom_placeholder') }}" required
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('nom_prenom') border-red-400 @enderror">
                                @error('nom_prenom')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>

                            {{-- Téléphone --}}
                            <div class="space-y-1.5 md:col-span-2">
                                <label for="phone" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('rescue_certificate.phone') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input id="phone" name="phone" type="tel" maxlength="20" inputmode="tel" dir="ltr"
                                    value="{{ $valPhone }}" placeholder="{{ __('rescue_certificate.phone_placeholder') }}" required
                                    class="num-ltr block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('phone') border-red-400 @enderror">
                                <p class="text-xs text-secondary dark:text-gray-500 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm text-outline" aria-hidden="true">info</span>
                                    {{ __('rescue_certificate.phone_notification_hint') }}
                                </p>
                                @error('phone')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Qualité du requérant : mineur / conjoint --}}
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex items-start gap-3 rounded-lg border border-surface-dim bg-surface-container px-4 py-3 cursor-pointer transition hover:border-primary/40 dark:border-gray-700 dark:bg-gray-900/50 has-[:checked]:border-primary/60 has-[:checked]:bg-primary/5">
                                <input type="checkbox" name="victim_is_minor" value="1"
                                    @checked($valMinor)
                                    @click="minor = !minor"
                                    class="mt-0.5 h-4 w-4 rounded border-surface-dim text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-800">
                                <span>
                                    <span class="block text-body-sm font-semibold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.victim_is_minor') }}</span>
                                    <span class="block text-xs text-secondary dark:text-gray-400 mt-0.5">{{ __('rescue_certificate.victim_is_minor_hint') }}</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 rounded-lg border border-surface-dim bg-surface-container px-4 py-3 cursor-pointer transition hover:border-primary/40 dark:border-gray-700 dark:bg-gray-900/50 has-[:checked]:border-primary/60 has-[:checked]:bg-primary/5">
                                <input type="checkbox" name="spouse_case" value="1"
                                    @checked($valSpouse)
                                    class="mt-0.5 h-4 w-4 rounded border-surface-dim text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-800">
                                <span>
                                    <span class="block text-body-sm font-semibold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.spouse_case') }}</span>
                                    <span class="block text-xs text-secondary dark:text-gray-400 mt-0.5">{{ __('rescue_certificate.spouse_case_hint') }}</span>
                                </span>
                            </label>
                        </div>

                        {{-- Champs parent — mineur uniquement --}}
                        <div x-show="minor" x-cloak
                             class="rounded-xl border border-primary/20 bg-primary/5 px-4 py-4 dark:border-primary/20 dark:bg-primary/10">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label for="parent_name" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                        {{ __('rescue_certificate.parent_name') }} <span class="text-primary" aria-hidden="true">*</span>
                                    </label>
                                    <input id="parent_name" name="parent_name" type="text" maxlength="255"
                                        value="{{ $valParentName }}" placeholder="{{ __('rescue_certificate.parent_name_placeholder') }}"
                                        class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('parent_name') border-red-400 @enderror">
                                    @error('parent_name')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label for="parent_cin" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                        {{ __('rescue_certificate.parent_cin') }} <span class="text-primary" aria-hidden="true">*</span>
                                    </label>
                                    <input id="parent_cin" name="parent_cin" type="text" maxlength="20"
                                        value="{{ $valParentCin }}" placeholder="{{ __('rescue_certificate.cin_placeholder') }}"
                                        class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('parent_cin') border-red-400 @enderror">
                                    @error('parent_cin')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Séparateur --}}
                    <div class="mx-6 border-t border-dashed border-surface-dim dark:border-gray-700"></div>

                    {{-- =====================================================
                         SECTION B — OPÉRATION DE SECOURS ET DE SAUVETAGE
                         ===================================================== --}}
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">B</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">
                                {{ __('rescue_certificate.incident_section') }}
                            </h3>
                        </div>

                        {{-- Type d'incident --}}
                        <div class="space-y-1.5">
                            <label for="incident_type" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                {{ __('rescue_certificate.incident_type') }} <span class="text-primary" aria-hidden="true">*</span>
                            </label>
                            <select id="incident_type" name="incident_type" required
                                class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('incident_type') border-red-400 @enderror">
                                <option value="" disabled @selected(! $valIncType)>{{ __('rescue_certificate.incident_type_select') }}</option>
                                @foreach ($incidentTypes as $typeKey)
                                    <option value="{{ $typeKey }}" @selected($valIncType === $typeKey)>@lang('rescue_certificate.incident_type_' . $typeKey)</option>
                                @endforeach
                            </select>
                            @error('incident_type')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                        </div>

                        {{-- Gouvernorat + Délégation --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Gouvernorat searchable dropdown --}}
                            <div class="space-y-1.5" id="gov-field">
                                <label id="gov-label" class="block text-body-sm font-bold text-on-surface dark:text-gray-200" for="gov-trigger">
                                    {{ __('rescue_certificate.code_structure') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input type="hidden" name="governorate_id" id="gov-hidden" value="{{ $valGov }}">
                                <div class="relative" id="gov-wrap">
                                    <button type="button" id="gov-trigger"
                                        class="flex w-full items-center justify-between rounded border border-surface-dim bg-surface-container-lowest px-3.5 h-11 text-body-md shadow-sm transition focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 @error('governorate_id') border-red-400 @enderror"
                                        aria-haspopup="listbox" aria-expanded="false" aria-labelledby="gov-label">
                                        <span id="gov-trigger-label" class="truncate text-outline dark:text-gray-500">{{ __('rescue_certificate.code_structure_select') }}</span>
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
                                                    placeholder="{{ __('rescue_certificate.search_placeholder') }}">
                                            </div>
                                        </div>
                                        <ul id="gov-list" class="max-h-56 overflow-y-auto py-1 text-body-sm" role="listbox"></ul>
                                    </div>
                                </div>
                                <p id="gov-inline-error" class="text-xs text-red-600 dark:text-red-400 hidden" role="alert">{{ __('rescue_certificate.select_gov_del') }}</p>
                                @error('governorate_id')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>

                            {{-- Délégation searchable dropdown --}}
                            <div class="space-y-1.5" id="del-field">
                                <label id="del-label" class="block text-body-sm font-bold text-on-surface dark:text-gray-200" for="del-trigger">
                                    {{ __('rescue_certificate.delegation') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input type="hidden" name="delegation_id" id="del-hidden" value="{{ $valDelId }}">
                                <div class="relative" id="del-wrap">
                                    <button type="button" id="del-trigger" disabled
                                        class="flex w-full items-center justify-between rounded border border-surface-dim bg-surface-container-lowest px-3.5 h-11 text-body-md shadow-sm transition focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 @error('delegation_id') border-red-400 @enderror"
                                        aria-haspopup="listbox" aria-expanded="false" aria-labelledby="del-label">
                                        <span id="del-trigger-label" class="truncate text-outline dark:text-gray-500">{{ __('rescue_certificate.delegation_select') }}</span>
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
                                                    placeholder="{{ __('rescue_certificate.search_placeholder') }}">
                                            </div>
                                        </div>
                                        <ul id="del-list" class="max-h-56 overflow-y-auto py-1 text-body-sm" role="listbox"></ul>
                                    </div>
                                </div>
                                <p id="del-inline-error" class="text-xs text-red-600 dark:text-red-400 hidden" role="alert">{{ __('rescue_certificate.delegation_empty') }}</p>
                                @error('delegation_id')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Adresse de l'incident --}}
                        <div class="space-y-1.5">
                            <label for="incident_address" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                {{ __('rescue_certificate.incident_address') }} <span class="text-primary" aria-hidden="true">*</span>
                            </label>
                            <div class="relative">
                                <span class="material-symbols-outlined filled absolute start-3 top-3 text-lg text-outline" aria-hidden="true">location_on</span>
                                <input id="incident_address" name="incident_address" type="text" maxlength="500"
                                    value="{{ $valIncAddr }}" placeholder="{{ __('rescue_certificate.incident_address_placeholder') }}" required
                                    class="ps-10 block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('incident_address') border-red-400 @enderror">
                            </div>
                            @error('incident_address')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                        </div>

                        {{-- Date + Heure --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label for="incident_date" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('rescue_certificate.incident_date') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <input id="incident_date" name="incident_date" type="date"
                                    value="{{ $valIncDate }}" required
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('incident_date') border-red-400 @enderror">
                                @error('incident_date')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="incident_time" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('rescue_certificate.incident_time') }}
                                </label>
                                <input id="incident_time" name="incident_time" type="time"
                                    value="{{ $valIncTime }}"
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('incident_time') border-red-400 @enderror">
                                @error('incident_time')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="space-y-1.5">
                            <label for="incident_description" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                {{ __('rescue_certificate.incident_description') }} <span class="text-primary" aria-hidden="true">*</span>
                            </label>
                            <textarea id="incident_description" name="incident_description" rows="4" maxlength="5000" required
                                placeholder="{{ __('rescue_certificate.incident_description_placeholder') }}"
                                class="block w-full px-3.5 py-2.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('incident_description') border-red-400 @enderror">{{ $valIncDesc }}</textarea>
                            @error('incident_description')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                        </div>

                        {{-- Notes --}}
                        <div class="space-y-1.5">
                            <label for="notes" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                {{ __('rescue_certificate.notes') }}
                            </label>
                            <textarea id="notes" name="notes" rows="3" maxlength="3000"
                                placeholder="{{ __('rescue_certificate.notes_placeholder') }}"
                                class="block w-full px-3.5 py-2.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('notes') border-red-400 @enderror">{{ $valNotes }}</textarea>
                            @error('notes')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Pied de formulaire --}}
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim px-6 py-5 dark:border-gray-700">
                        <a href="{{ $isEditing ? route('rescue-certificates.step2.show', $rescueCertificate) : route('rescue-certificates.index') }}"
                           class="inline-flex items-center justify-center rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            {{ __('rescue_certificate.cancel') }}
                        </a>
                        <button type="submit" id="create-submit-btn"
                            class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                            data-loading-text="{{ __('rescue_certificate.submitting') }}">
                            <span id="create-submit-label">{{ $isEditing ? __('rescue_certificate.save_and_continue') : __('rescue_certificate.continue') }}</span>
                            <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </form>

            </section>{{-- /#rescue-form-section --}}
        </div>
    </div>

    <script>
    (function () {
        /* ── Révélation du formulaire (bouton « Créer une nouvelle demande ») ── */
        var formSection = document.getElementById('rescue-form-section');
        Array.prototype.slice.call(document.querySelectorAll('[data-start-new-rescue]')).forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!formSection) return;
                formSection.classList.remove('hidden');
                formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                var firstField = document.getElementById('cin');
                if (firstField) setTimeout(function () { firstField.focus({ preventScroll: true }); }, 350);
            });
        });

        /* ── Data ─────────────────────────────────────────────────────── */
        var gouvernorats   = @json($gouvernorats);
        var delegationsMap = @json($delegations);
        var initGovId      = @json((string) $valGov);
        var initDelId      = @json((string) $valDelId);
        var noResults      = @json(__('rescue_certificate.activity_no_results'));
        var govSelectLbl   = @json(__('rescue_certificate.code_structure_select'));
        var delSelectLbl   = @json(__('rescue_certificate.delegation_select'));
        var delEmptyLbl    = @json(__('rescue_certificate.delegation_empty'));

        /* ── DOM ──────────────────────────────────────────────────────── */
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

        var submitBtn = document.getElementById('create-submit-btn');
        var submitLbl = document.getElementById('create-submit-label');

        var govOpen = false, delOpen = false;
        var govItems = [], delItems = [];

        /* ── Helpers ─────────────────────────────────────────────────── */
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

        /* ── Gouvernorat ─────────────────────────────────────────────── */
        govItems = buildItems(gouvernorats);

        function renderGovList(filter) {
            var filtered = filter ? govItems.filter(function (i) { return i.name.toLowerCase().includes(filter.toLowerCase()); }) : govItems;
            renderList(govList, filtered, govHidden.value, function (item) {
                govHidden.value = item.id;
                govTrigLbl.textContent = item.name;
                govTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
                closePanel(govPanel, govChevron, function (v) { govOpen = v; });
                // Load delegations
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
            if (govOpen && !document.getElementById('gov-wrap').contains(e.target)) {
                closePanel(govPanel, govChevron, function (v) { govOpen = v; });
            }
        });

        /* ── Délégation ──────────────────────────────────────────────── */
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
            if (delOpen && !document.getElementById('del-wrap').contains(e.target)) {
                closePanel(delPanel, delChevron, function (v) { delOpen = v; });
            }
        });

        /* ── Initialisation (valeurs pré-remplies en mode édition) ────── */
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
        })();

        /* ── Submit lock (loader) ────────────────────────────────────── */
        var form = document.getElementById('rescue-create-form');
        if (form) {
            form.addEventListener('submit', function () {
                if (submitBtn) { submitBtn.disabled = true; }
                var loadingText = submitBtn ? submitBtn.getAttribute('data-loading-text') : null;
                if (submitLbl && loadingText) submitLbl.textContent = loadingText;
            });
        }
    }());
    </script>
</x-app-layout>
