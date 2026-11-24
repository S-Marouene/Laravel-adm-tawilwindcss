<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Yearly plan of centralized formations (prévention 1/2 + BNS) — held at the
        // centrale (Tunis – Ben Arous) for a specific date and hour.
        Schema::create('training_planned_sessions', function (Blueprint $table) {
            $table->id();
            // One of TrainingRequest::CENTRALIZED_TYPES
            $table->string('training_type', 60);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable(); // null = unlimited
            // planned | started | completed | cancelled
            $table->string('status', 20)->default('planned');
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['training_type', 'starts_at']);
        });

        Schema::table('training_requests', function (Blueprint $table) {
            // Chosen centralized session — null = wait list (قائمة الانتظار)
            $table->foreignId('planned_session_id')
                ->nullable()
                ->constrained('training_planned_sessions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('planned_session_id');
        });

        Schema::dropIfExists('training_planned_sessions');
    }
};
