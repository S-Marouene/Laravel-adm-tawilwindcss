{{-- One bilingual (Français + العربية) note field pair with real-time
    auto-translation in both directions (services: Google gtx free ->
    MyMemory fallback, proxied server-side by /admin/translate).

    - Values are submitted as $name[fr] / $name[ar] → stored as JSON
      {"fr": …, "ar": …} and displayed in the reader's selected language.
    - Both fields are required when the note itself is required.
    - Typing in one field auto-translates into the other after a short
      debounce — but a hand-written counterpart is never overwritten,
      so the admin can freely fix any imperfect wording.

    Note: config is passed through data-* attributes (NOT inlined into
    x-data — @json double quotes would terminate the HTML attribute);
    the Alpine component reads initial values from the $ref textareas.

    Usage:
        <x-admin.bilingual-note name="admin_note" required
            :rows="2" placeholder-fr="{{ __('prevention.note_correction_placeholder') }}" />
--}}
@props([
    'name'          => 'admin_note',
    'rows'          => 3,
    'maxlength'     => 2000,
    'required'      => false,
    'placeholderFr' => null,
    'placeholderAr' => null,
    'translateUrl'  => route('admin.translate'),
])

@php
    // Boolean HTML attributes arrive as '' — normalize.
    $required = filter_var((string) $required, FILTER_VALIDATE_BOOLEAN);

    $errorFr = $errors->has($name.'.fr');
    $errorAr = $errors->has($name.'.ar');

    // Unique DOM ids — several note forms may share one page.
    $uid = $name.'-'.str()->random(4);

    // Fallback: Arabic textarea gets the same guidance text (already
    // localized in the admin's current UI language).
    $placeholderAr ??= $placeholderFr;
@endphp

<div {{ $attributes->only('class') }}
    x-data="bilingualTranslator()"
    data-translate-url="{{ $translateUrl }}">
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        {{-- French --}}
        <div>
            <label for="{{ $uid }}-fr"
                class="mb-1 flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider text-secondary dark:text-gray-400">
                <span>{{ __('lang.french') }} @if ($required) <span class="text-red-500">*</span> @endif</span>
                @if ($required)
                    {{-- Spinner while Arabic is being auto-filled from this side --}}
                    <span class="ml-auto flex items-center gap-1 normal-case tracking-normal"
                          x-show="busyAr" x-cloak>
                        <span class="inline-block h-3 w-3 animate-spin rounded-full border-[1.5px] border-secondary border-t-transparent"></span>
                        <span class="text-[9px] font-semibold">{{ __('lang.bilingual_working') }}</span>
                    </span>
                @endif
            </label>
            <textarea id="{{ $uid }}-fr" name="{{ $name }}[fr]" rows="{{ $rows }}" maxlength="{{ $maxlength }}"
                @if ($required) required @endif
                x-ref="fr"
                @input="changed('fr', $event.target.value)"
                class="text-xs {{ $errorFr ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : '' }}"
                @if ($placeholderFr) placeholder="{{ $placeholderFr }}" @endif>{{ old($name.'.fr') }}</textarea>
            @error($name.'.fr') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Arabic — auto-translated in real time, freely editable --}}
        <div>
            <label for="{{ $uid }}-ar"
                class="mb-1 flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider text-secondary dark:text-gray-400">
                <span>{{ __('lang.arabic') }} @if ($required) <span class="text-red-500">*</span> @endif</span>
                {{-- Spinner while French is being auto-filled from this side --}}
                @if ($required)
                    <span class="ml-auto flex items-center gap-1 normal-case tracking-normal"
                          x-show="busyFr" x-cloak>
                        <span class="inline-block h-3 w-3 animate-spin rounded-full border-[1.5px] border-secondary border-t-transparent"></span>
                        <span class="text-[9px] font-semibold">{{ __('lang.bilingual_working') }}</span>
                    </span>
                @endif
            </label>
            <textarea id="{{ $uid }}-ar" name="{{ $name }}[ar]" rows="{{ $rows }}" maxlength="{{ $maxlength }}"
                dir="rtl" @if ($required) required @endif
                x-ref="ar"
                @input="changed('ar', $event.target.value)"
                class="text-xs {{ $errorAr ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : '' }}"
                @if ($placeholderAr) placeholder="{{ $placeholderAr }}" @endif>{{ old($name.'.ar') }}</textarea>
            @error($name.'.ar') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            <p class="mt-1 text-[9px] italic text-outline dark:text-gray-500"
               x-show="arError && $refs.ar.value.trim() === ''" x-cloak>{{ __('lang.bilingual_failed') }}</p>
        </div>
    </div>
    @if ($required)
        <p class="mt-1 flex items-center gap-1 text-[10px] text-outline dark:text-gray-500">
            <span class="material-symbols-outlined text-xs" aria-hidden="true">translate</span>
            {{ __('lang.bilingual_auto_hint') }}
        </p>
    @endif
</div>
