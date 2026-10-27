<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preventive_presence_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preventive_presence_request_id')
                ->constrained('preventive_presence_requests')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->text('note')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['preventive_presence_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preventive_presence_events');
    }
};
