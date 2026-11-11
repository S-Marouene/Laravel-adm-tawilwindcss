@extends('admin.layouts.admin')
@section('title', __('safety.page_title', ['id' => $safetyFileDossier->id]))

@php
    $d = $safetyFileDossier;
    $status = $d->status;

    // ── Référence affichée : officielle > provisoire ──
    $viewRef = $d->reference ?: ('DSH-' . str_pad($d->id, 6, '0', STR_PAD_LEFT));

    $pendingAppointment = $d->appointments->first(fn ($a) => $a->isPending());
    $lastFailedAppointment = $d->appointments->last(fn ($a) => $a->isFailed());
    $lastPassedAppointment = $d->appointments->last(fn ($a) => $a->isPassed());

    /* ── Stepper 5 étapes : dépôt → étude → inspection → certificat émis → retiré/livré ── */
    $currentStep = match ($status) {
        'draft'                  => 1,
        'completed', 'submitted' => 2,
        'under_consultation'     => 3,
        'approved'               => 3,
        'waiting_delivery'       => 4,
        'needs_correction'       => 2,
        // Dossier annulé : l'avancement est figé là où il en était — déduit
        // des preuves (certificat, visites, journal) puisque le statut ne
        // progresse plus. L'étape figée portera la marque rouge « annulé ».
        'cancelled'              => match (true) {
            $d->certificate && ($d->certificate->isCollected() || $d->certificate->isPostDelivered()) => 6,
            $d->certificate !== null       => 5,
            $d->hasPassedAppointment()     => 4,
            $d->appointments->isNotEmpty()
                || $d->events->contains(fn ($e) => $e->type === 'advanced_to_consultation') => 3,
            default                        => 2,
        },
        default                  => null,
    };
    // Certificate issued → step 5 (waiting for pickup/delivery)
    if ($d->certificate && $currentStep < 5) {
        $currentStep = 5;
    }
    // Certificate collected or delivered → step 6 (all done)
    if ($d->certificate && ($d->certificate->isCollected() || $d->certificate->isPostDelivered())) {
        $currentStep = 6;
    }
    $isCancelled = $status === 'cancelled';

    $stepDates = [];
    $stepDates[1] = $d->created_at;
    $stepDates[2] = $d->reviewed_at;
    $stepDates[3] = $lastPassedAppointment?->visit_date ?? $pendingAppointment?->visit_date ?? null;
    $stepDates[4] = $d->certificate?->issued_at;
    $stepDates[5] = $d->certificate?->collected_at ?? $d->certificate?->post_delivered_at ?? null;

    $steps = [];
    // Detect historical issues to mark steps even after completion
    $hadCorrection   = $d->events->contains(fn ($e) => $e->type === 'correction_requested');
    $hadFailedVisit  = $lastFailedAppointment !== null;

    // Étape qui porte la marque « annulé » (dossier annulé uniquement)
    $cancelledAt = $isCancelled ? min($currentStep, 5) : null;

    // Étape 5 — libellé selon le circuit de remise (retrait au bureau / poste B3)
    $certSub5 = match (true) {
        $d->certificate && ($d->certificate->isCollected() || $d->certificate->isPostDelivered()) => __('safety.step_done'),
        $d->certificate?->isPostSent() => __('safety.step_cert_post_sent'),
        $d->certificate?->isPost()     => __('safety.step_cert_ready_post'),
        $d->certificate !== null       => __('safety.step_cert_ready_pickup'),
        default                        => __('safety.step_cert_waiting_pickup'),
    };

    foreach ([1, 2, 3, 4, 5] as $i) {
        if ($isCancelled) {
            if ($i < $cancelledAt)       $state = 'done';
            elseif ($i === $cancelledAt) $state = 'cancelled';
            else                         $state = 'pending';
        } else {
            if ($currentStep === 6 || $i < $currentStep) $state = 'done';
            elseif ($i === $currentStep)                 $state = $status === 'needs_correction' ? 'error' : 'current';
            else                                         $state = 'pending';
        }

        // Override done state if the step had an incident in history
        if ($state === 'done' && $i === 2 && $hadCorrection)  $state = 'warning';
        if ($state === 'done' && $i === 3 && $hadFailedVisit) $state = 'warning';

        $sub = match (true) {
            $state === 'cancelled'          => __('safety.step_cancelled'),
            $state === 'warning' && $i === 2 => __('safety.step_had_correction'),
            $state === 'warning' && $i === 3 => __('safety.step_had_failed_visit'),
            $status === 'needs_correction' && $state === 'error' => __('safety.step_correction_required'),
            $state === 'done'    => __('safety.step_done'),
            $state === 'pending' => __('safety.step_pending'),
            $i === 2 && $hadCorrection => __('safety.step_recheck'),
            $i === 3 && $pendingAppointment => __('safety.step_visit_scheduled', ['date' => $pendingAppointment->visit_date->format('d/m/Y')]),
            $i === 3 && $lastFailedAppointment => __('safety.step_visit_failed_retry'),
            $i === 3             => __('safety.step3_sub'),
            $i === 4             => $d->certificate ? __('safety.step_cert_issued') : __('safety.step_cert_to_issue'),
            $i === 5             => $certSub5,
            default              => __('safety.step_in_progress'),
        };

        // Sous-titre en rouge : correction requise, visite échouée, annulation
        $alert = in_array($state, ['error', 'cancelled'])
            || ($state === 'current' && $i === 3 && $lastFailedAppointment);

        $steps[] = ['num' => $i, 'state' => $state, 'sub' => $sub, 'alert' => $alert];
    }
    // Progression : les étapes « done » et « warning » (terminées malgré un
    // incident résolu) comptent comme réalisées ; annulé = avancement figé.
    $doneSteps = collect($steps)->whereIn('state', ['done', 'warning'])->count();
    $progress  = (int) round($doneSteps / 5 * 100);
    $phaseNum  = $currentStep > 0 ? min($currentStep, 5) : 0;

    /* ── Timeline « journal d'audit » : pastilles + chips ──
       ● amber = validé / officiel    ● red ONPC = convocation / incident
       ● slate = dépôt, transfert interne    ● violet = consultation    ● indigo = livraison */
    $eventChip = fn ($type) => match (true) {
        in_array($type, ['submitted', 'resubmitted', 'draft_created', 'step1_updated', 'step2_saved', 'document_uploaded'])
            => ['bg-secondary-container text-on-secondary-container', 'post_add', __('safety.tl_deposit'), 'bg-surface-dim text-secondary'],
        $type === 'appointment_scheduled'
            => ['bg-primary text-on-primary', 'campaign', __('safety.tl_convocation'), 'bg-primary text-on-primary'],
        in_array($type, ['advanced_to_consultation', 'appointment_deleted'])
            => ['bg-violet-600 text-white', 'sync_alt', __('safety.tl_phase'), 'bg-surface-dim text-secondary'],
        $type === 'correction_requested'
            => ['bg-amber-500 text-white', 'edit_note', __('safety.tl_correction'), 'bg-amber-100 text-amber-800'],
        in_array($type, ['approved', 'appointment_passed', 'waiting_delivery', 'certificate_issued'])
            => ['bg-amber-600 text-white', 'task_alt', __('safety.tl_favorable'), 'bg-amber-100 text-amber-800'],
        $type === 'delivery_chosen'
            => ['bg-indigo-600 text-white', 'local_shipping', __('safety.tl_delivery'), 'bg-indigo-100 text-indigo-800'],
        $type === 'certificate_post_sent'
            => ['bg-blue-600 text-white', 'local_post_office', __('safety.tl_b3_sent'), 'bg-blue-100 text-blue-800'],
        $type === 'certificate_post_delivered'
            => ['bg-cyan-600 text-white', 'mark_email_read', __('safety.tl_delivered'), 'bg-cyan-100 text-cyan-800'],
        $type === 'certificate_collected'
            => ['bg-amber-600 text-white', 'handshake', __('safety.tl_collected'), 'bg-amber-100 text-amber-800'],
        $type === 'certificate_reset'
            => ['bg-indigo-500 text-white', 'undo', __('safety.tl_reset'), 'bg-indigo-100 text-indigo-800'],
        $type === 'history_snapshot' || $type === 'document_deleted'
            => ['bg-slate-400 text-white', 'inventory_2', __('safety.tl_archive'), 'bg-surface-dim text-secondary'],
        // default: cancelled, appointment_failed, certificate_deleted
        default
            => ['bg-red-600 text-white', 'error', __('safety.alert_chip'), 'bg-red-100 text-red-700'],
    };

    /* RDV annulé — participe à l'archive de l'historique (pas un incident) */
    $eventChip = function ($type) use ($eventChip) {
        if ($type === 'appointment_cancelled') {
            return ['bg-slate-400 text-white', 'event_busy', __('safety.tl_cancelled'), 'bg-surface-dim text-secondary'];
        }
        return $eventChip($type);
    };

    /* ── Pastille de statut (héros) ── */
    $statusPill = match ($status) {
        'draft', 'completed'  => ['bg-surface-container-high text-secondary', 'edit_note'],
        'submitted'           => ['bg-secondary-container text-on-secondary-container', 'schedule_send'],
        'under_consultation'  => ['bg-blue-100 text-blue-800', 'manage_search'],
        'needs_correction'    => ['bg-error-container text-error', 'error'],
        'approved'            => ['bg-amber-100 text-amber-900', 'task_alt'],
        'waiting_delivery'    => ['bg-indigo-100 text-indigo-800', 'verified'],
        'cancelled'           => ['bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300', 'cancel'],
        default               => ['bg-surface-container-high text-secondary', 'info'],
    };
    [$statusPillCls, $statusPillIcon] = $statusPill;
    $statusLabel = [
        'draft'              => __('safety.st_draft'),
        'completed'          => __('safety.st_completed'),
        'submitted'          => __('safety.st_submitted'),
        'under_consultation' => __('safety.st_under_consultation'),
        'needs_correction'   => __('safety.st_needs_correction'),
        'approved'           => __('safety.st_approved'),
        'waiting_delivery'   => __('safety.st_waiting_delivery'),
        'cancelled'          => __('safety.st_cancelled'),
    ];

    /* ── Bandeau d'état du héros (contextuel) ── */
    $heroAlert = match (true) {
        $status === 'cancelled'           => 'error',
        $status === 'draft'               => 'draft',
        $status === 'needs_correction'    => 'error',
        $pendingAppointment !== null      => 'visit',
        $status === 'waiting_delivery' && ! $d->certificate => 'delivery',
        $status === 'under_consultation' && $lastFailedAppointment => 'visit_failed',
        $status === 'under_consultation'  => 'info',
        $status === 'approved' && ! $d->certificate => 'info',
        default                           => null,
    };

    /* ── Documents : complétude ── */
    $uploadedTypes = $uploadedDocs->filter(fn ($doc) => $documentTypes->contains('id', $doc->document_type_id))->count();
    $fmtSize = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1) . ' Mo' : number_format($b / 1024, 0) . ' Ko';
    $isDocComplete = $documentTypes->count() > 0 && $uploadedTypes >= $documentTypes->count();
    $docPct = $documentTypes->count() > 0 ? (int) round(min($uploadedTypes, $documentTypes->count()) / $documentTypes->count() * 100) : 0;

    /* ── Certificat : libellés + statuses (palette froide) ── */
    $cert = $d->certificate;
    $certStatusCfg = $cert ? match ($cert->status) {
        \App\Models\SafetyFileCertificate::STATUS_READY           => [__('safety.cert_ready'),              'bg-amber-500 text-white'],
        \App\Models\SafetyFileCertificate::STATUS_POST_SENT       => [__('safety.tl_b3_sent'),          'bg-blue-100 text-blue-800'],
        \App\Models\SafetyFileCertificate::STATUS_POST_DELIVERED  => [__('safety.cert_post_delivered'),  'bg-cyan-100 text-cyan-800'],
        \App\Models\SafetyFileCertificate::STATUS_COLLECTED       => [__('safety.tl_collected'),          'bg-violet-100 text-violet-800'],
        default => [ucfirst($cert->status), 'bg-surface-dim text-secondary'],
    } : null;

    /* ── Fiche impression : édité aujourd'hui ── */
    $printDate = now()->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY, HH:mm');
@endphp

<style>
    /* ═══════════════════════════════════════════════════════════════════
       FICHE DU DOSSIER — impression professionnelle (A4)
       Le contenu écran (.dossier-screen) est masqué ; seule la fiche
       (.print-sheet) est imprimée. Palette fixe noire / rouge ONPC.
       ═══════════════════════════════════════════════════════════════════ */
    .print-sheet { display: none; }

    @media print {
        @page { size: A4; margin: 11mm 11mm 14mm; }

        header, footer, .no-print { display: none !important; }

        html, body {
            background: #ffffff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-sheet { display: block !important; }
    }

    /* — Structure de la fiche (rendu uniquement à l'impression) — */
    .fiche {
        font-family: var(--font-sans, 'Roboto', Arial, sans-serif);
        font-size: 11.5px;
        line-height: 1.5;
        color: #1a1d29;
        background: #ffffff;
    }

    .fiche-band {
        background: #9e001f;
        color: #ffffff;
        padding: 12px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        border-radius: 3px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .fiche-band-logo  { width: 46px; height: 46px; object-fit: contain; border-radius: 4px; background: #fff; padding: 3px; }
    .fiche-band-org   { font-size: 14.5px; font-weight: 800; letter-spacing: .02em; line-height: 1.25; }
    .fiche-band-sub   { font-size: 10px; opacity: .92; font-weight: 400; }
    .fiche-band-ref   { text-align: end; line-height: 1.3; }
    .fiche-band-ref-label { font-size: 9px; text-transform: uppercase; letter-spacing: .14em; opacity: .85; font-weight: 600; }
    .fiche-band-ref-value { font-family: 'Roboto Mono', Menlo, Consolas, monospace; font-size: 15px; font-weight: 700; letter-spacing: .03em; }

    .fiche-title-row {
        display: flex; align-items: baseline; justify-content: space-between; gap: 12px;
        margin: 14px 0 16px; padding-bottom: 8px; border-bottom: 2px solid #1a1d29;
    }
    .fiche-title   { font-size: 15px; font-weight: 800; letter-spacing: .01em; }
    .fiche-printed { font-size: 10px; color: #4b5563; font-weight: 500; white-space: nowrap; }

    .fiche-h2 {
        font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em;
        margin: 0 0 6px; padding-inline-start: 8px;
        border-inline-start: 3.5px solid #9e001f; color: #1a1d29;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .fiche-sec { break-inside: avoid; margin-bottom: 16px; }

    .fiche-table { width: 100%; border-collapse: collapse; }
    .fiche-table th, .fiche-table td {
        border: 1px solid #c7cad1;
        padding: 4.5px 9px;
        text-align: start;
        vertical-align: top;
        font-size: 10.5px;
    }
    .fiche-table th { background: #f4f4f7; font-weight: 700; color: #1a1d29; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .fiche-kv th:first-child  { width: 37%; }
    .fiche-kv-4 th { width: 17.5%; }
    .fiche-table td { color: #24273a; }
    .fiche-mono     { font-family: 'Roboto Mono', Menlo, Consolas, monospace; letter-spacing: .02em; }

    .fiche-table-dark thead th { background: #1a1d29; color: #fff; }
    .fiche-dim      { color: #6b7280; }
    .fiche-missing  { color: #9ca3af; font-style: italic; }

    .fiche-cert {
        border: 1.5px solid #b45309; border-radius: 3px; padding: 8px 12px;
        background: #fffbeb; margin-bottom: 16px;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .fiche-cert-title { font-weight: 800; font-size: 11px; color: #92400e; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 3px; }

    .fiche-foot {
        margin-top: 10px; padding-top: 7px; border-top: 1px solid #c7cad1;
        display: flex; justify-content: space-between; gap: 12px;
        font-size: 9px; color: #6b7280;
    }
</style>

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- ══════════════ CONTENU ÉCRAN ══════════════ --}}
    <div class="dossier-screen no-print space-y-6">

        @if ($errors->any())
            <div class="bg-surface-container-lowest border border-rounded border-red-200 rounded-lg text-body-sm text-red-800 px-4 py-3 shadow-sm flex items-start gap-2.5 border-s-4 border-s-red-600" role="alert">
                <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">error</span>
                <div>
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════════════
             BANDES D'ACTION ADMINITSTRATIVES — contextuelles, en tête de page.
             Chaque bande correspond au PROCHAIN ACTE POSSIBLE du processus :
             étude → consultation (RDV) → visite → certificat.
        ══════════════════════════════════════════════════════════════════════════ --}}

        {{-- ── ACTE 1 : Étude — avancer / correction / annulation (avant consultation) ── --}}
        @can('manage safety-file-dossiers')
        @if (in_array($status, ['completed', 'submitted', 'needs_correction']))
        <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden" x-data="{ fixOpen: false }">
            <div class="border-s-4 border-s-primary p-5">
                <div class="flex flex-wrap items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">fact_check</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('safety.admin_review') }}</h2>
                            <span class="text-[10px] bg-primary text-on-primary font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('safety.action_required') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('safety.review_intro') }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    {{-- Advance to consultation — the only forward move in this phase --}}
                    <form method="POST" action="{{ route('admin.safety-file-dossiers.advance', $d) }}"
                          data-confirm data-confirm-style="info"
                          data-confirm-title="{{ __('safety.confirm_advance') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-3 px-6 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_circle_right</span>
                            {{ __('safety.advance_btn') }}
                        </button>
                    </form>

                    @unless ($pendingAppointment)
                    {{-- Request correction toggle --}}
                    <button type="button" @click="fixOpen = !fixOpen"
                        class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">edit_note</span>
                        <span x-text="fixOpen ? '{{ __('safety.hide_correction_form') }}' : '{{ __('safety.request_correction') }}'">{{ __('safety.request_correction') }}</span>
                    </button>
                </div>

                {{-- Correction form ───────────────────────────────────────────── --}}
                <div x-show="fixOpen" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="mt-4 rounded-lg border border-amber-200 bg-amber-50/60 dark:border-amber-800/40 dark:bg-amber-900/10 p-4">
                    <form method="POST" action="{{ route('admin.safety-file-dossiers.correction', $d) }}"
                          x-data="{ picked: 0 }">
                        @csrf

                        {{-- ── Form header: icon + purpose + what the applicant will see ── --}}
                        <div class="mb-4 flex items-start gap-3">
                            <span class="grid h-9 w-9 flex-none place-items-center rounded-lg bg-amber-500/15 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300" aria-hidden="true">
                                <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">edit_note</span>
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                                    {{ __('safety.fields_to_fix') }}
                                </p>
                                <p class="mt-0.5 text-[11px] leading-relaxed text-secondary dark:text-gray-400">
                                    {{ __('safety.correction_hint') }}
                                </p>
                            </div>
                        </div>

                        {{-- ── Note bilingue pour le demandeur ── --}}
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300 mb-1.5">
                            {{ __('safety.note_to_applicant') }} <span class="text-red-500">*</span>
                        </label>
                        <x-admin.bilingual-note name="admin_note" :rows="3" required
                            placeholder-key="safety.note_correction_placeholder" />

                        {{-- ── Section 1 : champs du dossier à corriger ── --}}
                        <p class="mt-4 mb-2 flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">fact_check</span>
                            <span>{{ __('safety.fields_to_fix') }}</span>
                            <span class="font-normal normal-case tracking-normal text-secondary dark:text-gray-500">{{ __('safety.select_all_apply') }}</span>
                        </p>
                        @php
                            $fixableFields = [
                                'nom_prenom'           => ['label' => __('safety.f_full_name'),       'icon' => 'person'],
                                'cin'                  => ['label' => __('safety.f_cin'),             'icon' => 'badge'],
                                'phone'                => ['label' => __('safety.f_phone'),           'icon' => 'call'],
                                'governorate_id'       => ['label' => __('safety.f_governorate'),     'icon' => 'public'],
                                'delegation_id'        => ['label' => __('safety.f_delegation'),      'icon' => 'account_balance'],
                                'building_address'     => ['label' => __('safety.f_building_address'),'icon' => 'home_work'],
                                // Map position (latitude/longitude) — pinned via Google Maps; correctable on its own.
                                'map_position'         => ['label' => __('safety.f_map'),             'icon' => 'map'],
                                'building_type_id'     => ['label' => __('safety.f_building_type'),   'icon' => 'domain'],
                                'building_activity_id' => ['label' => __('safety.f_activity'),        'icon' => 'category'],
                            ];
                        @endphp
                        <div class="grid gap-1.5"
                             @change="picked = $root.querySelectorAll('[data-fix]:checked, [data-doc]:checked').length">
                            @foreach ($fixableFields as $fieldKey => $fieldDef)
                                @php
                                    $currentVal = match($fieldKey) {
                                        'nom_prenom'           => $d->nom_prenom,
                                        'cin'                  => $d->cin,
                                        'phone'                => $d->phone,
                                        'governorate_id'       => $d->governorate?->name_fr ?? $d->governorate?->name_ar,
                                        'delegation_id'        => $d->delegation?->name_fr ?? $d->delegation?->name_ar,
                                        'building_address'     => $d->building_address,
                                        'building_type_id'     => $d->buildingType?->name,
                                        'building_activity_id' => $d->buildingActivity?->name ?? $d->activity_other,
                                        // Coordinates as "lat, lng" — mono display, LTR in any locale.
                                        'map_position'         => ($d->latitude !== null && $d->longitude !== null)
                                            ? number_format((float) $d->latitude, 6, '.', '').', '.number_format((float) $d->longitude, 6, '.', '')
                                            : null,
                                        default                => null,
                                    };
                                @endphp
                                <label class="group flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-lowest px-3 py-2 transition
                                              hover:border-amber-400/70 hover:bg-surface-container
                                              has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/80 has-[:checked]:shadow-sm
                                              dark:border-gray-600 dark:bg-gray-800/50 dark:hover:border-gray-500
                                              dark:has-[:checked]:border-amber-500/70 dark:has-[:checked]:bg-amber-900/20">
                                    <input type="checkbox" name="fields_to_fix[]" value="{{ $fieldKey }}" data-fix
                                           class="h-4 w-4 shrink-0 cursor-pointer rounded border-surface-dim text-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                           {{ in_array($fieldKey, old('fields_to_fix', [])) ? 'checked' : '' }}>
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-surface-container text-secondary transition
                                                 group-has-[:checked]:bg-amber-100 group-has-[:checked]:text-amber-700
                                                 dark:bg-gray-700 dark:text-gray-300 dark:group-has-[:checked]:bg-amber-900/50 dark:group-has-[:checked]:text-amber-300"
                                          aria-hidden="true">
                                        <span class="material-symbols-outlined text-sm">{{ $fieldDef['icon'] }}</span>
                                    </span>
                                    <span class="flex-1 text-xs font-semibold text-on-surface dark:text-gray-200">{{ $fieldDef['label'] }}</span>
                                    @if ($currentVal)
                                        @if ($fieldKey === 'map_position')
                                            {{-- Coordinates: keep the LTR order even in the RTL Arabic UI --}}
                                            <span class="truncate max-w-36 text-[10px] font-semibold font-mono text-outline dark:text-gray-500" dir="ltr" title="{{ $currentVal }}">{{ $currentVal }}</span>
                                        @else
                                            <span class="truncate max-w-28 text-[10px] text-outline dark:text-gray-500" title="{{ $currentVal }}">{{ $currentVal }}</span>
                                        @endif
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        {{-- ── Section 2 : documents à remplacer ── --}}
                        @php
                            $corrDocTypes = \App\Models\DocumentType::service($d->service)
                                ->orderBy('sort_order')->get();
                        @endphp
                        @if ($corrDocTypes->isNotEmpty())
                            <p class="mt-4 mb-2 flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">folder_open</span>
                                <span>{{ __('safety.docs_to_reupload') }}</span>
                                <span class="font-normal normal-case tracking-normal text-secondary dark:text-gray-500">{{ __('safety.select_replacement') }}</span>
                            </p>
                            <div class="grid gap-1.5"
                                 @change="picked = $root.querySelectorAll('[data-fix]:checked, [data-doc]:checked').length">
                                @foreach ($corrDocTypes as $docType)
                                    <label class="group flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-lowest px-3 py-2 transition
                                                  hover:border-amber-400/70 hover:bg-surface-container
                                                  has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/80 has-[:checked]:shadow-sm
                                                  dark:border-gray-600 dark:bg-gray-800/50 dark:hover:border-gray-500
                                                  dark:has-[:checked]:border-amber-500/70 dark:has-[:checked]:bg-amber-900/20">
                                        <input type="checkbox" name="docs_to_fix[]" value="{{ $docType->id }}" data-doc
                                               class="h-4 w-4 shrink-0 cursor-pointer rounded border-surface-dim text-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                               {{ in_array($docType->id, old('docs_to_fix', [])) ? 'checked' : '' }}>
                                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-surface-container text-secondary transition
                                                     group-has-[:checked]:bg-amber-100 group-has-[:checked]:text-amber-700
                                                     dark:bg-gray-700 dark:text-gray-300 dark:group-has-[:checked]:bg-amber-900/50 dark:group-has-[:checked]:text-amber-300"
                                              aria-hidden="true">
                                            <span class="material-symbols-outlined text-sm">description</span>
                                        </span>
                                        <span class="flex-1 text-xs font-semibold text-on-surface dark:text-gray-200">{{ $docType->name }}</span>
                                        @if ($uploadedDocs->has($docType->id))
                                            <span class="text-[10px] rounded-full bg-blue-100 dark:bg-blue-900/30 px-1.5 py-0.5 text-blue-700 dark:text-blue-300 font-bold whitespace-nowrap">{{ __('safety.uploaded_chip') }}</span>
                                        @else
                                            <span class="text-[10px] rounded-full bg-red-100 dark:bg-red-900/30 px-1.5 py-0.5 text-red-600 dark:text-red-400 font-bold whitespace-nowrap">{{ __('safety.missing_chip') }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        {{-- ── Submit — disabled until at least one item is picked ── --}}
                        <button type="submit" :disabled="picked === 0"
                            class="mt-4 w-full inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-2.5 px-5 rounded-lg transition-colors shadow-sm
                                   disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-amber-500">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">send</span>
                            {{ __('safety.send_correction') }}
                        </button>
                        <p class="mt-1.5 text-center text-[10px] text-secondary dark:text-gray-400"
                           x-show="picked === 0" x-cloak role="status">{{ __('safety.correction_empty') }}</p>
                    </form>
                </div>
                @endunless
            </div>
        </section>
        @endif
        @endcan

        {{-- ── ACTE 2 : Correction demandée — rappel de la note envoyée ── --}}
        @if ($status === 'needs_correction' && $d->admin_note)
        <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden">
            <div class="border-s-4 border-s-red-600 p-5">
                <div class="flex items-start gap-3.5 mb-3">
                    <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">assignment_late</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('safety.correction_requested_title') }}</h2>
                            <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded">{{ __('safety.alert_chip') }}</span>
                        </div>
                    </div>
                </div>
                <div class="rounded border border-red-200 bg-red-50 dark:bg-red-900/10 px-4 py-3">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                        {{ __('safety.note_visible_applicant') }}
                    </p>
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $d->admin_note }}</p>
                </div>
            </div>
        </section>
        @endif

        {{-- ── ACTE 3 : Consultation — programmer la visite (RDV)
              Affiché pour un dossier « en consultation » OU « approuvé » sans
              visite passée (un dossier approuvé doit quand même être inspecté
              avant la délivrance du certificat). Si une visite a échoué, la
              bande "visite échouée" ci-dessous porte le nouveau RDV ; si un
              RDV est déjà programmé, c'est la bande "RDV programmé". ── --}}
        @if (in_array($status, ['under_consultation', 'approved']) && ! $pendingAppointment && ! $lastFailedAppointment)
        <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
            <div class="border-s-4 border-s-primary p-5">
                <div class="flex items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">event_available</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('safety.consultation_phase') }}</h2>
                            <span class="text-[10px] bg-primary text-on-primary font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('safety.action_required') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('safety.consultation_intro') }}</p>
                    </div>
                </div>

                {{-- Contact strip — applicant's phone for the convocation --}}
                <div class="flex flex-wrap items-center gap-3 mb-4 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-2.5 text-xs">
                    <span class="material-symbols-outlined text-blue-600 text-base" aria-hidden="true">call</span>
                    <span class="text-secondary">{{ __('safety.applicant_phone_convocation') }}</span>
                    <strong class="font-mono num-ltr text-on-surface" dir="ltr">{{ $d->phone ?? __('safety.not_provided') }}</strong>
                </div>

                @can('manage safety-file-dossiers')
                    <form method="POST" action="{{ route('admin.safety-file-dossiers.appointments.store', $d) }}" class="grid grid-cols-1 sm:grid-cols-[1fr,1fr,auto] items-end gap-3">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('safety.visit_date') }} <span class="text-red-500">*</span></label>
                        <input type="date" name="visit_date" required min="{{ date('Y-m-d') }}" value="{{ old('visit_date') }}" class="w-full text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('safety.visit_time') }} <span class="text-red-500">*</span></label>
                        <input type="time" name="visit_time" required value="{{ old('visit_time') }}" class="w-full text-sm">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('safety.note_for_applicant') }} <span class="font-normal normal-case">{{ __('safety.note_optional_convocation') }}</span></label>
                        <x-admin.bilingual-note name="note" :rows="2"
                            placeholder-key="safety.visit_instructions_placeholder" />
                    </div>
                    <button type="submit" class="sm:col-span-3 w-full inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">calendar_add_on</span>
                        {{ __('safety.schedule_visit') }}
                    </button>
                    <p class="sm:col-span-3 text-[11px] text-secondary flex items-start gap-1.5">
                        <span class="material-symbols-outlined text-sm mt-px" aria-hidden="true">location_on</span>
                        <span>{{ __('safety.visit_at_building') }} <strong class="font-semibold break-all">{{ $d->building_address ?? '—' }}</strong></span>
                    </p>
                    </form>

                @endcan
            </div>
        </section>
        @endif

        {{-- ── RDV programmé — consignes de visite (avi inspécteur) ── --}}
        @if ($pendingAppointment)
        <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print">
            <div class="border-s-4 border-s-primary p-5">
                <div class="flex items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">assignment_late</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                {{ __('safety.next_visit_at', ['date' => $pendingAppointment->visit_date->format('d/m/Y'), 'time' => substr($pendingAppointment->visit_time, 0, 5)]) }}
                            </h2>
                            <span class="text-[10px] bg-primary text-on-primary font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('safety.convocation_sent') }}</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                        <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">person_check</span>
                        <div><span class="font-bold text-on-surface block">{{ __('safety.card_presence') }}</span><span class="text-secondary text-[11px]">{{ __('safety.must_be_on_site', ['name' => $d->nom_prenom]) }}</span></div>
                    </div>
                    <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                        <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">menu_book</span>
                        <div><span class="font-bold text-on-surface block">{{ __('safety.card_registre') }}</span><span class="text-secondary text-[11px]">{{ __('safety.card_registre_sub') }}</span></div>
                    </div>
                    <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                        <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">door_front</span>
                        <div><span class="font-bold text-on-surface block">{{ __('safety.card_accessibility') }}</span><span class="text-secondary text-[11px]">{{ __('safety.card_accessibility_sub') }}</span></div>
                    </div>
                    <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                        <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">water_drop</span>
                        <div><span class="font-bold text-on-surface block">{{ __('safety.card_fire') }}</span><span class="text-secondary text-[11px]">{{ __('safety.card_fire_sub') }}</span></div>
                    </div>
                </div>
                @if ($pendingAppointment->note)
                <div class="mt-4 rounded border border-amber-200 bg-amber-50/60 dark:border-amber-800/40 dark:bg-amber-900/10 p-3">
                    <p class="text-[11px] font-bold text-amber-900 dark:text-amber-300 mb-0.5 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">event_note</span>{{ __('safety.note_label') }}
                    </p>
                    <p class="text-xs text-amber-900 dark:text-amber-200 whitespace-pre-line leading-relaxed">{{ $pendingAppointment->note }}</p>
                </div>
                @endif

                @can('manage safety-file-dossiers')
                {{-- Actions admin : enregistrer le résultat ou annuler le RDV courant, directement depuis le bandeau --}}
                <div x-data="{ openResult: false, cancelOpen: false }" class="mt-4 pt-4 border-t border-surface-dim">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="openResult = !openResult; cancelOpen = false"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary hover:bg-primary-container text-on-primary px-3.5 py-2 text-[11px] font-bold transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">fact_check</span>
                            <span x-text="openResult ? '{{ __('safety.hide_result_form') }}' : '{{ __('safety.record_result') }}'"></span>
                        </button>
                        <button type="button" @click="cancelOpen = !cancelOpen; openResult = false"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 dark:border-red-800/40 bg-red-50 dark:bg-red-900/20 px-3.5 py-2 text-[11px] font-bold text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors"
                            title="{{ __('safety.cancel_appt_title') }}">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">cancel</span>
                            <span x-text="cancelOpen ? '{{ __('safety.hide_cancellation') }}' : '{{ __('safety.cancel_appointment') }}'"></span>
                        </button>
                        <span class="text-[11px] text-secondary hidden sm:inline">
                            {{ __('safety.hint_record_flow') }}
                        </span>
                    </div>
                    @include('admin.safety-file-dossiers.partials.appointment-panels', ['d' => $d, 'appt' => $pendingAppointment])
                </div>
                @endcan
            </div>
        </section>
        @endif

        {{-- ── Visite échouée : le record (passé/échoué + note) vit DANS cette
             bande, et quand la visite n'est pas bonne, un nouveau RDV se
             programme directement ici. Pas de "toast" séparé. ── --}}
        {{-- Masquée si une visite a déjà été réussie ensuite (le dossier est
             passé en « attente de remise » — l'échec historique reste au
             journal d'audit et dans l'historique des visites). ── --}}
        @if ($lastFailedAppointment && ! $d->hasPassedAppointment() && ! in_array($status, ['cancelled', 'draft']))
        <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden no-print">
            <div class="border-s-4 border-s-red-600 p-5">
                <div class="flex items-start gap-3.5 mb-3">
                    <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">report</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('safety.visit_failed_at', ['date' => $lastFailedAppointment->visit_date->format('d/m/Y'), 'time' => substr($lastFailedAppointment->visit_time, 0, 5)]) }}</h2>
                            <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('safety.alert_chip') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('safety.failed_intro') }}</p>
                    </div>
                </div>
                @if ($lastFailedAppointment->note)
                <div class="rounded border border-red-200 bg-red-50 dark:border-red-800/30 dark:bg-red-900/10 px-4 py-3">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>{{ __('safety.inspector_note') }}
                    </p>
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $lastFailedAppointment->note }}</p>
                </div>
                @endif

                {{-- Nouveau RDV directement dans la bande, tant qu'aucun n'est programmé --}}
                @if (! $pendingAppointment)
                    @can('manage safety-file-dossiers')
                    <div class="mt-4 rounded-lg border border-red-200 dark:border-red-800/40 bg-surface-container-low/60 p-4">
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-3 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">event_available</span>
                            {{ __('safety.schedule_new_rdv_title') }}
                        </p>
                        <form method="POST" action="{{ route('admin.safety-file-dossiers.appointments.store', $d) }}" class="grid grid-cols-1 sm:grid-cols-[1fr,1fr] gap-3">
                            @csrf
                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('safety.new_visit_date') }} <span class="text-red-500">*</span></label>
                                <input type="date" name="visit_date" required min="{{ date('Y-m-d') }}" value="{{ old('visit_date') }}" class="w-full text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('safety.new_visit_time') }} <span class="text-red-500">*</span></label>
                                <input type="time" name="visit_time" required value="{{ old('visit_time') }}" class="w-full text-sm">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('safety.note_for_applicant') }} <span class="font-normal normal-case">{{ __('safety.optional') }}</span></label>
                                <x-admin.bilingual-note name="note" :rows="2"
                                    placeholder-key="safety.new_visit_note_placeholder" />
                            </div>
                            <p class="sm:col-span-2 text-[11px] text-secondary flex items-start gap-1.5">
                                <span class="material-symbols-outlined text-sm mt-px" aria-hidden="true">location_on</span>
                                <span>{{ __('safety.visit_at_building') }} <strong class="font-semibold break-all">{{ $d->building_address ?? '—' }}</strong> · {{ __('safety.applicant_phone') }} <strong class="font-mono" dir="ltr">{{ $d->phone ?? '—' }}</strong></span>
                            </p>
                            <div class="sm:col-span-2">
                                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">calendar_add_on</span>
                                    {{ __('safety.schedule_new_visit') }}
                                </button>
                            </div>
                        </form>
                    </div>
                    @endcan
                @else
                    <p class="mt-3 text-xs font-semibold text-secondary flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">event_available</span>
                        {{ __('safety.next_visit_ready', ['date' => $pendingAppointment->visit_date->format('d/m/Y'), 'time' => substr($pendingAppointment->visit_time, 0, 5)]) }}.
                    </p>
                @endif
            </div>
        </section>
        @endif

        {{-- ── ACTE 4 : Émission du certificat — formulaire de livraison ── --}}
        @can('manage safety-file-dossiers')
        @if ($status === 'waiting_delivery' && ! $cert)
        <section id="act-deliver-cert" class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden" x-data="{ open: true }">
            <div class="border-s-4 border-s-red-600 p-5">
                <div class="flex flex-wrap items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">workspace_premium</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('safety.cert_title') }}</h2>
                            <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('safety.action_required') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('safety.not_issued_yet') }}</p>
                    </div>
                    <button type="button" @click="open = !open" class="text-secondary hover:text-on-surface transition-colors" :aria-expanded="open">
                        <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>

                @if ($d->delivery_method)
                    <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 dark:border-red-800/30 bg-red-50/60 dark:bg-red-900/10 px-3 py-2 text-xs text-secondary">
                        <span class="material-symbols-outlined text-base text-red-600 dark:text-red-400 mt-px shrink-0" aria-hidden="true">touch_app</span>
                        <span>{{ __('safety.applicant_chose') }} <strong class="text-on-surface">{{ ['pickup' => __('safety.pickup_office'), 'post' => __('safety.poste_tunisienne'), 'digital' => __('safety.digital_download')][$d->delivery_method] ?? ucfirst($d->delivery_method) }}</strong> {{ __('safety.pre_selected_below') }}</span>
                    </div>
                @endif

                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                    <form method="POST" action="{{ route('admin.safety-file-dossiers.certificate.store', $d) }}">
                        @csrf
                        <div class="space-y-3"
                             x-data="{
                                method: '{{ old('delivery_method', ($d->delivery_method === 'post' ? 'pickup' : ($d->delivery_method ?? 'pickup'))) }}',
                                gov: {{ old('governorate_id', $d->governorate_id) ?? 'null' }},
                                del: {{ old('delegation_id', $d->delegation_id) ?? 'null' }},
                                map: {{ \Illuminate\Support\Js::from($governorates
                                    ->mapWithKeys(fn ($g) => [
                                        $g->id => [
                                            'name'   => $g->name_fr ?? $g->name_ar,
                                            'office' => ['address' => $g->office_address, 'phone' => $g->office_phone, 'hours' => $g->office_hours],
                                            'delegations' => $g->delegations->map(fn ($del) => [
                                                'id'     => $del->id,
                                                'name'   => $del->name_fr ?? $del->name_ar,
                                                'office' => ['address' => $del->office_address, 'phone' => $del->office_phone, 'hours' => $del->office_hours],
                                            ])->values()->all(),
                                        ],
                                    ])
                                    ->toArray()) }},
                                get pickedOffice() {
                                    if (! this.gov) return null;
                                    const entry = this.map[this.gov] || {};
                                    if (this.del) {
                                        const del = (entry.delegations || []).find(d => String(d.id) === String(this.del));
                                        if (del?.office?.address || del?.office?.phone || del?.office?.hours) return { office: del.office, name: del.name };
                                    }
                                    if (entry.office?.address || entry.office?.phone || entry.office?.hours) return { office: entry.office, name: entry.name };
                                    return null;
                                },
                             }">

                            {{-- Mode de remise — Poste Tunisienne désactivée --}}
                            <div class="overflow-hidden rounded-lg border border-surface-dim dark:border-gray-600">
                                <p class="px-3 pb-1.5 pt-2.5 text-[10px] font-extrabold uppercase tracking-wider text-secondary">{{ __('safety.delivery_method') }}</p>
                                <label class="flex cursor-pointer items-center gap-2.5 px-3 py-2 hover:bg-surface-container dark:hover:bg-gray-700/40"
                                       :class="method === 'pickup' ? 'bg-amber-50 dark:bg-amber-900/20' : ''">
                                    <input type="radio" name="delivery_method" value="pickup" x-model="method" required class="text-amber-600">
                                    <span class="material-symbols-outlined text-amber-600 dark:text-amber-400" aria-hidden="true">storefront</span>
                                    <span class="flex-1 text-sm font-bold text-on-surface">{{ __('safety.pickup_office') }}</span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-600 text-white">{{ __('safety.available') }}</span>
                                </label>
                                <div class="flex select-none items-center gap-2.5 border-t border-surface-dim px-3 py-2 opacity-55 dark:border-gray-600">
                                    <input type="radio" disabled class="text-gray-400">
                                    <span class="material-symbols-outlined text-secondary" aria-hidden="true">local_shipping</span>
                                    <span class="flex-1 text-sm font-bold text-outline dark:text-gray-500">{{ __('safety.poste_tunisienne') }}</span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ __('safety.coming_soon') }}</span>
                                </div>
                                <div class="flex select-none items-center gap-2.5 border-t border-surface-dim px-3 py-2 opacity-55 dark:border-gray-600">
                                    <input type="radio" disabled class="text-gray-400">
                                    <span class="material-symbols-outlined text-secondary" aria-hidden="true">download</span>
                                    <span class="flex-1 text-sm font-bold text-outline dark:text-gray-500">{{ __('safety.digital_download') }}</span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ __('safety.coming_soon') }}</span>
                                </div>
                            </div>

                            {{-- Pickup : gouvernorat + délégation --}}
                            <div x-show="method === 'pickup'">
                                <div>
                                    <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.gouvernorat') }} <span class="text-red-500">*</span></label>
                                    <select name="governorate_id" :required="method === 'pickup'" x-model="gov"
                                            x-on:change="if (!(map[gov]?.delegations || []).some(d => String(d.id) === String(del))) del = ''"
                                            class="w-full text-sm">
                                        <option value="">{{ __('safety.select_gouvernorat') }}</option>
                                        <template x-for="(entry, govId) in map" :key="govId">
                                            <option :value="govId" :selected="String(govId) === String(gov)" x-text="entry.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="mt-3">
                                    <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.pickup_delegation') }} <span class="font-normal normal-case">{{ __('safety.optional') }}</span></label>
                                    <select name="delegation_id" x-model="del" class="w-full text-sm">
                                        <option value="">{{ __('safety.all_delegations') }}</option>
                                        <template x-for="dele in (map[gov] ? map[gov].delegations : [])" :key="dele.id">
                                            <option :value="dele.id" :selected="String(dele.id) === String(del)" x-text="dele.name"></option>
                                        </template>
                                    </select>
                                    <p class="mt-1 flex items-start gap-1 text-[10px] text-secondary">
                                        <span class="material-symbols-outlined text-xs mt-px shrink-0" aria-hidden="true">info</span>
                                        <span x-show="!del">{{ __('safety.delegation_empty_hint') }}</span>
                                        <span x-show="del" x-cloak>{{ __('safety.delegation_selected_hint') }}</span>
                                    </p>
                                </div>
                                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50/60 p-3 dark:border-amber-800/40 dark:bg-amber-900/10 animate-in fade-in duration-200">
                                    <template x-if="pickedOffice">
                                        <div>
                                            <p class="mb-1 flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">storefront</span>
                                                {{ __('safety.pickup_preview_title') }} —
                                                <span class="normal-case tracking-normal" x-text="pickedOffice.name"></span>
                                            </p>
                                            <div class="grid grid-cols-1 gap-1 text-[11px] text-amber-900 dark:text-amber-100 sm:grid-cols-2">
                                                <span x-show="pickedOffice.office.address" class="flex items-start gap-1.5 min-w-0">
                                                    <span class="material-symbols-outlined text-sm mt-px shrink-0" aria-hidden="true">location_on</span>
                                                    <span class="min-w-0"><span class="block text-secondary dark:text-amber-300/70 text-[9px] uppercase font-bold tracking-wider">{{ __('admin.office_address') }}</span><span x-text="pickedOffice.office.address"></span></span>
                                                </span>
                                                <span x-show="pickedOffice.office.phone" class="flex items-start gap-1.5 min-w-0">
                                                    <span class="material-symbols-outlined text-sm mt-px shrink-0" aria-hidden="true">call</span>
                                                    <span class="min-w-0"><span class="block text-secondary dark:text-amber-300/70 text-[9px] uppercase font-bold tracking-wider">{{ __('admin.office_phone') }}</span><span class="font-mono font-bold" dir="ltr" x-text="pickedOffice.office.phone"></span></span>
                                                </span>
                                                <span x-show="pickedOffice.office.hours" class="flex items-start gap-1.5 min-w-0 sm:col-span-2">
                                                    <span class="material-symbols-outlined text-sm mt-px shrink-0" aria-hidden="true">schedule</span>
                                                    <span class="min-w-0"><span class="block text-secondary dark:text-amber-300/70 text-[9px] uppercase font-bold tracking-wider">{{ __('admin.office_hours') }}</span><span x-text="pickedOffice.office.hours"></span></span>
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                    <p x-show="!pickedOffice" class="flex items-start gap-1.5 text-[11px] leading-relaxed text-amber-800 dark:text-amber-300">
                                        <span class="material-symbols-outlined text-sm mt-px shrink-0" aria-hidden="true">info</span>
                                        {{ __('safety.office_no_details') }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.valid_until') }} <span class="font-normal normal-case">{{ __('safety.optional') }}</span></label>
                                    <input type="date" name="valid_until" min="{{ date('Y-m-d') }}" value="{{ old('valid_until') }}" class="w-full text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.issue_note_label') }} <span class="font-normal normal-case text-primary">{{ __('safety.visible_short') }}</span></label>
                                    <x-admin.bilingual-note name="note" :rows="2" class="mt-1" />
                                </div>
                            </div>
                            <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">workspace_premium</span>
                                {{ __('safety.confirm_issue') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        @endif
        @endcan

        {{-- ── ACTE 5 : Certificat émis — pipeline + détails + actions + révocation ── --}}
        @can('manage safety-file-dossiers')
        @if ($cert && ! $cert->isCollected() && ! $cert->isPostDelivered())
        @php
            $certSteps = $cert->isPickup()
                ? [[__('safety.cs_ready_office'), 'schedule_send', $cert->issued_at],
                   [__('safety.cs_collected'), 'check', $cert->collected_at]]
                : [[__('safety.cs_ready'), 'schedule_send', $cert->issued_at],
                   [__('safety.cs_sent_b3'), 'local_post_office', $cert->post_sent_at],
                   [__('safety.cs_delivered'), 'mark_email_read', $cert->post_delivered_at]];
            $stepIdx = $cert->isPickup()
                ? ($cert->isCollected() ? 1 : 0)
                : match ($cert->status) {
                    \App\Models\SafetyFileCertificate::STATUS_POST_SENT      => 1,
                    \App\Models\SafetyFileCertificate::STATUS_POST_DELIVERED => 2,
                    default => 0,
                };
            $act5Theme = match(true) {
                $cert->isPickup() && $cert->isReady() => ['amber', 'handshake', __('safety.mark_collected')],
                $cert->isPickup()                    => ['amber', 'undo', __('safety.mark_ready_uncollect')],
                $cert->isPost() && $cert->isReady()  => ['blue', 'local_post_office', __('safety.register_post')],
                $cert->isPostSent()                  => ['cyan', 'mark_email_read', __('safety.confirm_post_delivery')],
                default                              => ['violet', 'verified', __('safety.delivery_confirmed')],
            };
            [$act5Color, $act5Icon, $act5Title] = $act5Theme;
            $act5BorderCls = match($act5Color) {
                'amber' => 'border-amber-200 dark:border-amber-800/40',
                'blue'  => 'border-blue-200 dark:border-blue-800/40',
                'cyan'  => 'border-cyan-200 dark:border-cyan-800/40',
                default => 'border-violet-200 dark:border-violet-800/40',
            };
            $act5BarCls = match($act5Color) {
                'amber' => 'border-s-amber-600',
                'blue'  => 'border-s-blue-600',
                'cyan'  => 'border-s-cyan-600',
                default => 'border-s-violet-600',
            };
            $act5IconCls = match($act5Color) {
                'amber' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
                'blue'  => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
                'cyan'  => 'bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-300',
                default => 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300',
            };
            $act5BadgeCls = match($act5Color) {
                'amber' => 'bg-amber-600 text-white',
                'blue'  => 'bg-blue-600 text-white',
                'cyan'  => 'bg-cyan-600 text-white',
                default => 'bg-violet-600 text-white',
            };
        @endphp
        <section id="act-cert-action" class="bg-surface-container-lowest border rounded-lg shadow-sm overflow-hidden {{ $act5BorderCls }}">
            <div class="border-s-4 p-5 {{ $act5BarCls }}">

                {{-- ── Header ── --}}
                <div class="flex flex-wrap items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded flex items-center justify-center flex-shrink-0 {{ $act5IconCls }}">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">{{ $act5Icon }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('safety.cert_title') }} — <span class="font-mono" dir="ltr">{{ $cert->reference }}</span></h2>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-surface-dim text-secondary">{{ $cert->isPickup() ? __('safety.pickup_chip') : __('safety.poste_tunisienne') }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $certStatusCfg[1] }}">{{ $certStatusCfg[0] }}</span>
                            </div>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">
                            @if ($cert->isPickup()) {{ __('safety.pickup_workflow_short') }}
                            @elseif ($cert->isPost()) {{ __('safety.post_workflow_short') }}
                            @endif
                            — {{ __('safety.issued_on', ['date' => $cert->issued_at->format('d/m/Y')]) }}
                        </p>
                    </div>
                </div>

                {{-- 1. Pipeline de livraison --}}
                <div class="rounded-lg border border-surface-dim bg-surface-container-low/60 p-3.5 mb-4">
                    <p class="mb-2.5 text-[10px] font-extrabold uppercase tracking-wider text-secondary">
                        @if ($cert->isPickup()) {{ __('safety.pickup_workflow_short') }} @else {{ __('safety.post_workflow_strict') }} @endif
                    </p>
                    <ol class="flex flex-col gap-2.5 sm:flex-row sm:items-stretch">
                        @foreach ($certSteps as $i => $step)
                            @php $done = $i < $stepIdx; $current = $i === $stepIdx; @endphp
                            <li class="flex flex-1 items-start gap-2.5 sm:flex-col sm:items-start" aria-current="{{ $current ? 'step' : 'false' }}">
                                <span class="flex h-7 w-7 flex-none items-center justify-center rounded-full text-[11px] font-black
                                    {{ $done ? 'bg-amber-600 text-white ring-4 ring-amber-100 dark:ring-amber-900/40' : ($current ? 'bg-primary text-on-primary ring-4 ring-primary-fixed/70' : 'bg-surface-container text-outline border border-surface-dim dark:bg-gray-700') }}">
                                    @if ($done)
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">check</span>
                                    @elseif ($current)
                                        {{ $i + 1 }}
                                    @else
                                        <span class="material-symbols-outlined text-xs" aria-hidden="true">circle</span>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold leading-tight {{ $done ? 'text-amber-800 dark:text-amber-400' : ($current ? 'text-primary' : 'text-outline dark:text-gray-500') }}">{{ $step[0] }}</p>
                                    <p class="mt-0.5 font-mono text-[10px] text-secondary" dir="ltr">{{ ($done || $current) && $step[2] ? $step[2]->format('d/m/Y') : __('safety.pending_dots') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- 2. Détails livraison --}}
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2.5 text-xs mb-4">
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.cert_ref') }}</dt>
                        <dd class="font-mono font-bold text-on-surface" dir="ltr">{{ $cert->reference }}</dd>
                    </div>
                    <div class="text-end">
                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.delivery') }}</dt>
                        <dd class="font-semibold text-on-surface">
                            @if ($cert->isPost()) {{ __('safety.poste_tunisienne') }}
                            @elseif ($cert->isPickup()) {{ __('safety.pickup_office') }}
                            @else — @endif
                        </dd>
                    </div>

                    @if ($cert->isPickup())
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.pickup_gov') }}</dt>
                            <dd class="text-end font-semibold text-on-surface">{{ $cert->governorate?->name_fr ?? $cert->governorate?->name_ar ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.pickup_delegation') }}</dt>
                            <dd class="text-end font-semibold text-on-surface">{{ $cert->delegation?->name_fr ?? $cert->delegation?->name_ar ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.issued_dt') }}</dt>
                            <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->issued_at->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.valid_until') }}</dt>
                            <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->valid_until ? $cert->valid_until->format('d/m/Y') : '—' }}</dd>
                        </div>
                        @if ($cert->collected_at)
                        <div class="col-span-2">
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.collected_on') }}</dt>
                            <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->collected_at->format('d/m/Y, H:i') }}</dd>
                        </div>
                        @endif
                    @else
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.recipient') }}</dt>
                            <dd class="text-end font-semibold text-on-surface">{{ $cert->post_recipient ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.f_phone') }}</dt>
                            <dd class="text-end font-semibold font-mono num-ltr text-on-surface" dir="ltr">{{ $cert->post_phone ?? '—' }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.postal_address') }}</dt>
                            <dd class="mt-1 rounded-lg bg-surface-container px-2.5 py-2 text-[11px] leading-relaxed break-words text-on-surface dark:bg-gray-700/30">{{ $cert->post_address ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.tracking_b3') }}</dt>
                            <dd class="text-end font-mono font-semibold text-on-surface" dir="ltr">{{ $cert->post_tracking ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.issued_valid_until') }}</dt>
                            <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->issued_at->format('d/m/Y') }} / {{ $cert->valid_until ? $cert->valid_until->format('d/m/Y') : '—' }}</dd>
                        </div>
                        @if ($cert->post_sent_at)
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.sent_on') }}</dt>
                            <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->post_sent_at->format('d/m/Y, H:i') }}</dd>
                        </div>
                        @endif
                        @if ($cert->post_delivered_at)
                        <div>
                            <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.delivered_on') }}</dt>
                            <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->post_delivered_at->format('d/m/Y, H:i') }}</dd>
                        </div>
                        @endif
                    @endif
                </dl>

                @if ($cert->note)
                    <div class="rounded-lg border border-surface-dim bg-surface-container px-2.5 py-2 mb-4 dark:bg-gray-700/30">
                        <p class="mb-0.5 text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.issue_note_label') }} — {{ __('safety.visible_short') }}</p>
                        <p class="whitespace-pre-line text-xs text-secondary dark:text-gray-300">{{ $cert->note }}</p>
                    </div>
                @endif

                {{-- 3. Actions — uniquement les mouvements légaux de l'état actuel --}}
                <div class="space-y-2 pt-1 border-t border-surface-dim">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-secondary pt-2">{{ $act5Title }}</p>
                    @if ($cert->isPickup() && $cert->isReady())
                        <form method="POST" action="{{ route('admin.safety-file-dossiers.certificate.update', $d) }}"
                              data-confirm data-confirm-style="success"
                              data-confirm-title="{{ __('safety.confirm_collected') }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="collected">
                            <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">handshake</span>
                                {{ __('safety.mark_collected') }}
                            </button>
                        </form>
                    @elseif ($cert->isPickup())
                        <form method="POST" action="{{ route('admin.safety-file-dossiers.certificate.update', $d) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="ready">
                            <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">undo</span>
                                {{ __('safety.mark_ready_uncollect') }}
                            </button>
                        </form>
                    @elseif ($cert->isPost() && $cert->isReady())
                        <form method="POST" action="{{ route('admin.safety-file-dossiers.certificate.update', $d) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="sent">
                            <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.tracking_b3') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="post_tracking" required maxlength="60"
                                value="{{ old('post_tracking', $cert->post_tracking) }}"
                                placeholder="{{ __('safety.tracking_placeholder') }}" class="w-full text-sm">
                            <button type="submit"
                                class="mt-2 inline-flex w-full items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">local_post_office</span>
                                {{ __('safety.register_post') }}
                            </button>
                        </form>
                    @elseif ($cert->isPostSent())
                        <form method="POST" action="{{ route('admin.safety-file-dossiers.certificate.update', $d) }}"
                              data-confirm data-confirm-style="success"
                              data-confirm-title="{{ __('safety.confirm_delivered') }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="delivered">
                            <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">mark_email_read</span>
                                {{ __('safety.confirm_post_delivery') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.safety-file-dossiers.certificate.update', $d) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="ready">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 border border-surface-dim bg-surface-container-lowest hover:bg-surface-container text-secondary text-[11px] font-bold py-2 px-4 rounded transition-colors dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">undo</span>
                                {{ __('safety.not_sent_rollback') }}
                            </button>
                        </form>
                    @else
                        <p class="flex items-center gap-1.5 text-xs font-bold text-amber-800 dark:text-amber-400">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">verified</span>
                            {{ __('safety.delivery_confirmed') }}
                        </p>
                    @endif
                </div>

                {{-- 4. Révocation --}}
                @if (! $cert->isCollected() && $cert->status !== \App\Models\SafetyFileCertificate::STATUS_POST_DELIVERED)
                    <div class="border-t border-surface-dim pt-3 mt-3" x-data="{ revokeOpen: false }">
                        <button type="button" @click="revokeOpen = !revokeOpen"
                                class="flex w-full items-center justify-between text-start" :aria-expanded="revokeOpen" aria-controls="cert-revoke">
                            <span class="flex items-center gap-1.5 text-xs font-bold text-red-700 dark:text-red-400">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">warning</span>
                                {{ __('safety.revoke_cert') }}
                            </span>
                            <svg class="h-3.5 w-3.5 text-red-400 transition-transform" :class="revokeOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="revokeOpen" x-cloak id="cert-revoke" class="mt-2 rounded-lg border border-red-200 bg-red-50 dark:border-red-800/40 dark:bg-red-900/20 p-3">
                            <p class="text-[11px] leading-relaxed text-red-800 dark:text-red-300">
                                {{ __('safety.revoke_warning', ['ref' => $cert->reference]) }}
                            </p>
                            <form method="POST" action="{{ route('admin.safety-file-dossiers.certificate.destroy', $d) }}"
                                  data-confirm data-confirm-style="danger"
                                  data-confirm-title="{{ __('safety.confirm_revoke', ['ref' => $cert->reference]) }}">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="mt-2 inline-flex w-full items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2 px-4 rounded transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">delete</span>
                                    {{ __('safety.confirm_revoke_btn') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <p class="flex items-center gap-1.5 border-t border-surface-dim pt-3 mt-3 text-[11px] font-bold text-secondary">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">lock</span>
                        {{ __('safety.locked_cert') }}
                    </p>
                @endif

            </div>
        </section>
        @endif
        @endcan

        {{-- ══════════════ CYCLE DE VIE — Stepper + progression ══════════════ --}}
        <section class="bg-surface-container-lowest border {{ $isCancelled ? 'border-red-200 dark:border-red-800/40' : 'border-surface-dim' }} rounded-lg shadow-sm overflow-hidden">
            @if ($isCancelled)
            {{-- Dossier annulé : bandeau rouge au-dessus du cycle figé --}}
            <div class="border-b border-red-200 dark:border-red-800/40 bg-red-50 dark:bg-red-900/20 border-s-4 border-s-red-600 p-5 md:px-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">cancel</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('safety.dossier_cancelled') }}</h3>
                        <p class="text-body-sm text-secondary mt-1">{{ __('safety.closed_by_admin') }}</p>
                        @if ($d->cancelled_at)
                        <p class="text-[11px] text-secondary mt-2 font-mono" dir="ltr">{{ $d->cancelled_at->format('d/m/Y, H:i') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            <div class="p-5 md:px-6">
                {{-- En-tête : titre + phase + barre de progression --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-surface-dim">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">route</span>
                        {{ __('safety.lifecycle') }}
                    </h3>
                    <div class="flex items-center gap-3 flex-1 sm:flex-none sm:min-w-64">
                        @if ($isCancelled)
                        <span class="text-xs font-bold text-white bg-red-600 px-2 py-0.5 rounded whitespace-nowrap">
                            {{ __('safety.step_cancelled') }}
                        </span>
                        @else
                        <span class="text-xs font-bold text-primary bg-primary-fixed/40 px-2 py-0.5 rounded whitespace-nowrap">
                            {{ __('safety.phase_word') }} {{ $phaseNum }} / 5
                        </span>
                        @endif
                        <div class="hidden sm:flex-1 h-1.5 bg-surface-dim/50 rounded-full overflow-hidden">
                            <div class="h-full {{ $isCancelled ? 'bg-red-500' : 'bg-gradient-to-r from-primary-container to-amber-500' }} rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                        </div>
                        <span class="text-[11px] font-mono font-bold text-secondary hidden sm:inline" dir="ltr">{{ $progress }}%</span>
                    </div>
                </div>

                {{-- Étapes reliées par une ligne de conduite --}}
                <div class="relative grid grid-cols-1 md:grid-cols-5 gap-5 md:gap-3">
                    <div class="hidden md:block absolute top-5 [inset-inline-start:10%] [inset-inline-end:10%] h-0.5 bg-surface-dim" aria-hidden="true"></div>

                    @foreach ($steps as $idx => $step)
                        <div class="relative flex md:flex-col items-start md:items-center gap-3 md:gap-2 md:text-center">
                            @if ($step['state'] === 'done')
                                <span class="relative z-10 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-sm ring-4 ring-amber-100 dark:ring-amber-900/40">
                                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">check</span>
                                </span>
                            @elseif ($step['state'] === 'warning')
                                {{-- Terminé malgré un incident résolu (correction / visite échouée) --}}
                                <span class="relative z-10 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-sm ring-4 ring-amber-100 dark:ring-amber-900/40">
                                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">check</span>
                                </span>
                            @elseif ($step['state'] === 'current')
                                <span class="relative z-10 w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center text-sm font-black shadow-sm ring-4 ring-amber-200 dark:ring-amber-800/50 animate-pulse">
                                    {{ $step['num'] }}
                                </span>
                            @elseif ($step['state'] === 'error')
                                <span class="relative z-10 w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow-sm ring-4 ring-red-100 dark:ring-red-900/40">
                                    <span class="material-symbols-outlined text-lg" aria-hidden="true">edit_note</span>
                                </span>
                            @elseif ($step['state'] === 'cancelled')
                                {{-- Dossier annulé à cette étape --}}
                                <span class="relative z-10 w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow-sm ring-4 ring-red-100 dark:ring-red-900/40">
                                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">cancel</span>
                                </span>
                            @else
                                <span class="relative z-10 w-10 h-10 rounded-full bg-surface-container text-secondary flex items-center justify-center text-sm font-bold border border-surface-dim dark:bg-gray-700">
                                    {{ $step['num'] }}
                                </span>
                            @endif

                            <div class="min-w-0 md:px-1">
                                <div class="flex md:justify-center items-center gap-2">
                                    <h4 class="text-xs font-bold {{ $step['state'] === 'pending' ? 'text-secondary' : 'text-on-surface' }}">
                                        {{ [__('safety.step1'), __('safety.step2'), __('safety.step3'), __('safety.step4'), __('safety.step5')][$idx] }}
                                    </h4>
                                    @if ($step['state'] === 'current')
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                                    @elseif ($step['state'] === 'error' || $step['state'] === 'cancelled')
                                        <span class="text-[10px] uppercase font-black text-red-800 bg-red-100 dark:bg-red-900/40 dark:text-red-300 px-1.5 py-px rounded">{{ __('safety.alert_chip') }}</span>
                                    @endif
                                </div>
                                <p class="text-[11px] mt-0.5 leading-snug {{ $step['alert'] ? 'text-red-700 dark:text-red-400 font-medium' : ($step['state'] === 'current' ? 'text-amber-700 dark:text-amber-400 font-medium' : ($step['state'] === 'done' || $step['state'] === 'warning' ? 'text-amber-800 dark:text-amber-400' : 'text-secondary')) }}">
                                    {{ $step['sub'] }}
                                </p>
                                <p class="text-[10px] mt-1 font-semibold {{ in_array($step['state'], ['done','warning']) ? 'text-amber-700 dark:text-amber-500' : ($step['state'] === 'current' ? 'text-amber-600' : 'text-secondary') }}" dir="ltr">
                                    @if ($stepDates[$step['num']] ?? null)
                                        {{ \Illuminate\Support\Carbon::parse($stepDates[$step['num']])->format('d/m/Y') }}
                                        @if ($step['num'] === 3 && $pendingAppointment && $pendingAppointment->visit_date->isSameDay(\Illuminate\Support\Carbon::parse($stepDates[3])))
                                            <span class="text-secondary">• {{ substr($pendingAppointment->visit_time, 0, 5) }}</span>
                                        @endif
                                    @elseif ($step['state'] !== 'done')
                                        <span class="text-surface-dim font-normal">—</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ══════════════ HÉROS — Fiche identity du dossier ══════════════ --}}
        <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
            <div class="h-1 bg-gradient-to-r from-primary via-primary-container/70 to-transparent" aria-hidden="true"></div>

            <div class="p-5 md:p-6">
                {{-- Ligne haute : titre + actions --}}
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                    <div class="min-w-0">
                        <h1 class="text-headline-md font-headline-md text-on-surface tracking-tight leading-tight" dir="ltr">
                            {{ __('safety.safety_file_label') }} <span class="font-mono">{{ $viewRef }}</span>
                            <span class="ms-2 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold align-middle {{ $d->isHazardous() ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300' : 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' }}">
                                <span class="material-symbols-outlined text-[13px]" aria-hidden="true">{{ $d->isHazardous() ? 'report' : 'apartment' }}</span>
                                {{ $d->variantLabel() }}
                            </span>
                        </h1>
                        <p class="text-xs text-secondary mt-1 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-xs" aria-hidden="true">calendar_today</span>
                            {{ __('safety.deposited_on', ['date' => $d->created_at->locale(app()->getLocale())->isoFormat('D MMMM YYYY')]) }}
                            @if ($d->reviewed_at && $d->reviewer)
                                • {{ __('safety.last_review_by_on', ['name' => $d->reviewer->name, 'date' => $d->reviewed_at->format('d/m/Y')]) }}
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        <span class="inline-flex items-center gap-1.5 text-label-caps font-label-caps px-2.5 py-1 rounded font-bold {{ $statusPillCls }}">
                            <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $statusPillIcon }}</span>
                            {{ $statusLabel[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                        </span>
                        <a href="{{ route('admin.safety-file-dossiers.index') }}"
                            class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-2.5 rounded transition-colors">
                            <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                        </a>
                        <button type="button" onclick="window.print()"
                            class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                        </button>
                    </div>
                </div>

                {{-- Faits clés — détails complets du dossier --}}
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">badge</span>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-secondary">{{ __('safety.applicant_building') }}</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">badge</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('safety.f_full_name') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate" title="{{ $d->nom_prenom ?? '' }}">{{ $d->nom_prenom ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">fingerprint</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('safety.f_cin') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate font-mono" dir="ltr" title="{{ $d->cin ?? '' }}">{{ $d->cin ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">call</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('safety.f_phone') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate font-mono num-ltr" dir="ltr" title="{{ $d->phone ?? '' }}">{{ $d->phone ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">mail</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('safety.f_email') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate" dir="ltr" title="{{ $d->user?->email ?? '' }}">{{ $d->user?->email ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">account_balance</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('safety.f_governorate') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $d->governorate?->name_fr ?? $d->governorate?->name_ar ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">location_city</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('safety.f_delegation') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $d->delegation?->name_fr ?? $d->delegation?->name_ar ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">apartment</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('safety.f_building_type') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $d->buildingType?->name ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">domain</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('safety.f_activity') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $d->buildingActivity?->name ?? $d->activity_other ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">place</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('safety.f_building_address') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate" title="{{ $d->building_address ?? '' }}">{{ $d->building_address ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">local_shipping</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('safety.delivery_chosen') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">
                                @if ($d->delivery_method)
                                    {{ ['pickup' => __('safety.pickup_office'), 'post' => __('safety.post_b3'), 'digital' => __('safety.digital_download')][$d->delivery_method] ?? ucfirst($d->delivery_method) }}
                                @else
                                    <span class="font-medium text-secondary">{{ __('safety.not_chosen_yet') }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ── Map: pinned building location (if coordinates were provided) ── --}}
                @if ($d->latitude && $d->longitude)
                    <div class="mt-3">
                        @include('safety-file.partials.map-display', [
                            'lat'   => $d->latitude,
                            'lng'   => $d->longitude,
                            'mapId' => 'map-admin-show',
                        ])
                    </div>
                @endif

                {{-- Note de l'administration (conservée après correction) --}}
                @if ($d->admin_note && $status !== 'needs_correction')
                <div class="mt-4 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-3">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">sticky_note_2</span>
                        {{ __('safety.admin_note_visible') }}
                    </p>
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $d->admin_note }}</p>
                </div>
                @endif

                {{-- Alerte d'état opérationnel — contextuelle --}}
                @if ($heroAlert === 'delivery')
                    <div class="mt-3 flex items-start gap-2.5 bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-300 dark:border-indigo-800/40 rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400 text-base mt-px" aria-hidden="true">verified</span>
                        <div class="font-bold text-indigo-900 dark:text-indigo-200 leading-relaxed">
                            {{ __('safety.all_passed') }}
                            <span class="block font-normal text-indigo-700 dark:text-indigo-300 mt-0.5">
                                @if ($lastPassedAppointment)
                                    {{ __('safety.visit_passed_on', ['date' => $lastPassedAppointment->visit_date->format('d/m/Y'), 'time' => substr($lastPassedAppointment->visit_time, 0, 5)]) }}@if ($lastPassedAppointment->note) {{ __('safety.note_prefix') }} {{ $lastPassedAppointment->note }}@endif.
                                @endif
                                {{ __('safety.deliver_cert_below') }}
                                {{ __('safety.pickup_flow') }}
                                ).
                            </span>
                        </div>
                    </div>
                @elseif ($heroAlert === 'info')
                    <div class="mt-3 flex items-start gap-2.5 bg-amber-50 dark:bg-amber-900/10 border border-amber-300 dark:border-amber-800/40 rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-base mt-px" aria-hidden="true">hourglass_bottom</span>
                        <div class="font-bold text-amber-900 dark:text-amber-200 leading-relaxed">
                            {{ $status === 'under_consultation' ? __('safety.approved_await_rdv') : __('safety.approved_wait_act') }}
                        </div>
                    </div>
                @elseif ($heroAlert === 'neutral' && $status === 'cancelled')
                    <div class="mt-3 flex items-start gap-2.5 bg-surface-container-low border border-surface-dim rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-secondary text-base mt-px" aria-hidden="true">cancel</span>
                        <div class="font-bold text-on-surface leading-relaxed">
                            {{ __('safety.dossier_cancelled') }}<span class="font-normal text-secondary"> {{ __('safety.closed_by_admin') }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- ══════════════ GRILLE 8/4 ══════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- ══════ COLONNE GAUCHE (8 colonnes) ══════ --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- ══════ VISITES — gestion des RDV ══════ --}}
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden"
                     x-data="{ open: {{ $pendingAppointment ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="w-full flex flex-wrap items-center justify-between gap-3 p-5 border-b border-surface-dim hover:bg-surface-container transition-colors text-start">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">event_repeat</span>
                            </span>
                            <div>
                                <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">{{ __('safety.site_visits') }}</h3>
                                <p class="text-xs text-secondary mt-0.5">
                                    {{ __('safety.visits_count', ['count' => $d->appointments->count()]) }} <strong class="font-semibold text-on-surface">{{ $d->building_address ?? '—' }}</strong>
                                </p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak class="p-5 pt-4">
                        @if ($d->appointments->isEmpty())
                            <div class="p-6 text-center border border-dashed border-surface-dim rounded-lg">
                                <span class="material-symbols-outlined text-3xl text-outline" aria-hidden="true">event_busy</span>
                                <p class="text-sm text-outline mt-2">{{ __('safety.visits_none') }}
                                    @if ($status === 'under_consultation')
                                        {{ __('safety.visits_none_hint') }}
                                    @endif
                                    .
                                </p>
                            </div>
                        @else
                            <div class="relative ps-7 space-y-4 before:absolute before:start-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                                @foreach ($d->appointments as $i => $appt)
                                    <div class="relative">
                                        {{-- Pastille schematic --}}
                                        <div class="absolute -start-7 top-1 w-6 flex items-center justify-center">
                                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black ring-4 ring-surface-container-lowest
                                                {{ $appt->isPassed() ? 'bg-amber-500 text-white' : ($appt->isFailed() ? 'bg-red-600 text-white' : ($appt->isCancelled() ? 'bg-slate-400 text-white' : 'bg-blue-500 text-white animate-pulse')) }}">
                                                @if ($appt->isPassed())✓@elseif ($appt->isFailed())✕@elseif ($appt->isCancelled())<span class="material-symbols-outlined text-[10px]" aria-hidden="true">event_busy</span>@else{{$i + 1}}@endif
                                            </span>
                                        </div>

                                        <div x-data="{ openResult: false, cancelOpen: false }" class="bg-surface-container-low/60 border border-surface-dim rounded-lg p-4 transition-all hover:border-primary/40 {{ $appt->isCancelled() ? 'opacity-75' : '' }}">
                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase
                                                        {{ $appt->isPassed() ? 'bg-amber-100 text-amber-800' : ($appt->isFailed() ? 'bg-red-100 text-red-700' : ($appt->isCancelled() ? 'bg-surface-dim text-secondary' : 'bg-blue-100 text-blue-700')) }}">
                                                        {{ $appt->isPassed() ? __('safety.status_passed') : ($appt->isFailed() ? __('safety.status_failed') : ($appt->isCancelled() ? __('safety.status_cancelled') : __('safety.status_scheduled'))) }}
                                                    </span>
                                                    <span class="text-xs font-mono font-bold text-on-surface {{ $appt->isCancelled() ? 'line-through decoration-1' : '' }}" dir="ltr">
                                                        {{ $appt->visit_date->format('d/m/Y') }} • {{ substr($appt->visit_time, 0, 5) }}
                                                    </span>
                                                    @if ($appt->scheduler)
                                                        <span class="text-[10px] text-outline">{{ __('safety.by_name', ['name' => $appt->scheduler->name]) }}</span>
                                                    @endif
                                                </div>

                                                @can('manage safety-file-dossiers')
                                                <div class="flex items-center gap-2">
                                                    @if ($appt->isPending())
                                                        <button type="button" @click="openResult = !openResult; cancelOpen = false"
                                                            class="inline-flex items-center gap-1 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-1.5 text-[11px] font-bold text-on-surface hover:bg-surface-container">
                                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">fact_check</span> {{ __('safety.record_result') }}
                                                        </button>
                                                        <button type="button" @click="cancelOpen = !cancelOpen; openResult = false"
                                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 px-3 py-1.5 text-[11px] font-bold text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors"
                                                            title="{{ __('safety.cancel_appt_title') }}">
                                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">cancel</span> {{ __('safety.cancel_appointment') }}
                                                        </button>
                                                    @endif
                                                </div>
                                                @endcan
                                            </div>

                                            {{-- Note : rouge = rapport d'inspection (échoué) · neutre = consigne ou motif d'annulation --}}
                                            @if ($appt->note)
                                                <div class="mt-2 rounded-lg border px-3 py-2
                                                    {{ $appt->isFailed()
                                                        ? 'border-red-200 bg-red-50 dark:border-red-800/30 dark:bg-red-900/10'
                                                        : 'border-surface-dim bg-surface-container dark:bg-gray-700/30' }}">
                                                    <p class="text-[11px] font-bold mb-0.5 flex items-center gap-1.5
                                                        {{ $appt->isFailed() ? 'text-red-700 dark:text-red-400' : 'text-secondary' }}">
                                                        <span class="material-symbols-outlined text-sm" aria-hidden="true">{{ $appt->isFailed() ? 'rate_review' : 'event_note' }}</span>
                                                        {{ $appt->isFailed() ? __('safety.inspector_note') : ($appt->isCancelled() ? __('safety.cancellation_reason') : __('safety.note_label')) }}
                                                    </p>
                                                    <p class="text-xs whitespace-pre-line leading-relaxed {{ $appt->isFailed() ? 'text-red-800 dark:text-red-200' : 'text-secondary' }}">{{ $appt->note }}</p>
                                                </div>
                                            @endif

                                            @if ($appt->isPending())
                                                @include('admin.safety-file-dossiers.partials.appointment-panels', ['d' => $d, 'appt' => $appt])
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ══════ CERTIFICAT — bandeau en haut si action requise, sinon ici (état terminal) ══════ --}}
                @if ($d->hasPassedAppointment() || $cert)
                @can('manage safety-file-dossiers')
                @if (! $cert)
                <div class="bg-surface-container-lowest border border-amber-200 dark:border-amber-700/40 rounded-lg shadow-sm overflow-hidden">
                    <div class="p-5">
                        <a href="#act-deliver-cert"
                           onclick="document.getElementById('act-deliver-cert')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); return false;"
                           class="group flex items-center justify-between gap-3 rounded-lg border border-red-200 dark:border-red-800/40 bg-red-50/60 dark:bg-red-900/10 px-4 py-3.5 text-sm transition-all hover:border-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 flex-none place-items-center rounded-lg bg-red-600 text-white shadow-sm transition-transform group-hover:scale-105" aria-hidden="true">
                                    <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1">workspace_premium</span>
                                </span>
                                <div>
                                    <p class="font-extrabold text-red-900 dark:text-red-200">{{ __('safety.cert_title') }}</p>
                                    <p class="text-xs text-red-700 dark:text-red-300/80">{{ __('safety.not_issued_yet') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 text-red-600 dark:text-red-400 font-bold text-xs">
                                <span>{{ __('safety.action_required') }}</span>
                                <span class="material-symbols-outlined text-base transition-transform group-hover:-translate-y-0.5" aria-hidden="true">arrow_upward</span>
                            </div>
                        </a>
                    </div>
                </div>
                @elseif ($cert->isCollected() || $cert->isPostDelivered())
                {{-- Certificat en état terminal : affiché ici (récupéré / livré) --}}
                <div class="bg-surface-container-lowest border border-violet-200 dark:border-violet-800/40 rounded-lg shadow-sm overflow-hidden"
                     x-data="{ open: false }">

                    {{-- Clickable header --}}
                    <button type="button" @click="open = !open"
                            class="w-full flex flex-wrap items-center justify-between gap-3 p-4 hover:bg-violet-50/40 dark:hover:bg-violet-900/10 transition-colors text-start border-b border-violet-100 dark:border-violet-800/30">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1" aria-hidden="true">workspace_premium</span>
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-sm font-extrabold text-on-surface leading-tight flex items-center gap-2 flex-wrap">
                                    <span class="font-mono text-violet-700 dark:text-violet-300" dir="ltr">{{ $cert->reference }}</span>
                                    <span class="text-on-surface font-extrabold">—
                                        @if ($cert->isCollected()) {{ __('safety.tl_collected') }}
                                        @else {{ __('safety.tl_delivered') }}
                                        @endif
                                    </span>
                                </h3>
                                <p class="text-[11px] text-secondary mt-0.5 flex flex-wrap items-center gap-x-2">
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px]" aria-hidden="true">schedule_send</span>
                                        {{ __('safety.issued_dt') }}: <strong class="text-on-surface" dir="ltr">{{ $cert->issued_at->format('d/m/Y') }}</strong>
                                    </span>
                                    @if ($cert->isCollected() && $cert->collected_at)
                                    <span class="text-surface-dim">·</span>
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px]" aria-hidden="true">handshake</span>
                                        {{ __('safety.collected_on') }}: <strong class="text-on-surface" dir="ltr">{{ $cert->collected_at->format('d/m/Y') }}</strong>
                                    </span>
                                    @elseif ($cert->isPostDelivered() && $cert->post_delivered_at)
                                    <span class="text-surface-dim">·</span>
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px]" aria-hidden="true">mark_email_read</span>
                                        {{ __('safety.delivered_on') }}: <strong class="text-on-surface" dir="ltr">{{ $cert->post_delivered_at->format('d/m/Y') }}</strong>
                                    </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-surface-dim text-secondary">{{ $cert->isPickup() ? __('safety.pickup_chip') : __('safety.poste_tunisienne') }}</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $certStatusCfg[1] }}">{{ $certStatusCfg[0] }}</span>
                            <svg class="w-4 h-4 text-secondary transition-transform shrink-0 ms-1" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </button>

                    <div x-show="open" x-cloak class="p-5 space-y-4">

                        {{-- Pipeline — all steps shown as done since terminal state --}}
                        @php
                            $certSteps = $cert->isPickup()
                                ? [[__('safety.cs_ready_office'), 'schedule_send', $cert->issued_at],
                                   [__('safety.cs_collected'), 'handshake', $cert->collected_at]]
                                : [[__('safety.cs_ready'), 'schedule_send', $cert->issued_at],
                                   [__('safety.cs_sent_b3'), 'local_post_office', $cert->post_sent_at],
                                   [__('safety.cs_delivered'), 'mark_email_read', $cert->post_delivered_at]];
                            // All steps done in terminal state
                            $certTotalSteps = count($certSteps);
                        @endphp
                        <div class="rounded-lg border border-surface-dim bg-surface-container-low/60 p-3.5">
                            <p class="mb-2.5 text-[10px] font-extrabold uppercase tracking-wider text-secondary">
                                @if ($cert->isPickup()) {{ __('safety.pickup_workflow_short') }} @else {{ __('safety.post_workflow_strict') }} @endif
                            </p>
                            <ol class="flex flex-col gap-2.5 sm:flex-row sm:items-stretch">
                                @foreach ($certSteps as $i => $step)
                                    <li class="flex flex-1 items-start gap-2.5 sm:flex-col sm:items-start">
                                        <span class="flex h-7 w-7 flex-none items-center justify-center rounded-full text-[11px] font-black bg-amber-600 text-white ring-4 ring-amber-100 dark:ring-amber-900/40">
                                            <span class="material-symbols-outlined text-base" aria-hidden="true">check</span>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-bold leading-tight text-amber-800 dark:text-amber-400">{{ $step[0] }}</p>
                                            <p class="mt-0.5 font-mono text-[10px] text-secondary" dir="ltr">{{ $step[2] ? \Illuminate\Support\Carbon::parse($step[2])->format('d/m/Y') : '—' }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        {{-- Détails --}}
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2.5 text-xs">
                            <div>
                                <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.cert_ref') }}</dt>
                                <dd class="font-mono font-bold text-on-surface" dir="ltr">{{ $cert->reference }}</dd>
                            </div>
                            <div class="text-end">
                                <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.delivery') }}</dt>
                                <dd class="font-semibold text-on-surface">
                                    @if ($cert->isPost()) {{ __('safety.poste_tunisienne') }}
                                    @elseif ($cert->isPickup()) {{ __('safety.pickup_office') }}
                                    @else — @endif
                                </dd>
                            </div>
                            @if ($cert->isPickup())
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.pickup_gov') }}</dt>
                                    <dd class="text-end font-semibold text-on-surface">{{ $cert->governorate?->name_fr ?? $cert->governorate?->name_ar ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.pickup_delegation') }}</dt>
                                    <dd class="text-end font-semibold text-on-surface">{{ $cert->delegation?->name_fr ?? $cert->delegation?->name_ar ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.issued_dt') }}</dt>
                                    <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->issued_at->format('d/m/Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.valid_until') }}</dt>
                                    <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->valid_until ? $cert->valid_until->format('d/m/Y') : '—' }}</dd>
                                </div>
                                @if ($cert->collected_at)
                                <div class="col-span-2">
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.collected_on') }}</dt>
                                    <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->collected_at->format('d/m/Y, H:i') }}</dd>
                                </div>
                                @endif
                            @else
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.recipient') }}</dt>
                                    <dd class="text-end font-semibold text-on-surface">{{ $cert->post_recipient ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.f_phone') }}</dt>
                                    <dd class="text-end font-semibold font-mono num-ltr text-on-surface" dir="ltr">{{ $cert->post_phone ?? '—' }}</dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.postal_address') }}</dt>
                                    <dd class="mt-1 rounded-lg bg-surface-container px-2.5 py-2 text-[11px] leading-relaxed break-words text-on-surface dark:bg-gray-700/30">{{ $cert->post_address ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.tracking_b3') }}</dt>
                                    <dd class="text-end font-mono font-semibold text-on-surface" dir="ltr">{{ $cert->post_tracking ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.issued_valid_until') }}</dt>
                                    <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->issued_at->format('d/m/Y') }} / {{ $cert->valid_until ? $cert->valid_until->format('d/m/Y') : '—' }}</dd>
                                </div>
                                @if ($cert->post_delivered_at)
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.delivered_on') }}</dt>
                                    <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->post_delivered_at->format('d/m/Y, H:i') }}</dd>
                                </div>
                                @endif
                            @endif
                        </dl>

                        @if ($cert->note)
                            <div class="rounded-lg border border-surface-dim bg-surface-container px-2.5 py-2 dark:bg-gray-700/30">
                                <p class="mb-0.5 text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('safety.issue_note_label') }} — {{ __('safety.visible_short') }}</p>
                                <p class="whitespace-pre-line text-xs text-secondary dark:text-gray-300">{{ $cert->note }}</p>
                            </div>
                        @endif

                        <p class="flex items-center gap-1.5 border-t border-surface-dim pt-3 text-[11px] font-bold text-secondary">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">lock</span>
                            {{ __('safety.locked_cert') }}
                        </p>
                    </div>
                </div>
                @endif
                @endcan
                @endif

                {{-- ══════ TIMELINE — Historique & Journal d'audit ══════ --}}
                @if ($d->events->isNotEmpty())
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print"
                     x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="w-full flex flex-wrap items-center justify-between gap-3 p-5 border-b border-surface-dim hover:bg-surface-container transition-colors text-start">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded bg-primary-fixed/50 text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">history</span>
                            </span>
                            <div>
                                <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('safety.timeline_title') }}</h3>
                                <p class="text-xs text-secondary mt-0.5">{{ __('safety.timeline_sub') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] text-secondary font-semibold flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">receipt_long</span>
                                {{ __('safety.events_count', ['count' => $d->events->count()]) }}
                            </span>
                            <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </button>

                    <div x-show="open" x-cloak class="p-5 pt-4">
                        {{-- Chronologie linéaire haute densité --}}
                        <div class="relative ps-6 sm:ps-8 space-y-5 before:absolute before:start-3 sm:before:start-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                            @foreach ($d->events->reverse()->values() as $event)
                                @php [$dot, $icon, $chip, $chipCls] = $eventChip($event->type); @endphp
                                <div class="relative">
                                    {{-- Pastille --}}
                                    <div class="absolute -start-6 sm:-start-8 top-0 w-6 sm:w-8 flex items-center justify-center">
                                        <div class="w-5 h-5 rounded-full {{ $dot }} flex items-center justify-center ring-4 ring-surface-container-lowest shadow-xs">
                                            <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $icon }}</span>
                                        </div>
                                    </div>
                                    {{-- Carte de contenu --}}
                                    <div class="bg-surface-container-low/60 border border-surface-dim rounded-lg p-4 transition-all hover:border-primary/40">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-bold {{ $chipCls }} px-2 py-0.5 rounded uppercase">{{ $chip }}</span>
                                                <span class="text-xs font-semibold text-on-surface">
                                                    @if ($event->actor)
                                                        {{ $event->actor->name }}
                                                    @else
                                                        <span class="italic text-secondary">{{ __('safety.system') }}</span>
                                                    @endif
                                                </span>
                                                @if ($event->actor)
                                                    @php
                                                        $actorRole = $event->actor->roles->first()?->name;
                                                        $isApplicant = $event->actor->roles->isEmpty();
                                                    @endphp
                                                    <span class="text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded {{ $isApplicant ? 'bg-surface-dim text-secondary' : 'bg-secondary-container text-on-secondary-container' }}">
                                                        {{ $isApplicant ? __('safety.applicant_role') : ucfirst(str_replace('-', ' ', $actorRole ?? 'Admin')) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <time class="text-xs font-mono font-medium text-secondary" dir="ltr">{{ $event->created_at->format('d/m/Y H:i') }}</time>
                                        </div>
                                        <h5 class="text-xs font-bold text-on-surface mb-1">{{ $event->typeLabel() }}</h5>

                                        @if ($event->detailsText())
                                            <p class="text-xs inline-flex items-center gap-1 rounded bg-surface-dim px-2 py-0.5 font-semibold text-on-surface mb-1.5">
                                                <span class="material-symbols-outlined text-[13px]" aria-hidden="true">info</span>
                                                {{ $event->detailsText() }}
                                            </p>
                                        @endif

                                        @if ($event->note)
                                            <p class="text-body-sm font-body-sm text-secondary">
                                                <span class="font-bold text-on-surface">{{ __('safety.note_colon') }}</span>
                                                <span class="whitespace-pre-line">{{ $event->note }}</span>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                {{-- ══════ HISTORIQUE DES CORRECTIONS ══════ --}}
                @if (!empty($allCorrectionRounds))
                <div class="bg-surface-container-lowest border border-amber-200 dark:border-amber-700/40 rounded-lg shadow-sm overflow-hidden no-print"
                     x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center gap-2 p-5 border-b border-amber-100 dark:border-amber-800/30 hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition-colors text-start">
                        <span class="w-8 h-8 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">manage_history</span>
                        </span>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">{{ __('safety.correction_history') }}</h3>
                            <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5">
                                {{ __('safety.rounds', ['count' => count($allCorrectionRounds)]) }}
                            </p>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300" dir="ltr">{{ count($allCorrectionRounds) }}</span>
                        <svg class="w-4 h-4 text-amber-700 dark:text-amber-400 transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="p-5">
                        @include('safety-file.partials.correction-history', [
                            'allCorrectionRounds' => $allCorrectionRounds,
                            'archivedDocuments'   => $archivedDocuments,
                            'documentTypes'       => $documentTypes,
                        ])
                    </div>
                </div>
                @endif

            </div>{{-- /colonne gauche --}}

            {{-- ══════ COLONNE DROITE (4 colonnes) ══════ --}}
            <div class="lg:col-span-4 space-y-6">

                {{-- ENCADRÉ 1 : Pièces justificatives versées --}}
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden"
                     x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center gap-2 p-5 hover:bg-surface-container transition-colors text-start border-b border-surface-dim">
                        <span class="w-8 h-8 rounded bg-primary-fixed/50 text-primary flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">attach_file</span>
                        </span>
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">{{ __('safety.supporting_docs') }}</h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded whitespace-nowrap {{ $isDocComplete ? 'text-amber-900 bg-amber-100 dark:bg-amber-900/40 dark:text-amber-300' : 'text-secondary bg-surface-dim' }}" dir="ltr">{{ $uploadedTypes }}/{{ $documentTypes->count() }}</span>
                        <svg class="w-4 h-4 text-secondary transition-transform shrink-0 ms-1" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak class="p-5">
                        {{-- Barre de complétude --}}
                        <div class="h-2 bg-surface-dim/50 rounded-full overflow-hidden mb-1" role="progressbar" aria-valuenow="{{ $docPct }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full transition-all duration-500 {{ $isDocComplete ? 'bg-amber-500' : 'bg-primary' }}" style="width: {{ $docPct }}%"></div>
                        </div>
                        <p class="text-[10px] font-semibold mb-4 {{ $isDocComplete ? 'text-amber-800 dark:text-amber-400' : 'text-secondary' }}">
                            {{ $isDocComplete ? __('safety.checklist_complete') : __('safety.checklist_progress', ['uploaded' => $uploadedTypes, 'total' => $documentTypes->count()]) }}
                        </p>

                        <div class="space-y-2.5">
                            @forelse ($documentTypes as $docType)
                                @php $doc = $uploadedDocs->get($docType->id); @endphp
                                @if ($doc)
                                    <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-2.5 group hover:border-primary/40 transition-colors">
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <span class="material-symbols-outlined text-red-600 text-lg mt-0.5 shrink-0" aria-hidden="true">{{ str_contains((string) $doc->mime_type, 'pdf') ? 'picture_as_pdf' : 'description' }}</span>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold text-on-surface leading-snug">{{ $docType->name }}</h4>
                                                <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap" dir="ltr">
                                                    <span class="font-mono">{{ $fmtSize($doc->size) }}</span>
                                                    <span class="text-surface-dim">•</span>
                                                    <span>{{ $doc->updated_at->format('d/m/Y') }}</span>
                                                    @if ($doc->original_name)
                                                        <span class="truncate max-w-36 text-secondary/80" title="{{ $doc->original_name }}">— {{ $doc->original_name }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('admin.safety-file-dossiers.documents.download', ['document' => $doc]) }}"
                                           class="w-7 h-7 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-600 hover:text-white flex items-center justify-center shrink-0 transition-colors"
                                           title="{{ __('safety.title_download', ['name' => $doc->original_name]) }}">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                        </a>
                                    </div>
                                @else
                                    <div class="p-3 border border-dashed border-surface-dim rounded flex items-start justify-between gap-2.5">
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <span class="w-7 h-7 rounded bg-surface-dim/40 text-outline flex items-center justify-center shrink-0" aria-hidden="true">
                                                <span class="material-symbols-outlined text-sm">{{ $docType->is_conditional ? 'help_outline' : 'error_outline' }}</span>
                                            </span>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold {{ $docType->is_conditional ? 'text-secondary' : 'text-on-surface font-semibold' }}">{{ $docType->name }}</h4>
                                                <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap">
                                                    @if ($docType->is_conditional)
                                                        <span class="font-semibold text-amber-900 bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40 px-1.5 rounded">{{ __('safety.conditional') }}</span>
                                                    @endif
                                                    <span>{{ __('safety.missing_doc') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <div class="p-4 text-center">
                                    <span class="material-symbols-outlined text-2xl text-outline" aria-hidden="true">folder_off</span>
                                    <p class="text-xs text-secondary mt-1">{{ __('safety.configured_none') }}</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Fichiers transmis hors checklist --}}
                        @php $orphanDocs = $uploadedDocs->filter(fn ($doc) => ! $documentTypes->contains('id', $doc->document_type_id)); @endphp
                        @if ($orphanDocs->isNotEmpty())
                            <div class="pt-3 mt-2 border-t border-surface-dim/60">
                                <p class="text-[11px] font-bold text-secondary flex items-center gap-1.5 mb-2">
                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">attachment</span>
                                    {{ __('safety.other_files', ['count' => $orphanDocs->count()]) }}
                                </p>
                                <div class="space-y-2.5">
                                    @foreach ($orphanDocs as $doc)
                                        <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-2.5">
                                            <div class="flex items-start gap-2.5 min-w-0">
                                                <span class="material-symbols-outlined text-red-600 text-lg mt-0.5 shrink-0" aria-hidden="true">{{ str_contains((string) $doc->mime_type, 'pdf') ? 'picture_as_pdf' : 'description' }}</span>
                                                <div class="min-w-0">
                                                    <h4 class="text-xs font-bold text-on-surface truncate" title="{{ $doc->original_name }}">{{ $doc->original_name ?? __('safety.document_fallback') }}</h4>
                                                    <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5" dir="ltr">
                                                        <span class="font-mono">{{ $fmtSize($doc->size) }}</span>
                                                        <span class="text-surface-dim">•</span>
                                                        <span>{{ $doc->created_at->format('d/m/Y') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="{{ route('admin.safety-file-dossiers.documents.download', ['document' => $doc]) }}"
                                               class="w-7 h-7 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-600 hover:text-white flex items-center justify-center shrink-0 transition-colors" title="{{ __('safety.download') }}">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ENCADRÉ 2 : Fichiers archivés (corrections précédentes) --}}
                @if ($archivedDocuments->isNotEmpty())
                    <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden" x-data="{ openArchive: false }">
                        <div class="p-5">
                            <button type="button" @click="openArchive = !openArchive" class="w-full text-start">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="w-8 h-8 rounded bg-surface-dim/60 text-secondary flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">inventory_2</span>
                                    </span>
                                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">{{ __('safety.archived_files') }}</h3>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-surface-dim text-secondary" dir="ltr">{{ $archivedDocuments->count() }}</span>
                                    <svg class="w-4 h-4 text-secondary transition-transform" :class="openArchive ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                                <p class="text-[11px] text-secondary leading-relaxed">{{ __('safety.archived_hint') }}</p>
                            </button>

                            <div x-show="openArchive" x-cloak class="pt-4 space-y-2.5">
                                @foreach ($archivedDocuments as $doc)
                                    <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-2.5">
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <span class="material-symbols-outlined text-outline text-lg mt-0.5 shrink-0" aria-hidden="true">inventory_2</span>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold text-secondary leading-snug">{{ $doc->documentType?->name ?? __('safety.document_fallback') }}</h4>
                                                <div class="flex items-center gap-2 text-[11px] text-secondary/80 mt-0.5 flex-wrap" dir="ltr">
                                                    <span class="font-mono">{{ $fmtSize($doc->size) }}</span>
                                                    <span class="text-surface-dim">•</span>
                                                    <span>{{ $doc->created_at->format('d/m/Y') }}</span>
                                                    @if ($doc->original_name)
                                                        <span class="truncate max-w-36" title="{{ $doc->original_name }}">— {{ $doc->original_name }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('admin.safety-file-dossiers.documents.download', ['document' => $doc]) }}"
                                           class="w-7 h-7 rounded bg-surface-container text-secondary hover:bg-primary hover:text-on-primary flex items-center justify-center shrink-0 transition-colors" title="{{ __('safety.download') }}">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

            </div>{{-- /colonne droite --}}
        </div>{{-- /grille 8/4 --}}
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         ZONE DANGEREUSE — annulation définitive du dossier (bas de page).
         Un seul point d'annulation dans toute la page, toujours en dernier.
    ══════════════════════════════════════════════════════════════════════════ --}}
    @can('manage safety-file-dossiers')
    @if ($d->isActionable())
    <section class="mt-6 border border-red-300 dark:border-red-800/50 rounded-lg bg-red-50/40 dark:bg-red-900/5 overflow-hidden" x-data="{ cancelOpen: false }">
        <div class="p-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0 dark:bg-red-900/30 dark:text-red-300">
                    <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">warning</span>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-extrabold text-red-700 dark:text-red-300 uppercase tracking-wide">{{ __('safety.danger_zone_title') }}</h2>
                    <p class="text-xs text-secondary mt-0.5">{{ __('safety.danger_zone') }}</p>
                </div>
            </div>
            <button type="button" @click="cancelOpen = !cancelOpen"
                class="inline-flex items-center justify-center gap-2 border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold py-2.5 px-5 rounded transition-colors dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 shrink-0">
                <span class="material-symbols-outlined text-base" aria-hidden="true">cancel</span>
                <span x-text="cancelOpen ? '{{ __('safety.hide_cancellation') }}' : '{{ __('safety.cancel_dossier') }}'">{{ __('safety.cancel_dossier') }}</span>
            </button>
        </div>

        {{-- Formulaire d'annulation (déplié) --}}
        <div x-show="cancelOpen" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="border-t border-red-200 dark:border-red-800/40 p-5">
            <form method="POST" action="{{ route('admin.safety-file-dossiers.cancel', $d) }}"
                  data-confirm data-confirm-style="danger"
                  data-confirm-title="{{ __('safety.confirm_cancel_dossier') }}"
                  data-confirm-focus-cancel="false">
                @csrf
                <label class="block text-xs font-semibold text-red-800 dark:text-red-200 mb-1.5">{{ __('safety.cancellation_reason') }} <span class="text-red-500">*</span></label>
                <x-admin.bilingual-note name="admin_note" :rows="3" required
                    placeholder-key="safety.cancel_reason_placeholder" />
                <button type="submit" class="mt-3 w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">cancel</span>
                    {{ __('safety.confirm_cancellation') }}
                </button>
            </form>
        </div>
    </section>
    @endif
    @endcan

    {{-- ═════════════════════════════════════════════════════════════════
        FICHE IMPRIMABLE — feuille professionnelle du dossier (A4)
        Masquée à l'écran, seule visible sous @media print.
    ═════════════════════════════════════════════════════════════════ --}}
    <div class="print-sheet" aria-hidden="true">
        <div class="fiche">
            {{-- Bandeau institutionnel --}}
            <div class="fiche-band">
                <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                    <img src="/images/logo.png" alt="" class="fiche-band-logo">
                    <div style="min-width:0;">
                        <div class="fiche-band-org">{{ setting('app_name', 'ONPC — Office National de la Protection Civile') }}</div>
                        <div class="fiche-band-sub">{{ __('safety.safety_service_label', ['gov' => $d->governorate?->name_fr ?? $d->governorate?->name_ar ?? '—']) }}</div>
                    </div>
                </div>
                <div class="fiche-band-ref">
                    <div class="fiche-band-ref-label">{{ __('safety.dossier') }}</div>
                    <div class="fiche-band-ref-value" dir="ltr">{{ $viewRef }}</div>
                </div>
            </div>

            {{-- Titre + date d'édition --}}
            <div class="fiche-title-row">
                <div class="fiche-title">{{ __('safety.print_title') }}</div>
                <div class="fiche-printed" dir="ltr">{{ __('safety.printed_on') }} {{ $printDate }}</div>
            </div>

            {{-- Bloc jumelé : demandeur • bâtiment --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <section class="fiche-sec">
                    <h2 class="fiche-h2">{{ __('safety.fiche_applicant') }}</h2>
                    <table class="fiche-table fiche-kv">
                        <tr><th>{{ __('safety.f_full_name') }}</th><td style="font-weight:700;">{{ $d->nom_prenom }}</td></tr>
                        <tr><th>{{ __('safety.f_cin') }}</th><td class="fiche-mono" dir="ltr">{{ $d->cin }}</td></tr>
                        <tr><th>{{ __('safety.f_phone') }}</th><td class="fiche-mono" dir="ltr">{{ $d->phone ?? '—' }}</td></tr>
                        <tr><th>{{ __('safety.f_email') }}</th><td dir="ltr">{{ $d->user?->email ?? '—' }}</td></tr>
                    </table>
                </section>
                <section class="fiche-sec">
                    <h2 class="fiche-h2">{{ __('safety.fiche_building') }}</h2>
                    <table class="fiche-table fiche-kv">
                        <tr><th>{{ __('safety.f_building_type') }}</th><td>{{ $d->buildingType?->name ?? '—' }}</td></tr>
                        <tr><th>{{ __('safety.f_activity') }}</th><td>{{ $d->buildingActivity?->name ?? $d->activity_other ?? '—' }}</td></tr>
                        <tr><th>{{ __('safety.f_delegation') }}</th><td>{{ $d->delegation?->name_fr ?? $d->delegation?->name_ar ?? '—' }}</td></tr>
                        <tr><th>{{ __('safety.address') }}</th><td>{{ $d->building_address ?? '—' }}</td></tr>
                        @if ($d->latitude && $d->longitude)
                        <tr><th>{{ __('safety.coordinates') }}</th><td class="fiche-mono" dir="ltr">{{ number_format((float)$d->latitude, 5) }}, {{ number_format((float)$d->longitude, 5) }}</td></tr>
                        @endif
                    </table>
                </section>
            </div>

            {{-- Références administratives --}}
            <section class="fiche-sec">
                <h2 class="fiche-h2">{{ __('safety.admin_refs') }}</h2>
                <table class="fiche-table fiche-kv-4">
                    <tr>
                        <th>{{ __('safety.internal_ref') }}</th>
                        <td class="fiche-mono" dir="ltr">{{ $d->reference ?: '—' }}</td>
                        <th>{{ __('safety.deposited_short') }}</th>
                        <td class="fiche-mono" dir="ltr">{{ $d->created_at->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('safety.office') }}</th>
                        <td>{{ $d->governorate?->name_fr ?? $d->governorate?->name_ar ?? '—' }}</td>
                        <th></th>
                        <td></td>
                    </tr>
                </table>
            </section>

            {{-- Certificat (si émis) --}}
            @if ($cert)
            <section class="fiche-sec">
                <div class="fiche-cert">
                    <div class="fiche-cert-title">{{ __('safety.cert_title') }}</div>
                    {{ __('safety.cert_ref') }} <strong class="fiche-mono">{{ $cert->reference }}</strong>
                    — {{ __('safety.delivery') }}: {{ $cert->isPickup() ? __('safety.pickup_office') : __('safety.poste_tunisienne') }}
                    — {{ __('safety.fiche_status') }}: {{ $certStatusCfg[0] }}
                    — {{ __('safety.fiche_issued') }}: {{ $cert->issued_at->format('d/m/Y') }}
                    @if ($cert->valid_until) — {{ __('safety.valid_until') }}: {{ $cert->valid_until->format('d/m/Y') }}@endif
                    @if ($cert->post_tracking) — {{ __('safety.fiche_tracking') }}: <span class="fiche-mono">{{ $cert->post_tracking }}</span>@endif
                </div>
            </section>
            @endif

            {{-- Documents versés au dossier --}}
            <section class="fiche-sec">
                <h2 class="fiche-h2">
                    {{ __('safety.supporting_docs') }}
                    <span dir="ltr" style="font-weight:700;">— {{ $uploadedTypes }}/{{ $documentTypes->count() }}</span>
                </h2>
                <table class="fiche-table fiche-table-dark">
                    <thead>
                        <tr>
                            <th style="width:6%;">{{ __('safety.col_no') }}</th>
                            <th>{{ __('safety.col_document') }}</th>
                            <th>{{ __('safety.col_file') }}</th>
                            <th style="width:11%;">{{ __('safety.col_size') }}</th>
                            <th style="width:13%;">{{ __('safety.col_date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $docNo = 0; @endphp
                        @forelse ($documentTypes as $docType)
                            @php $doc = $uploadedDocs->get($docType->id); @endphp
                            @php $docNo++; @endphp
                            <tr>
                                <td class="fiche-mono" dir="ltr" style="text-align:center;">{{ str_pad($docNo, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $docType->name }}</td>
                                @if ($doc)
                                    <td dir="ltr">{{ $doc->original_name ?: '—' }}</td>
                                    <td class="fiche-mono" dir="ltr">{{ $fmtSize($doc->size) }}</td>
                                    <td class="fiche-mono" dir="ltr">{{ $doc->updated_at->format('d/m/Y') }}</td>
                                @else
                                    <td class="fiche-missing">{{ __('safety.missing_chip') }}{{ $docType->is_conditional ? '' : ' ' . __('safety.required_suffix') }}</td>
                                    <td class="fiche-dim">—</td>
                                    <td class="fiche-dim">—</td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="5" class="fiche-dim" style="text-align:center;">—</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            {{-- Pied de fiche --}}
            <div class="fiche-foot">
                <div>{{ $d->hasPassedAppointment() ? __('safety.fiche_foot_passed') : __('safety.fiche_foot_not') }}</div>
                <div>{{ $viewRef }} · {{ $d->id }}</div>
            </div>
        </div>
    </div>
@endsection
