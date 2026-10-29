@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex cursor-pointer items-center border-b-2 border-primary text-primary font-bold pb-2 pt-1 text-body-md leading-5 focus:outline-none transition-colors duration-150'
            : 'inline-flex cursor-pointer items-center text-on-surface font-medium leading-5 text-body-md hover:text-primary transition-colors duration-150 pb-2 pt-1';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
