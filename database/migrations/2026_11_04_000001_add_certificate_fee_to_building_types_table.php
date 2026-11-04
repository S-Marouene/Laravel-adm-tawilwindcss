<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('building_types', function (Blueprint $table) {
            // Fee (in TND) the applicant pays to receive the prevention certificate.
            // Set per building type — shown in the delivery-choice section.
            $table->decimal('certificate_fee', 8, 3)->default(0)->after('has_activities');
        });
    }

    public function down(): void
    {
        Schema::table('building_types', function (Blueprint $table) {
            $table->dropColumn('certificate_fee');
        });
    }
};
