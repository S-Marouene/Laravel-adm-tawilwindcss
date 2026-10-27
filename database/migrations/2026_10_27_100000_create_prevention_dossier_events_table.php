<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Full audit log for prevention dossiers — every status change,
        // correction, approval and RDV (appointment) event is recorded here
        // so the admin timeline can display the complete history.
        Schema::create('prevention_dossier_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prevention_dossier_id')
                  ->constrained('prevention_dossiers')
                  ->cascadeOnDelete();

            // Who did the action (admin or applicant) — null for system actions
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Event type:
            // submitted | resubmitted | advanced_to_consultation | correction_requested
            // approved | cancelled | appointment_scheduled | appointment_passed
            // appointment_failed | appointment_deleted
            $table->string('type', 40);

            // Optional note attached to the event (correction note, fail reason, etc.)
            $table->text('note')->nullable();

            // Extra structured details (e.g. RDV date/time/location)
            $table->json('data')->nullable();

            $table->timestamps();

            $table->index(['prevention_dossier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prevention_dossier_events');
    }
};
