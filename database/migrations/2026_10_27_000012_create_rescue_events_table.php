<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rescue_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rescue_certificate_id')
                ->constrained('rescue_certificates')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->text('note')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['rescue_certificate_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rescue_events');
    }
};
