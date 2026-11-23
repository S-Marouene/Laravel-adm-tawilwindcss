<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_intervention_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_intervention_id')
                ->constrained('special_interventions')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->text('note')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['special_intervention_id', 'created_at'], 'si_events_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_intervention_events');
    }
};
