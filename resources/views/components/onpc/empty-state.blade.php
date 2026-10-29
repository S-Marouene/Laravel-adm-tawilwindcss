{{-- État vide ONPC — utilisé dans les listes sans contenu --}}
@props([
    'title' => null,
    'description' => null,
    'icon' => 'inbox',
    'action' => null,
    'label' => null,
    'href' => null,
])

<div class="text-center py-12 px-6 bg-surface-container-low border border-dashed border-surface-dim rounded {{ $attributes->class([]) }}">
    <span class="material-symbols-outlined text-4xl text-outline mx-auto mb-3" aria-hidden="true">{{ $icon }}</span>
    <h3 class="text-headline-sm text-on-surface">{{ $title ?? (app()->getLocale() === 'ar' ? 'لا يوجد محتوى بعد' : 'Aucun contenu pour le moment') }}</h3>
    <p class="text-body-sm text-secondary mt-1.5 max-w-sm mx-auto">{{ $description ?? (app()->getLocale() === 'ar' ? 'ابدأ الآن لعرض عناصر هذه القائمة.' : 'Démarrez une action pour afficher le contenu de cette liste.') }}</p>
    @if ($action && $href)
        <a href="{{ $href }}" class="inline-flex items-center gap-2 mt-5 px-4 py-2.5 ui-btn-primary text-body-sm">
            {{ $label ?? $action }}
        </a>
    @endif
</div>
