<?php

namespace Database\Seeders;

use App\Support\TranslationImporter;
use Illuminate\Database\Seeder;

/**
 * Fills the translations dictionary with every key of the static
 * lang/{fr,ar,en} files — the starting point of the editable dictionary.
 * Re-running it only adds new keys (existing rows are never overwritten).
 */
class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        $result = TranslationImporter::importFromFiles();

        $this->command->info("Translations: {$result['imported']} imported, {$result['skipped']} already present (kept), {$result['groups']} groups.");
    }
}
