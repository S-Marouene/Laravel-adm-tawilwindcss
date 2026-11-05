<?php

namespace App\Notifications;

use App\Models\PreventionDossier;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Persisted notification stored in the `notifications` database table.
 * Also dispatches the real-time broadcast event so Echo clients receive it instantly.
 *
 * Both French and Arabic translations are stored together so the recipient
 * always sees their preferred language, regardless of when they open the panel.
 *
 * Two channels:
 *  - 'database' : persists the record for the bell panel & "all notifications" page
 *  - 'broadcast': pushes immediately to open browser tabs via Reverb/WebSocket
 */
class DossierNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PreventionDossier $dossier,
        public readonly string $event,
        public readonly string $title_fr,
        public readonly string $body_fr,
        public readonly string $title_ar,
        public readonly string $body_ar,
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
            'title_fr'   => $this->title_fr,
            'body_fr'    => $this->body_fr,
            'title_ar'   => $this->title_ar,
            'body_ar'    => $this->body_ar,
            'level'      => $this->level,
            'url'        => $this->url,
        ];
    }

    // ------------------------------------------------------------------
    // Broadcast channel — real-time push via Reverb/Echo
    // Both languages are sent; the client picks the right one.
    // ------------------------------------------------------------------

    public function toBroadcast(object $notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'dossier_id' => $this->dossier->id,
            'reference'  => $this->dossier->reference,
            'event'      => $this->event,
            'title_fr'   => $this->title_fr,
            'body_fr'    => $this->body_fr,
            'title_ar'   => $this->title_ar,
            'body_ar'    => $this->body_ar,
            'level'      => $this->level,
            'url'        => $this->url,
        ]);
    }

    public function broadcastType(): string
    {
        return 'dossier.notification';
    }
}
