<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow multiple files per (rescue certificate, document type) — same model
 * as `dossier_documents` (prevention): each upload creates a new row, and a
 * replaced file stays on disk with status = 'archived' for history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rescue_documents', function (Blueprint $table) {
            // MySQL keeps FK constraints backed by the unique index — drop them first
            $table->dropForeign(['rescue_certificate_id']);
            $table->dropForeign(['document_type_id']);

            // Multiple files per (certificate, document type) are allowed — no unique constraint
            $table->dropUnique('rescue_documents_rescue_certificate_id_document_type_id_unique');
            $table->index('rescue_certificate_id');

            $table->foreign('rescue_certificate_id')->references('id')->on('rescue_certificates')->onDelete('cascade');
            $table->foreign('document_type_id')->references('id')->on('document_types')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Archiving is irreversible; only restore the index when no duplicate pairs exist.
        $duplicates = \Illuminate\Support\Facades\DB::table('rescue_documents')
            ->select('rescue_certificate_id', 'document_type_id')
            ->where('status', '!=', 'archived')
            ->groupBy('rescue_certificate_id', 'document_type_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicates > 0) {
            return; // cannot re-apply the unique index safely
        }

        Schema::table('rescue_documents', function (Blueprint $table) {
            $table->dropForeign(['rescue_certificate_id']);
            $table->dropForeign(['document_type_id']);
            $table->dropIndex('rescue_documents_rescue_certificate_id_index');
            $table->unique(['rescue_certificate_id', 'document_type_id']);
            $table->foreign('rescue_certificate_id')->references('id')->on('rescue_certificates')->onDelete('cascade');
            $table->foreign('document_type_id')->references('id')->on('document_types')->onDelete('cascade');
        });
    }
};
