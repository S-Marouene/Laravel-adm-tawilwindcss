<?php

namespace App\Support;

use App\Models\Translation;
use Illuminate\Contracts\Translation\Loader as LoaderContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Translation loader that overlays the database dictionary on top of the
 * static lang/{locale}/{group}.php files.
 *
 * It decorates the default FileLoader (registered as `translation.loader`
 * in AppServiceProvider): the file lines are loaded first, then any row
 * found in the `translations` table replaces the file value, key by key.
 * The dictionary is therefore a pure override layer — a row missing, or a
 * text left empty, falls back to the original file translation.
 *
 * DB lines are cached forever per (locale, group); the Translation model
 * flushes the cache whenever an administrator edits an entry.
 */
class DatabaseTranslationLoader implements LoaderContract
{
    public function __construct(protected LoaderContract $loader)
    {
    }

    public function load($locale, $group, $namespace = null): array
    {
        $lines = $this->loader->load($locale, $group, $namespace);

        // Plain groups only ('prevention', 'admin'…) — never JSON ('*') or
        // namespaced groups, which the dictionary does not manage.
        if ($namespace === '*' && $group !== '*') {
            $overrides = $this->databaseLines($locale, $group);

            if ($overrides !== []) {
                $lines = array_replace($lines, $overrides);
            }
        }

        return $lines;
    }

    /** Cached DB overrides for one group: [key => text] with non-empty texts only. */
    protected function databaseLines(string $locale, string $group): array
    {
        try {
            return Cache::rememberForever("translations:$locale:$group", function () use ($locale, $group) {
                if (! Schema::hasTable('translations')) {
                    return [];
                }

                return Translation::query()
                    ->where('group', $group)
                    ->pluck($locale, 'key')
                    ->filter(fn ($text) => Translation::clean($text) !== null)
                    ->all();
            });
        } catch (\Throwable) {
            // During bootstrap / before the migration runs — serve files only.
            return [];
        }
    }

    public function addNamespace($namespace, $hints): void
    {
        $this->loader->addNamespace($namespace, $hints);
    }

    public function addJsonPath($path): void
    {
        $this->loader->addJsonPath($path);
    }

    public function namespaces(): array
    {
        return $this->loader->namespaces();
    }
}
