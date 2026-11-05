<?php

namespace App\Notifications;

use App\Models\PreventionDossier;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Persisted notification stored in the `notifications` database table.
 * Also dispatches the real-time broadcast event so Echo clients receive it instantly.
 *
 * Two channels:
 *  - 'database' : persists the record for the bell panel & "all notifications" page
 *  - 'broadcast': pushes immediately to open browser tabs via Reverb/WebSocket
 *
 * NOTE: intentionally NOT queued (synchronous) so notifications are stored
 * immediately even without a queue worker running. The broadcast portion
 * will silently no-op if Reverb isn't running, but the database record
 * still lands — the badge count works either way.
 */
class DossierNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PreventionDossier $dossier,
        public readonly string $event,
        public readonly string $title,
        public readonly string $body,
        public readonly string $level = 'info',
        public readonly ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    // ------------------------------------------------------------------
    // Database channel — stored in notifications table
    // ------------------------------------------------------------------

    public function toDatabase(object $notifiable): array
    {
        return [
            'dossier_id' => $this->dossier->id,
            'reference'  => $this->dossier->reference,
            'event'      => $this->event,
            'title'      => $this->title,
            'body'       => $this->body,
            'level'      => $this->level,
            'url'        => $this->url,
        ];
    }

    // ------------------------------------------------------------------
    // Broadcast channel — real-time push via Reverb/Echo
    // ------------------------------------------------------------------

    public function toBroadcast(object $notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'dossier_id' => $this->dossier->id,
            'reference'  => $this->dossier->reference,
            'event'      => $this->event,
            'title'      => $this->title,
            'body'       => $this->body,
            'level'      => $this->level,
            'url'        => $this->url,
        ]);
    }

    public function broadcastType(): string
    {
        return 'dossier.notification';
    }
}
