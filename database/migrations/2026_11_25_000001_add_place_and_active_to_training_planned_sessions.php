<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A centralized session now has a physical place (one of the governorates,
 * Ben Arous by default) and an on/off switch for citizen submissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        $hasGovernorateColumn = Schema::hasColumn('training_planned_sessions', 'governorate_id');
        $hasActiveColumn      = Schema::hasColumn('training_planned_sessions', 'is_active');

        Schema::table('training_planned_sessions', function (Blueprint $table) use ($hasGovernorateColumn, $hasActiveColumn) {
            if (! $hasGovernorateColumn) {
                // governorates.id is an unsignedTinyInteger (1–24).
                $table->unsignedTinyInteger('governorate_id')->nullable()->after('ends_at')
                    ->references('id')->on('governorates')->nullOnDelete();
            }

            if (! $hasActiveColumn) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });

        // A previous partial MySQL run may have added the column without its
        // foreign key. Add it only when the constraint is actually missing.
        if ($hasGovernorateColumn && DB::getDriverName() === 'mysql') {
            $constraintExists = DB::selectOne(
                'SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = ?
                 LIMIT 1',
                ['training_planned_sessions', 'training_planned_sessions_governorate_id_foreign']
            );

            if (! $constraintExists) {
                // Normalize the column left by the first (failed) migration:
                // foreignId() created a BIGINT while governorates.id is TINYINT.
                DB::statement('ALTER TABLE training_planned_sessions MODIFY governorate_id TINYINT UNSIGNED NULL');

                Schema::table('training_planned_sessions', function (Blueprint $table) {
                    $table->foreign('governorate_id')->references('id')->on('governorates')->nullOnDelete();
                });
            }
        }

        // Existing sessions were all held in Ben Arous. Only backfill when the
        // reference governorate actually exists (it is seeded after migrations).
        $benArousId = DB::table('governorates')
            ->where('name_fr', 'Direction régionale de Ben Arous')
            ->orWhere('name_en', 'Regional Directorate of Ben Arous')
            ->orWhere('name_ar', 'الإدارة الجهوية ببن عروس')
            ->value('id');

        if ($benArousId) {
            DB::table('training_planned_sessions')
                ->whereNull('governorate_id')
                ->update(['governorate_id' => $benArousId]);
        }
    }

    public function down(): void
    {
        Schema::table('training_planned_sessions', function (Blueprint $table) {
            $table->dropForeign(['governorate_id']);
            $table->dropColumn(['governorate_id', 'is_active']);
        });
    }
};
