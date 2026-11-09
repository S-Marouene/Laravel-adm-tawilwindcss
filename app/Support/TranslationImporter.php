<?php

namespace App\Support;

use App\Models\Translation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

/**
 * Imports every translation of the static lang/{fr,ar,en}/*.php files into
 * the `translations` table — the initial content of the dictionary.
 *
 * The import never overwrites an existing row (first-insert wins), so it
 * can be re-run safely after administrators started editing: their custom
 * texts are preserved and only genuinely new keys are added.
 */
class TranslationImporter
{
    /**
     * Import all lang files.
     *
     * @return array{imported: int, skipped: int, groups: int}
     */
    public static function importFromFiles(): array
    {
        // group.key => ['fr' => …, 'ar' => …, 'en' => …]
        $dictionary = [];

        foreach (Translation::LOCALES as $locale) {
            $directory = lang_path($locale);

            if (! is_dir($directory)) {
                continue;
            }

            foreach (File::files($directory) as $file) {
                $group = $file->getFilenameWithoutExtension();

                if (! preg_match('/^[a-z0-9_-]+$/', $group)) {
                    continue;
                }

                foreach (Arr::dot(require $file->getRealPath()) as $key => $text) {
                    // Structured arrays (e.g. service_details.php content) are
                    // not translations — they stay served by the files only.
                    if (! is_string($text)) {
                        continue;
                    }

                    // Skip auto-generated numeric segments (services.0.text…)
                    if (preg_match('/(^|\.)\d+(\.|$)/', $key)) {
                        continue;
                    }

                    $dictionary[$group][$key][$locale] = $text;
                }
            }
        }

        // One query for the whole table — the import is idempotent.
        $existing = Translation::query()->get(['group', 'key'])
            ->map(fn ($t) => $t->group . '|' . $t->key)
            ->flip()
            ->all();

        $imported = 0;
        $skipped = 0;

        foreach ($dictionary as $group => $keys) {
            $rows = [];

            foreach ($keys as $key => $locales) {
                if (isset($existing["$group|$key"])) {
                    $skipped++;
                    continue;
                }

                $rows[] = [
                    'group' => $group,
                    'key'   => $key,
                    'fr'    => $locales['fr'] ?? null,
                    'ar'    => $locales['ar'] ?? null,
                    'en'    => $locales['en'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($rows !== []) {
                foreach (array_chunk($rows, 200) as $chunk) {
                    Translation::insert($chunk);
                }
                $imported += count($rows);
            }

            Translation::flushGroupCache($group);
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'groups' => count($dictionary)];
    }
}
