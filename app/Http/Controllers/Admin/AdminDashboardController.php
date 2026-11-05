<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DossierAppointment;
use App\Models\PreventionCertificate;
use App\Models\PreventionDossier;
use App\Models\PreventionDossierEvent;
use App\Models\User;
use App\Models\Service;
use App\Support\AdminScope;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminDashboardController extends Controller
{
    /**
     * Salle des Opérations — Dashboard de la Direction de la Prévention.
     *
     * Every metric is scoped through AdminScope, so:
     *  - full-access roles (super-admin, admin, centrale) see the whole country;
     *  - 'manager' roles see only their governorate;
     *  - 'delegation' roles see only their delegation.
     */
    public function index()
    {
        $user  = auth()->user();
        $scope = $user->adminScope(); // 'all' | 'governorate' | 'delegation'

        // Staff not assigned to the Prevention service never see prevention
        // data here — they keep landing on their own service workspace.
        if (! $user->hasAnyRole(['super-admin', 'admin']) && ! $user->hasService(Service::SERVICE_PREVENTION)) {
            $route = AdminScope::firstAllowedServiceRoute($user);

            return redirect()->route($route ?? 'admin.prevention-dossiers.index');
        }

        // ── Scoped base query (dossiers, excluding drafts) ──────────────────
        $base = fn () => AdminScope::apply(
            PreventionDossier::query()->whereNotIn('status', ['draft'])
        );

        // ── Status distribution (single grouped query) ──────────────────────
        $statusCounts = $base()->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $c = static fn (string $status): int => (int) ($statusCounts[$status] ?? 0);

        $counts = [
            'completed'          => $c('completed'),
            'submitted'          => $c('submitted'),
            'under_consultation' => $c('under_consultation'),
            'needs_correction'   => $c('needs_correction'),
            'approved'           => $c('approved'),
            'waiting_delivery'   => $c('waiting_delivery'),
            'cancelled'          => $c('cancelled'),
        ];

        $totalDossiers = array_sum($counts);

        // ── KPI numbers ─────────────────────────────────────────────────────
        $active        = $counts['completed'] + $counts['submitted']
            + $counts['under_consultation'] + $counts['needs_correction'];
        $toReview      = $counts['completed'] + $counts['submitted'];
        $visitPipeline = $counts['under_consultation'] + $counts['approved'];

        $lateDossiers = $base()->whereIn('status', ['completed', 'submitted', 'under_consultation', 'needs_correction'])
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        $newThisWeek = $base()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        // ── Weekly intake (last 8 weeks) for the trend chart ────────────────
        $weekly = collect(range(7, 0))->reverse()->map(function ($i) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end   = now()->subWeeks($i)->endOfWeek();

            return [
                'label' => sprintf('S%s', $start->isoFormat('ww')),
                'count' => AdminScope::apply(PreventionDossier::query()->whereNotIn('status', ['draft']))
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ];
        })->values();

        // ── Territorial breakdown (top 8) ───────────────────────────────────
        // Full access → by governorate; scoped → by delegation inside the territory.
        if ($scope === 'all') {
            $breakdown = $base()->whereNotNull('governorate_id')
                ->with('governorate')
                ->selectRaw('governorate_id, COUNT(*) as c')
                ->groupBy('governorate_id')
                ->orderByDesc('c')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'name'  => $row->governorate?->name_fr ?? $row->governorate?->name_ar ?? '—',
                    'count' => (int) $row->c,
                ])
                ->filter(fn ($b) => $b['name'] !== '—' && $b['count'] > 0)
                ->values();
        } else {
            $breakdown = $base()->whereNotNull('delegation_id')
                ->with('delegation')
                ->selectRaw('delegation_id, COUNT(*) as c')
                ->groupBy('delegation_id')
                ->orderByDesc('c')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'name'  => $row->delegation?->name_fr ?? $row->delegation?->name_ar ?? '—',
                    'count' => (int) $row->c,
                ])
                ->filter(fn ($b) => $b['name'] !== '—' && $b['count'] > 0)
                ->values();
        }

        // ── Latest dossiers (queue) ─────────────────────────────────────────
        $recentDossiers = $base()->with(['governorate', 'delegation', 'buildingType'])
            ->latest()
            ->take(7)
            ->get();

        // ── Upcoming on-site visits (commissions) ───────────────────────────
        $upcomingVisits = DossierAppointment::query()
            ->where('status', 'pending')
            ->whereDate('visit_date', '>=', today())
            ->whereHas('dossier', fn ($q) => AdminScope::apply(
                $q->whereNotIn('status', ['draft'])
            ))
            ->with(['dossier', 'dossier.governorate', 'dossier.buildingType'])
            ->orderBy('visit_date')
            ->orderBy('visit_time')
            ->take(5)
            ->get();

        // ── Certificates summary (scoped through the dossier) ──────────────
        $certCounts = PreventionCertificate::query()
            ->whereHas('dossier', fn ($q) => AdminScope::apply($q))
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $certificates = [
            'ready'          => (int) ($certCounts[PreventionCertificate::STATUS_READY] ?? 0),
            'post_sent'      => (int) ($certCounts[PreventionCertificate::STATUS_POST_SENT] ?? 0),
            'post_delivered' => (int) ($certCounts[PreventionCertificate::STATUS_POST_DELIVERED] ?? 0),
            'collected'      => (int) ($certCounts[PreventionCertificate::STATUS_COLLECTED] ?? 0),
        ];
        $certificatesTotal = array_sum($certificates);
        $certPendingDelivery = $certificates['ready'] + $counts['waiting_delivery'];

        // ── Main courante — latest dossier events ───────────────────────────
        $recentEvents = PreventionDossierEvent::query()
            ->whereHas('dossier', fn ($q) => AdminScope::apply(
                $q->whereNotIn('status', ['draft'])
            ))
            ->with(['dossier', 'actor.roles'])
            ->latest()
            ->take(6)
            ->get();

        // ── System data (full-access only) ─────────────────────────────────
        $system = null;
        if ($scope === 'all') {
            $system = [
                'total_users'       => User::count(),
                'total_roles'       => Role::count(),
                'total_permissions' => Permission::count(),
                'recent_activities' => ActivityLog::with('user')->latest()->take(6)->get(),
                'activity_counts'   => [
                    'today' => ActivityLog::whereDate('created_at', today())->count(),
                    'week'  => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'month' => ActivityLog::whereMonth('created_at', now()->month)->count(),
                ],
            ];
        }

        return view('admin.dashboard', [
            'scope'                => $scope,
            'scopeLabel'           => $user->scopeLabel(),
            'totalDossiers'        => $totalDossiers,
            'counts'               => $counts,
            'active'               => $active,
            'toReview'             => $toReview,
            'visitPipeline'        => $visitPipeline,
            'lateDossiers'         => $lateDossiers,
            'newThisWeek'          => $newThisWeek,
            'weekly'               => $weekly,
            'breakdown'            => $breakdown,
            'recentDossiers'       => $recentDossiers,
            'upcomingVisits'       => $upcomingVisits,
            'certificates'         => $certificates,
            'certificatesTotal'    => $certificatesTotal,
            'certPendingDelivery'  => $certPendingDelivery,
            'recentEvents'         => $recentEvents,
            'system'               => $system,
        ]);
    }
}
