<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * JSON API consumed by the notification bell panel in the admin header
 * and the citizen navbar. All routes require auth.
 */
class NotificationController extends Controller
{
    /** GET /notifications/all — full-page list (HTML) */
    public function viewAll(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        $isAdmin = $user->isAdministrator();

        $view = $isAdmin ? 'notifications.index' : 'notifications.index';

        if ($isAdmin) {
            return view('notifications.index', compact('notifications'));
        }

        return view('notifications.index', compact('notifications'));
    }

    /** GET /notifications — return unread + recent read notifications */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($n) => [
                'id'       => $n->id,
                'level'    => $n->data['level'] ?? 'info',
                'title'    => $n->data['title'] ?? 'Notification',
                'body'     => $n->data['body'] ?? '',
                'url'      => $n->data['url'] ?? null,
                'event'    => $n->data['event'] ?? null,
                'read_at'  => $n->read_at?->toIso8601String(),
                'at'       => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /** PATCH /notifications/{id}/read — mark one notification as read */
    public function markRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->notifiable_id === $request->user()->id, 403);

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    /** POST /notifications/mark-all-read — mark all as read */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** DELETE /notifications/{id} — delete one notification */
    public function destroy(Request $request, DatabaseNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->notifiable_id === $request->user()->id, 403);

        $notification->delete();

        return response()->json(['ok' => true]);
    }

    /** DELETE /notifications — delete all notifications for this user */
    public function destroyAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json(['ok' => true]);
    }
}
