{{-- Insigne de statut ONPC — variants: neutral, info, success, warning, error, primary --}}
@props(['variant' => 'neutral', 'icon' => null])

@php
$variants = [
    'neutral' => 'text-secondary bg-surface-container border-surface-dim',
    'info'    => 'text-primary bg-primary/10 border-primary/20',
    'success' => 'text-emerald-800 bg-emerald-50 border-emerald-300',
    'warning' => 'text-amber-800 bg-amber-50 border-amber-200',
    'error'   => 'text-error bg-error-container border-error/20',
    'primary' => 'text-on-primary bg-primary border-primary/30',
];
$icons = [
    'neutral' => 'info',
    'info'    => 'schedule',
    'success' => 'check_circle',
    'warning' => 'warning',
    'error'   => 'error',
    'primary' => 'star',
];
$iconName = $icon ?: ($icons[$variant] ?? 'info');
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded border' . ($variant === 'neutral' ? '' : '') . ' ' . $variants[$variant]]) }}>
@if ($variant !== 'neutral' || $icon)
    <span class="material-symbols-outlined text-[13px]" aria-hidden="true">{{ $iconName }}</span>
@endif
    {{ $slot }}
</span>
