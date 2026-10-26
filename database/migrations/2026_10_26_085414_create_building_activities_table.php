<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_type_id')->constrained('building_types')->onDelete('cascade');
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('name_en');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('building_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_activities');
    }
};
