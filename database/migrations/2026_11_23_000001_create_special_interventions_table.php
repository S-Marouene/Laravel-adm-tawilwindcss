<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Citizen-facing reference (INTV26-0001) — assigned once, on submission
            $table->string('reference', 32)->nullable()->unique();

            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('current_step')->default(1);

            // Applicant identity
            $table->string('cin', 20)->nullable();
            $table->string('nom_prenom')->nullable();
            $table->string('phone', 20)->nullable();

            // Regional entity (المعتمدية / الإدارة الجهوية)
            $table->unsignedTinyInteger('governorate_id')->nullable();
            $table->foreignId('delegation_id')->nullable()->constrained('delegations')->nullOnDelete();

            // Intervention date / time chosen by the citizen
            $table->date('intervention_date')->nullable();
            $table->time('intervention_time')->nullable();

            // Selected interventions + quantities (hours) — JSON array
            // [{ id, name_ar, name_fr, price_tnd, hours, amount }, ...]
            $table->json('interventions')->nullable();

            // Total amount in Tunisian Dinars (calculated)
            $table->unsignedInteger('total_amount')->default(0); // in millimes? no — in dinars integer

            // Admin workflow
            $table->text('admin_note')->nullable(); // bilingual via HasTranslations
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->index(['user_id', 'status']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_interventions');
    }
};
