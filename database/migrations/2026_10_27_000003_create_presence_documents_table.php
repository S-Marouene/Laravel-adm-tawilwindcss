<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presence_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preventive_presence_request_id')
                ->constrained('preventive_presence_requests')
                ->onDelete('cascade');
            $table->foreignId('document_type_id')->constrained('document_types')->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('status')->default('pending'); // pending | uploaded
            $table->unique(['preventive_presence_request_id', 'document_type_id'], 'presence_docs_request_type_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presence_documents');
    }
};
