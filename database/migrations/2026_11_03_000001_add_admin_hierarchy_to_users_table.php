<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Administrator territory hierarchy:
        // - delegation-scoped admins are bound to one delegation (and its governorate);
        // - governorate-scoped admins are bound to one governorate (all delegations);
        // - centrale / admin / super-admin have no territory and see everything.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('governorate_id')
                ->nullable()
                ->after('is_admin');
            $table->unsignedBigInteger('delegation_id')
                ->nullable()
                ->after('governorate_id');

            $table->foreign('governorate_id')
                  ->references('id')->on('governorates')
                  ->nullOnDelete();
            $table->foreign('delegation_id')
                  ->references('id')->on('delegations')
                  ->nullOnDelete();

            $table->index(['governorate_id', 'delegation_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['governorate_id']);
            $table->dropForeign(['delegation_id']);
            $table->dropIndex(['governorate_id', 'delegation_id']);
            $table->dropColumn('governorate_id');
            $table->dropColumn('delegation_id');
        });
    }
};
