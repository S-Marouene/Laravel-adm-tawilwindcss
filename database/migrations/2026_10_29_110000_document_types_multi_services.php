<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A document type may belong to MULTIPLE services (checklists).
        Schema::table('document_types', function (Blueprint $table) {
            $table->json('services')->nullable()->after('service_key');
        });

        // Backfill from the legacy single service_key column
        DB::table('document_types')->whereNotNull('service_key')->get()->each(function ($row) {
            DB::table('document_types')
                ->where('id', $row->id)
                ->update(['services' => json_encode(array_values(array_filter([$row->service_key])))]);
        });

        DB::table('document_types')->whereNull('services')
            ->update(['services' => json_encode(['prevention'])]);
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('services');
        });
    }
};
