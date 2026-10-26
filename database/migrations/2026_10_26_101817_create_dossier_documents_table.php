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
            $table->string('file_path')->nullable();       // stored file path
            $table->string('original_name')->nullable();    // original uploaded filename
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size')->nullable();    // bytes
            $table->string('status')->default('pending');   // pending | uploaded
            $table->timestamps();

            $table->unique(['prevention_dossier_id', 'document_type_id']);
            $table->index('prevention_dossier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossier_documents');
    }
};
