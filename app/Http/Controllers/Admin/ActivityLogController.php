<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view activity-logs');
        $query = ActivityLog::with('user')->latest()->latest('id');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Search within description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->search.'%');
        }

        $logs = $query->paginate(25)->withQueryString();

        $types = ActivityLog::select('type')->distinct()->pluck('type')->sort();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('admin.activity-logs.index', compact('logs', 'types', 'users'));
    }

    public function show(ActivityLog $activityLog)
    {
        $this->authorize('view activity-logs');
        $activityLog->load('user');
        return view('admin.activity-logs.show', compact('activityLog'));
    }

    public function destroy(ActivityLog $activityLog)
    {
        $this->authorize('delete activity-logs');
        $activityLog->delete();

        return redirect()->route('admin.activity-logs.index')
            ->with('success', 'Log entry deleted.');
    }

    public function clear()
    {
        $this->authorize('delete activity-logs');
        ActivityLog::query()->delete();

        return redirect()->route('admin.activity-logs.index')
            ->with('success', 'All activity logs cleared.');
    }
}
