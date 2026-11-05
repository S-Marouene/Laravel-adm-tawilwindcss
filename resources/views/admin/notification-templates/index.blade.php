@extends('admin.layouts.admin')

@section('title', 'Modèles de notifications')

@section('content')

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">Modèles de notifications</h1>
        <p class="text-sm text-secondary dark:text-gray-400 mt-1">
            Personnalisez les messages envoyés aux usagers et aux administrateurs — en français et en arabe.<br>
            <span class="text-xs text-outline">Utilisez <code class="bg-surface-container px-1 rounded font-mono">:reference</code> et <code class="bg-surface-container px-1 rounded font-mono">:nom_prenom</code> comme variables.</span>
        </p>
    </div>
    <form method="POST" action="{{ route('admin.notification-templates.reset') }}" data-confirm data-confirm-style="warning" data-confirm-title="Réinitialiser tous les modèles ?" data-confirm-text="Cette action remplacera vos modifications par les textes par défaut.">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded border border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-700 transition-colors">
            <span class="material-symbols-outlined text-base">restart_alt</span>
            Réinitialiser tout
        </button>
    </form>
</div>

@php
$levelBadge = [
    'info'    => 'bg-secondary/10 text-secondary dark:text-gray-400',
    'success' => 'bg-primary/10 text-primary',
    'warning' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
    'danger'  => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
];
$levelIcon = [
    'info'    => 'info',
    'success' => 'check_circle',
    'warning' => 'warning',
    'danger'  => 'error',
];
@endphp

<div class="admin-table-card overflow-hidden">
    <table class="w-full">
        <thead>
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary">Événement</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary">Niveau</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary">Titre FR</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-secondary" dir="rtl">عنوان AR</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-secondary">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-surface-dim dark:divide-gray-700">
            @foreach($templates as $tpl)
            <tr class="hover:bg-surface-container dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-6 py-4">
                    <p class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ $tpl->eventLabel() }}</p>
                    <p class="text-xs font-mono text-outline mt-0.5">{{ $tpl->event_key }}</p>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold {{ $levelBadge[$tpl->level] ?? $levelBadge['info'] }}">
                        <span class="material-symbols-outlined text-[13px]">{{ $levelIcon[$tpl->level] ?? 'info' }}</span>
                        {{ $tpl->level }}
                    </span>
                </td>
                <td class="px-6 py-4 max-w-[200px]">
                    <p class="text-sm font-medium text-on-surface dark:text-gray-200 truncate">{{ $tpl->title_fr }}</p>
                    <p class="text-xs text-secondary dark:text-gray-400 truncate mt-0.5">{{ Str::limit($tpl->body_fr, 80) }}</p>
                </td>
                <td class="px-6 py-4 max-w-[200px]" dir="rtl">
                    <p class="text-sm font-medium text-on-surface dark:text-gray-200 truncate">{{ $tpl->title_ar }}</p>
                    <p class="text-xs text-secondary dark:text-gray-400 truncate mt-0.5">{{ Str::limit($tpl->body_ar, 80) }}</p>
                </td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('admin.notification-templates.edit', $tpl) }}" class="admin-action-link">
                        Modifier
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
