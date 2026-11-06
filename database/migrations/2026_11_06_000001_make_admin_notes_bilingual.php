<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make every admin-entered note bilingual (fr / ar).
 *
 * The note columns now store a JSON object {"fr": "...", "ar": "..."}.
 * Existing plain-text values are migrated into the "fr" key so nothing
 * is lost; models fall back to the other language / legacy text when a
 * translation is missing.
 */
return new class extends Migration
{
    /** @var array<string, string[]> table => note columns */
    private const COLUMNS = [
        'prevention_dossiers'                => ['admin_note'],
        'dossier_appointments'               => ['note'],
        'prevention_certificates'            => ['note'],
        'preventive_presence_requests'       => ['admin_note', 'service_note'],
        'preventive_presence_events'         => ['note'],
        'rescue_certificates'                => ['admin_note', 'delivery_note'],
        'rescue_events'                      => ['note'],
        'works_notifications'                => ['admin_note'],
        'works_events'                       => ['note'],
        'prevention_dossier_events'          => ['note'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                // 1. Wrap legacy plain-text values into {"fr": value}
                DB::table($table)
                    ->whereNotNull($column)
                    ->select($table.'.id', $column)
                    ->orderBy($table.'.id')
                    ->chunkById(500, function ($rows) use ($table, $column) {
                        foreach ($rows as $row) {
                            $raw = $row->{$column};

                            if ($raw === null || trim((string) $raw) === '' || $this->isAlreadyJson($raw)) {
                                continue;
                            }

                            DB::table($table)->where('id', $row->id)->update([
                                $column => json_encode(
                                    ['fr' => trim((string) $raw)],
                                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                ),
                            ]);
                        }
                    });

                // 2. Change the column type from TEXT (string) to JSON.
                DB::statement(sprintf(
                    'ALTER TABLE `%s` MODIFY `%s` JSON NULL',
                    $table,
                    $column
                ));
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                // Collapse {"fr": "...", "ar": "..."} back into plain text (fr, else ar).
                DB::table($table)
                    ->whereNotNull($column)
                    ->select($table.'.id', $column)
                    ->orderBy($table.'.id')
                    ->chunkById(500, function ($rows) use ($table, $column) {
                        foreach ($rows as $row) {
                            $decoded = json_decode((string) $row->{$column}, true);

                            if (! is_array($decoded)) {
                                continue;
                            }

                            $plain = $decoded['fr'] ?? $decoded['ar'] ?? '';

                            DB::table($table)->where('id', $row->id)->update([
                                $column => $plain !== '' ? $plain : null,
                            ]);
                        }
                    });

                DB::statement(sprintf(
                    'ALTER TABLE `%s` MODIFY `%s` TEXT NULL',
                    $table,
                    $column
                ));
            }
        }
    }

    /** True when the stored value is already a valid JSON object. */
    private function isAlreadyJson(mixed $raw): bool
    {
        $trimmed = trim((string) $raw);

        if (! str_starts_with($trimmed, '{')) {
            return false;
        }

        $decoded = json_decode($trimmed, true);

        return is_array($decoded);
    }
};
