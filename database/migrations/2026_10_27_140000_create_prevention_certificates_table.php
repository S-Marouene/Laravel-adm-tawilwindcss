<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prevention_certificates', function (Blueprint $table) {
            $table->id();

            // One certificate per prevention dossier (issued after a passed RDV)
            $table->foreignId('prevention_dossier_id')
                  ->constrained('prevention_dossiers')
                  ->cascadeOnDelete();

            // Unique certificate reference, e.g. PC-2026-000123
            $table->string('reference', 30)->unique();

            // Admin who issued the certificate
            $table->foreignId('issued_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Issue date + optional validity end date
            $table->date('issued_at');
            $table->date('valid_until')->nullable();

            // Delegation where the applicant must pick the certificate up
            $table->foreignId('delegation_id')
                  ->nullable()
                  ->constrained('delegations')
                  ->nullOnDelete();

            // State: ready (waiting at the delegation) | collected (picked up)
            $table->string('status', 20)->default('ready');
            $table->timestamp('collected_at')->nullable();

            // Optional internal note
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique('prevention_dossier_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prevention_certificates');
    }
};
