<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use App\Services\ActivityLogger;
use App\Support\TranslationImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Editable FR / AR / EN translations dictionary.
 *
 * The `translations` table is an override layer on top of the lang/ files
 * (see App\Support\DatabaseTranslationLoader): the editor lists every group
 * (lang file) with its keys, administrators edit the three texts in place
 * and the change is served immediately (cache is flushed by the model).
 */
class TranslationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage translations');
        // ── Groups (lang files) with their row counts ──
        $groupCounts = Translation::query()
            ->selectRaw('`group`, count(*) as total')
            ->groupBy('group')
            ->orderBy('group')
            ->pluck('total', 'group');

        $groups = $groupCounts->keys();
        $group  = $request->filled('group') && $groups->contains($request->get('group'))
            ? $request->get('group')
            : $groups->first();

        // ── Rows of the selected group, with search across key + 3 texts ──
        $query = Translation::query()
            ->where('group', $group)
            ->orderBy('key');

        if ($q = trim((string) $request->get('q'))) {
            $query->where(function ($w) use ($q) {
                $w->where('key', 'like', "%{$q}%")
                    ->orWhere('fr', 'like', "%{$q}%")
                    ->orWhere('ar', 'like', "%{$q}%")
                    ->orWhere('en', 'like', "%{$q}%");
            });
        }

        $translations = $query->paginate(50)->appends($request->only(['q', 'group']));

        return view('admin.translations.index', compact('groupCounts', 'groups', 'group', 'translations'));
    }

    /** Save the three texts of one key (inline form in the table row). */
    public function update(Request $request, Translation $translation): RedirectResponse
    {
        $this->authorize('manage translations');
        $validated = $request->validate([
            'fr' => ['nullable', 'string', 'max:10000'],
            'ar' => ['nullable', 'string', 'max:10000'],
            'en' => ['nullable', 'string', 'max:10000'],
        ]);

        $translation->update([
            'fr' => Translation::clean($validated['fr'] ?? null),
            'ar' => Translation::clean($validated['ar'] ?? null),
            'en' => Translation::clean($validated['en'] ?? null),
            'updated_by' => auth()->id(),
        ]);

        ActivityLogger::log('translation_update', "Translations — key '{$translation->group}.{$translation->key}' updated by " . auth()->user()->name, $translation, [
            'group' => $translation->group,
            'key'   => $translation->key,
        ]);

        return redirect()
            ->route('admin.translations.index', array_filter(['group' => $translation->group, 'q' => $request->get('q'), 'page' => $request->get('page')]))
            ->with('success', __('admin.translations_updated'));
    }

    /** Add a custom key to the dictionary (never overrides anything — new row). */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage translations');
        $validated = $request->validate([
            'group' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/'],
            'key'   => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'fr'    => ['nullable', 'string', 'max:10000'],
            'ar'    => ['nullable', 'string', 'max:10000'],
            'en'    => ['nullable', 'string', 'max:10000'],
        ], [
            'group.regex' => __('admin.translations_group_invalid'),
            'key.regex'   => __('admin.translations_key_invalid'),
        ]);

        $exists = Translation::query()
            ->where('group', $validated['group'])
            ->where('key', $validated['key'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['key' => __('admin.translations_key_exists')]);
        }

        $translation = Translation::create($validated + ['updated_by' => auth()->id()]);

        ActivityLogger::log('translation_create', "Translations — custom key '{$translation->group}.{$translation->key}' created by " . auth()->user()->name, $translation, [
            'group' => $translation->group,
            'key'   => $translation->key,
        ]);

        return redirect()
            ->route('admin.translations.index', ['group' => $translation->group])
            ->with('success', __('admin.translations_key_created'));
    }

    /** Remove the DB row — the key falls back to its lang/ file value. */
    public function destroy(Request $request, Translation $translation): RedirectResponse
    {
        $this->authorize('manage translations');
        ActivityLogger::log('translation_delete', "Translations — key '{$translation->group}.{$translation->key}' removed by " . auth()->user()->name . ' (falls back to lang file)', $translation, [
            'group' => $translation->group,
            'key'   => $translation->key,
        ]);

        $group = $translation->group;
        $translation->delete();

        return redirect()
            ->route('admin.translations.index', array_filter(['group' => $group, 'q' => $request->get('q')]))
            ->with('success', __('admin.translations_deleted'));
    }

    /** One-click import of the lang/ files (only missing keys are added). */
    public function import(): RedirectResponse
    {
        $this->authorize('manage translations');
        $result = TranslationImporter::importFromFiles();

        ActivityLogger::log('translation_import', 'Translations — imported ' . $result['imported'] . ' keys from lang/ files by ' . auth()->user()->name, null, $result);

        return redirect()
            ->route('admin.translations.index')
            ->with('success', __('admin.translations_imported', ['imported' => $result['imported'], 'skipped' => $result['skipped']]));
    }
}
