@props(['disabled' => false])

<button @disabled($disabled) {{ $attributes->merge([
    'type' => 'button',
    'class' => 'relative inline-flex cursor-pointer items-center justify-center px-5 py-2.5
                bg-white/90 dark:bg-gray-800/90
                border border-gray-300/80 dark:border-gray-600/80
                rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300
                shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-gov-300 dark:hover:border-gov-600 hover:bg-sand-50 dark:hover:bg-gray-700/60
                focus:outline-none focus:ring-2 focus:ring-gov-500/30 focus:ring-offset-2 dark:focus:ring-offset-gray-800
                disabled:opacity-50 disabled:cursor-not-allowed
                transition-all duration-200 ease-in-out active:scale-[0.98]'
]) }}>
    <span class="inline-flex items-center gap-2">
        {{ $slot }}
    </span>
</button>
