@props(['disabled' => false])

<button @disabled($disabled) {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'relative inline-flex cursor-pointer items-center justify-center px-5 py-2.5
                bg-gradient-to-r from-red-600 via-red-700 to-rose-800 hover:from-red-700 hover:to-rose-900
                dark:from-red-500 dark:to-red-600 dark:hover:from-red-600 dark:hover:to-red-700
                rounded-xl font-semibold text-sm text-white
                shadow-sm hover:shadow-lg hover:-translate-y-0.5
                focus:outline-none focus:ring-2 focus:ring-red-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-800
                disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-none
                transition-all duration-200 ease-in-out active:scale-[0.98]'
]) }}>
    <span class="inline-flex items-center gap-2">
        {{ $slot }}
    </span>
</button>
