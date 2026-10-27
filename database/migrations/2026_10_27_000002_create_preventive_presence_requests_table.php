<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preventive_presence_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->string('status')->default('draft');

            // Applicant identity
            $table->string('cin')->nullable();
            $table->string('nom_prenom')->nullable();
            $table->string('phone', 20)->nullable();

            // Where the event will take place
            $table->unsignedTinyInteger('governorate_id')->nullable();
            $table->foreignId('delegation_id')->nullable()->constrained('delegations')->nullOnDelete();
            $table->string('event_address', 500)->nullable();

            // Event details (التظاهر/التظاهرات)
            $table->string('event_title', 300)->nullable();        // nature/theme of the event
            $table->date('event_date')->nullable();               // first day
            $table->date('event_end_date')->nullable();           // last day (multi-day events)
            $table->time('event_time')->nullable();               // start time
            $table->unsignedInteger('expected_attendance')->nullable();
            $table->text('notes')->nullable();                    // free description of the service requested

            // Payment (بمقابل — وسيطة الإثبات: وصل) — recorded by the administration
            $table->decimal('fee_amount', 10, 3)->nullable();
            $table->string('receipt_number', 50)->nullable();     // رقم وصل أداء المعاليم
            $table->timestamp('paid_at')->nullable();

            // Service performed (إسداء الخدمة) — recorded by the administration
            $table->date('service_date')->nullable();
            $table->string('service_team', 255)->nullable();      // assigned unit / أمر
            $table->unsignedSmallInteger('service_agents')->nullable();
            $table->text('service_note')->nullable();
            $table->timestamp('served_at')->nullable();

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
        Schema::dropIfExists('preventive_presence_requests');
    }
};
