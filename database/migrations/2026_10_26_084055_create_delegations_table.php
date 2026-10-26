<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegations', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('governorate_id');
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('name_en');
            $table->timestamps();

            $table->foreign('governorate_id')
                  ->references('id')
                  ->on('governorates')
                  ->onDelete('cascade');

            $table->index('governorate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegations');
    }
};
