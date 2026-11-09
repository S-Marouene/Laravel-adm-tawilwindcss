<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Database-backed translations dictionary (FR / AR / EN).
 *
 * Every lang file group (prevention, admin, nav…) + key is stored as a row.
 * The DatabaseTranslationLoader overlays these values on top of the static
 * lang files, so the dictionary is a pure override layer: a missing row (or
 * an empty text) simply falls back to lang/{locale}/{group}.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            // Lang file name — e.g. 'prevention', 'prevention_request', 'admin'
            $table->string('group', 64);
            // Dotted key inside the group — e.g. 'stepper_1', 'service_labels.prevention'
            $table->string('key');
            $table->text('fr')->nullable();
            $table->text('ar')->nullable();
            $table->text('en')->nullable();
            // Last administrator who edited the row (null = untouched seed value)
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['group', 'key']);
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
