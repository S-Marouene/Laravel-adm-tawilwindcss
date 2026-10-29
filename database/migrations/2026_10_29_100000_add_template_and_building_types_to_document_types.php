<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Feature 1 : formulaire officiel téléchargeable (modèle à compléter) ──
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('template_path')->nullable()->after('service_key'); // fichier stocké (disque local)
            $table->string('template_name')->nullable()->after('template_path'); // nom original affiché
        });

        // ── Feature 2 : périmètre par type de bâtiment ──
        // Aucune ligne pour un document → il s'applique à TOUS les types de bâtiment.
        Schema::create('building_type_document_type', function (Blueprint $table) {
            $table->foreignId('building_type_id')->constrainedOn('building_types')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrainedOn('document_types')->cascadeOnDelete();
            $table->primary(['building_type_id', 'document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_type_document_type');

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn(['template_path', 'template_name']);
        });
    }
};
