@props(['active'])

@php
$classes = ($active ?? false)
            ? 'ui-nav-link is-active inline-flex cursor-pointer items-center px-3 py-2 rounded-xl text-sm font-semibold leading-5 text-gov-700 dark:text-gov-200 bg-gov-50 dark:bg-gov-900/35 border border-gov-200/80 dark:border-gov-700/70 shadow-sm focus:outline-none focus:ring-2 focus:ring-gov-500/30 transition-all duration-200 ease-in-out'
            : 'ui-nav-link inline-flex cursor-pointer items-center px-3 py-2 rounded-xl text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gov-700 dark:hover:text-gov-300 hover:bg-sand-50 dark:hover:bg-gray-700/50 focus:outline-none focus:ring-2 focus:ring-gov-500/30 transition-all duration-200 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
