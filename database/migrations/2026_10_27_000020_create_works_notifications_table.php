<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->string('status')->default('draft');

            // Owner (مالك البناية) — natural person or legal entity
            $table->string('owner_type', 20)->default('natural'); // natural | moral
            $table->string('owner_name')->nullable();             // owner's name (natural person)
            $table->string('owner_cin', 20)->nullable();          // CIN of owner / legal representative
            $table->string('company_name')->nullable();           // legal entity name
            $table->string('jort_reference', 100)->nullable();    // reference of the legal incorporation notice (dcmān)
            $table->string('phone', 20)->nullable();

            // Building location (مكان الأشغال)
            $table->unsignedTinyInteger('governorate_id')->nullable();
            $table->foreignId('delegation_id')->nullable()->constrained('delegations')->nullOnDelete();
            $table->string('property_address', 500)->nullable();

            // Works details (الأشغال)
            $table->string('works_type', 100)->nullable();        // nature of the works
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
        Schema::dropIfExists('works_notifications');
    }
};
