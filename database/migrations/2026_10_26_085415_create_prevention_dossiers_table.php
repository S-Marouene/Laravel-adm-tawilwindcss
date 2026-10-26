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

            // Status: draft | completed | submitted
            $table->string('status')->default('draft');

            // ── Step 1 ──
            $table->string('cin')->nullable();
            $table->string('nom_prenom')->nullable();
            $table->unsignedTinyInteger('governorate_id')->nullable();
            $table->string('delegation')->nullable();

            // ── Step 2 ──
            $table->foreignId('building_type_id')->nullable()->constrained('building_types')->nullOnDelete();
            $table->foreignId('building_activity_id')->nullable()->constrained('building_activities')->nullOnDelete();
            $table->string('activity_other')->nullable(); // free-text when activity = "other"

            // ── API fields (step 3+, future) ──
            $table->string('num_demande')->nullable();
            $table->string('api_reference')->nullable(); // external API ID once submitted

            $table->timestamps();

            $table->foreign('governorate_id')->references('id')->on('governorates')->nullOnDelete();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prevention_dossiers');
    }
};
