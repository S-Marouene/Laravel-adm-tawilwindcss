<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_participants', function (Blueprint $table) {
            // Priority within a society deposit — the top-ranked persons are taken
            // first up to the session capacity; the rest fall to the wait list.
            $table->unsignedSmallInteger('priority')->default(0)->after('training_kind');
        });
    }

    public function down(): void
    {
        Schema::table('training_participants', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
