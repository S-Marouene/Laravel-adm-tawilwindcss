<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('works_notification_id')->constrained('works_notifications')->onDelete('cascade');
            $table->foreignId('document_type_id')->constrained('document_types')->onDelete('cascade');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('status')->default('pending'); // pending | uploaded
            $table->timestamps();

            $table->unique(['works_notification_id', 'document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('works_documents');
    }
};
