<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreventionDossierEvent extends Model
{
    use HasTranslations;

    protected $fillable = [
        'prevention_dossier_id', 'user_id', 'type', 'note', 'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Bilingual note attached to this event — stored as {"fr": …, "ar": …},
     * reading returns the localized string.
     */
    protected function note(): Attribute
    {
        return $this->translated('note');
    }

    // Possible types — useful for reference:
    // submitted | resubmitted | advanced_to_consultation | correction_requested
    // history_snapshot (legacy fallback: old version frozen at resubmit time)
    // approved | cancelled | appointment_scheduled | appointment_passed
    // appointment_failed | appointment_deleted | appointment_cancelled
    // certificate_issued | certificate_collected | certificate_deleted
    // certificate_reset | draft_created | step1_updated | step2_saved
    // document_uploaded | document_deleted

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(PreventionDossier::class, 'prevention_dossier_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // -----------------------------------------------------------------------
    // Display helpers
    // -----------------------------------------------------------------------

    /** Icon (SVG path) shown in the timeline badge for this event type */
    public function iconPath(): string
    {
        return match ($this->type) {
            'submitted', 'resubmitted'  => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
            'advanced_to_consultation'  => 'M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z',
            'correction_requested'      => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            'history_snapshot'          => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'approved', 'certificate_issued' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'cancelled', 'certificate_deleted', 'appointment_deleted' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            'appointment_cancelled'     => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
            'appointment_scheduled'     => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'appointment_passed', 'certificate_collected' => 'M5 13l4 4L19 7',
            'appointment_failed'        => 'M6 18L18 6M6 6l12 12',
            'waiting_delivery', 'delivery_chosen' => 'M9 16l6-6m-6 0l6 6m5-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'certificate_post_sent'     => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
            'certificate_post_delivered' => 'M3 19h18m-2-8V9m-1-1h-4v2m7 0l-3-3m6 3a9 9 0 11-18 0 9 9 0 0118 0z',
            default                     => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }

    /** Human-readable label for the timeline */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'submitted'                 => __('prevention.evt_submitted'),
            'resubmitted'               => __('prevention.evt_resubmitted'),
            'advanced_to_consultation'  => __('prevention.evt_advanced'),
            'correction_requested'      => __('prevention.evt_correction_requested'),
            'history_snapshot'          => __('prevention.evt_history_snapshot'),
            'approved'                  => __('prevention.evt_approved'),
            'cancelled'                 => __('prevention.evt_cancelled'),
            'appointment_scheduled'     => __('prevention.evt_rdv_scheduled'),
            'appointment_passed'        => __('prevention.evt_rdv_passed'),
            'appointment_failed'        => __('prevention.evt_rdv_failed'),
            'appointment_deleted'       => __('prevention.evt_rdv_deleted'),
            'appointment_cancelled'     => __('prevention.evt_rdv_cancelled'),
            'waiting_delivery'          => __('prevention.evt_waiting_delivery'),
            'delivery_chosen'           => __('prevention.evt_delivery_chosen'),
            'certificate_issued'        => __('prevention.evt_cert_issued'),
            'certificate_post_sent'     => __('prevention.evt_cert_post_sent'),
            'certificate_post_delivered' => __('prevention.evt_cert_post_delivered'),
            'certificate_collected'     => __('prevention.evt_cert_collected'),
            'certificate_deleted'       => __('prevention.evt_cert_deleted'),
            'certificate_reset'         => __('prevention.evt_cert_reset'),
            'draft_created'             => __('prevention.evt_draft_created'),
            'step1_updated'             => __('prevention.evt_step1_updated'),
            'step2_saved'               => __('prevention.evt_step2_saved'),
            'document_uploaded'         => __('prevention.evt_doc_uploaded'),
            'document_deleted'          => __('prevention.evt_doc_deleted'),
            default                     => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /** Tailwind dot class used by the timeline */
    public function typeDot(): string
    {
        return match ($this->type) {
            'submitted', 'resubmitted'   => 'bg-teal-500',
            'advanced_to_consultation'   => 'bg-purple-500',
            'correction_requested'       => 'bg-amber-500',
            'history_snapshot'           => 'bg-amber-400',
            'approved'                   => 'bg-emerald-500',
            'cancelled'                  => 'bg-red-400',
            'appointment_scheduled'      => 'bg-blue-500',
            'appointment_passed'         => 'bg-emerald-500',
            'appointment_failed'         => 'bg-red-500',
            'appointment_deleted'        => 'bg-gray-400',
            'appointment_cancelled'      => 'bg-gray-400',
            'waiting_delivery'           => 'bg-amber-500',
            'delivery_chosen'            => 'bg-indigo-500',
            'certificate_issued'         => 'bg-emerald-500',
            'certificate_post_sent'      => 'bg-emerald-500',
            'certificate_post_delivered' => 'bg-teal-500',
            'certificate_collected'      => 'bg-teal-500',
            'certificate_deleted'        => 'bg-gray-400',
            'certificate_reset'         => 'bg-amber-500',
            'draft_created'              => 'bg-teal-400',
            'step1_updated'              => 'bg-teal-400',
            'step2_saved'                => 'bg-teal-400',
            'document_uploaded'          => 'bg-indigo-400',
            'document_deleted'           => 'bg-orange-400',
            default                      => 'bg-gray-400',
        };
    }

    /** Formatted RDV details (visit date / time + building address) when present */
    public function detailsText(): ?string
    {
        $data = $this->data;

        if (! is_array($data)) {
            return null;
        }

        // Correction-history events: summarize the old → new versions and the
        // number of files that were archived for that round.
        if (! empty($data['previous'])) {
            $text = __('prevention.evt_old_version');

            if (! empty($data['archived_files']) && is_array($data['archived_files'])) {
                $text .= ' · ' . __('prevention.evt_files_archived', ['count' => count($data['archived_files'])]);
            }

            return $text;
        }

        if (! empty($data['updated'])) {
            $text = __('prevention.evt_new_version');

            $changed = collect($data['updated'])->filter(fn ($v, $k) => filled($v));

            if ($changed->isNotEmpty()) {
                $keys = $changed->keys()->take(3)->implode(', ');
                $text .= ' · ' . $keys . ($changed->count() > 3 ? ', …' : '');
            }

            return $text;
        }

        // Document upload/delete events
        if (! empty($data['count'])) {
            return __('prevention.evt_files_uploaded', ['count' => $data['count']]);
        }

        if (! empty($data['document'])) {
            $text = __('prevention.evt_file_label', ['name' => $data['document']]);
            if (! empty($data['type'])) {
                $text .= ' · ' . __('prevention.evt_type_label', ['type' => $data['type']]);
            }
            return $text;
        }

        // Step 2 saved — show the building type chosen
        if (! empty($data['building_type'])) {
            $text = __('prevention.evt_building_label', ['name' => $data['building_type']]);
            if (! empty($data['building_activity'])) {
                $text .= ' · ' . __('prevention.evt_activity_label', ['name' => $data['building_activity']]);
            }
            return $text;
        }

        if ((empty($data['visit_date']) && empty($data['reference']) && empty($data['method']))) {
            return null;
        }

        // Delivery-protocol events: show the certificate tracking / method
        if (! empty($data['tracking'])) {
            return __('prevention.evt_tracking_label', ['value' => $data['tracking']]);
        }

        if (! empty($data['method'])) {
            $text = __('prevention.evt_delivery_label', ['value' => $data['method']]);

            if (! empty($data['address'])) {
                $text .= ' · ' . $data['address'];
            }

            return $text;
        }

        // Certificate events: show reference + pickup governorate / delegation
        if (! empty($data['reference'])) {
            $text = __('prevention.evt_certificate_label', ['ref' => $data['reference']]);

            if (! empty($data['delegation'])) {
                $text .= ' · ' . __('prevention.evt_delegation_label', ['value' => $data['delegation']]);
            } elseif (! empty($data['governorate'])) {
                $text .= ' · ' . __('prevention.evt_governorate_label', ['value' => $data['governorate']]);
            }

            return $text;
        }

        $text = __('prevention.evt_visit_label', [
                'date' => Carbon::parse($data['visit_date'])->format('d M Y'),
                'time' => substr((string) ($data['visit_time'] ?? ''), 0, 5),
            ]);

        // The visit takes place at the building — location comes from the dossier
        $address = $this->dossier?->building_address;

        if ($address) {
            $text .= ' · ' . __('prevention.evt_location_label', ['value' => $address]);
        }

        return $text;
    }
}
