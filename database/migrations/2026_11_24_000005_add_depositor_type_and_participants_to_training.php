<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            // 'individual' = real person | 'company' = society depositing for several people
            $table->string('depositor_type', 20)->default('individual');
            $table->string('company_name')->nullable();
        });

        Schema::create('training_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_request_id')->constrained('training_requests')->cascadeOnDelete();
            $table->string('nom_prenom');
            $table->string('cin', 20);
            $table->string('phone', 20)->nullable();
            // 'new' = first-time formation | 'recycling' = ركسلة
            $table->string('training_kind', 20)->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_participants');

        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropColumn(['depositor_type', 'company_name']);
        });
    }
};
