<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The RDV no longer carries its own location/visit_note field:
        // the visit happens at the building, whose address lives on the
        // dossier (prevention_dossiers.building_address).
        Schema::table('dossier_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('dossier_appointments', 'visit_note')) {
                $table->dropColumn('visit_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dossier_appointments', function (Blueprint $table) {
            $table->string('visit_note', 500)->nullable()->after('visit_time');
        });
    }
};
