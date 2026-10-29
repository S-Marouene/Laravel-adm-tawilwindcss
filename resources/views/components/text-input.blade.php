@props(['disabled' => false, 'leadingIcon' => null, 'trailingIcon' => null])

@php
    // Propriétés logiques (padding-inline-start/end) : fonctionne tel quel
    // en LTR (français) et RTL (arabe). Style inline => imperméable aux
    // règles globales de app.css (le texte ne glisse plus sous les icônes).
    $inputStyles = collect([
        $leadingIcon ? 'padding-inline-start:2.5rem' : null,
        $trailingIcon ? 'padding-inline-end:2.5rem' : null,
    ])->filter()->implode(';');
@endphp

<div class="relative" x-data="{ show: false }">
    @if($leadingIcon)
        <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
            {!! $leadingIcon !!}
        </div>
    @endif

    <input @disabled($disabled) {{ $attributes->merge([
        'class' => 'block w-full px-3.5 py-2.5 text-body-md text-on-surface placeholder:text-secondary/70
                    bg-surface-container-lowest
                    border border-surface-dim
                    rounded
                    hover:border-surface-container-highest
                    focus:ring-0 focus:border-primary dark:focus:border-primary
                    focus:outline-none
                    disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-surface-container
                    transition-colors duration-150 ease-in-out',
        'style' => $inputStyles,
    ]) }}>

    @if($trailingIcon && $attributes->get('type') === 'password')
        <button type="button" @click="show = !show"
            class="absolute inset-y-0 end-0 pe-3 flex items-center text-outline hover:text-secondary dark:hover:text-gray-300 transition-colors">
            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
        </button>
    @elseif($trailingIcon)
        <div class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none text-outline">
            {!! $trailingIcon !!}
        </div>
    @endif
</div>
