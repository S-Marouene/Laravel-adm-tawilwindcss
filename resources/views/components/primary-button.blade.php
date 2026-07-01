@props(['disabled' => false])

<button @disabled($disabled) {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'relative inline-flex items-center justify-center w-full px-5 py-2.5
                bg-gradient-to-r from-gov-600 to-gov-700 hover:from-gov-700 hover:to-gov-800
                dark:from-gov-500 dark:to-gov-600 dark:hover:from-gov-600 dark:hover:to-gov-700
                rounded-xl font-semibold text-sm text-white
                shadow-sm hover:shadow-md
                focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-800
                disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-none
                transition-all duration-200 ease-in-out active:scale-[0.98]'
]) }}>
    <span class="inline-flex items-center gap-2">
        {{ $slot }}
    </span>
</button>
