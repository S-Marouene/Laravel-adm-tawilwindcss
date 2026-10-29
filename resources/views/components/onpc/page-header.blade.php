{{-- En-tête de page administratif/citoyen — titre + actions --}}
@props(['title' => '', 'subtitle' => null, 'icon' => null, 'actions' => null])

@php
$iconName = $icon ?: 'article';
@endphp

<header {{ $attributes->merge(['class' => 'mb-6 border-b border-surface-dim pb-5']) }}>
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div class="flex items-start gap-3 min-w-0">
            <span class="w-10 h-10 bg-primary text-on-primary rounded flex items-center justify-center shadow-sm shrink-0">
                <span class="material-symbols-outlined filled text-xl" aria-hidden="true">{{ $iconName }}</span>
            </span>
            <div class="min-w-0">
                <h1 class="text-headline-md text-on-surface tracking-tight truncate">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="text-body-sm text-secondary mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @if ($actions)
            <div class="flex flex-wrap items-center gap-2 shrink-0">{{ $actions }}</div>
        @endif
    </div>
</header>
