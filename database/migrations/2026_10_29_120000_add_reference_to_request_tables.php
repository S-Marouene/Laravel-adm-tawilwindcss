<?php

use App\Support\Reference;
use App\Models\PreventionDossier;
use App\Models\PreventivePresenceRequest;
use App\Models\RescueCertificate;
use App\Models\WorksNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persists the citizen-facing reference (PRV26-0001, PRS26-0002…) on every
     * request type. Existing already-submitted rows are backfilled at
     * migration time; drafts keep no reference (assigned on submission).
     */
    public function up(): void
    {
        foreach ($this->tables() as $table) {
            if (! Schema::hasColumn($table, 'reference')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->string('reference', 32)->nullable()->unique()->after('current_step');
                });
            }
        }

        // Backfill dossiers already submitted (drafts stay reference-less).
        // Each row is numbered with the year digit of its own submission date.
        PreventionDossier::where('status', '!=', 'draft')
            ->whereNull('reference')
            ->orderBy('id')
            ->get()
            ->each(fn ($dossier) => $dossier->update([
                'reference' => Reference::make('prevention', $dossier, $dossier->created_at?->year),
            ]));

        PreventivePresenceRequest::where('status', '!=', 'draft')
            ->whereNull('reference')
            ->orderBy('id')
            ->get()
            ->each(fn ($request) => $request->update([
                'reference' => Reference::make('presence', $request, $request->created_at?->year),
            ]));

        RescueCertificate::where('status', '!=', 'draft')
            ->whereNull('reference')
            ->orderBy('id')
            ->get()
            ->each(fn ($certificate) => $certificate->update([
                'reference' => Reference::make('rescue', $certificate, $certificate->created_at?->year),
            ]));

        WorksNotification::where('status', '!=', 'draft')
            ->whereNull('reference')
            ->orderBy('id')
            ->get()
            ->each(fn ($notification) => $notification->update([
                'reference' => Reference::make('works', $notification, $notification->created_at?->year),
            ]));
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            if (Schema::hasColumn($table, 'reference')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropUnique(['reference']);
                    $table->dropColumn('reference');
                });
            }
        }
    }

    private function tables(): array
    {
        return [
            'prevention_dossiers',
            'preventive_presence_requests',
            'rescue_certificates',
            'works_notifications',
        ];
    }
};
