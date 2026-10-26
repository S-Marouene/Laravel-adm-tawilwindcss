<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            // Add the locale-safe FK column right after governorate_id
            $table->foreignId('delegation_id')
                  ->nullable()
                  ->after('governorate_id')
                  ->constrained('delegations')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            $table->dropForeign(['delegation_id']);
            $table->dropColumn('delegation_id');
        });
    }
};
