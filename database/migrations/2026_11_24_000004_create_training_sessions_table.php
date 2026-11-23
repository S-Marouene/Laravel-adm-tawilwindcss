<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each row is one day's session for one participant.
 * The admin fills in attendance day by day during the formation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_request_id')
                ->constrained('training_requests')
                ->cascadeOnDelete();
            $table->date('session_date');
            // 'present' | 'absent' | 'excused'
            $table->string('attendance', 20)->default('absent');
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['training_request_id', 'session_date'], 'tr_session_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
