<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Scoped administrators (gouvernorat / delegation) go straight to
        // their first handled service's dossier workspace — the user-management
        // dashboard is not relevant for them.
        if (auth()->user()->adminScope() !== 'all') {
            $route = \App\Support\AdminScope::firstAllowedServiceRoute(auth()->user());

            return redirect()->route($route ?? 'admin.prevention-dossiers.index');
        }

        $stats = [
            'total_users' => User::count(),
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'recent_users' => User::latest()->take(5)->get(),
            'recent_activities' => ActivityLog::with('user')->latest()->take(7)->get(),
            'activity_counts' => [
                'today' => ActivityLog::whereDate('created_at', today())->count(),
                'week' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'month' => ActivityLog::whereMonth('created_at', now()->month)->count(),
            ],
        ];

        return view('admin.dashboard', $stats);
    }
}
