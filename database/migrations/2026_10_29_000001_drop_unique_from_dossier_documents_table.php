<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow multiple uploaded files per (dossier, document type):
     * drop the unique constraint so the applicant may add several
     * files without replacing the previously uploaded ones.
     */
    public function up(): void
    {
        Schema::table('dossier_documents', function (Blueprint $table) {
            $table->dropUnique('dossier_documents_prevention_dossier_id_document_type_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('dossier_documents', function (Blueprint $table) {
            $table->unique(['prevention_dossier_id', 'document_type_id']);
        });
    }
};
