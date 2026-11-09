@extends('admin.layouts.admin')
@section('title', __('admin.translations'))

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- ── En-tête ── --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100 flex items-center gap-2">
                <span class="material-symbols-outlined text-gov-600 text-2xl" aria-hidden="true">translate</span>
                {{ __('admin.translations') }}
            </h1>
            <p class="text-sm text-secondary dark:text-gray-400 mt-1">{{ __('admin.translations_desc') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('admin.translations.import') }}" data-confirm
                  data-confirm-title="{{ __('admin.translations_import_confirm') }}"
                  data-confirm-text="{{ __('admin.translations_import_hint') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border border-surface-dim dark:border-gray-600 bg-surface-container-lowest dark:bg-gray-900/50 text-on-surface dark:text-gray-100 rounded-xl hover:bg-surface-container dark:hover:bg-gray-700 transition-colors">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">cloud_download</span>
                    {{ __('admin.translations_import') }}
                </button>
            </form>
            <button type="button" @click="newKey = !newKey"
                class="admin-add-button px-4 py-2 text-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.translations_add_key') }}
            </button>
        </div>
    </div>

    <div x-data="{ newKey: false }">
        {{-- ── Nouvelle clé (repliable) ── --}}
        <div x-show="newKey" x-cloak class="admin-form-card mb-6" x-transition>
            <h2 class="text-sm font-bold text-on-surface dark:text-gray-100 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-gov-600 text-base" aria-hidden="true">add_circle</span>
                {{ __('admin.translations_add_key') }}
            </h2>
            <form method="POST" action="{{ route('admin.translations.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">{{ __('admin.translations_group') }} <span class="text-red-500">*</span></label>
                        <select name="group" required
                            class="block w-full px-4 py-2.5 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
                            @foreach ($groups as $g)
                                <option value="{{ $g }}" {{ $g === $group ? 'selected' : '' }}>{{ $g }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-secondary dark:text-gray-500 mt-1">{{ __('admin.translations_group_hint') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">{{ __('admin.translations_key') }} <span class="text-red-500">*</span></label>
                        <input type="text" name="key" required value="{{ old('key') }}" placeholder="my_new_key"
                            class="block w-full px-4 py-2.5 text-sm font-mono bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
                        <p class="text-[11px] text-secondary dark:text-gray-500 mt-1">{{ __('admin.translations_key_hint') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">FR</label>
                        <textarea name="fr" rows="2" class="block w-full px-4 py-2.5 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">{{ old('fr') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">AR</label>
                        <textarea name="ar" rows="2" dir="rtl" class="block w-full px-4 py-2.5 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">{{ old('ar') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-secondary dark:text-gray-400 mb-1">EN</label>
                        <textarea name="en" rows="2" class="block w-full px-4 py-2.5 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">{{ old('en') }}</textarea>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="admin-add-button px-6 py-2.5 text-sm">{{ __('action.add') }}</button>
                </div>
            </form>
        </div>

        {{-- ── Onglets de groupes (fichiers de langue) ── --}}
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach ($groupCounts as $g => $count)
                <a href="{{ route('admin.translations.index', ['group' => $g]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border transition-colors {{ $g === $group
                       ? 'bg-gov-600 text-white border-gov-600'
                       : 'bg-surface-container-lowest dark:bg-gray-900/50 text-secondary dark:text-gray-300 border-surface-dim dark:border-gray-600 hover:bg-surface-container dark:hover:bg-gray-700' }}">
                    <span class="font-mono">{{ $g }}</span>
                    <span class="opacity-70">{{ $count }}</span>
                </a>
            @endforeach
        </div>

        {{-- ── Recherche ── --}}
        <form method="GET" action="{{ route('admin.translations.index') }}" class="mb-4 flex gap-3">
            <input type="hidden" name="group" value="{{ $group }}">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('admin.translations_search') }}"
                class="flex-1 px-4 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">
            <button type="submit" class="px-4 py-2 text-sm font-semibold bg-gov-600 text-white rounded-xl hover:bg-gov-700">{{ __('action.search') }}</button>
        </form>

        {{-- ── Tableau du dictionnaire ── --}}
        <div class="admin-table-card">
            <div class="px-6 py-4 border-b border-surface-dim dark:border-gray-700 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-on-surface dark:text-gray-100 font-mono">{{ $group }}.*</h2>
                    <p class="text-xs text-secondary dark:text-gray-400 mt-0.5">
                        {{ $translations->total() }} {{ __('admin.translations_keys') }}
                        · {{ __('admin.translations_fallback_hint') }}
                    </p>
                </div>
                <span class="admin-badge bg-gov-50 dark:bg-gov-900/40 text-gov-700 dark:text-gov-300">
                    {{ __('admin.translations_page', ['from' => $translations->firstItem() ?? 0, 'to' => $translations->lastItem() ?? 0, 'total' => $translations->total()]) }}
                </span>
            </div>

            <div class="overflow-x-auto">
                @forelse ($translations as $t)
                <div class="border-b border-surface-dim dark:border-gray-700 last:border-b-0">
                    <form method="POST" action="{{ route('admin.translations.update', $t) }}" class="p-4 md:px-6 md:py-4 hover:bg-surface-container/40 dark:hover:bg-gray-700/20 transition-colors">
                        @csrf @method('PUT')
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="text-xs font-bold font-mono text-on-surface dark:text-gray-100">{{ $t->key }}</span>
                            @if ($t->isCustomized())
                                <span class="admin-badge bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">{{ __('admin.translations_customized') }}</span>
                            @endif
                            @if ($t->isIncomplete())
                                <span class="admin-badge bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">{{ __('admin.translations_incomplete') }}</span>
                            @endif
                            <span class="flex-1"></span>
                            <button type="submit" title="{{ __('action.save') }}"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-white bg-gov-600 hover:bg-gov-700 rounded-lg transition-colors">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">save</span>
                                {{ __('action.save') }}
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary dark:text-gray-500 mb-1">FR</label>
                                <textarea name="fr" rows="2"
                                    class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">{{ $t->fr }}</textarea>
                            </div>
                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary dark:text-gray-500 mb-1">AR</label>
                                <textarea name="ar" rows="2" dir="rtl"
                                    class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">{{ $t->ar }}</textarea>
                            </div>
                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary dark:text-gray-500 mb-1">EN</label>
                                <textarea name="en" rows="2"
                                    class="block w-full px-3 py-2 text-sm bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gov-500/30 dark:text-gray-100">{{ $t->en }}</textarea>
                            </div>
                        </div>
                    </form>
                </div>
                @empty
                <div class="px-6 py-12 text-center text-secondary dark:text-gray-400">
                    <span class="material-symbols-outlined text-3xl text-surface-dim block mx-auto mb-2" aria-hidden="true">translate</span>
                    {{ __('admin.translations_empty') }}
                </div>
                @endforelse
            </div>

            @if ($translations->hasPages())
                <div class="px-6 py-4 border-t border-surface-dim dark:border-gray-700">{{ $translations->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
