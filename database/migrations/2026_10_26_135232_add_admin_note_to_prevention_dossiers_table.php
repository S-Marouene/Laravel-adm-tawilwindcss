<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            // Admin note (shown to user when status is needs_correction or cancelled)
            $table->text('admin_note')->nullable()->after('api_reference');
            // Who performed the last status action (admin user id)
            $table->foreignId('reviewed_by')->nullable()->after('admin_note')
                  ->constrained('users')->nullOnDelete();
            // When the last status action happened
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('prevention_dossiers', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['admin_note', 'reviewed_by', 'reviewed_at']);
        });
    }
};
