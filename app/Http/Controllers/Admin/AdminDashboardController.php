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
