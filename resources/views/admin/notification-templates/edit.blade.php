@extends('admin.layouts.admin')

@section('title', 'Modifier le modèle — ' . $template->eventLabel())

@section('content')

<div class="mb-6 flex items-center gap-3">
    <a href="{{ route('admin.notification-templates.index') }}" class="inline-flex items-center gap-1.5 text-sm text-secondary hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-base">arrow_back</span>
        Modèles de notifications
    </a>
    <span class="text-outline">/</span>
    <span class="text-sm font-semibold text-on-surface">{{ $template->eventLabel() }}</span>
</div>

@php
$levelColors = [
    'info'    => 'bg-secondary/10 text-secondary',
    'success' => 'bg-primary/10 text-primary',
    'warning' => 'bg-amber-100 text-amber-700',
    'danger'  => 'bg-red-100 text-red-600',
];
@endphp

<div class="max-w-4xl">
    {{-- Info banner --}}
    <div class="mb-5 rounded-lg border border-surface-dim bg-surface-container-low p-4 text-sm text-secondary dark:bg-gray-800 dark:border-gray-700">
        <p class="font-semibold text-on-surface dark:text-gray-200 mb-1 flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-primary">info</span>
            Variables disponibles
        </p>
        <p>Utilisez <code class="bg-surface-container dark:bg-gray-700 px-1.5 py-0.5 rounded font-mono text-xs">:reference</code> pour la référence du dossier et <code class="bg-surface-container dark:bg-gray-700 px-1.5 py-0.5 rounded font-mono text-xs">:nom_prenom</code> pour le nom du demandeur.</p>
    </div>

    <form method="POST" action="{{ route('admin.notification-templates.update', $template) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Level selector --}}
        <div class="bg-surface-container-lowest dark:bg-gray-800 border border-surface-dim dark:border-gray-700 rounded-xl p-5">
            <label class="block text-xs font-extrabold uppercase tracking-wider text-secondary mb-3">Niveau / Icône</label>
            <div class="flex flex-wrap gap-2">
                @foreach(['info' => 'Informatif', 'success' => 'Succès', 'warning' => 'Avertissement', 'danger' => 'Danger'] as $val => $label)
                <label class="cursor-pointer">
                    <input type="radio" name="level" value="{{ $val }}" {{ $template->level === $val ? 'checked' : '' }} class="sr-only peer">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border-2 transition-all
                        peer-checked:border-primary peer-checked:bg-primary/10
                        border-surface-dim bg-surface-container hover:border-primary/40
                        {{ $levelColors[$val] ?? '' }}">
                        {{ $label }}
                    </span>
                </label>
                @endforeach
            </div>
            @error('level') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- French --}}
        <div class="bg-surface-container-lowest dark:bg-gray-800 border border-surface-dim dark:border-gray-700 rounded-xl overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-3 border-b border-surface-dim dark:border-gray-700 bg-surface-container-low dark:bg-gray-700/40">
                <span class="text-lg">🇫🇷</span>
                <span class="text-sm font-bold text-on-surface dark:text-gray-200">Français</span>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-secondary mb-1.5" for="title_fr">
                        Titre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title_fr" name="title_fr" required maxlength="255"
                           value="{{ old('title_fr', $template->title_fr) }}"
                           class="w-full h-10 px-3.5 bg-surface-container-lowest border border-surface-dim rounded text-sm text-on-surface focus:outline-none focus:border-primary dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100"
                           placeholder="Ex: Dossier :reference approuvé">
                    @error('title_fr') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-secondary mb-1.5" for="body_fr">
                        Message <span class="text-red-500">*</span>
                    </label>
                    <textarea id="body_fr" name="body_fr" required maxlength="1000" rows="3"
                              class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-surface-dim rounded text-sm text-on-surface focus:outline-none focus:border-primary dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100 resize-none"
                              placeholder="Ex: Votre dossier :reference a été approuvé par l'administration.">{{ old('body_fr', $template->body_fr) }}</textarea>
                    @error('body_fr') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Arabic --}}
        <div class="bg-surface-container-lowest dark:bg-gray-800 border border-surface-dim dark:border-gray-700 rounded-xl overflow-hidden" dir="rtl">
            <div class="flex items-center gap-2 px-5 py-3 border-b border-surface-dim dark:border-gray-700 bg-surface-container-low dark:bg-gray-700/40">
                <span class="text-lg">🇹🇳</span>
                <span class="text-sm font-bold text-on-surface dark:text-gray-200">العربية</span>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-secondary mb-1.5" for="title_ar">
                        العنوان <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title_ar" name="title_ar" required maxlength="255"
                           value="{{ old('title_ar', $template->title_ar) }}"
                           class="w-full h-10 px-3.5 bg-surface-container-lowest border border-surface-dim rounded text-sm text-on-surface focus:outline-none focus:border-primary dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100"
                           placeholder="مثال: تمت الموافقة على الملف :reference">
                    @error('title_ar') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-secondary mb-1.5" for="body_ar">
                        نص الرسالة <span class="text-red-500">*</span>
                    </label>
                    <textarea id="body_ar" name="body_ar" required maxlength="1000" rows="3"
                              class="w-full px-3.5 py-2.5 bg-surface-container-lowest border border-surface-dim rounded text-sm text-on-surface focus:outline-none focus:border-primary dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100 resize-none"
                              placeholder="مثال: تمت الموافقة على ملفكم :reference من قِبل الإدارة.">{{ old('body_ar', $template->body_ar) }}</textarea>
                    @error('body_ar') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.notification-templates.index') }}"
               class="px-5 py-2.5 text-sm font-semibold rounded border border-surface-dim bg-surface-container text-secondary hover:bg-surface-container-high dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 transition-colors">
                Annuler
            </a>
            <button type="submit"
                    class="px-5 py-2.5 text-sm font-bold rounded bg-primary hover:bg-primary-container text-on-primary transition-colors shadow-sm">
                <span class="material-symbols-outlined text-base align-middle me-1">save</span>
                Enregistrer
            </button>
        </div>
    </form>
</div>

@endsection
