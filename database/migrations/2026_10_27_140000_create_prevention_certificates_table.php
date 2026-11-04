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

            // Governorate where the certificate is available
            $table->unsignedTinyInteger('governorate_id')->nullable();
            $table->foreign('governorate_id')
                  ->references('id')
                  ->on('governorates')
                  ->nullOnDelete();

            // State: ready (waiting at the delegation) | collected (picked up)
            $table->string('status', 20)->default('ready');

            // Delivery metadata (post / B3 — Poste Tunisienne)
            $table->string('delivery_method', 20)->nullable();
            $table->string('post_recipient')->nullable();
            $table->string('post_phone', 40)->nullable();
            $table->text('post_address')->nullable();
            $table->string('post_tracking', 60)->nullable();
            $table->timestamp('post_sent_at')->nullable();
            $table->timestamp('post_delivered_at')->nullable();

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
