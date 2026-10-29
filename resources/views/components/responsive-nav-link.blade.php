@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full cursor-pointer ps-3 pe-4 py-2.5 rounded bg-primary text-start text-body-sm font-bold text-on-primary focus:outline-none transition-colors'
            : 'block w-full cursor-pointer ps-3 pe-4 py-2.5 rounded text-start text-body-sm font-medium text-on-surface hover:bg-surface-container hover:text-primary focus:outline-none transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
