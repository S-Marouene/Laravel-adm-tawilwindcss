<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notification message templates editable by super-admin / admin.
     *
     * Each row is one event_key (e.g. "submitted", "needs_correction").
     * The title and body are stored in both French and Arabic.
     * Placeholders: :reference, :nom_prenom  — resolved at send time.
     */
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();   // e.g. 'submitted', 'approved'
            $table->string('level', 20)->default('info'); // info|success|warning|danger
            $table->string('title_fr');
            $table->text('body_fr');
            $table->string('title_ar');
            $table->text('body_ar');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
