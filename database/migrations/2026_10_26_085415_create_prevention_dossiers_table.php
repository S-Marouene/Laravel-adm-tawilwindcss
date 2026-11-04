<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prevention_dossiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Current step (1 = step1 done, 2 = step2 done / completed)
            $table->unsignedTinyInteger('current_step')->default(1);

            // Citizen-facing reference (PRV26-0001) — assigned once, on submission
            $table->string('reference', 32)->nullable()->unique();

            // ── Delivery flow ──
            $table->string('delivery_method', 20)->nullable();
            $table->text('post_address')->nullable();
            $table->timestamp('delivery_choice_at')->nullable();

            // Status: draft | completed | submitted | under_consultation | needs_correction
            //       | approved | waiting_delivery | cancelled
            $table->string('status')->default('draft');

            // ── Step 1 ──
            $table->string('cin')->nullable();
            $table->string('nom_prenom')->nullable();
            $table->string('phone', 20)->nullable();
            $table->unsignedTinyInteger('governorate_id')->nullable();
            $table->foreignId('delegation_id')->nullable()->constrained('delegations')->nullOnDelete();
            $table->string('building_address', 500)->nullable();

            // ── Step 2 ──
            $table->foreignId('building_type_id')->nullable()->constrained('building_types')->nullOnDelete();
            $table->foreignId('building_activity_id')->nullable()->constrained('building_activities')->nullOnDelete();
            $table->string('activity_other')->nullable();

            // ── API fields (step 3+, future) ──
            $table->string('num_demande')->nullable();
            $table->string('api_reference')->nullable();

            // ── Admin workflow ──
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->foreign('governorate_id')->references('id')->on('governorates')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index('delivery_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prevention_dossiers');
    }
};
