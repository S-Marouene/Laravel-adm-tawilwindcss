<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('works_notification_id')
                ->constrained('works_notifications')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->text('note')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['works_notification_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('works_events');
    }
};
