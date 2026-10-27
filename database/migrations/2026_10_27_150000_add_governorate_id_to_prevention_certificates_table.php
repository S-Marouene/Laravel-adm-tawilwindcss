<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prevention_certificates', function (Blueprint $table) {
            // Governorate where the certificate is available — always required.
            // governorates.id is an unsignedTinyInteger (codes 1–24), so the FK column must match.
            $table->unsignedTinyInteger('governorate_id')->nullable()->after('delegation_id');

            $table->foreign('governorate_id')
                  ->references('id')
                  ->on('governorates')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prevention_certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('governorate_id');
        });
    }
};
