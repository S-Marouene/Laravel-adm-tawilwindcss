<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rescue_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('current_step')->default(1);

            // Citizen-facing reference (SEC26-0001) — assigned once, on submission
            $table->string('reference', 32)->nullable()->unique();

            $table->string('status')->default('draft');

            // Victim identity (المتضرر)
            $table->string('cin', 20)->nullable();
            $table->string('nom_prenom')->nullable();
            $table->boolean('victim_is_minor')->default(false);
            $table->string('parent_name')->nullable();
            $table->string('parent_cin', 20)->nullable();
            $table->boolean('spouse_case')->default(false);
            $table->string('phone', 20)->nullable();

            // Place + details of the rescue operation
            $table->unsignedTinyInteger('governorate_id')->nullable();
            $table->foreignId('delegation_id')->nullable()->constrained('delegations')->nullOnDelete();
            $table->string('incident_address', 500)->nullable();
            $table->string('incident_type', 40)->nullable();
            $table->date('incident_date')->nullable();
            $table->time('incident_time')->nullable();
            $table->text('incident_description')->nullable();
            $table->text('notes')->nullable();

            // Certificate delivery
            $table->string('certificate_number', 50)->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('delivery_note')->nullable();
            $table->timestamp('delivered_at')->nullable();

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
        Schema::dropIfExists('rescue_certificates');
    }
};
