<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_documents', function (Blueprint $table) {
            // Static document key (cin, old_certificate) as it no longer comes
            // from the document_types table.
            $table->string('document_key')->nullable()->after('document_type_id');
            // The participant this document belongs to (null for individual requests).
            $table->foreignId('participant_id')->nullable()->after('document_key')
                ->constrained('training_participants')->nullOnDelete();
        });

        // Static documents are not linked to a document type.
        Schema::table('training_documents', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('training_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('participant_id');
            $table->dropColumn('document_key');
        });

        Schema::table('training_documents', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable(false)->change();
        });
    }
};
