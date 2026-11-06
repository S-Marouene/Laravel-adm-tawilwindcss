<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * Admin notification center — monitoring of every notification
 * pushed to every recipient (citizens and back-office users).
 *
 * Access: super-admin + admin only (routes guarded by
 * the 'manage notifications' permission).
 */
class NotificationCenterController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $query = DatabaseNotification::query()
            ->with('notifiable') // morphTo — App\Models\User for both citizens and staff
            ->latest();

        // ── Filters ─────────────────────────────────────────────
        // Status: all / unread / read
        $query->when($request->status === 'unread', fn (Builder $q) => $q->whereNull('read_at'));
        $query->when($request->status === 'read', fn (Builder $q) => $q->whereNotNull('read_at'));

        // Level (info / success / warning / danger)
        $query->when(
            $request->filled('level'),
            fn (Builder $q) => $q->where('data->level', $request->level)
        );

        // Recipient type: back-office roles vs simple citizens
        $query->when($request->filled('to'), function (Builder $q) use ($request) {
            return $request->to === 'staff'
                ? $q->whereHas('notifiable', fn (Builder $u) => $u->whereHas('roles', fn (Builder $r) => $r->whereIn('name', User::ADMIN_ROLES)))
                : $q->whereHas('notifiable', fn (Builder $u) => $u->whereDoesntHave('roles', fn (Builder $r) => $r->whereIn('name', User::ADMIN_ROLES)));
        });

        // Free search over the JSON payload (title FR/AR, body FR/AR)
        $query->when($request->filled('q'), function (Builder $q) use ($request) {
            $term = str_replace(['%', '_'], ['\\%', '\\_'], $request->q);

            $q->where(function (Builder $n) use ($term) {
                foreach (['title_fr', 'title_ar', 'body_fr', 'body_ar'] as $key) {
                    $n->orWhere("data->{$key}", 'like', "%{$term}%");
                }
            });
        });

        // ── Stats (unfiltered — global counts) ──────────────────
        $staffWhere = fn (Builder $u) => $u->whereHas('notifiable', fn (Builder $x) => $x->whereHas('roles', fn (Builder $r) => $r->whereIn('name', User::ADMIN_ROLES)));
        $total = DatabaseNotification::query()->count();
        $stats = [
            'total' => $total,
            'unread' => DatabaseNotification::query()->whereNull('read_at')->count(),
            'sent_staff' => DatabaseNotification::query()->where($staffWhere)->count(),
            'sent_citizens' => $total - DatabaseNotification::query()->where($staffWhere)->count(),
        ];

        $notifications = $query->paginate(25)->withQueryString();

        return view('admin.notification-center.index', compact('notifications', 'stats'));
    }

    /** POST — mark every notification of every user as read. */
    public function markAllRead(Request $request): RedirectResponse
    {
        DatabaseNotification::query()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    /** DELETE — remove all notifications already read. */
    public function clearRead(Request $request): RedirectResponse
    {
        $deleted = DatabaseNotification::query()->whereNotNull('read_at')->delete();

        return back()->with('success', "{$deleted} notification(s) lue(s) supprimée(s).");
    }

    /** DELETE — remove a single notification, whatever the recipient. */
    public function destroy(DatabaseNotification $notification): RedirectResponse
    {
        $notification->delete();

        return back()->with('success', 'Notification supprimée.');
    }
}
