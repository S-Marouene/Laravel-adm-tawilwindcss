<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_building_building_building_works_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('works_notification_id')
                ->constrained('building_works_declarations')
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
        Schema::dropIfExists('building_building_building_building_works_events');
    }
};
