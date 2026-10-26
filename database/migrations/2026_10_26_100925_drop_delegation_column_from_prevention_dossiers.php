<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            if (Schema::hasColumn('prevention_dossiers', 'delegation')) {
                $table->dropColumn('delegation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            $table->string('delegation')->nullable()->after('governorate_id');
        });
    }
};
