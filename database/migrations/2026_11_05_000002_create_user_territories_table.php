<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replace the single-territory FK columns on `users` (governorate_id,
 * delegation_id) with a proper pivot table so a manager can be assigned
 * to multiple territories.
 *
 * Each row represents one territory assignment:
 *   - delegation_id IS NULL  → the manager covers the whole governorate
 *   - delegation_id NOT NULL → the manager covers only that one delegation
 *
 * The unique constraint prevents duplicate assignments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_territories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('governorate_id');
            $table->unsignedBigInteger('delegation_id')->nullable();

            $table->foreign('governorate_id')
                ->references('id')->on('governorates')
                ->cascadeOnDelete();
            $table->foreign('delegation_id')
                ->references('id')->on('delegations')
                ->cascadeOnDelete();

            // Prevent duplicate rows for the same user + territory
            $table->unique(['user_id', 'governorate_id', 'delegation_id'], 'user_territories_unique');

            $table->index(['governorate_id', 'delegation_id']);
            $table->timestamps();
        });

        // Migrate existing single-territory data → pivot rows
        // (handles existing managers and delegation-role users already in the DB)
        \DB::table('users')
            ->whereNotNull('governorate_id')
            ->select('id', 'governorate_id', 'delegation_id')
            ->orderBy('id')
            ->each(function ($row) {
                \DB::table('user_territories')->insertOrIgnore([
                    'user_id'        => $row->id,
                    'governorate_id' => $row->governorate_id,
                    'delegation_id'  => $row->delegation_id ?: null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            });

        // Drop the now-redundant columns from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['governorate_id']);
            $table->dropForeign(['delegation_id']);
            $table->dropIndex(['governorate_id', 'delegation_id']);
            $table->dropColumn(['governorate_id', 'delegation_id']);
        });
    }

    public function down(): void
    {
        // Re-add single-territory columns
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('governorate_id')->nullable()->after('password');
            $table->unsignedBigInteger('delegation_id')->nullable()->after('governorate_id');

            $table->foreign('governorate_id')->references('id')->on('governorates')->nullOnDelete();
            $table->foreign('delegation_id')->references('id')->on('delegations')->nullOnDelete();
            $table->index(['governorate_id', 'delegation_id']);
        });

        Schema::dropIfExists('user_territories');
    }
};
