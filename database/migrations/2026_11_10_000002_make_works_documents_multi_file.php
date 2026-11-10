<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow multiple files per (works notification, document type) — same model
 * as `dossier_documents` (prevention) and `rescue_documents`: each upload
 * creates a new row, and a replaced file stays on disk with status = 'archived'
 * for history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('building_building_building_building_works_documents', function (Blueprint $table) {
            // MySQL keeps FK constraints backed by the unique index — drop them first
            $table->dropForeign(['works_notification_id']);
            $table->dropForeign(['document_type_id']);

            // Multiple files per (notification, document type) are allowed — no unique constraint
            $table->dropUnique('building_building_building_building_works_documents_works_notification_id_document_type_id_unique');
            $table->index('works_notification_id');

            $table->foreign('works_notification_id')->references('id')->on('building_works_declarations')->onDelete('cascade');
            $table->foreign('document_type_id')->references('id')->on('document_types')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Archiving is irreversible; only restore the index when no duplicate pairs exist.
        $duplicates = \Illuminate\Support\Facades\DB::table('building_building_building_building_works_documents')
            ->select('works_notification_id', 'document_type_id')
            ->where('status', '!=', 'archived')
            ->groupBy('works_notification_id', 'document_type_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicates > 0) {
            return; // cannot re-apply the unique index safely
        }

        Schema::table('building_building_building_building_works_documents', function (Blueprint $table) {
            $table->dropForeign(['works_notification_id']);
            $table->dropForeign(['document_type_id']);
            $table->dropIndex('building_building_building_building_works_documents_works_notification_id_index');
            $table->unique(['works_notification_id', 'document_type_id']);
            $table->foreign('works_notification_id')->references('id')->on('building_works_declarations')->onDelete('cascade');
            $table->foreign('document_type_id')->references('id')->on('document_types')->onDelete('cascade');
        });
    }
};
