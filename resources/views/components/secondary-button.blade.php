@props(['disabled' => false])

<button @disabled($disabled) {{ $attributes->merge([
    'type' => 'button',
    'class' => 'relative inline-flex items-center justify-center px-5 py-2.5
                bg-white dark:bg-gray-800
                border border-gray-300 dark:border-gray-600
                rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300
                shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700/50
                focus:outline-none focus:ring-2 focus:ring-gov-500/30 focus:ring-offset-2 dark:focus:ring-offset-gray-800
                disabled:opacity-50 disabled:cursor-not-allowed
                transition-all duration-200 ease-in-out active:scale-[0.98]'
]) }}>
    <span class="inline-flex items-center gap-2">
        {{ $slot }}
    </span>
</button>
