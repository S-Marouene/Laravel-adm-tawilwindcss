<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the full prevention-style delivery pipeline to rescue_certificates:
 * delivery_method (pickup|post), post fields, pickup tracking timestamps,
 * certificate status sub-state, issued_by, issued_at, valid_until,
 * and the citizen's delivery choice metadata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rescue_certificates', function (Blueprint $table) {
            // ── Delivery method chosen by the citizen (pickup | post) ──
            $table->string('delivery_method', 20)->nullable()->after('admin_note');

            // Date & time the citizen submitted their delivery choice
            $table->timestamp('delivery_choice_at')->nullable()->after('delivery_method');

            // ── Postal (Poste Tunisienne B3) delivery ──
            $table->string('post_recipient')->nullable()->after('delivery_choice_at');
            $table->string('post_phone', 40)->nullable()->after('post_recipient');
            $table->text('post_address')->nullable()->after('post_phone');
            $table->string('post_tracking', 60)->nullable()->after('post_address');
            $table->timestamp('post_sent_at')->nullable()->after('post_tracking');
            $table->timestamp('post_delivered_at')->nullable()->after('post_sent_at');

            // ── Pickup office (gouvernorat / délégation where the cert is available) ──
            // NOTE: rescue_certificates already has governorate_id and delegation_id
            // for the incident location — we add a separate pair for the pickup office.
            $table->unsignedTinyInteger('pickup_governorate_id')->nullable()->after('post_delivered_at');
            $table->foreignId('pickup_delegation_id')
                  ->nullable()
                  ->constrained('delegations')
                  ->nullOnDelete()
                  ->after('pickup_governorate_id');

            // ── Certificate sub-status ──
            // ready | collected | post_sent | post_delivered
            // Separate from the dossier-level status (approved | served)
            $table->string('cert_status', 20)->nullable()->after('pickup_delegation_id');

            // ── Issue metadata ──
            $table->foreignId('issued_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->after('cert_status');
            $table->date('issued_at')->nullable()->after('issued_by');
            $table->date('valid_until')->nullable()->after('issued_at');

            // Citizen's stored postal address (pre-filled from their delivery choice)
            $table->text('post_address_citizen')->nullable()->after('valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('rescue_certificates', function (Blueprint $table) {
            $table->dropForeign(['pickup_delegation_id']);
            $table->dropForeign(['issued_by']);
            $table->dropColumns([
                'delivery_method', 'delivery_choice_at',
                'post_recipient', 'post_phone', 'post_address', 'post_tracking',
                'post_sent_at', 'post_delivered_at',
                'pickup_governorate_id', 'pickup_delegation_id',
                'cert_status', 'issued_by', 'issued_at', 'valid_until',
                'post_address_citizen',
            ]);
        });
    }
};
