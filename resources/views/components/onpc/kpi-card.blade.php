{{-- Carte KPI du tableau de bord — design/admin.html --}}
@props([
    'label' => '',
    'value' => '',
    'hint' => null,
    'icon' => 'dashboard',
    'tone' => 'primary', // primary | neutral | success | warning
    'footer' => null,
])

@php
$tones = [
    'primary' => 'bg-primary/10 text-primary',
    'neutral' => 'bg-surface-container text-on-surface',
    'success' => 'bg-emerald-50 text-emerald-700',
    'warning' => 'bg-amber-50 text-amber-800',
];
@endphp

<div class="onpc-kpi {{ $attributes->class([]) }}">
    <div class="flex justify-between items-start gap-2">
        <span class="text-label-caps font-label-caps text-secondary uppercase font-bold tracking-wider">{{ $label }}</span>
        <span class="p-1.5 rounded {{ $tones[$tone] }}">
            <span class="material-symbols-outlined text-xl" aria-hidden="true">{{ $icon }}</span>
        </span>
    </div>
    <div class="mt-2 flex items-baseline gap-2">
        <span class="text-headline-lg font-black text-on-surface">{{ $value }}</span>
        {{ $hint }}
    </div>
    @if ($footer)
        <div class="mt-3 flex items-center justify-between text-[11px] text-secondary border-t border-surface-dim pt-2">
            {{ $footer }}
        </div>
    @elseif(!empty($footerText ?? ''))
        <div class="mt-3 text-[11px] text-secondary border-t border-surface-dim pt-2">{{ $footerText }}</div>
    @endif
    @isset($footerSlot)
        <div class="mt-3 grid grid-cols-2 gap-1 text-[11px] text-secondary border-t border-surface-dim pt-2">{{ $footerSlot }}</div>
    @endisset
</div>
