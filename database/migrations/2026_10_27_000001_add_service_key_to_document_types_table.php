<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            // Scope so each service keeps its own document checklist
            // ('prevention' = شهادة الوقاية, 'preventive-presence' = الحضور الوقائي)
            $table->string('service_key', 40)->nullable()->after('name_en');
        });

        // Backfill: every existing document type belongs to the prevention certificate service
        DB::table('document_types')
            ->whereNull('service_key')
            ->update(['service_key' => 'prevention']);
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('service_key');
        });
    }
};
