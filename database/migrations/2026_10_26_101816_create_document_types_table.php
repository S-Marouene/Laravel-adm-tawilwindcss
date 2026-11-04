<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('name_en');
            // Scope so each service keeps its own document checklist
            $table->string('service_key', 40)->nullable();
            // A document type may belong to MULTIPLE services (checklists)
            $table->json('services')->nullable();
            $table->boolean('is_conditional')->default(false);
            // Official downloadable form (template to fill in)
            $table->string('template_path')->nullable();
            $table->string('template_name')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Périmètre par type de bâtiment : aucune ligne pour un document →
        // il ne s'applique à aucun type (strict, voir DocumentType::appliesToBuildingType).
        Schema::create('building_type_document_type', function (Blueprint $table) {
            $table->foreignId('building_type_id')->constrainedOn('building_types')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrainedOn('document_types')->cascadeOnDelete();
            $table->primary(['building_type_id', 'document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_type_document_type');
        Schema::dropIfExists('document_types');
    }
};
