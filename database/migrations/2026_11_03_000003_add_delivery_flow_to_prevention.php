<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dossier: the applicant picks how he wants to receive the certificate
        // once the inspection (RDV) was marked as passed.
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            // digital (coming soon) | pickup (delegation / gouvernorat office) | post (B3 — Poste Tunisienne)
            $table->string('delivery_method', 20)->nullable()->after('reference');
            $table->text('post_address')->nullable()->after('delivery_method');
            $table->timestamp('delivery_choice_at')->nullable()->after('post_address');

            $table->index('delivery_method');
        });

        // Certificate: delivery metadata (pickup office already exists via
        // governorate_id / delegation_id; post adds the B3 tracking chain).
        Schema::table('prevention_certificates', function (Blueprint $table) {
            $table->string('delivery_method', 20)->nullable()->after('status');
            $table->string('post_recipient')->nullable()->after('delivery_method');
            $table->string('post_phone', 40)->nullable()->after('post_recipient');
            $table->text('post_address')->nullable()->after('post_phone');
            $table->string('post_tracking', 60)->nullable()->after('post_address');
            $table->timestamp('post_sent_at')->nullable()->after('post_tracking');
            $table->timestamp('post_delivered_at')->nullable()->after('post_sent_at');
        });

        // Pickup-office details shown to the applicant when he chooses
        // "pickup" — maintained by the administration in the reference panels.
        Schema::table('governorates', function (Blueprint $table) {
            $table->string('office_address')->nullable();
            $table->string('office_phone', 40)->nullable();
            $table->string('office_hours')->nullable();
        });

        Schema::table('delegations', function (Blueprint $table) {
            $table->string('office_address')->nullable();
            $table->string('office_phone', 40)->nullable();
            $table->string('office_hours')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            $table->dropIndex(['delivery_method']);
            $table->dropColumn(['delivery_method', 'post_address', 'delivery_choice_at']);
        });

        Schema::table('prevention_certificates', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_method', 'post_recipient', 'post_phone', 'post_address',
                'post_tracking', 'post_sent_at', 'post_delivered_at',
            ]);
        });

        Schema::table('governorates', function (Blueprint $table) {
            $table->dropColumn(['office_address', 'office_phone', 'office_hours']);
        });

        Schema::table('delegations', function (Blueprint $table) {
            $table->dropColumn(['office_address', 'office_phone', 'office_hours']);
        });
    }
};
