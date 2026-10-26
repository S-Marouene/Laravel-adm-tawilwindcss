<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('nom_prenom');
            // New status: under_consultation — added via string column, no enum change needed
            // status column already supports arbitrary strings: draft|completed|submitted|under_consultation
        });
    }

    public function down(): void
    {
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
