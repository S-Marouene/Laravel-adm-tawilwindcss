<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_works_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('current_step')->default(1);

            // Citizen-facing reference (TRV26-0001) — assigned once, on submission
            $table->string('reference', 32)->nullable()->unique();

            $table->string('status')->default('draft');

            // Owner (مالك البناية) — natural person or legal entity
            $table->string('owner_type', 20)->default('natural');
            $table->string('owner_name')->nullable();
            $table->string('owner_cin', 20)->nullable();
            $table->string('company_name')->nullable();
            $table->string('jort_reference', 100)->nullable();
            $table->string('phone', 20)->nullable();

            // Building location (مكان الأشغال)
            $table->unsignedTinyInteger('governorate_id')->nullable();
            $table->foreignId('delegation_id')->nullable()->constrained('delegations')->nullOnDelete();
            $table->string('property_address', 500)->nullable();

            // Works details (الأشغال)
            $table->string('works_type', 100)->nullable();
            $table->date('works_start_date')->nullable();
            $table->text('works_description')->nullable();
            $table->text('notes')->nullable();

            // Admin workflow
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->index(['user_id', 'status']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_works_declarations');
    }
};
