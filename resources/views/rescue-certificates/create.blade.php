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
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.create_title') }}</h2>
                <p class="mt-0.5 text-sm text-secondary dark:text-gray-400">{{ __('rescue_certificate.create_subtitle') }}</p>
            </div>
            @if ($isEditing)
                <a href="{{ route('rescue-certificates.step2.show', $rescueCertificate) }}" class="inline-flex items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2 text-sm font-medium text-on-surface transition hover:bg-surface-container dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('rescue_certificate.back_to_step2') }}
                </a>
            @else
                <a href="{{ route('rescue-certificates.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2 text-sm font-medium text-on-surface transition hover:bg-surface-container dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('rescue_certificate.back') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[280px,1fr]">

                {{-- Step sidebar --}}
                <aside class="rounded-2xl border border-surface-dim bg-surface-container-lowest p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 h-fit">
                    <ol class="space-y-0">
                        <li class="flex items-start gap-3">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-gov-600 text-sm font-bold text-white ring-4 ring-gov-100 dark:ring-gov-900/40">1</span>
                            <div class="min-w-0 pt-1">
                                <p class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.step_one_title') }}</p>
                                <p class="mt-1 text-xs text-gov-600 dark:text-gov-400 font-medium">
                                    {{ $isEditing ? __('rescue_certificate.step_editing') : __('rescue_certificate.step_status') }}
                                </p>
                            </div>
                        </li>
                        {{-- Connector: green when editing (step 1 was completed) --}}
                        <li class="flex"><span class="ms-[17px] w-0.5 h-6 {{ $isEditing ? 'bg-emerald-300 dark:bg-emerald-700' : 'bg-gray-200 dark:bg-gray-700' }}"></span></li>
                        <li class="flex items-start gap-3 {{ $isEditing ? '' : 'opacity-40' }}">
                            @if ($isEditing)
                                <a href="{{ route('rescue-certificates.step2.show', $rescueCertificate) }}" class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </a>
                            @else
                                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full border-2 border-surface-dim text-sm font-bold text-outline dark:border-gray-600 dark:text-gray-500">2</span>
                            @endif
                            <div class="min-w-0 flex-1 pt-1">
                                <p class="text-sm font-semibold {{ $isEditing ? 'text-on-surface dark:text-gray-100' : 'text-secondary dark:text-gray-400' }}">{{ __('rescue_certificate.step_two_title') }}</p>
                            </div>
                        </li>
                    </ol>
                </aside>

                {{-- Form card --}}
                <section class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-surface-dim px-6 py-5 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.step_heading') }}</h3>
                        <p class="mt-1 text-sm text-secondary dark:text-gray-400">{{ __('rescue_certificate.step_description') }}</p>
                    </div>

                    <form method="POST" action="{{ $formAction }}" class="px-6 py-6" id="rescue-create-form"
                          x-data="{ minor: @if ($valMinor === '1' || $valMinor === true || $valMinor === 1 || $valMinor) true @else false @endif }">
                        @csrf
                        @if ($isEditing) @method('PUT') @endif

                        @if ($errors->any())
                            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                                <p class="font-semibold">{{ __('rescue_certificate.create_error_title') }}</p>
                                <ul class="mt-2 list-disc space-y-1 ps-5">
                                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Row 1: CIN + Nom --}}
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="cin" class="block text-sm font-semibold text-on-surface dark:text-gray-200">
                                    {{ __('rescue_certificate.cin') }} <span class="text-red-500 ms-0.5">*</span>
                                </label>
                                <input id="cin" name="cin" type="text" inputmode="numeric" maxlength="20"
                                    value="{{ $valCin }}" placeholder="{{ __('rescue_certificate.cin_placeholder') }}" required
                                    class="mt-2 block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('cin') border-red-400 @enderror">
                                @error('cin')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="nom_prenom" class="block text-sm font-semibold text-on-surface dark:text-gray-200">
                                    {{ __('rescue_certificate.nom_prenom') }} <span class="text-red-500 ms-0.5">*</span>
                                </label>
                                <input id="nom_prenom" name="nom_prenom" type="text" maxlength="255"
                                    value="{{ $valNom }}" placeholder="{{ __('rescue_certificate.nom_prenom_placeholder') }}" required
                                    class="mt-2 block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('nom_prenom') border-red-400 @enderror">
                                @error('nom_prenom')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Row 1b: Phone --}}
                        <div class="mt-5">
                            <label for="phone" class="block text-sm font-semibold text-on-surface dark:text-gray-200">
                                {{ __('rescue_certificate.phone') }} <span class="text-red-500 ms-0.5">*</span>
                            </label>
                            <input id="phone" name="phone" type="tel" maxlength="20"
                                value="{{ $valPhone }}" placeholder="{{ __('rescue_certificate.phone_placeholder') }}" required
                                class="mt-2 block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('phone') border-red-400 @enderror">
                            @error('phone')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            <p class="mt-1 text-xs text-outline dark:text-gray-500">{{ __('rescue_certificate.phone_notification_hint') }}</p>
                        </div>

                        {{-- Row 1c: Victim status (minor / spouse) --}}
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <label class="flex items-start gap-3 rounded-lg border border-surface-dim bg-surface-container px-4 py-3 cursor-pointer dark:border-gray-700 dark:bg-gray-900/50">
                                <input type="checkbox" name="victim_is_minor" value="1"
                                    @checked($valMinor)
                                    @click="minor = ! minor"
                                    class="mt-0.5 h-4 w-4 rounded border-surface-dim text-gov-600 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-800">
                                <span>
                                    <span class="block text-sm font-semibold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.victim_is_minor') }}</span>
                                    <span class="block text-xs text-secondary dark:text-gray-400 mt-0.5">{{ __('rescue_certificate.victim_is_minor_hint') }}</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 rounded-lg border border-surface-dim bg-surface-container px-4 py-3 cursor-pointer dark:border-gray-700 dark:bg-gray-900/50">
                                <input type="checkbox" name="spouse_case" value="1"
                                    @checked($valSpouse)
                                    class="mt-0.5 h-4 w-4 rounded border-surface-dim text-gov-600 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-800">
                                <span>
                                    <span class="block text-sm font-semibold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.spouse_case') }}</span>
                                    <span class="block text-xs text-secondary dark:text-gray-400 mt-0.5">{{ __('rescue_certificate.spouse_case_hint') }}</span>
                                </span>
                            </label>
                        </div>

                        {{-- Parent (only when the victim is a minor) --}}
                        <div x-show="minor" x-cloak class="mt-5 rounded-xl border border-gov-200 bg-gov-50/60 px-4 py-4 dark:border-gov-700/40 dark:bg-gov-900/10">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="parent_name" class="block text-sm font-semibold text-on-surface dark:text-gray-200">
                                        {{ __('rescue_certificate.parent_name') }} <span class="text-red-500 ms-0.5">*</span>
                                    </label>
                                    <input id="parent_name" name="parent_name" type="text" maxlength="255"
                                        value="{{ $valParentName }}" placeholder="{{ __('rescue_certificate.parent_name_placeholder') }}"
                                        class="mt-2 block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('parent_name') border-red-400 @enderror">
                                    @error('parent_name')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="parent_cin" class="block text-sm font-semibold text-on-surface dark:text-gray-200">
                                        {{ __('rescue_certificate.parent_cin') }} <span class="text-red-500 ms-0.5">*</span>
                                    </label>
                                    <input id="parent_cin" name="parent_cin" type="text" maxlength="20"
                                        value="{{ $valParentCin }}" placeholder="{{ __('rescue_certificate.cin_placeholder') }}"
                                        class="mt-2 block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('parent_cin') border-red-400 @enderror">
                                    @error('parent_cin')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="my-6 border-t border-dashed border-surface-dim dark:border-gray-700"></div>

                        {{-- Section: Rescue operation details --}}
                        <h4 class="mb-4 text-sm font-bold text-on-surface dark:text-gray-100 flex items-center gap-2">
                            <svg class="h-4 w-4 text-gov-600 dark:text-gov-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ __('rescue_certificate.incident_section') }}
                        </h4>

                        {{-- Incident type --}}
                        <div>
                            <label for="incident_type" class="block text-sm font-semibold text-on-surface dark:text-gray-200">
                                {{ __('rescue_certificate.incident_type') }} <span class="text-red-500 ms-0.5">*</span>
                            </label>
                            <select id="incident_type" name="incident_type" required
                                class="mt-2 block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('incident_type') border-red-400 @enderror">
                                <option value="" disabled @selected(! $valIncType)>{{ __('rescue_certificate.incident_type_select') }}</option>
                                @foreach ($incidentTypes as $typeKey)
                                    <option value="{{ $typeKey }}" @selected($valIncType === $typeKey)>@lang('rescue_certificate.incident_type_' . $typeKey)</option>
                                @endforeach
                            </select>
                            @error('incident_type')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>

                        {{-- Row: Gouvernorat (searchable) + Délégation (searchable) --}}
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">

                            {{-- ── Gouvernorat searchable dropdown ── --}}
                            <div>
                                <label class="block text-sm font-semibold text-on-surface dark:text-gray-200 mb-2">
                                    {{ __('rescue_certificate.code_structure') }} <span class="text-red-500 ms-0.5">*</span>
                                </label>
                                {{-- Hidden real input --}}
                                <input type="hidden" name="governorate_id" id="gov-hidden" value="{{ $valGov }}">
                                <div class="relative" id="gov-wrap">
                                    <button type="button" id="gov-trigger"
                                        class="flex w-full items-center justify-between rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2.5 text-sm shadow-sm focus:border-gov-500 focus:outline-none focus:ring-1 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 @error('governorate_id') border-red-400 @enderror"
                                        aria-haspopup="listbox" aria-expanded="false">
                                        <span id="gov-trigger-label" class="truncate text-outline dark:text-gray-500">{{ __('rescue_certificate.code_structure_select') }}</span>
                                        <svg class="h-4 w-4 flex-none text-outline transition-transform" id="gov-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div id="gov-panel" class="absolute z-50 mt-1 hidden w-full overflow-hidden rounded-xl border border-surface-dim bg-surface-container-lowest shadow-lg dark:border-gray-700 dark:bg-gray-800" role="listbox">
                                        <div class="border-b border-surface-container-high px-3 py-2 dark:border-gray-700">
                                            <div class="relative">
                                                <svg class="absolute start-2.5 top-2.5 h-3.5 w-3.5 text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                </svg>
                                                <input type="text" id="gov-search" autocomplete="off"
                                                    class="block w-full rounded-md border-0 bg-surface-container py-1.5 ps-8 pe-3 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-gov-400 dark:bg-gray-700 dark:text-gray-200"
                                                    placeholder="{{ __('rescue_certificate.search_placeholder') }}">
                                            </div>
                                        </div>
                                        <ul id="gov-list" class="max-h-56 overflow-y-auto py-1 text-sm" role="listbox"></ul>
                                    </div>
                                </div>
                                @error('governorate_id')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>

                            {{-- ── Délégation searchable dropdown ── --}}
                            <div>
                                <label class="block text-sm font-semibold text-on-surface dark:text-gray-200 mb-2">
                                    {{ __('rescue_certificate.delegation') }} <span class="text-red-500 ms-0.5">*</span>
                                </label>
                                <input type="hidden" name="delegation_id" id="del-hidden" value="{{ $valDelId }}">
                                <div class="relative" id="del-wrap">
                                    <button type="button" id="del-trigger" disabled
                                        class="flex w-full items-center justify-between rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2.5 text-sm shadow-sm focus:border-gov-500 focus:outline-none focus:ring-1 focus:ring-gov-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 @error('delegation_id') border-red-400 @enderror"
                                        aria-haspopup="listbox" aria-expanded="false">
                                        <span id="del-trigger-label" class="truncate text-outline dark:text-gray-500">{{ __('rescue_certificate.delegation_select') }}</span>
                                        <svg class="h-4 w-4 flex-none text-outline transition-transform" id="del-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div id="del-panel" class="absolute z-40 mt-1 hidden w-full overflow-hidden rounded-xl border border-surface-dim bg-surface-container-lowest shadow-lg dark:border-gray-700 dark:bg-gray-800" role="listbox">
                                        <div class="border-b border-surface-container-high px-3 py-2 dark:border-gray-700">
                                            <div class="relative">
                                                <svg class="absolute start-2.5 top-2.5 h-3.5 w-3.5 text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                </svg>
                                                <input type="text" id="del-search" autocomplete="off"
                                                    class="block w-full rounded-md border-0 bg-surface-container py-1.5 ps-8 pe-3 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-gov-400 dark:bg-gray-700 dark:text-gray-200"
                                                    placeholder="{{ __('rescue_certificate.search_placeholder') }}">
                                            </div>
                                        </div>
                                        <ul id="del-list" class="max-h-56 overflow-y-auto py-1 text-sm" role="listbox"></ul>
                                    </div>
                                </div>
                                @error('delegation_id')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>

                        </div>{{-- /grid --}}

                        {{-- Event address --}}
                        <div class="mt-5">
                            <label for="incident_address" class="block text-sm font-semibold text-on-surface dark:text-gray-200">
                                {{ __('rescue_certificate.incident_address') }} <span class="text-red-500 ms-0.5">*</span>
                            </label>
                            <input id="incident_address" name="incident_address" type="text" maxlength="500"
                                value="{{ $valIncAddr }}" placeholder="{{ __('rescue_certificate.incident_address_placeholder') }}" required
                                class="mt-2 block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('incident_address') border-red-400 @enderror">
                            @error('incident_address')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>

                        {{-- Row: date + end date + time + attendance --}}
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="incident_date" class="block text-sm font-semibold text-on-surface dark:text-gray-200">
                                    {{ __('rescue_certificate.incident_date') }} <span class="text-red-500 ms-0.5">*</span>
                                </label>
                                <input id="incident_date" name="incident_date" type="date"
                                    value="{{ $valIncDate }}" required
                                    class="mt-2 block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('incident_date') border-red-400 @enderror">
                                @error('incident_date')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="incident_time" class="block text-sm font-semibold text-on-surface dark:text-gray-200">
                                    {{ __('rescue_certificate.incident_time') }}
                                </label>
                                <input id="incident_time" name="incident_time" type="time"
                                    value="{{ $valIncTime }}"
                                    class="mt-2 block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('incident_time') border-red-400 @enderror">
                                @error('incident_time')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Description of the rescue operation --}}
                        <div class="mt-5">
                            <label for="incident_description" class="block text-sm font-semibold text-on-surface dark:text-gray-200">
                                {{ __('rescue_certificate.incident_description') }} <span class="text-red-500 ms-0.5">*</span>
                            </label>
                            <textarea id="incident_description" name="incident_description" rows="4" maxlength="5000" required
                                placeholder="{{ __('rescue_certificate.incident_description_placeholder') }}"
                                class="mt-2 block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('incident_description') border-red-400 @enderror">{{ $valIncDesc }}</textarea>
                            @error('incident_description')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>

                        {{-- Notes --}}
                        <div class="mt-5">
                            <label for="notes" class="block text-sm font-semibold text-on-surface dark:text-gray-200">
                                {{ __('rescue_certificate.notes') }}
                            </label>
                            <textarea id="notes" name="notes" rows="3" maxlength="3000"
                                placeholder="{{ __('rescue_certificate.notes_placeholder') }}"
                                class="mt-2 block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('notes') border-red-400 @enderror">{{ $valNotes }}</textarea>
                            @error('notes')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>

                        {{-- Footer --}}
                        <div class="mt-8 flex items-center justify-between gap-3 border-t border-surface-dim pt-5 dark:border-gray-700">
                            @if ($isEditing)
                                <a href="{{ route('rescue-certificates.step2.show', $rescueCertificate) }}" class="inline-flex items-center justify-center rounded-lg border border-surface-dim bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('rescue_certificate.cancel') }}</a>
                            @else
                                <a href="{{ route('rescue-certificates.index') }}" class="inline-flex items-center justify-center rounded-lg border border-surface-dim bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('rescue_certificate.cancel') }}</a>
                            @endif
                            <button type="submit" id="create-submit-btn"
                                class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                                data-loading-text="{{ __('rescue_certificate.submitting') }}">
                                <span id="create-submit-label">{{ $isEditing ? __('rescue_certificate.save_and_continue') : __('rescue_certificate.continue') }}</span>
                                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </section>

            </div>
        </div>
    </div>

    <script>
    (function () {
        /* ── Data ────────────────────────────────────────────────────────── */
        var gouvernorats   = @json($gouvernorats);   // { id: "name", ... }
        var delegationsMap = @json($delegations);    // { govId: [{id,name}, ...] }
        var initGovId      = @json($valGov);         // pre-selected gov id (or '')
        var initDelId      = @json($valDelId);       // pre-selected del id (or '')
        var noResults      = @json(__('rescue_certificate.activity_no_results'));
        var govSelectLbl   = @json(__('rescue_certificate.code_structure_select'));
        var delSelectLbl   = @json(__('rescue_certificate.delegation_select'));
        var delEmptyLbl    = @json(__('rescue_certificate.delegation_empty'));

        /* ── DOM ─────────────────────────────────────────────────────────── */
        var govHidden = document.getElementById('gov-hidden');
        var govTrigger= document.getElementById('gov-trigger');
        var govTrigLbl= document.getElementById('gov-trigger-label');
        var govChevron= document.getElementById('gov-chevron');
        var govPanel  = document.getElementById('gov-panel');
        var govSearch = document.getElementById('gov-search');
        var govList   = document.getElementById('gov-list');

        var delHidden = document.getElementById('del-hidden');
        var delTrigger= document.getElementById('del-trigger');
        var delTrigLbl= document.getElementById('del-trigger-label');
        var delChevron= document.getElementById('del-chevron');
        var delPanel  = document.getElementById('del-panel');
        var delSearch = document.getElementById('del-search');
        var delList   = document.getElementById('del-list');

        var form      = document.getElementById('rescue-create-form');
        var submitBtn = document.getElementById('create-submit-btn');
        var submitLbl = document.getElementById('create-submit-label');

        var govOpen = false, delOpen = false;
        var govItems = []; // [{id, name}]
        var delItems = []; // [{id, name}]

        /* ── Generic helpers ─────────────────────────────────────────────── */
        function buildItems(source) {
            // source can be object {id:name} or array [{id,name}]
            if (Array.isArray(source)) return source;
            return Object.keys(source).map(function(k) {
                return { id: k, name: source[k] };
            });
        }

        function renderList(ul, items, selectedId, onSelect) {
            ul.innerHTML = '';
            if (!items.length) {
                var li = document.createElement('li');
                li.className = 'px-4 py-2 text-outline text-sm italic';
                li.textContent = noResults;
                ul.appendChild(li);
                return;
            }
            items.forEach(function(item) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                var isSel = String(item.id) === String(selectedId);
                li.className = 'flex cursor-pointer items-center gap-2 px-4 py-2 text-sm transition ' +
                    (isSel
                        ? 'bg-gov-50 font-semibold text-gov-700 dark:bg-gov-900/30 dark:text-gov-300'
                        : 'text-on-surface hover:bg-surface-container dark:text-gray-200 dark:hover:bg-gray-700');
                li.innerHTML = isSel
                    ? '<svg class="h-3.5 w-3.5 flex-none text-gov-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>' + item.name + '</span>'
                    : '<span class="h-3.5 w-3.5 flex-none"></span><span>' + item.name + '</span>';
                li.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    onSelect(item);
                });
                ul.appendChild(li);
            });
        }

        function openDropdown(panel, chevron, search, openFlagSetter) {
            panel.classList.remove('hidden');
            chevron.style.transform = 'rotate(180deg)';
            search.value = '';
            openFlagSetter(true);
            search.focus();
        }

        function closeDropdown(panel, chevron, openFlagSetter) {
            panel.classList.add('hidden');
            chevron.style.transform = '';
            openFlagSetter(false);
        }

        /* ── Gouvernorat ─────────────────────────────────────────────────── */
        govItems = buildItems(gouvernorats);

        function renderGovList(filter) {
            var q = (filter || '').toLowerCase();
            var filtered = q ? govItems.filter(function(i){ return i.name.toLowerCase().includes(q); }) : govItems;
            renderList(govList, filtered, govHidden.value, selectGov);
        }

        function selectGov(item) {
            govHidden.value  = item.id;
            govTrigLbl.textContent = item.name;
            govTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
            govTrigLbl.classList.add('text-on-surface', 'dark:text-gray-100');
            closeDropdown(govPanel, govChevron, function(v){ govOpen = v; });
            // Reset delegation
            delHidden.value = '';
            delTrigLbl.textContent = delSelectLbl;
            delTrigLbl.classList.add('text-outline', 'dark:text-gray-500');
            delTrigLbl.classList.remove('text-on-surface', 'dark:text-gray-100');
            delTrigger.disabled = false;
            loadDelegations(item.id, '');
        }

        govTrigger.addEventListener('click', function() {
            if (govOpen) { closeDropdown(govPanel, govChevron, function(v){ govOpen = v; }); }
            else { openDropdown(govPanel, govChevron, govSearch, function(v){ govOpen = v; }); renderGovList(''); }
        });
        govSearch.addEventListener('input', function() { renderGovList(this.value); });

        /* ── Délégation ──────────────────────────────────────────────────── */
        function loadDelegations(govId, selectedDelId) {
            delItems = delegationsMap[govId] || [];
            renderDelList('', selectedDelId);
            delTrigger.disabled = delItems.length === 0;
        }

        function renderDelList(filter, selectedDelId) {
            var sid = selectedDelId !== undefined ? selectedDelId : delHidden.value;
            var q   = (filter || '').toLowerCase();
            var filtered = q ? delItems.filter(function(i){ return i.name.toLowerCase().includes(q); }) : delItems;
            renderList(delList, filtered, sid, selectDel);
        }

        function selectDel(item) {
            delHidden.value = item.id;
            delTrigLbl.textContent = item.name;
            delTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
            delTrigLbl.classList.add('text-on-surface', 'dark:text-gray-100');
            closeDropdown(delPanel, delChevron, function(v){ delOpen = v; });
        }

        delTrigger.addEventListener('click', function() {
            if (this.disabled) return;
            if (delOpen) { closeDropdown(delPanel, delChevron, function(v){ delOpen = v; }); }
            else { openDropdown(delPanel, delChevron, delSearch, function(v){ delOpen = v; }); renderDelList('', delHidden.value); }
        });
        delSearch.addEventListener('input', function() { renderDelList(this.value); });

        /* ── Close on outside click ──────────────────────────────────────── */
        document.addEventListener('click', function(e) {
            if (govOpen && !govPanel.contains(e.target) && !govTrigger.contains(e.target)) {
                closeDropdown(govPanel, govChevron, function(v){ govOpen = v; });
            }
            if (delOpen && !delPanel.contains(e.target) && !delTrigger.contains(e.target)) {
                closeDropdown(delPanel, delChevron, function(v){ delOpen = v; });
            }
        });

        /* ── Restore state on page load (edit mode / validation error) ───── */
        if (initGovId) {
            var govItem = govItems.find(function(i){ return String(i.id) === String(initGovId); });
            if (govItem) {
                govHidden.value = govItem.id;
                govTrigLbl.textContent = govItem.name;
                govTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
                govTrigLbl.classList.add('text-on-surface', 'dark:text-gray-100');
                delTrigger.disabled = false;
                loadDelegations(govItem.id, initDelId);
                // Also restore the delegation label
                if (initDelId) {
                    var delItem = (delegationsMap[govItem.id] || []).find(function(d){ return String(d.id) === String(initDelId); });
                    if (delItem) {
                        delHidden.value = delItem.id;
                        delTrigLbl.textContent = delItem.name;
                        delTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
                        delTrigLbl.classList.add('text-on-surface', 'dark:text-gray-100');
                    }
                }
            }
        }

        /* ── Submit lock ─────────────────────────────────────────────────── */
        form.addEventListener('submit', function() {
            // Validate hidden inputs manually since they're not <select required>
            if (!govHidden.value || !delHidden.value) {
                alert('{{ __('rescue_certificate.select_gov_del') }}');
                return false;
            }
            submitBtn.disabled = true;
            submitLbl.textContent = submitBtn.dataset.loadingText;
        });

    }());
    </script>
</x-app-layout>
