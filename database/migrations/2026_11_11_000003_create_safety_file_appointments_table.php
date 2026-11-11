<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_file_appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('safety_file_dossier_id')
                  ->constrained('safety_file_dossiers')
                  ->cascadeOnDelete();

            // Who scheduled it
            $table->foreignId('scheduled_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Date & time of the visit
            $table->date('visit_date');
            $table->time('visit_time');

            // The visit happens at the building, whose address lives on the
            // dossier (safety_file_dossiers.building_address) — no location column here.

            // Status: pending | passed | failed
            $table->string('status', 20)->default('pending');

            // Admin note per RDV (filled when status = failed, describing what's wrong at the building)
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['safety_file_dossier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_file_appointments');
    }
};
