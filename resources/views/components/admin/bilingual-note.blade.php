{{-- One bilingual (Français + العربية) note field pair with Google-Translate
    style ONE-WAY auto-translation (services: Google gtx free -> MyMemory
    fallback, proxied server-side by /admin/translate).

    - Values are submitted as $name[fr] / $name[ar] → stored as JSON
      {"fr": …, "ar": …} and displayed in the reader's selected language.
    - Both fields are required when the note itself is required.
    - There is always ONE driving language (FR ➜ AR or AR ➜ FR): every
      keystroke in the source re-translates the counterpart — the target
      is freely rewritten, like Google Translate. Fixing a word in the
      target never blocks anything. The ⟲ button flips the direction and
      the ⏸ button pauses auto-translation entirely (manual entry).

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
    // Pass a translation KEY (e.g. 'prevention.note_correction_placeholder')
    // instead of literal text: the FR column gets the French string and the
    // AR column the Arabic one, whatever the admin's UI locale is.
    'placeholderKey' => null,
    'translateUrl'  => route('admin.translate'),
])

@php
    // Boolean HTML attributes arrive as '' — normalize.
    $required = filter_var((string) $required, FILTER_VALIDATE_BOOLEAN);

    $errorFr = $errors->has($name.'.fr');
    $errorAr = $errors->has($name.'.ar');

    // Unique DOM ids — several note forms may share one page.
    $uid = $name.'-'.str()->random(4);

    // A translation key drives both columns: French placeholder for the
    // French field, Arabic placeholder for the Arabic field (explicitly
    // fetched per language, not per UI locale).
    if ($placeholderKey) {
        $placeholderFr = trans($placeholderKey, [], 'fr');
        $placeholderAr = trans($placeholderKey, [], 'ar');
    }

    // Fallback: Arabic textarea gets the same guidance text if nothing
    // language-specific was provided.
    $placeholderAr ??= $placeholderFr;
@endphp

<div {{ $attributes->only('class') }}
    x-data="bilingualTranslator()"
    data-translate-url="{{ $translateUrl }}">

    {{-- Control bar: direction indicator + swap (⟲) + pause (⏸/▶) --}}
    <div class="mb-1 flex items-center gap-1.5 text-[10px] font-semibold text-outline dark:text-gray-500"
         :class="paused ? 'opacity-50' : ''">
        <span class="material-symbols-outlined text-xs" aria-hidden="true">translate</span>
        <span class="font-extrabold uppercase tracking-wider" x-show="!paused">{{ __('lang.auto_translation') }}</span>
        <span x-show="!paused && src" class="font-extrabold tracking-wider text-secondary dark:text-gray-300"
              x-text="src === 'ar' ? 'AR ➜ FR' : 'FR ➜ AR'"></span>
        <span x-show="paused" x-cloak class="italic">{{ __('lang.bilingual_paused') }}</span>

        <button type="button" x-on:click="swap()" title="{{ __('lang.bilingual_swap') }}"
            class="ml-auto flex items-center rounded p-0.5 transition hover:bg-black/5 hover:text-secondary dark:hover:bg-white/10 dark:hover:text-gray-200">
            <span class="material-symbols-outlined text-sm" aria-hidden="true">swap_horiz</span>
        </button>
        <button type="button" x-on:click="togglePause()" title="{{ __('lang.bilingual_pause') }}" x-show="!paused"
            class="flex items-center rounded p-0.5 transition hover:bg-black/5 hover:text-secondary dark:hover:bg-white/10 dark:hover:text-gray-200">
            <span class="material-symbols-outlined text-sm" aria-hidden="true">pause</span>
        </button>
        <button type="button" x-on:click="togglePause()" title="{{ __('lang.bilingual_resume') }}" x-show="paused" x-cloak
            class="flex items-center rounded p-0.5 text-primary dark:text-gray-200">
            <span class="material-symbols-outlined text-sm" aria-hidden="true">play_arrow</span>
        </button>
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        {{-- French — drives Arabic in FR ➜ AR mode --}}
        <div>
            <label for="{{ $uid }}-fr"
                class="mb-1 flex h-4 flex-nowrap items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider text-secondary dark:text-gray-400">
                <span>{{ __('lang.french') }} @if ($required) <span class="text-red-500">*</span> @endif</span>
                <span x-show="!paused && src === 'ar'" x-cloak
                    class="flex h-3 items-center gap-0.5 rounded-full bg-secondary/10 px-1.5 text-[8px] font-bold leading-none normal-case tracking-normal text-secondary dark:bg-white/10 dark:text-gray-300">
                    <span class="material-symbols-outlined text-[9px] leading-none" aria-hidden="true">lock</span>
                    auto
                </span>
                @if ($required)
                    {{-- Spinner while French is being auto-filled from this side --}}
                    <span class="ml-auto flex items-center gap-1 normal-case tracking-normal"
                          x-show="busyFr" x-cloak>
                        <span class="inline-block h-3 w-3 animate-spin rounded-full border-[1.5px] border-secondary border-t-transparent"></span>
                        <span class="text-[9px] font-semibold">{{ __('lang.bilingual_working') }}</span>
                    </span>
                @endif
            </label>
            <textarea id="{{ $uid }}-fr" name="{{ $name }}[fr]" rows="{{ $rows }}" maxlength="{{ $maxlength }}"
                @if ($required) required @endif
                x-ref="fr"
                @input="changed('fr', $event.target.value)"
                :readonly="!paused && src === 'ar'"
                :class="!paused && src === 'ar'
                    ? 'cursor-not-allowed bg-black/[0.03] opacity-70 dark:bg-white/[0.04]'
                    : ''"
                class="min-h-20 rounded-lg text-xs {{ $errorFr ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : '' }}"
                @if ($placeholderFr) placeholder="{{ $placeholderFr }}" @endif>{{ old($name.'.fr') }}</textarea>
            @error($name.'.fr') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            <p class="mt-1 text-[9px] italic text-outline dark:text-gray-500"
               x-show="frError" x-cloak>{{ __('lang.bilingual_failed') }}</p>
        </div>

        {{-- Arabic — drives French in AR ➜ FR mode --}}
        <div>
            <label for="{{ $uid }}-ar"
                class="mb-1 flex h-4 flex-nowrap items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider text-secondary dark:text-gray-400">
                <span>{{ __('lang.arabic') }} @if ($required) <span class="text-red-500">*</span> @endif</span>
                <span x-show="!paused && src === 'fr'" x-cloak
                    class="flex h-3 items-center gap-0.5 rounded-full bg-secondary/10 px-1.5 text-[8px] font-bold leading-none normal-case tracking-normal text-secondary dark:bg-white/10 dark:text-gray-300">
                    <span class="material-symbols-outlined text-[9px] leading-none" aria-hidden="true">lock</span>
                    auto
                </span>
                @if ($required)
                    {{-- Spinner while Arabic is being auto-filled from this side --}}
                    <span class="ml-auto flex items-center gap-1 normal-case tracking-normal"
                          x-show="busyAr" x-cloak>
                        <span class="inline-block h-3 w-3 animate-spin rounded-full border-[1.5px] border-secondary border-t-transparent"></span>
                        <span class="text-[9px] font-semibold">{{ __('lang.bilingual_working') }}</span>
                    </span>
                @endif
            </label>
            <textarea id="{{ $uid }}-ar" name="{{ $name }}[ar]" rows="{{ $rows }}" maxlength="{{ $maxlength }}"
                dir="rtl" @if ($required) required @endif
                x-ref="ar"
                @input="changed('ar', $event.target.value)"
                :readonly="!paused && src === 'fr'"
                :class="!paused && src === 'fr'
                    ? 'cursor-not-allowed bg-black/[0.03] opacity-70 dark:bg-white/[0.04]'
                    : ''"
                class="min-h-20 rounded-lg text-xs {{ $errorAr ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : '' }}"
                @if ($placeholderAr) placeholder="{{ $placeholderAr }}" @endif>{{ old($name.'.ar') }}</textarea>
            @error($name.'.ar') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            <p class="mt-1 text-[9px] italic text-outline dark:text-gray-500"
               x-show="arError" x-cloak>{{ __('lang.bilingual_failed') }}</p>
        </div>
    </div>
    @if ($required)
        <p class="mt-1 flex items-center gap-1 text-[10px] text-outline dark:text-gray-500">
            <span class="material-symbols-outlined text-xs" aria-hidden="true">translate</span>
            {{ __('lang.bilingual_auto_hint') }}
        </p>
    @endif
</div>