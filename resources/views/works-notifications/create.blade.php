@php
    $isEditing = isset($worksNotification);
    $formAction = $isEditing
        ? route('works-notifications.step1.update', $worksNotification)
        : route('works-notifications.step1.store');
    $valOwnerType = old('owner_type', $isEditing && $worksNotification->owner_type ? $worksNotification->owner_type : 'natural');
    $valOwnerName = old('owner_name', $isEditing ? $worksNotification->owner_name : '');
    $valOwnerCin  = old('owner_cin', $isEditing ? $worksNotification->owner_cin : '');
    $valCompany   = old('company_name', $isEditing ? $worksNotification->company_name : '');
    $valJort      = old('jort_reference', $isEditing ? $worksNotification->jort_reference : '');
    $valPhone  = old('phone',    $isEditing ? $worksNotification->phone    : '');
    $valGov    = old('governorate_id', $isEditing ? $worksNotification->governorate_id : '');
    $valDelId  = old('delegation_id', $isEditing ? $worksNotification->delegation_id : '');
    $valPropAddr = old('property_address', $isEditing ? $worksNotification->property_address : '');
    $valWorksType = old('works_type', $isEditing ? $worksNotification->works_type : '');
    $valWorksDate = old('works_start_date', $isEditing && $worksNotification->works_start_date ? $worksNotification->works_start_date->format('Y-m-d') : '');
    $valWorksDesc = old('works_description', $isEditing ? $worksNotification->works_description : '');
    $valNotes  = old('notes', $isEditing ? $worksNotification->notes : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ __('works_notification.create_title') }}</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('works_notification.create_subtitle') }}</p>
            </div>
            @if ($isEditing)
                <a href="{{ route('works-notifications.step2.show', $worksNotification) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('works_notification.back_to_step2') }}
                </a>
            @else
                <a href="{{ route('works-notifications.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('works_notification.back') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[280px,1fr]">

                {{-- Step sidebar --}}
                <aside class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 h-fit">
                    <ol class="space-y-0">
                        <li class="flex items-start gap-3">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-gov-600 text-sm font-bold text-white ring-4 ring-gov-100 dark:ring-gov-900/40">1</span>
                            <div class="min-w-0 pt-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('works_notification.step_one_title') }}</p>
                                <p class="mt-1 text-xs text-gov-600 dark:text-gov-400 font-medium">
                                    {{ $isEditing ? __('works_notification.step_editing') : __('works_notification.step_status') }}
                                </p>
                            </div>
                        </li>
                        {{-- Connector: green when editing (step 1 was completed) --}}
                        <li class="flex"><span class="ms-[17px] w-0.5 h-6 {{ $isEditing ? 'bg-emerald-300 dark:bg-emerald-700' : 'bg-gray-200 dark:bg-gray-700' }}"></span></li>
                        <li class="flex items-start gap-3 {{ $isEditing ? '' : 'opacity-40' }}">
                            @if ($isEditing)
                                <a href="{{ route('works-notifications.step2.show', $worksNotification) }}" class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </a>
                            @else
                                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full border-2 border-gray-300 text-sm font-bold text-gray-400 dark:border-gray-600 dark:text-gray-500">2</span>
                            @endif
                            <div class="min-w-0 flex-1 pt-1">
                                <p class="text-sm font-semibold {{ $isEditing ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">{{ __('works_notification.step_two_title') }}</p>
                            </div>
                        </li>
                    </ol>
                </aside>

                {{-- Form card --}}
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('works_notification.step_heading') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('works_notification.step_description') }}</p>
                    </div>

                    <form method="POST" action="{{ $formAction }}" class="px-6 py-6" id="works-create-form"
                          x-data="{ ownerType: @js($valOwnerType) }">
                        @csrf
                        @if ($isEditing) @method('PUT') @endif

                        @if ($errors->any())
                            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                                <p class="font-semibold">{{ __('works_notification.create_error_title') }}</p>
                                <ul class="mt-2 list-disc space-y-1 ps-5">
                                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Owner type: natural / moral --}}
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition {{ $valOwnerType === 'natural' ? 'border-gov-400 bg-gov-50 dark:border-gov-600 dark:bg-gov-900/20' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50' }}">
                                <input type="radio" name="owner_type" value="natural" @checked($valOwnerType === 'natural')
                                    @click="ownerType = 'natural'"
                                    class="h-4 w-4 text-gov-600 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-800">
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('works_notification.owner_natural') }}</span>
                            </label>
                            <label class="flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition {{ $valOwnerType === 'moral' ? 'border-gov-400 bg-gov-50 dark:border-gov-600 dark:bg-gov-900/20' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50' }}">
                                <input type="radio" name="owner_type" value="moral" @checked($valOwnerType === 'moral')
                                    @click="ownerType = 'moral'"
                                    class="h-4 w-4 text-gov-600 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-800">
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('works_notification.owner_moral') }}</span>
                            </label>
                        </div>

                        {{-- Owner name / company name (Alpine toggle) --}}
                        <div class="mt-5">
                            {{-- Natural person: owner name --}}
                            <div x-show="ownerType === 'natural'" x-cloak>
                                <label for="owner_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    {{ __('works_notification.owner_name') }} <span class="text-red-500 ms-0.5">*</span>
                                </label>
                                <input id="owner_name" name="owner_name" type="text" maxlength="255"
                                    value="{{ $valOwnerName }}" placeholder="{{ __('works_notification.owner_name_placeholder') }}"
                                    class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('owner_name') border-red-400 @enderror">
                                @error('owner_name')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                            {{-- Legal entity: company name + JORT reference --}}
                            <div x-show="ownerType === 'moral'" x-cloak>
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <label for="company_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                            {{ __('works_notification.company_name') }} <span class="text-red-500 ms-0.5">*</span>
                                        </label>
                                        <input id="company_name" name="company_name" type="text" maxlength="255"
                                            value="{{ $valCompany }}" placeholder="{{ __('works_notification.company_name_placeholder') }}"
                                            class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('company_name') border-red-400 @enderror">
                                        @error('company_name')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="jort_reference" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                            {{ __('works_notification.jort_reference') }}
                                        </label>
                                        <input id="jort_reference" name="jort_reference" type="text" maxlength="100"
                                            value="{{ $valJort }}" placeholder="{{ __('works_notification.jort_reference_placeholder') }}"
                                            class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('jort_reference') border-red-400 @enderror">
                                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('works_notification.jort_reference_hint') }}</p>
                                        @error('jort_reference')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CIN of owner / legal representative --}}
                        <div class="mt-5">
                            <label for="owner_cin" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ __('works_notification.owner_cin') }} <span class="text-red-500 ms-0.5">*</span>
                            </label>
                            <input id="owner_cin" name="owner_cin" type="text" inputmode="numeric" maxlength="20"
                                value="{{ $valOwnerCin }}" placeholder="{{ __('works_notification.cin_placeholder') }}" required
                                class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('owner_cin') border-red-400 @enderror">
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('works_notification.owner_cin_hint') }}</p>
                            @error('owner_cin')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>

                        {{-- Row 1b: Phone --}}
                        <div class="mt-5">
                            <label for="phone" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ __('works_notification.phone') }} <span class="text-red-500 ms-0.5">*</span>
                            </label>
                            <input id="phone" name="phone" type="tel" maxlength="20"
                                value="{{ $valPhone }}" placeholder="{{ __('works_notification.phone_placeholder') }}" required
                                class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('phone') border-red-400 @enderror">
                            @error('phone')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('works_notification.phone_notification_hint') }}</p>
                        </div>

                        {{-- Section: Building & works details --}}
                        <h4 class="mb-4 text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <svg class="h-4 w-4 text-gov-600 dark:text-gov-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18m-4-10h.01M9 15h.01m2-4h.01m2 4h.01m2-8h.01M19 21V11l-6-4"/></svg>
                            {{ __('works_notification.works_section') }}
                        </h4>

                        {{-- Works type (nature of the works) --}}
                        <div>
                            <label for="works_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ __('works_notification.works_type') }}
                            </label>
                            <input id="works_type" name="works_type" type="text" maxlength="100"
                                value="{{ $valWorksType }}" placeholder="{{ __('works_notification.works_type_placeholder') }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('works_type') border-red-400 @enderror">
                            @error('works_type')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>

                        {{-- Row: Gouvernorat (searchable) + Délégation (searchable) --}}
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">

                            {{-- ── Gouvernorat searchable dropdown ── --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                    {{ __('works_notification.code_structure') }} <span class="text-red-500 ms-0.5">*</span>
                                </label>
                                {{-- Hidden real input --}}
                                <input type="hidden" name="governorate_id" id="gov-hidden" value="{{ $valGov }}">
                                <div class="relative" id="gov-wrap">
                                    <button type="button" id="gov-trigger"
                                        class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-gov-500 focus:outline-none focus:ring-1 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 @error('governorate_id') border-red-400 @enderror"
                                        aria-haspopup="listbox" aria-expanded="false">
                                        <span id="gov-trigger-label" class="truncate text-gray-400 dark:text-gray-500">{{ __('works_notification.code_structure_select') }}</span>
                                        <svg class="h-4 w-4 flex-none text-gray-400 transition-transform" id="gov-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div id="gov-panel" class="absolute z-50 mt-1 hidden w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800" role="listbox">
                                        <div class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                            <div class="relative">
                                                <svg class="absolute start-2.5 top-2.5 h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                </svg>
                                                <input type="text" id="gov-search" autocomplete="off"
                                                    class="block w-full rounded-md border-0 bg-gray-50 py-1.5 ps-8 pe-3 text-sm text-gray-900 placeholder-gray-400 focus:ring-1 focus:ring-gov-400 dark:bg-gray-700 dark:text-gray-200"
                                                    placeholder="{{ __('works_notification.search_placeholder') }}">
                                            </div>
                                        </div>
                                        <ul id="gov-list" class="max-h-56 overflow-y-auto py-1 text-sm" role="listbox"></ul>
                                    </div>
                                </div>
                                @error('governorate_id')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>

                            {{-- ── Délégation searchable dropdown ── --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                    {{ __('works_notification.delegation') }} <span class="text-red-500 ms-0.5">*</span>
                                </label>
                                <input type="hidden" name="delegation_id" id="del-hidden" value="{{ $valDelId }}">
                                <div class="relative" id="del-wrap">
                                    <button type="button" id="del-trigger" disabled
                                        class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-gov-500 focus:outline-none focus:ring-1 focus:ring-gov-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 @error('delegation_id') border-red-400 @enderror"
                                        aria-haspopup="listbox" aria-expanded="false">
                                        <span id="del-trigger-label" class="truncate text-gray-400 dark:text-gray-500">{{ __('works_notification.delegation_select') }}</span>
                                        <svg class="h-4 w-4 flex-none text-gray-400 transition-transform" id="del-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div id="del-panel" class="absolute z-40 mt-1 hidden w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800" role="listbox">
                                        <div class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                            <div class="relative">
                                                <svg class="absolute start-2.5 top-2.5 h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                </svg>
                                                <input type="text" id="del-search" autocomplete="off"
                                                    class="block w-full rounded-md border-0 bg-gray-50 py-1.5 ps-8 pe-3 text-sm text-gray-900 placeholder-gray-400 focus:ring-1 focus:ring-gov-400 dark:bg-gray-700 dark:text-gray-200"
                                                    placeholder="{{ __('works_notification.search_placeholder') }}">
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
                            <label for="property_address" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ __('works_notification.property_address') }} <span class="text-red-500 ms-0.5">*</span>
                            </label>
                            <input id="property_address" name="property_address" type="text" maxlength="500"
                                value="{{ $valPropAddr }}" placeholder="{{ __('works_notification.property_address_placeholder') }}" required
                                class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('property_address') border-red-400 @enderror">
                            @error('property_address')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>

                        {{-- Works start date --}}
                        <div class="mt-5">
                            <label for="works_start_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ __('works_notification.works_start_date') }}
                            </label>
                            <input id="works_start_date" name="works_start_date" type="date"
                                value="{{ $valWorksDate }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('works_start_date') border-red-400 @enderror">
                            @error('works_start_date')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        </div>

                        {{-- Description of the works --}}
                        <div class="mt-5">
                            <label for="works_description" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ __('works_notification.works_description') }} <span class="text-red-500 ms-0.5">*</span>
                            </label>
                            <textarea id="works_description" name="works_description" rows="4" maxlength="5000" required
                                placeholder="{{ __('works_notification.works_description_placeholder') }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('works_description') border-red-400 @enderror">{{ $valWorksDesc }}</textarea>
                            @error('works_description')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>

                        {{-- Notes --}}
                        <div class="mt-5">
                            <label for="notes" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ __('works_notification.notes') }}
                            </label>
                            <textarea id="notes" name="notes" rows="3" maxlength="3000"
                                placeholder="{{ __('works_notification.notes_placeholder') }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('notes') border-red-400 @enderror">{{ $valNotes }}</textarea>
                            @error('notes')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>

                        {{-- Footer --}}
                        <div class="mt-8 flex items-center justify-between gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                            @if ($isEditing)
                                <a href="{{ route('works-notifications.step2.show', $worksNotification) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('works_notification.cancel') }}</a>
                            @else
                                <a href="{{ route('works-notifications.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('works_notification.cancel') }}</a>
                            @endif
                            <button type="submit" id="create-submit-btn"
                                class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                                data-loading-text="{{ __('works_notification.submitting') }}">
                                <span id="create-submit-label">{{ $isEditing ? __('works_notification.save_and_continue') : __('works_notification.continue') }}</span>
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
        var noResults      = @json(__('works_notification.activity_no_results'));
        var govSelectLbl   = @json(__('works_notification.code_structure_select'));
        var delSelectLbl   = @json(__('works_notification.delegation_select'));
        var delEmptyLbl    = @json(__('works_notification.delegation_empty'));

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

        var form      = document.getElementById('works-create-form');
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
                li.className = 'px-4 py-2 text-gray-400 text-sm italic';
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
                        : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700');
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
            govTrigLbl.classList.remove('text-gray-400', 'dark:text-gray-500');
            govTrigLbl.classList.add('text-gray-900', 'dark:text-gray-100');
            closeDropdown(govPanel, govChevron, function(v){ govOpen = v; });
            // Reset delegation
            delHidden.value = '';
            delTrigLbl.textContent = delSelectLbl;
            delTrigLbl.classList.add('text-gray-400', 'dark:text-gray-500');
            delTrigLbl.classList.remove('text-gray-900', 'dark:text-gray-100');
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
            delTrigLbl.classList.remove('text-gray-400', 'dark:text-gray-500');
            delTrigLbl.classList.add('text-gray-900', 'dark:text-gray-100');
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
                govTrigLbl.classList.remove('text-gray-400', 'dark:text-gray-500');
                govTrigLbl.classList.add('text-gray-900', 'dark:text-gray-100');
                delTrigger.disabled = false;
                loadDelegations(govItem.id, initDelId);
                // Also restore the delegation label
                if (initDelId) {
                    var delItem = (delegationsMap[govItem.id] || []).find(function(d){ return String(d.id) === String(initDelId); });
                    if (delItem) {
                        delHidden.value = delItem.id;
                        delTrigLbl.textContent = delItem.name;
                        delTrigLbl.classList.remove('text-gray-400', 'dark:text-gray-500');
                        delTrigLbl.classList.add('text-gray-900', 'dark:text-gray-100');
                    }
                }
            }
        }

        /* ── Submit lock ─────────────────────────────────────────────────── */
        form.addEventListener('submit', function() {
            // Validate hidden inputs manually since they're not <select required>
            if (!govHidden.value || !delHidden.value) {
                alert('{{ __('works_notification.select_gov_del') }}');
                return false;
            }
            submitBtn.disabled = true;
            submitLbl.textContent = submitBtn.dataset.loadingText;
        });

    }());
    </script>
</x-app-layout>
