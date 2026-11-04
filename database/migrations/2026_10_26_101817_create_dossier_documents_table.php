<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prevention_dossier_id')->constrained('prevention_dossiers')->onDelete('cascade');
            $table->foreignId('document_type_id')->constrained('document_types')->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('status')->default('pending'); // pending | uploaded | archived
            $table->timestamps();

            // Multiple files per (dossier, document type) are allowed — no unique constraint
            $table->index('prevention_dossier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossier_documents');
    }
};
