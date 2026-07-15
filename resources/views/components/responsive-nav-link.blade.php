@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full cursor-pointer ps-3 pe-4 py-2.5 rounded-xl border-l-4 border-gov-500 text-start text-base font-semibold text-gov-700 dark:text-gov-200 bg-gov-50 dark:bg-gov-900/35 focus:outline-none focus:ring-2 focus:ring-gov-500/30 transition-all duration-200 ease-in-out'
            : 'block w-full cursor-pointer ps-3 pe-4 py-2.5 rounded-xl border-l-4 border-transparent text-start text-base font-medium text-gray-600 dark:text-gray-300 hover:text-gov-700 dark:hover:text-gov-300 hover:bg-sand-50 dark:hover:bg-gray-700/50 hover:border-gov-200 dark:hover:border-gov-700 focus:outline-none focus:ring-2 focus:ring-gov-500/30 transition-all duration-200 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
