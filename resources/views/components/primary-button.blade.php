@props(['disabled' => false])

<button @disabled($disabled) {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'ui-btn-primary cursor-pointer w-full px-5 py-2.5 text-body-sm
                focus:outline-none focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 dark:focus:ring-offset-gray-900
                disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-none
                transition-all duration-150 ease-in-out'
]) }}>
    <span class="inline-flex items-center gap-2">
        {{ $slot }}
    </span>
</button>
