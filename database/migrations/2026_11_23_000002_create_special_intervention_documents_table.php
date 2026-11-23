<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_intervention_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_intervention_id')
                ->constrained('special_interventions')
                ->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->onDelete('cascade');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('status')->default('pending'); // pending | uploaded | archived
            $table->timestamps();

            $table->unique(['special_intervention_id', 'document_type_id'], 'si_docs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_intervention_documents');
    }
};
