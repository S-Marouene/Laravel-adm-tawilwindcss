<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add building_address to prevention_dossiers
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            $table->string('building_address', 500)->nullable()->after('phone');
        });

        // 2. Rename location → visit_note in dossier_appointments
        //    (The admin visits the address on the dossier, this field is now an
        //     optional note/instruction for the inspector, not a location.)
        Schema::table('dossier_appointments', function (Blueprint $table) {
            $table->renameColumn('location', 'visit_note');
        });
    }

    public function down(): void
    {
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            $table->dropColumn('building_address');
        });

        Schema::table('dossier_appointments', function (Blueprint $table) {
            $table->renameColumn('visit_note', 'location');
        });
    }
};
