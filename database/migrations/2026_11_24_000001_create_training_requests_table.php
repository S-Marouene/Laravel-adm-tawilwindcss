<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('current_step')->default(1);

            // Citizen-facing reference  TRN26-0001
            $table->string('reference', 32)->nullable()->unique();

            $table->string('status')->default('draft');
            // draft | completed | under_consultation | needs_correction
            // | payment_pending | payment_confirmed | scheduled
            // | in_progress | passed | failed | certificate_ready
            // | served | cancelled

            // ── Applicant identity ──
            $table->string('cin', 20)->nullable();
            $table->string('nom_prenom')->nullable();
            $table->string('phone', 20)->nullable();

            // ── Location (which regional office handles the request) ──
            $table->unsignedTinyInteger('governorate_id')->nullable();
            $table->foreignId('delegation_id')->nullable()->constrained('delegations')->nullOnDelete();

            // ── Training details ──
            // Type key from TrainingRequest::TYPES  e.g. 'taxi_drivers'
            $table->string('training_type', 60)->nullable();
            // 'new' = first-time formation | 'recycling' = rسكلة
            $table->string('training_kind', 20)->nullable()->default('new');
            $table->text('notes')->nullable();

            // ── Payment (بمقابل — وسيطة الإثبات: وصل) ──
            $table->decimal('fee_amount', 10, 3)->nullable();
            $table->string('receipt_number', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            // online payment placeholder (coming soon)
            $table->string('payment_method', 20)->nullable(); // 'office' | 'online'
            $table->string('payment_online_ref', 100)->nullable();

            // ── Formation scheduling ──
            $table->date('training_start_date')->nullable();
            $table->time('training_start_time')->nullable();
            $table->unsignedTinyInteger('training_duration_days')->nullable(); // duration in days

            // ── Exam result ──
            $table->string('result', 20)->nullable(); // 'passed' | 'failed'
            $table->text('result_note')->nullable();  // bilingual note (HasTranslations)
            $table->timestamp('result_at')->nullable();
            $table->foreignId('result_by')->nullable()->constrained('users')->nullOnDelete();

            // ── Certificate delivery (same as rescue/prevention pipeline) ──
            $table->string('certificate_number', 50)->nullable();
            $table->string('delivery_method', 20)->nullable(); // 'pickup' | 'post'
            $table->timestamp('delivery_choice_at')->nullable();
            // Post delivery
            $table->string('post_recipient')->nullable();
            $table->string('post_phone', 40)->nullable();
            $table->text('post_address')->nullable();
            $table->string('post_tracking', 60)->nullable();
            $table->timestamp('post_sent_at')->nullable();
            $table->timestamp('post_delivered_at')->nullable();
            // Pickup office
            $table->unsignedTinyInteger('pickup_governorate_id')->nullable();
            $table->foreignId('pickup_delegation_id')->nullable()->constrained('delegations')->nullOnDelete();
            // cert sub-status: ready | collected | post_sent | post_delivered
            $table->string('cert_status', 20)->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('issued_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // ── Admin workflow ──
            $table->text('admin_note')->nullable(); // bilingual JSON
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->index(['user_id', 'status']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_requests');
    }
};
