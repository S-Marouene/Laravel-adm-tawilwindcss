@props(['disabled' => false])

<button @disabled($disabled) {{ $attributes->merge([
    'type' => 'button',
    'class' => 'relative inline-flex cursor-pointer items-center justify-center gap-2 px-4 py-2.5
                bg-surface-container-lowest
                border border-surface-dim
                rounded font-bold text-body-sm text-on-surface
                hover:bg-surface-container hover:border-surface-container-highest
                focus:outline-none focus:ring-2 focus:ring-primary/30
                disabled:opacity-50 disabled:cursor-not-allowed
                transition-colors duration-150 ease-in-out active:scale-[0.99]'
]) }}>
    <span class="inline-flex items-center gap-2">
        {{ $slot }}
    </span>
</button>
