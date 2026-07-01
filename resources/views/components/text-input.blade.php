@props(['disabled' => false, 'leadingIcon' => null, 'trailingIcon' => null])

<div class="relative" x-data="{ show: false }">
    @if($leadingIcon)
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            {!! $leadingIcon !!}
        </div>
    @endif

    <input @disabled($disabled) {{ $attributes->merge([
        'class' => 'block w-full px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500
                    bg-white dark:bg-gray-900/50
                    border border-gray-300 dark:border-gray-600
                    rounded-xl shadow-sm
                    focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 dark:focus:border-gov-400
                    focus:outline-none
                    disabled:opacity-50 disabled:cursor-not-allowed
                    transition-all duration-200 ease-in-out
                    ' . ($leadingIcon ? 'pl-10' : '')
                    . ' ' . ($trailingIcon ? 'pr-10' : '')]) }}>

    @if($trailingIcon && $attributes->get('type') === 'password')
        <button type="button" @click="show = !show"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
        </button>
    @elseif($trailingIcon)
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
            {!! $trailingIcon !!}
        </div>
    @endif
</div>
