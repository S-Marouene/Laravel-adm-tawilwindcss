<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossier_appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prevention_dossier_id')
                  ->constrained('prevention_dossiers')
                  ->cascadeOnDelete();

            // Who scheduled it
            $table->foreignId('scheduled_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Date & time of the visit
            $table->date('visit_date');
            $table->time('visit_time');

            // Optional address / location note
            $table->string('location', 500)->nullable();

            // Status: pending | passed | failed
            $table->string('status', 20)->default('pending');

            // Admin note per RDV (filled when status = failed, describing what's wrong at the building)
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['prevention_dossier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossier_appointments');
    }
};
