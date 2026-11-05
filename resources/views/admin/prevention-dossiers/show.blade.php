@extends('admin.layouts.admin')
@section('title', __('prevention.page_title', ['id' => $preventionDossier->id]))

@php
    $d = $preventionDossier;
    $status = $d->status;

    // ── Référence affichée : officielle > stockée > provisoire ──
    $viewRef = $d->num_demande ?: ($d->reference ?: ('PREV-' . str_pad($d->id, 6, '0', STR_PAD_LEFT)));

    $pendingAppointment = $d->appointments->first(fn ($a) => $a->isPending());
    $lastFailedAppointment = $d->appointments->last(fn ($a) => $a->isFailed());
    $lastPassedAppointment = $d->appointments->last(fn ($a) => $a->isPassed());

    /* ── Stepper 4 étapes : dépôt → étude → inspection → certificat ── */
    $currentStep = match ($status) {
        'draft'                  => 1,
        'completed', 'submitted' => 2,
        'under_consultation'     => 3,
        'approved'               => 4,
        'waiting_delivery'       => 4,
        'needs_correction'       => 2,
        default                  => null,
    };
    if ($d->certificate) {
        $currentStep = 5; // finalisé
    }

    $stepDates = [];
    $stepDates[1] = $d->created_at;
    $stepDates[2] = $d->reviewed_at;
    $stepDates[3] = $d->appointments->last(fn ($a) => $a->isPassed())?->visit_date ?? $pendingAppointment->visit_date ?? null;
    $stepDates[4] = $d->certificate?->issued_at;

    $steps = [];
    foreach ([1, 2, 3, 4] as $i) {
        if ($currentStep === 5 || $i < $currentStep) $state = 'done';
        elseif ($i === $currentStep)                 $state = $status === 'needs_correction' ? 'error' : 'current';
        else                                         $state = 'pending';

        $sub = match (true) {
            $state === 'done'    => __('prevention.step_done'),
            $state === 'pending' => __('prevention.step_pending'),
            $status === 'needs_correction' && $state === 'error' => __('prevention.step_correction_required'),
            $i === 2             => __('prevention.step2_sub'),
            $i === 3 && $pendingAppointment => __('prevention.step_visit_scheduled', ['date' => $pendingAppointment->visit_date->format('d/m/Y')]),
            $i === 3             => __('prevention.step3_sub'),
            $i === 4             => $d->certificate ? __('prevention.step_delivered') : __('prevention.step_cert_to_issue'),
            default              => __('prevention.step_in_progress'),
        };

        $steps[] = ['num' => $i, 'state' => $state, 'sub' => $sub];
    }
    $doneSteps = collect($steps)->where('state', 'done')->count();
    $progress  = $currentStep === 5 ? 100 : (int) round($doneSteps / 4 * 100);
    $phaseNum  = $currentStep ? min($currentStep, 4) : 1;

    /* ── Timeline « journal d'audit » : pastilles + chips ──
       ● amber = validé / officiel    ● red ONPC = convocation / incident
       ● slate = dépôt, transfert interne    ● violet = consultation    ● indigo = livraison */
    $eventChip = fn ($type) => match (true) {
        in_array($type, ['submitted', 'resubmitted', 'draft_created', 'step1_updated', 'step2_saved', 'document_uploaded'])
            => ['bg-secondary-container text-on-secondary-container', 'post_add', __('prevention.tl_deposit'), 'bg-surface-dim text-secondary'],
        $type === 'appointment_scheduled'
            => ['bg-primary text-on-primary', 'campaign', __('prevention.tl_convocation'), 'bg-primary text-on-primary'],
        in_array($type, ['advanced_to_consultation', 'appointment_deleted'])
            => ['bg-violet-600 text-white', 'sync_alt', __('prevention.tl_phase'), 'bg-surface-dim text-secondary'],
        $type === 'correction_requested'
            => ['bg-amber-500 text-white', 'edit_note', __('prevention.tl_correction'), 'bg-amber-100 text-amber-800'],
        in_array($type, ['approved', 'appointment_passed', 'waiting_delivery', 'certificate_issued'])
            => ['bg-amber-600 text-white', 'task_alt', __('prevention.tl_favorable'), 'bg-amber-100 text-amber-800'],
        $type === 'delivery_chosen'
            => ['bg-indigo-600 text-white', 'local_shipping', __('prevention.tl_delivery'), 'bg-indigo-100 text-indigo-800'],
        $type === 'certificate_post_sent'
            => ['bg-blue-600 text-white', 'local_post_office', __('prevention.tl_b3_sent'), 'bg-blue-100 text-blue-800'],
        $type === 'certificate_post_delivered'
            => ['bg-cyan-600 text-white', 'mark_email_read', __('prevention.tl_delivered'), 'bg-cyan-100 text-cyan-800'],
        $type === 'certificate_collected'
            => ['bg-amber-600 text-white', 'handshake', __('prevention.tl_collected'), 'bg-amber-100 text-amber-800'],
        $type === 'certificate_reset'
            => ['bg-indigo-500 text-white', 'undo', __('prevention.tl_reset'), 'bg-indigo-100 text-indigo-800'],
        $type === 'history_snapshot' || $type === 'document_deleted'
            => ['bg-slate-400 text-white', 'inventory_2', __('prevention.tl_archive'), 'bg-surface-dim text-secondary'],
        // default: cancelled, appointment_failed, certificate_deleted
        default
            => ['bg-red-600 text-white', 'error', __('prevention.alert_chip'), 'bg-red-100 text-red-700'],
    };

    /* RDV annulé — participe à l'archive de l'historique (pas un incident) */
    $eventChip = function ($type) use ($eventChip) {
        if ($type === 'appointment_cancelled') {
            return ['bg-slate-400 text-white', 'event_busy', __('prevention.tl_cancelled'), 'bg-surface-dim text-secondary'];
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
        'cancelled'           => ['bg-surface-dim text-secondary', 'cancel'],
        default               => ['bg-surface-container-high text-secondary', 'info'],
    };
    [$statusPillCls, $statusPillIcon] = $statusPill;
    $statusLabel = [
        'draft'              => __('prevention.st_draft'),
        'completed'          => __('prevention.st_completed'),
        'submitted'          => __('prevention.st_submitted'),
        'under_consultation' => __('prevention.st_under_consultation'),
        'needs_correction'   => __('prevention.st_needs_correction'),
        'approved'           => __('prevention.st_approved'),
        'waiting_delivery'   => __('prevention.st_waiting_delivery'),
        'cancelled'          => __('prevention.st_cancelled'),
    ];

    /* ── Bandeau d'état du héros (contextuel) ── */
    $heroAlert = match (true) {
        $status === 'draft'               => 'draft',
        $status === 'needs_correction'    => 'error',
        $pendingAppointment !== null      => 'visit',
        $status === 'waiting_delivery' && ! $d->certificate => 'delivery',
        $status === 'under_consultation' && $lastFailedAppointment => 'visit_failed',
        $status === 'under_consultation'  => 'info',
        $status === 'approved' && ! $d->certificate => 'info',
        in_array($status, ['cancelled'])  => 'neutral',
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
        \App\Models\PreventionCertificate::STATUS_READY           => [__('prevention.cert_ready'),              'bg-amber-500 text-white'],
        \App\Models\PreventionCertificate::STATUS_POST_SENT       => [__('prevention.tl_b3_sent'),          'bg-blue-100 text-blue-800'],
        \App\Models\PreventionCertificate::STATUS_POST_DELIVERED  => [__('prevention.cert_post_delivered'),  'bg-cyan-100 text-cyan-800'],
        \App\Models\PreventionCertificate::STATUS_COLLECTED       => [__('prevention.tl_collected'),          'bg-violet-100 text-violet-800'],
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
        @can('manage prevention-dossiers')
        @if (in_array($status, ['completed', 'submitted', 'needs_correction']))
        <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden" x-data="{ fixOpen: false }">
            <div class="border-s-4 border-s-primary p-5">
                <div class="flex flex-wrap items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">fact_check</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('prevention.admin_review') }}</h2>
                            <span class="text-[10px] bg-primary text-on-primary font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('prevention.action_required') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('prevention.review_intro') }}</p>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-3">
                    {{-- Advance to consultation — the only forward move in this phase --}}
                    <form method="POST" action="{{ route('admin.prevention-dossiers.advance', $d) }}" class="flex-1"
                          onsubmit="return confirm('{{ __('prevention.confirm_advance') }}')">
                        @csrf
                        <button type="submit" class="inline-flex w-full lg:w-auto items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-6 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_circle_right</span>
                            {{ __('prevention.advance_btn') }}
                        </button>
                    </form>

                    @unless ($pendingAppointment)
                    {{-- {{ __('prevention.request_correction') }} (collapsed amber panel) — masqué si un RDV est en attente : c'est la visite qui décide --}}
                    <button type="button" @click="fixOpen = !fixOpen"
                        class="inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">edit_note</span>
                        <span x-text="fixOpen ? '{{ __('prevention.hide_correction_form') }}' : '{{ __('prevention.request_correction') }}'">{{ __('prevention.request_correction') }}</span>
                    </button>
                </div>

                {{-- Correction form ───────────────────────────────────────────── --}}
                <div x-show="fixOpen" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="mt-4 rounded-lg border border-amber-200 bg-amber-50/60 dark:border-amber-800/40 dark:bg-amber-900/10 p-4">
                    <form method="POST" action="{{ route('admin.prevention-dossiers.correction', $d) }}">
                        @csrf
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300 mb-1.5">
                            {{ __('prevention.note_to_applicant') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea name="admin_note" rows="4" required maxlength="2000" class="text-xs"
                            placeholder="{{ __('prevention.note_correction_placeholder') }}">{{ old('admin_note') }}</textarea>

                        <p class="mt-3 mb-1.5 text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                            {{ __('prevention.fields_to_fix') }} <span class="font-normal normal-case">{{ __('prevention.select_all_apply') }}</span>
                        </p>
                        @php
                            $fixableFields = [
                                'nom_prenom'           => __('prevention.f_full_name'),
                                'cin'                  => __('prevention.f_cin'),
                                'phone'                => __('prevention.f_phone'),
                                'governorate_id'       => __('prevention.f_governorate'),
                                'delegation_id'        => __('prevention.f_delegation'),
                                'building_address'     => __('prevention.f_building_address'),
                                'building_type_id'     => __('prevention.f_building_type'),
                                'building_activity_id' => __('prevention.f_activity'),
                            ];
                        @endphp
                        <div class="rounded-lg border border-surface-dim dark:border-gray-600 divide-y divide-surface-dim dark:divide-gray-700 overflow-hidden bg-surface-container-lowest">
                            @foreach ($fixableFields as $fieldKey => $fieldLabel)
                                <label class="flex items-center gap-2.5 px-3 py-2 cursor-pointer hover:bg-surface-container text-xs text-on-surface">
                                    <input type="checkbox" name="fields_to_fix[]" value="{{ $fieldKey }}"
                                           class="rounded border-surface-dim text-amber-500 focus:ring-amber-500/30"
                                           {{ in_array($fieldKey, old('fields_to_fix', [])) ? 'checked' : '' }}>
                                    <span class="flex-1">{{ $fieldLabel }}</span>
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
                                            default                => null,
                                        };
                                    @endphp
                                    @if ($currentVal)
                                        <span class="text-outline truncate max-w-28 text-end" title="{{ $currentVal }}">{{ $currentVal }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        @php
                            $corrDocTypes = \App\Models\DocumentType::service(\App\Models\DocumentType::SERVICE_PREVENTION)
                                ->with('buildingTypes')->orderBy('sort_order')->get()
                                ->filter(fn ($t) => $t->appliesToBuildingType($d->building_type_id));
                        @endphp
                        @if ($corrDocTypes->isNotEmpty())
                            <p class="mt-3 mb-1.5 text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                                {{ __('prevention.docs_to_reupload') }} <span class="font-normal normal-case">{{ __('prevention.select_replacement') }}</span>
                            </p>
                            <div class="rounded-lg border border-surface-dim dark:border-gray-600 divide-y divide-surface-dim dark:divide-gray-700 overflow-hidden bg-surface-container-lowest">
                                @foreach ($corrDocTypes as $docType)
                                    <label class="flex items-center gap-2.5 px-3 py-2 cursor-pointer hover:bg-surface-container text-xs text-on-surface">
                                        <input type="checkbox" name="docs_to_fix[]" value="{{ $docType->id }}"
                                               class="rounded border-surface-dim text-amber-500 focus:ring-amber-500/30"
                                               {{ in_array($docType->id, old('docs_to_fix', [])) ? 'checked' : '' }}>
                                        <span class="flex-1">{{ $docType->name }}</span>
                                        @if ($uploadedDocs->has($docType->id))
                                            <span class="text-[10px] rounded-full bg-blue-100 dark:bg-blue-900/30 px-1.5 py-0.5 text-blue-700 dark:text-blue-300 font-bold whitespace-nowrap">{{ __('prevention.uploaded_chip') }}</span>
                                        @else
                                            <span class="text-[10px] rounded-full bg-red-100 dark:bg-red-900/30 px-1.5 py-0.5 text-red-600 dark:text-red-400 font-bold whitespace-nowrap">{{ __('prevention.missing_chip') }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <button type="submit" class="mt-3 w-full inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">send</span>
                            {{ __('prevention.send_correction') }}
                        </button>
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
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('prevention.correction_requested_title') }}</h2>
                            <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded">{{ __('prevention.alert_chip') }}</span>
                        </div>
                    </div>
                </div>
                <div class="rounded border border-red-200 bg-red-50 dark:bg-red-900/10 px-4 py-3">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                        {{ __('prevention.note_visible_applicant') }}
                    </p>
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $d->admin_note }}</p>
                </div>
            </div>
        </section>
        @endif

        {{-- ── ACTE 3 : Consultation — programmer la visite (RDV)
             Affiché uniquement pour une PREMIÈRE programmation : si une visite
             a échoué, la bande "visite échouée" ci-dessous porte le nouveau RDV ;
             si un RDV est déjà programmé, c'est la bande "RDV programmé". ── --}}
        @if ($status === 'under_consultation' && ! $pendingAppointment && ! $lastFailedAppointment)
        <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
            <div class="border-s-4 border-s-primary p-5">
                <div class="flex items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">event_available</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('prevention.consultation_phase') }}</h2>
                            <span class="text-[10px] bg-primary text-on-primary font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('prevention.action_required') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('prevention.consultation_intro') }}</p>
                    </div>
                </div>

                {{-- Contact strip — applicant's phone for the convocation --}}
                <div class="flex flex-wrap items-center gap-3 mb-4 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-2.5 text-xs">
                    <span class="material-symbols-outlined text-blue-600 text-base" aria-hidden="true">call</span>
                    <span class="text-secondary">{{ __('prevention.applicant_phone_convocation') }}</span>
                    <strong class="font-mono num-ltr text-on-surface" dir="ltr">{{ $d->phone ?? __('prevention.not_provided') }}</strong>
                </div>

                @can('manage prevention-dossiers')
                    <form method="POST" action="{{ route('admin.prevention-dossiers.appointments.store', $d) }}" class="grid grid-cols-1 sm:grid-cols-[1fr,1fr,auto] items-end gap-3">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('prevention.visit_date') }} <span class="text-red-500">*</span></label>
                        <input type="date" name="visit_date" required min="{{ date('Y-m-d') }}" value="{{ old('visit_date') }}" class="w-full text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('prevention.visit_time') }} <span class="text-red-500">*</span></label>
                        <input type="time" name="visit_time" required value="{{ old('visit_time') }}" class="w-full text-sm">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('prevention.note_for_applicant') }} <span class="font-normal normal-case">{{ __('prevention.note_optional_convocation') }}</span></label>
                        <textarea name="note" rows="2" maxlength="2000" placeholder="{{ __('prevention.visit_instructions_placeholder') }}">{{ old('note') }}</textarea>
                    </div>
                    <button type="submit" class="sm:col-span-3 w-full inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">calendar_add_on</span>
                        {{ __('prevention.schedule_visit') }}
                    </button>
                    <p class="sm:col-span-3 text-[11px] text-secondary flex items-start gap-1.5">
                        <span class="material-symbols-outlined text-sm mt-px" aria-hidden="true">location_on</span>
                        <span>{{ __('prevention.visit_at_building') }} <strong class="font-semibold break-all">{{ $d->building_address ?? '—' }}</strong></span>
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
                                {{ __('prevention.next_visit_at', ['date' => $pendingAppointment->visit_date->format('d/m/Y'), 'time' => substr($pendingAppointment->visit_time, 0, 5)]) }}
                            </h2>
                            <span class="text-[10px] bg-primary text-on-primary font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('prevention.convocation_sent') }}</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                        <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">person_check</span>
                        <div><span class="font-bold text-on-surface block">{{ __('prevention.card_presence') }}</span><span class="text-secondary text-[11px]">{{ __('prevention.must_be_on_site', ['name' => $d->nom_prenom]) }}</span></div>
                    </div>
                    <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                        <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">menu_book</span>
                        <div><span class="font-bold text-on-surface block">{{ __('prevention.card_registre') }}</span><span class="text-secondary text-[11px]">{{ __('prevention.card_registre_sub') }}</span></div>
                    </div>
                    <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                        <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">door_front</span>
                        <div><span class="font-bold text-on-surface block">{{ __('prevention.card_accessibility') }}</span><span class="text-secondary text-[11px]">{{ __('prevention.card_accessibility_sub') }}</span></div>
                    </div>
                    <div class="flex items-start gap-2.5 bg-surface-container-low p-3 rounded border border-surface-dim">
                        <span class="material-symbols-outlined text-primary text-base mt-0.5" aria-hidden="true">water_drop</span>
                        <div><span class="font-bold text-on-surface block">{{ __('prevention.card_fire') }}</span><span class="text-secondary text-[11px]">{{ __('prevention.card_fire_sub') }}</span></div>
                    </div>
                </div>
                @if ($pendingAppointment->note)
                <div class="mt-4 rounded border border-amber-200 bg-amber-50/60 dark:border-amber-800/40 dark:bg-amber-900/10 p-3">
                    <p class="text-[11px] font-bold text-amber-900 dark:text-amber-300 mb-0.5 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">event_note</span>{{ __('prevention.note_label') }}
                    </p>
                    <p class="text-xs text-amber-900 dark:text-amber-200 whitespace-pre-line leading-relaxed">{{ $pendingAppointment->note }}</p>
                </div>
                @endif

                @can('manage prevention-dossiers')
                {{-- Actions admin : enregistrer le résultat ou annuler le RDV courant, directement depuis le bandeau --}}
                <div x-data="{ openResult: false, cancelOpen: false }" class="mt-4 pt-4 border-t border-surface-dim">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="openResult = !openResult; cancelOpen = false"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary hover:bg-primary-container text-on-primary px-3.5 py-2 text-[11px] font-bold transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">fact_check</span>
                            <span x-text="openResult ? '{{ __('prevention.hide_result_form') }}' : '{{ __('prevention.record_result') }}'"></span>
                        </button>
                        <button type="button" @click="cancelOpen = !cancelOpen; openResult = false"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 dark:border-red-800/40 bg-red-50 dark:bg-red-900/20 px-3.5 py-2 text-[11px] font-bold text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors"
                            title="{{ __('prevention.cancel_appt_title') }}">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">cancel</span>
                            <span x-text="cancelOpen ? '{{ __('prevention.hide_cancellation') }}' : '{{ __('prevention.cancel_appointment') }}'"></span>
                        </button>
                        <span class="text-[11px] text-secondary hidden sm:inline">
                            {{ __('prevention.hint_record_flow') }}
                        </span>
                    </div>
                    @include('admin.prevention-dossiers.partials.appointment-panels', ['d' => $d, 'appt' => $pendingAppointment])
                </div>
                @endcan
            </div>
        </section>
        @endif

        {{-- ── Visite échouée : le record (passé/échoué + note) vit DANS cette
             bande, et quand la visite n'est pas bonne, un nouveau RDV se
             programme directement ici. Pas de "toast" séparé. ── --}}
        @if ($lastFailedAppointment && ! in_array($status, ['cancelled', 'draft']))
        <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden no-print">
            <div class="border-s-4 border-s-red-600 p-5">
                <div class="flex items-start gap-3.5 mb-3">
                    <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">report</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('prevention.visit_failed_at', ['date' => $lastFailedAppointment->visit_date->format('d/m/Y'), 'time' => substr($lastFailedAppointment->visit_time, 0, 5)]) }}</h2>
                            <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('prevention.alert_chip') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('prevention.failed_intro') }}</p>
                    </div>
                </div>
                @if ($lastFailedAppointment->note)
                <div class="rounded border border-red-200 bg-red-50 dark:border-red-800/30 dark:bg-red-900/10 px-4 py-3">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>{{ __('prevention.inspector_note') }}
                    </p>
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $lastFailedAppointment->note }}</p>
                </div>
                @endif

                {{-- Nouveau RDV directement dans la bande, tant qu'aucun n'est programmé --}}
                @if (! $pendingAppointment)
                    @can('manage prevention-dossiers')
                    <div class="mt-4 rounded-lg border border-red-200 dark:border-red-800/40 bg-surface-container-low/60 p-4">
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-3 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">event_available</span>
                            {{ __('prevention.schedule_new_rdv_title') }}
                        </p>
                        <form method="POST" action="{{ route('admin.prevention-dossiers.appointments.store', $d) }}" class="grid grid-cols-1 sm:grid-cols-[1fr,1fr] gap-3">
                            @csrf
                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('prevention.new_visit_date') }} <span class="text-red-500">*</span></label>
                                <input type="date" name="visit_date" required min="{{ date('Y-m-d') }}" value="{{ old('visit_date') }}" class="w-full text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('prevention.new_visit_time') }} <span class="text-red-500">*</span></label>
                                <input type="time" name="visit_time" required value="{{ old('visit_time') }}" class="w-full text-sm">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-secondary mb-1">{{ __('prevention.note_for_applicant') }} <span class="font-normal normal-case">{{ __('prevention.optional') }}</span></label>
                                <textarea name="note" rows="2" maxlength="2000" placeholder="{{ __('prevention.new_visit_note_placeholder') }}">{{ old('note') }}</textarea>
                            </div>
                            <p class="sm:col-span-2 text-[11px] text-secondary flex items-start gap-1.5">
                                <span class="material-symbols-outlined text-sm mt-px" aria-hidden="true">location_on</span>
                                <span>{{ __('prevention.visit_at_building') }} <strong class="font-semibold break-all">{{ $d->building_address ?? '—' }}</strong> · {{ __('prevention.applicant_phone') }} <strong class="font-mono" dir="ltr">{{ $d->phone ?? '—' }}</strong></span>
                            </p>
                            <div class="sm:col-span-2">
                                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">calendar_add_on</span>
                                    {{ __('prevention.schedule_new_visit') }}
                                </button>
                            </div>
                        </form>
                    </div>
                    @endcan
                @else
                    <p class="mt-3 text-xs font-semibold text-secondary flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">event_available</span>
                        {{ __('prevention.next_visit_ready', ['date' => $pendingAppointment->visit_date->format('d/m/Y'), 'time' => substr($pendingAppointment->visit_time, 0, 5)]) }}.
                    </p>
                @endif
            </div>
        </section>
        @endif

        {{-- ══════════════ CYCLE DE VIE — Stepper + progression ══════════════ --}}
        <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
            <div class="p-5 md:px-6">
                {{-- En-tête : titre + phase + barre de progression --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-surface-dim">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">route</span>
                        {{ __('prevention.lifecycle') }}
                    </h3>
                    <div class="flex items-center gap-3 flex-1 sm:flex-none sm:min-w-64">
                        <span class="text-xs font-bold text-primary bg-primary-fixed/40 px-2 py-0.5 rounded whitespace-nowrap">
                            {{ __('prevention.phase_word') }} {{ $phaseNum }} / 4
                        </span>
                        <div class="hidden sm:flex-1 h-1.5 bg-surface-dim/50 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-primary-container to-amber-500 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                        </div>
                        <span class="text-[11px] font-mono font-bold text-secondary hidden sm:inline" dir="ltr">{{ $progress }}%</span>
                    </div>
                </div>

                {{-- Étapes reliées par une ligne de conduite --}}
                <div class="relative grid grid-cols-1 md:grid-cols-4 gap-5 md:gap-3">
                    <div class="hidden md:block absolute top-5 [inset-inline-start:12%] [inset-inline-end:12%] h-0.5 bg-surface-dim" aria-hidden="true"></div>

                    @foreach ($steps as $idx => $step)
                        <div class="relative flex md:flex-col items-start md:items-center gap-3 md:gap-2 md:text-center">
                            @if ($step['state'] === 'done')
                                <span class="relative z-10 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-sm ring-4 ring-amber-100 dark:ring-amber-900/40">
                                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">check</span>
                                </span>
                            @elseif ($step['state'] === 'current')
                                <span class="relative z-10 w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center text-sm font-black shadow-sm ring-4 ring-primary-fixed/70">
                                    {{ $step['num'] }}
                                </span>
                            @elseif ($step['state'] === 'error')
                                <span class="relative z-10 w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow-sm ring-4 ring-red-100 dark:ring-red-900/40">
                                    <span class="material-symbols-outlined text-lg" aria-hidden="true">priority_high</span>
                                </span>
                            @else
                                <span class="relative z-10 w-10 h-10 rounded-full bg-surface-container text-secondary flex items-center justify-center text-sm font-bold border border-surface-dim dark:bg-gray-700">
                                    {{ $step['num'] }}
                                </span>
                            @endif

                            <div class="min-w-0 md:px-1">
                                <div class="flex md:justify-center items-center gap-2">
                                    <h4 class="text-xs font-bold {{ $step['state'] === 'pending' ? 'text-secondary' : 'text-on-surface' }}">
                                        {{ [__('prevention.step1'), __('prevention.step2'), __('prevention.step3'), __('prevention.step4')][$idx] }}
                                    </h4>
                                    @if ($step['state'] === 'current')
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse" aria-hidden="true"></span>
                                    @elseif ($step['state'] === 'error')
                                        <span class="text-[10px] uppercase font-black text-red-800 bg-red-100 dark:bg-red-900/40 px-1.5 py-px rounded">{{ __('prevention.alert_chip') }}</span>
                                    @endif
                                </div>
                                <p class="text-[11px] mt-0.5 leading-snug {{ $step['state'] === 'error' ? 'text-red-800 dark:text-red-400 font-medium' : ($step['state'] === 'current' ? 'text-primary font-medium' : 'text-secondary') }}">
                                    {{ $step['sub'] }}
                                </p>
                                <p class="text-[10px] mt-1 font-semibold {{ $step['state'] === 'done' ? 'text-amber-800 dark:text-amber-400' : 'text-secondary' }}" dir="ltr">
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
                            {{ __('prevention.prevention_dossier') }} <span class="font-mono">{{ $viewRef }}</span>
                        </h1>
                        <p class="text-xs text-secondary mt-1 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-xs" aria-hidden="true">calendar_today</span>
                            {{ __('prevention.deposited_on', ['date' => $d->created_at->locale(app()->getLocale())->isoFormat('D MMMM YYYY')]) }}
                            @if ($d->reviewed_at && $d->reviewer)
                                • {{ __('prevention.last_review_by_on', ['name' => $d->reviewer->name, 'date' => $d->reviewed_at->format('d/m/Y')]) }}
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        <span class="inline-flex items-center gap-1.5 text-label-caps font-label-caps px-2.5 py-1 rounded font-bold {{ $statusPillCls }}">
                            <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $statusPillIcon }}</span>
                            {{ $statusLabel[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                        </span>
                        <a href="{{ route('admin.prevention-dossiers.index') }}"
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
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-secondary">{{ __('prevention.applicant_building') }}</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">badge</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention.f_full_name') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate" title="{{ $d->nom_prenom ?? '' }}">{{ $d->nom_prenom ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">fingerprint</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention.f_cin') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate font-mono" dir="ltr" title="{{ $d->cin ?? '' }}">{{ $d->cin ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">call</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention.f_phone') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate font-mono num-ltr" dir="ltr" title="{{ $d->phone ?? '' }}">{{ $d->phone ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">mail</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention.f_email') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate" dir="ltr" title="{{ $d->user?->email ?? '' }}">{{ $d->user?->email ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">account_balance</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention.f_governorate') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $d->governorate?->name_fr ?? $d->governorate?->name_ar ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">location_city</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention.f_delegation') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $d->delegation?->name_fr ?? $d->delegation?->name_ar ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">apartment</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention.f_building_type') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $d->buildingType?->name ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">domain</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention.f_activity') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">{{ $d->buildingActivity?->name ?? $d->activity_other ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">place</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention.f_building_address') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate" title="{{ $d->building_address ?? '' }}">{{ $d->building_address ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">local_shipping</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('prevention.delivery_chosen') }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate">
                                @if ($d->delivery_method)
                                    {{ ['pickup' => __('prevention.pickup_office'), 'post' => __('prevention.post_b3'), 'digital' => __('prevention.digital_download')][$d->delivery_method] ?? ucfirst($d->delivery_method) }}
                                @else
                                    <span class="font-medium text-secondary">{{ __('prevention.not_chosen_yet') }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ── Map: pinned building location (if coordinates were provided) ── --}}
                @if ($d->latitude && $d->longitude)
                    <div class="mt-3">
                        @include('prevention-requests.partials.map-display', [
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
                        {{ __('prevention.admin_note_visible') }}
                    </p>
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $d->admin_note }}</p>
                </div>
                @endif

                {{-- Alerte d'état opérationnel — contextuelle --}}
                @if ($heroAlert === 'delivery')
                    <div class="mt-3 flex items-start gap-2.5 bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-300 dark:border-indigo-800/40 rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400 text-base mt-px" aria-hidden="true">verified</span>
                        <div class="font-bold text-indigo-900 dark:text-indigo-200 leading-relaxed">
                            {{ __('prevention.all_passed') }}
                            <span class="block font-normal text-indigo-700 dark:text-indigo-300 mt-0.5">
                                @if ($lastPassedAppointment)
                                    {{ __('prevention.visit_passed_on', ['date' => $lastPassedAppointment->visit_date->format('d/m/Y'), 'time' => substr($lastPassedAppointment->visit_time, 0, 5)]) }}@if ($lastPassedAppointment->note) {{ __('prevention.note_prefix') }} {{ $lastPassedAppointment->note }}@endif.
                                @endif
                                {{ __('prevention.deliver_cert_below') }}
                                @if ($d->delivery_method === 'post')
                                    {{ __('prevention.b3_flow') }}
                                @else
                                    {{ __('prevention.pickup_flow') }}
                                @endif
                                ).
                            </span>
                        </div>
                    </div>
                @elseif ($heroAlert === 'info')
                    <div class="mt-3 flex items-start gap-2.5 bg-amber-50 dark:bg-amber-900/10 border border-amber-300 dark:border-amber-800/40 rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-base mt-px" aria-hidden="true">hourglass_bottom</span>
                        <div class="font-bold text-amber-900 dark:text-amber-200 leading-relaxed">
                            {{ $status === 'under_consultation' ? __('prevention.approved_await_rdv') : __('prevention.approved_wait_act') }}
                        </div>
                    </div>
                @elseif ($heroAlert === 'neutral' && $status === 'cancelled')
                    <div class="mt-3 flex items-start gap-2.5 bg-surface-container-low border border-surface-dim rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-secondary text-base mt-px" aria-hidden="true">cancel</span>
                        <div class="font-bold text-on-surface leading-relaxed">
                            {{ __('prevention.dossier_cancelled') }}<span class="font-normal text-secondary"> {{ __('prevention.closed_by_admin') }}</span>
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
                                <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">{{ __('prevention.site_visits') }}</h3>
                                <p class="text-xs text-secondary mt-0.5">
                                    {{ __('prevention.visits_count', ['count' => $d->appointments->count()]) }} <strong class="font-semibold text-on-surface">{{ $d->building_address ?? '—' }}</strong>
                                </p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak class="p-5 pt-4">
                        @if ($d->appointments->isEmpty())
                            <div class="p-6 text-center border border-dashed border-surface-dim rounded-lg">
                                <span class="material-symbols-outlined text-3xl text-outline" aria-hidden="true">event_busy</span>
                                <p class="text-sm text-outline mt-2">{{ __('prevention.visits_none') }}
                                    @if ($status === 'under_consultation')
                                        {{ __('prevention.visits_none_hint') }}
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
                                                        {{ $appt->isPassed() ? __('prevention.status_passed') : ($appt->isFailed() ? __('prevention.status_failed') : ($appt->isCancelled() ? __('prevention.status_cancelled') : __('prevention.status_scheduled'))) }}
                                                    </span>
                                                    <span class="text-xs font-mono font-bold text-on-surface {{ $appt->isCancelled() ? 'line-through decoration-1' : '' }}" dir="ltr">
                                                        {{ $appt->visit_date->format('d/m/Y') }} • {{ substr($appt->visit_time, 0, 5) }}
                                                    </span>
                                                    @if ($appt->scheduler)
                                                        <span class="text-[10px] text-outline">{{ __('prevention.by_name', ['name' => $appt->scheduler->name]) }}</span>
                                                    @endif
                                                </div>

                                                @can('manage prevention-dossiers')
                                                <div class="flex items-center gap-2">
                                                    @if ($appt->isPending())
                                                        <button type="button" @click="openResult = !openResult; cancelOpen = false"
                                                            class="inline-flex items-center gap-1 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-1.5 text-[11px] font-bold text-on-surface hover:bg-surface-container">
                                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">fact_check</span> {{ __('prevention.record_result') }}
                                                        </button>
                                                        <button type="button" @click="cancelOpen = !cancelOpen; openResult = false"
                                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 px-3 py-1.5 text-[11px] font-bold text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors"
                                                            title="{{ __('prevention.cancel_appt_title') }}">
                                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">cancel</span> {{ __('prevention.cancel_appointment') }}
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
                                                        {{ $appt->isFailed() ? __('prevention.inspector_note') : ($appt->isCancelled() ? __('prevention.cancellation_reason') : __('prevention.note_label')) }}
                                                    </p>
                                                    <p class="text-xs whitespace-pre-line leading-relaxed {{ $appt->isFailed() ? 'text-red-800 dark:text-red-200' : 'text-secondary' }}">{{ $appt->note }}</p>
                                                </div>
                                            @endif

                                            @if ($appt->isPending())
                                                @include('admin.prevention-dossiers.partials.appointment-panels', ['d' => $d, 'appt' => $appt])
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ══════ CERTIFICAT DE PRÉVENTION — bande dorée + panel process ══════ --}}
                @if ($d->hasPassedAppointment() || $cert)
                @can('manage prevention-dossiers')
                <div class="bg-surface-container-lowest border rounded-lg shadow-sm overflow-hidden
                            {{ $cert ? ($cert->isCollected() || $cert->isPostDelivered() ? 'border-violet-200 dark:border-violet-800/40' : 'border-amber-200 dark:border-amber-700/40') : 'border-amber-200 dark:border-amber-700/40' }}"
                     x-data="{ open: {{ $cert ? ($cert->isCollected() || $cert->isPostDelivered() ? 'true' : 'true') : 'true' }}, revokeOpen: false }">

                    <button type="button" @click="open = !open"
                            class="w-full flex flex-wrap items-center justify-between gap-3 p-5 border-b border-amber-100 dark:border-amber-800/30 hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition-colors text-start">
                        <div class="flex items-center gap-2.5">
                            <span class="w-10 h-10 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">workspace_premium</span>
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('prevention.cert_title') }} @if ($cert)— <span class="font-mono" dir="ltr">{{ $cert->reference }}</span>@endif</h3>
                                <p class="text-xs text-secondary mt-0.5">
                                    @if ($cert)
                                        @if ($cert->isPickup()) {{ __('prevention.pickup_workflow_short') }}
                                        @elseif ($cert->isPost()) {{ __('prevention.post_workflow_short') }}
                                        @endif {{ __('prevention.issued_on', ['date' => $cert->issued_at->format('d/m/Y')]) }}
                                    @else
                                        {{ __('prevention.not_issued_yet') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5">
                            @if ($cert)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-surface-dim text-secondary">
                                    {{ $cert->isPickup() ? __('prevention.pickup_chip') : __('prevention.poste_tunisienne') }}
                                </span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $certStatusCfg[1] }}">{{ $certStatusCfg[0] }}</span>
                            @endif
                            <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </button>

                    <div x-show="open" x-cloak>

                        {{-- ── Certificat existant ── --}}
                        @if ($cert)
                        <div class="p-5 space-y-4">
                            @php
                                $certSteps = $cert->isPickup()
                                    ? [[__('prevention.cs_ready_office'), 'schedule_send', $cert->issued_at],
                                       [__('prevention.cs_collected'), 'check', $cert->collected_at]]
                                    : [[__('prevention.cs_ready'), 'schedule_send', $cert->issued_at],
                                       [__('prevention.cs_sent_b3'), 'local_post_office', $cert->post_sent_at],
                                       [__('prevention.cs_delivered'), 'mark_email_read', $cert->post_delivered_at]];
                                $stepIdx = $cert->isPickup()
                                    ? ($cert->isCollected() ? 1 : 0)
                                    : match ($cert->status) {
                                        \App\Models\PreventionCertificate::STATUS_POST_SENT      => 1,
                                        \App\Models\PreventionCertificate::STATUS_POST_DELIVERED => 2,
                                        default => 0,
                                    };
                            @endphp

                            {{-- 1. Pipeline de livraison (ordre strict du processus) --}}
                            <div class="rounded-lg border border-surface-dim bg-surface-container-low/60 p-3.5">
                                <p class="mb-2.5 text-[10px] font-extrabold uppercase tracking-wider text-secondary">
                                    @if ($cert->isPickup())
                                        {{ __('prevention.pickup_workflow_short') }}
                                    @else
                                        {{ __('prevention.post_workflow_strict') }}
                                    @endif
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
                                                <p class="text-[11px] font-bold leading-tight {{ $done ? 'text-amber-800 dark:text-amber-400' : ($current ? 'text-primary' : 'text-outline dark:text-gray-500') }}">
                                                    {{ $step[0] }}
                                                </p>
                                                <p class="mt-0.5 font-mono text-[10px] text-secondary" dir="ltr">
                                                    {{ ($done || $current) && $step[2] ? $step[2]->format('d/m/Y') : __('prevention.pending_dots') }}
                                                </p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>

                            {{-- 2. Détails livraison --}}
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-2.5 text-xs">
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.cert_ref') }}</dt>
                                    <dd class="font-mono font-bold text-on-surface" dir="ltr">{{ $cert->reference }}</dd>
                                </div>
                                <div class="text-end">
                                    <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.delivery') }}</dt>
                                    <dd class="font-semibold text-on-surface">
                                        @if ($cert->isPost()) Poste Tunisienne (B3)
                                        @elseif ($cert->isPickup()) Pickup at office
                                        @else — @endif
                                    </dd>
                                </div>

                                @if ($cert->isPickup())
                                    <div>
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.pickup_gov') }}</dt>
                                        <dd class="text-end font-semibold text-on-surface">{{ $cert->governorate?->name_fr ?? $cert->governorate?->name_ar ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.pickup_delegation') }}</dt>
                                        <dd class="text-end font-semibold text-on-surface">{{ $cert->delegation?->name_fr ?? $cert->delegation?->name_ar ?? __('prevention.all_delegations') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.issued_dt') }}</dt>
                                        <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->issued_at->format('d/m/Y') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.valid_until') }}</dt>
                                        <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->valid_until ? $cert->valid_until->format('d/m/Y') : '—' }}</dd>
                                    </div>
                                    @if ($cert->collected_at)
                                    <div class="col-span-2">
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.collected_on') }}</dt>
                                        <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->collected_at->format('d/m/Y, H:i') }}</dd>
                                    </div>
                                    @endif
                                @else
                                    <div>
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.recipient') }}</dt>
                                        <dd class="text-end font-semibold text-on-surface">{{ $cert->post_recipient ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.f_phone') }}</dt>
                                        <dd class="text-end font-semibold font-mono num-ltr text-on-surface" dir="ltr">{{ $cert->post_phone ?? '—' }}</dd>
                                    </div>
                                    <div class="col-span-2">
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.postal_address') }}</dt>
                                        <dd class="mt-1 rounded-lg bg-surface-container px-2.5 py-2 text-[11px] leading-relaxed break-words text-on-surface dark:bg-gray-700/30">{{ $cert->post_address ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.tracking_b3') }}</dt>
                                        <dd class="text-end font-mono font-semibold text-on-surface" dir="ltr">{{ $cert->post_tracking ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.issued_valid_until') }}</dt>
                                        <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->issued_at->format('d/m/Y') }} / {{ $cert->valid_until ? $cert->valid_until->format('d/m/Y') : '—' }}</dd>
                                    </div>
                                    @if ($cert->post_sent_at)
                                    <div>
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.sent_on') }}</dt>
                                        <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->post_sent_at->format('d/m/Y, H:i') }}</dd>
                                    </div>
                                    @endif
                                    @if ($cert->post_delivered_at)
                                    <div>
                                        <dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.delivered_on') }}</dt>
                                        <dd class="text-end font-semibold text-on-surface" dir="ltr">{{ $cert->post_delivered_at->format('d/m/Y, H:i') }}</dd>
                                    </div>
                                    @endif
                                @endif
                            </dl>

                            @if ($cert->note)
                                <div class="rounded-lg border border-surface-dim bg-surface-container px-2.5 py-2 dark:bg-gray-700/30">
                                    <p class="mb-0.5 text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.internal_note_not_visible') }}</p>
                                    <p class="whitespace-pre-line text-xs text-secondary dark:text-gray-300">{{ $cert->note }}</p>
                                </div>
                            @endif

                            {{-- 3. Actions — uniquement les mouvements légaux de l'état actuel --}}
                            <div class="space-y-2 pt-1">
                                @if ($cert->isPickup() && $cert->isReady())
                                    <form method="POST" action="{{ route('admin.prevention-dossiers.certificate.update', $d) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="action" value="collected">
                                        <button type="submit"
                                            class="inline-flex w-full items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-base" aria-hidden="true">handshake</span>
                                            {{ __('prevention.mark_collected') }}
                                        </button>
                                    </form>
                                @elseif ($cert->isPickup())
                                    <form method="POST" action="{{ route('admin.prevention-dossiers.certificate.update', $d) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="action" value="ready">
                                        <button type="submit"
                                            class="inline-flex w-full items-center justify-center gap-2 border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-bold py-2 px-4 rounded transition-colors dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">undo</span>
                                            {{ __('prevention.mark_ready_uncollect') }}
                                        </button>
                                    </form>
                                @elseif ($cert->isPost() && $cert->isReady())
                                    <form method="POST" action="{{ route('admin.prevention-dossiers.certificate.update', $d) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="action" value="sent">
                                        <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.tracking_b3') }} <span class="text-red-500">*</span></label>
                                        <input type="text" name="post_tracking" required maxlength="60"
                                            value="{{ old('post_tracking', $cert->post_tracking) }}"
                                            placeholder="{{ __('prevention.tracking_placeholder') }}" class="w-full text-sm">
                                        <button type="submit"
                                            class="mt-2 inline-flex w-full items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-base" aria-hidden="true">local_post_office</span>
                                            {{ __('prevention.register_post') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.prevention-dossiers.certificate.update', $d) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="action" value="ready">
                                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 border border-surface-dim bg-surface-container-lowest hover:bg-surface-container text-secondary text-[11px] font-bold py-2 px-4 rounded transition-colors dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">undo</span>
                                            {{ __('prevention.reedit_stays_ready') }}
                                        </button>
                                    </form>
                                @elseif ($cert->isPostSent())
                                    <form method="POST" action="{{ route('admin.prevention-dossiers.certificate.update', $d) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="action" value="delivered">
                                        <button type="submit"
                                            class="inline-flex w-full items-center justify-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-base" aria-hidden="true">mark_email_read</span>
                                            {{ __('prevention.confirm_post_delivery') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.prevention-dossiers.certificate.update', $d) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="action" value="ready">
                                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 border border-surface-dim bg-surface-container-lowest hover:bg-surface-container text-secondary text-[11px] font-bold py-2 px-4 rounded transition-colors dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">undo</span>
                                            {{ __('prevention.not_sent_rollback') }}
                                        </button>
                                    </form>
                                @else
                                    <p class="flex items-center gap-1.5 text-xs font-bold text-amber-800 dark:text-amber-400">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">verified</span>
                                        {{ __('prevention.delivery_confirmed') }}
                                    </p>
                                @endif
                            </div>

                            {{-- 4. Révocation — zone dangereuse, verrouillée une fois collecté --}}
                            @if (! $cert->isCollected() && $cert->status !== \App\Models\PreventionCertificate::STATUS_POST_DELIVERED)
                                <div class="border-t border-surface-dim pt-3" x-data="{ revokeOpen: false }">
                                    <button type="button" @click="revokeOpen = !revokeOpen"
                                            class="flex w-full items-center justify-between text-start" :aria-expanded="revokeOpen" aria-controls="cert-revoke">
                                        <span class="flex items-center gap-1.5 text-xs font-bold text-red-700 dark:text-red-400">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">warning</span>
                                            {{ __('prevention.revoke_cert') }}
                                        </span>
                                        <svg class="h-3.5 w-3.5 text-red-400 transition-transform" :class="revokeOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="revokeOpen" x-cloak id="cert-revoke" class="mt-2 rounded-lg border border-red-200 bg-red-50 dark:border-red-800/40 dark:bg-red-900/20 p-3">
                                        <p class="text-[11px] leading-relaxed text-red-800 dark:text-red-300">
                                            {{ __('prevention.revoke_warning', ['ref' => $cert->reference]) }}
                                        </p>
                                        <form method="POST" action="{{ route('admin.prevention-dossiers.certificate.destroy', $d) }}"
                                              onsubmit="return confirm('{{ __('prevention.confirm_revoke', ['ref' => $cert->reference]) }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="mt-2 inline-flex w-full items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2 px-4 rounded transition-colors shadow-sm">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">delete</span>
                                                {{ __('prevention.confirm_revoke_btn') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <p class="flex items-center gap-1.5 border-t border-surface-dim pt-3 text-[11px] font-bold text-secondary">
                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">lock</span>
                                    {{ __('prevention.locked_cert') }}
                                </p>
                            @endif
                        </div>

                        {{-- ── Pas encore émis : formulaire guidé ── --}}
                        @else
                        <div class="p-5">
                            @if ($d->delivery_method)
                                <div class="mb-3 flex items-start gap-2 rounded-lg border border-surface-dim bg-surface-container-low px-3 py-2 text-xs text-secondary">
                                    <span class="material-symbols-outlined text-base text-primary mt-px shrink-0" aria-hidden="true">touch_app</span>
                                    <span>{{ __('prevention.applicant_chose') }} <strong class="text-on-surface">{{ ['pickup' => __('prevention.pickup_office'), 'post' => __('prevention.poste_tunisienne'), 'digital' => __('prevention.digital_download')][$d->delivery_method] ?? ucfirst($d->delivery_method) }}</strong> {{ __('prevention.pre_selected_below') }} @if ($d->delivery_method === 'post') {{ __('prevention.postal_prefilled') }} @endif</span>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.prevention-dossiers.certificate.store', $d) }}">
                                @csrf
                                <div class="space-y-3"
                                     x-data="{
                                        method: '{{ old('delivery_method', $d->delivery_method ?? 'pickup') }}',
                                        gov: {{ old('governorate_id', $d->governorate_id) ?? 'null' }},
                                        del: {{ old('delegation_id') ? old('delegation_id') : 'null' }},
                                        map: {{ \Illuminate\Support\Js::from($governorates
                                            ->mapWithKeys(fn ($g) => [
                                                $g->id => ['name' => $g->name_fr ?? $g->name_ar, 'delegations' => $g->delegations->map(fn ($del) => ['id' => $del->id, 'name' => $del->name_fr ?? $del->name_ar])->values()->all()],
                                            ])
                                            ->toArray()) }}
                                     }">

                                    {{-- Mode de remise — digital annoncé mais pas actif --}}
                                    <div class="overflow-hidden rounded-lg border border-surface-dim dark:border-gray-600">
                                        <p class="px-3 pb-1.5 pt-2.5 text-[10px] font-extrabold uppercase tracking-wider text-secondary">{{ __('prevention.delivery_method') }}</p>
                                        <label class="flex cursor-pointer items-center gap-2.5 px-3 py-2 hover:bg-surface-container dark:hover:bg-gray-700/40"
                                               :class="method === 'pickup' ? 'bg-amber-50 dark:bg-amber-900/20' : ''">
                                            <input type="radio" name="delivery_method" value="pickup" x-model="method" required class="text-amber-600">
                                            <span class="material-symbols-outlined text-amber-600 dark:text-amber-400" aria-hidden="true">storefront</span>
                                            <span class="flex-1 text-sm font-bold text-on-surface">{{ __('prevention.pickup_office') }}</span>
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-600 text-white">{{ __('prevention.available') }}</span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-2.5 border-t border-surface-dim px-3 py-2 hover:bg-surface-container dark:border-gray-600 dark:hover:bg-gray-700/40"
                                               :class="method === 'post' ? 'bg-blue-50 dark:bg-blue-900/20' : ''">
                                            <input type="radio" name="delivery_method" value="post" x-model="method" class="text-blue-600">
                                            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400" aria-hidden="true">local_shipping</span>
                                            <span class="flex-1 text-sm font-bold text-on-surface">{{ __('prevention.poste_tunisienne') }}</span>
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-600 text-white">{{ __('prevention.registered') }}</span>
                                        </label>
                                        <div class="flex select-none items-center gap-2.5 border-t border-surface-dim px-3 py-2 opacity-55 dark:border-gray-600">
                                            <input type="radio" disabled class="text-gray-400">
                                            <span class="material-symbols-outlined text-secondary" aria-hidden="true">download</span>
                                            <span class="flex-1 text-sm font-bold text-outline dark:text-gray-500">{{ __('prevention.digital_download') }}</span>
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ __('prevention.coming_soon') }}</span>
                                        </div>
                                    </div>

                                    {{-- Pickup : gouvernorat + délégation --}}
                                    <div x-show="method === 'pickup'" x-cloak>
                                        <div>
                                            <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.gouvernorat') }} <span class="text-red-500">*</span></label>
                                            <select name="governorate_id" :required="method === 'pickup'" x-model="gov" class="w-full text-sm">
                                                <option value="">{{ __('prevention.select_gouvernorat') }}</option>
                                                <template x-for="(entry, govId) in map" :key="govId">
                                                    <option :value="govId" x-text="entry.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="mt-3">
                                            <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.pickup_delegation') }} <span class="font-normal normal-case">{{ __('prevention.optional') }}</span></label>
                                            <select name="delegation_id" x-model="del" class="w-full text-sm">
                                                <option value="">{{ __('prevention.all_delegations') }}</option>
                                                <template x-for="dele in (map[gov] ? map[gov].delegations : [])" :key="dele.id">
                                                    <option :value="dele.id" x-text="dele.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Post : données destinataire B3 --}}
                                    <div x-show="method === 'post'" x-cloak class="space-y-3 rounded-lg border border-blue-200 p-3 dark:border-blue-800/40">
                                        <p class="flex items-center gap-1.5 text-[11px] font-bold text-blue-700 dark:text-blue-300">
                                            <span class="material-symbols-outlined flex-none text-sm" aria-hidden="true">local_post_office</span>
                                            {{ __('prevention.b3_intro') }}
                                        </p>
                                        <div>
                                            <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.recipient') }} <span class="text-red-500">*</span></label>
                                            <input type="text" name="post_recipient" :required="method === 'post'" maxlength="255"
                                                value="{{ old('post_recipient', $d->delivery_method === 'post' ? $d->nom_prenom : '') }}" class="w-full text-sm">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">Phone</label>
                                            <input type="text" name="post_phone" maxlength="40"
                                                value="{{ old('post_phone', $d->delivery_method === 'post' ? $d->phone : '') }}" class="w-full text-sm">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.postal_address') }} <span class="text-red-500">*</span></label>
                                            <textarea name="post_address" rows="3" :required="method === 'post'" maxlength="500"
                                                placeholder="{{ __('prevention.postal_address_placeholder') }}">{{ old('post_address', $d->delivery_method === 'post' ? $d->post_address : '') }}</textarea>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.tracking_b3') }} <span class="font-normal normal-case">{{ __('prevention.tracking_optional_lot') }}</span></label>
                                            <input type="text" name="post_tracking" maxlength="60" value="{{ old('post_tracking') }}" placeholder="{{ __('prevention.tracking_placeholder') }}" class="w-full text-sm">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.valid_until') }} <span class="font-normal normal-case">{{ __('prevention.optional') }}</span></label>
                                            <input type="date" name="valid_until" min="{{ date('Y-m-d') }}" value="{{ old('valid_until') }}" class="w-full text-sm">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('prevention.internal_note') }} <span class="font-normal normal-case">{{ __('prevention.not_visible_short') }}</span></label>
                                            <textarea name="note" rows="2" maxlength="2000" placeholder="{{ __('prevention.internal_note') }}..." class="text-xs">{{ old('note') }}</textarea>
                                        </div>
                                    </div>
                                    <button type="submit"
                                        class="inline-flex w-full items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">workspace_premium</span>
                                        {{ __('prevention.confirm_issue') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
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
                                <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('prevention.timeline_title') }}</h3>
                                <p class="text-xs text-secondary mt-0.5">{{ __('prevention.timeline_sub') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] text-secondary font-semibold flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">receipt_long</span>
                                {{ __('prevention.events_count', ['count' => $d->events->count()]) }}
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
                                                        <span class="italic text-secondary">{{ __('prevention.system') }}</span>
                                                    @endif
                                                </span>
                                                @if ($event->actor)
                                                    @php
                                                        $actorRole = $event->actor->roles->first()?->name;
                                                        $isApplicant = $event->actor->roles->isEmpty();
                                                    @endphp
                                                    <span class="text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded {{ $isApplicant ? 'bg-surface-dim text-secondary' : 'bg-secondary-container text-on-secondary-container' }}">
                                                        {{ $isApplicant ? __('prevention.applicant_role') : ucfirst(str_replace('-', ' ', $actorRole ?? 'Admin')) }}
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
                                                <span class="font-bold text-on-surface">{{ __('prevention.note_colon') }}</span>
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
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">{{ __('prevention.correction_history') }}</h3>
                            <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5">
                                {{ __('prevention.rounds', ['count' => count($allCorrectionRounds)]) }}
                            </p>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300" dir="ltr">{{ count($allCorrectionRounds) }}</span>
                        <svg class="w-4 h-4 text-amber-700 dark:text-amber-400 transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="p-5">
                        @include('prevention-requests.partials.correction-history', [
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
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">{{ __('prevention.supporting_docs') }}</h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded whitespace-nowrap {{ $isDocComplete ? 'text-amber-900 bg-amber-100 dark:bg-amber-900/40 dark:text-amber-300' : 'text-secondary bg-surface-dim' }}" dir="ltr">{{ $uploadedTypes }}/{{ $documentTypes->count() }}</span>
                        <svg class="w-4 h-4 text-secondary transition-transform shrink-0 ms-1" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak class="p-5">
                        {{-- Barre de complétude --}}
                        <div class="h-2 bg-surface-dim/50 rounded-full overflow-hidden mb-1" role="progressbar" aria-valuenow="{{ $docPct }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full transition-all duration-500 {{ $isDocComplete ? 'bg-amber-500' : 'bg-primary' }}" style="width: {{ $docPct }}%"></div>
                        </div>
                        <p class="text-[10px] font-semibold mb-4 {{ $isDocComplete ? 'text-amber-800 dark:text-amber-400' : 'text-secondary' }}">
                            {{ $isDocComplete ? __('prevention.checklist_complete') : __('prevention.checklist_progress', ['uploaded' => $uploadedTypes, 'total' => $documentTypes->count()]) }}
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
                                        <a href="{{ route('admin.prevention-dossiers.documents.download', ['document' => $doc]) }}"
                                           class="w-7 h-7 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-600 hover:text-white flex items-center justify-center shrink-0 transition-colors"
                                           title="{{ __('prevention.title_download', ['name' => $doc->original_name]) }}">
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
                                                        <span class="font-semibold text-amber-900 bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40 px-1.5 rounded">{{ __('prevention.conditional') }}</span>
                                                    @endif
                                                    <span>{{ __('prevention.missing_doc') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <div class="p-4 text-center">
                                    <span class="material-symbols-outlined text-2xl text-outline" aria-hidden="true">folder_off</span>
                                    <p class="text-xs text-secondary mt-1">{{ __('prevention.configured_none') }}</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Fichiers transmis hors checklist --}}
                        @php $orphanDocs = $uploadedDocs->filter(fn ($doc) => ! $documentTypes->contains('id', $doc->document_type_id)); @endphp
                        @if ($orphanDocs->isNotEmpty())
                            <div class="pt-3 mt-2 border-t border-surface-dim/60">
                                <p class="text-[11px] font-bold text-secondary flex items-center gap-1.5 mb-2">
                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">attachment</span>
                                    {{ __('prevention.other_files', ['count' => $orphanDocs->count()]) }}
                                </p>
                                <div class="space-y-2.5">
                                    @foreach ($orphanDocs as $doc)
                                        <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-2.5">
                                            <div class="flex items-start gap-2.5 min-w-0">
                                                <span class="material-symbols-outlined text-red-600 text-lg mt-0.5 shrink-0" aria-hidden="true">{{ str_contains((string) $doc->mime_type, 'pdf') ? 'picture_as_pdf' : 'description' }}</span>
                                                <div class="min-w-0">
                                                    <h4 class="text-xs font-bold text-on-surface truncate" title="{{ $doc->original_name }}">{{ $doc->original_name ?? __('prevention.document_fallback') }}</h4>
                                                    <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5" dir="ltr">
                                                        <span class="font-mono">{{ $fmtSize($doc->size) }}</span>
                                                        <span class="text-surface-dim">•</span>
                                                        <span>{{ $doc->created_at->format('d/m/Y') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="{{ route('admin.prevention-dossiers.documents.download', ['document' => $doc]) }}"
                                               class="w-7 h-7 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-600 hover:text-white flex items-center justify-center shrink-0 transition-colors" title="{{ __('prevention.download') }}">
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
                                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">{{ __('prevention.archived_files') }}</h3>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-surface-dim text-secondary" dir="ltr">{{ $archivedDocuments->count() }}</span>
                                    <svg class="w-4 h-4 text-secondary transition-transform" :class="openArchive ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                                <p class="text-[11px] text-secondary leading-relaxed">{{ __('prevention.archived_hint') }}</p>
                            </button>

                            <div x-show="openArchive" x-cloak class="pt-4 space-y-2.5">
                                @foreach ($archivedDocuments as $doc)
                                    <div class="p-3 bg-surface-container-low rounded border border-surface-dim flex items-start justify-between gap-2.5">
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <span class="material-symbols-outlined text-outline text-lg mt-0.5 shrink-0" aria-hidden="true">inventory_2</span>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold text-secondary leading-snug">{{ $doc->documentType?->name ?? __('prevention.document_fallback') }}</h4>
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
                                        <a href="{{ route('admin.prevention-dossiers.documents.download', ['document' => $doc]) }}"
                                           class="w-7 h-7 rounded bg-surface-container text-secondary hover:bg-primary hover:text-on-primary flex items-center justify-center shrink-0 transition-colors" title="{{ __('prevention.download') }}">
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
    @can('manage prevention-dossiers')
    @if ($d->isActionable())
    <section class="mt-6 border border-red-300 dark:border-red-800/50 rounded-lg bg-red-50/40 dark:bg-red-900/5 overflow-hidden" x-data="{ cancelOpen: false }">
        <div class="p-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0 dark:bg-red-900/30 dark:text-red-300">
                    <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">warning</span>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-extrabold text-red-700 dark:text-red-300 uppercase tracking-wide">{{ __('prevention.danger_zone_title') }}</h2>
                    <p class="text-xs text-secondary mt-0.5">{{ __('prevention.danger_zone') }}</p>
                </div>
            </div>
            <button type="button" @click="cancelOpen = !cancelOpen"
                class="inline-flex items-center justify-center gap-2 border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold py-2.5 px-5 rounded transition-colors dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 shrink-0">
                <span class="material-symbols-outlined text-base" aria-hidden="true">cancel</span>
                <span x-text="cancelOpen ? '{{ __('prevention.hide_cancellation') }}' : '{{ __('prevention.cancel_dossier') }}'">{{ __('prevention.cancel_dossier') }}</span>
            </button>
        </div>

        {{-- Formulaire d'annulation (déplié) --}}
        <div x-show="cancelOpen" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="border-t border-red-200 dark:border-red-800/40 p-5">
            <form method="POST" action="{{ route('admin.prevention-dossiers.cancel', $d) }}"
                  onsubmit="return confirm('{{ __('prevention.confirm_cancel_dossier') }}')">
                @csrf
                <label class="block text-xs font-semibold text-red-800 dark:text-red-200 mb-1.5">{{ __('prevention.cancellation_reason') }} <span class="text-red-500">*</span></label>
                <textarea name="admin_note" rows="4" required maxlength="2000"
                    placeholder="{{ __('prevention.cancel_reason_placeholder') }}">{{ old('admin_note') }}</textarea>
                <button type="submit" class="mt-3 w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">cancel</span>
                    {{ __('prevention.confirm_cancellation') }}
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
                        <div class="fiche-band-sub">{{ __('prevention.prevention_service', ['gov' => $d->governorate?->name_fr ?? $d->governorate?->name_ar ?? '—']) }}</div>
                    </div>
                </div>
                <div class="fiche-band-ref">
                    <div class="fiche-band-ref-label">{{ __('prevention.dossier') }}</div>
                    <div class="fiche-band-ref-value" dir="ltr">{{ $viewRef }}</div>
                </div>
            </div>

            {{-- Titre + date d'édition --}}
            <div class="fiche-title-row">
                <div class="fiche-title">{{ __('prevention.print_title') }}</div>
                <div class="fiche-printed" dir="ltr">{{ __('prevention.printed_on') }} {{ $printDate }}</div>
            </div>

            {{-- Bloc jumelé : demandeur • bâtiment --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <section class="fiche-sec">
                    <h2 class="fiche-h2">{{ __('prevention.fiche_applicant') }}</h2>
                    <table class="fiche-table fiche-kv">
                        <tr><th>{{ __('prevention.f_full_name') }}</th><td style="font-weight:700;">{{ $d->nom_prenom }}</td></tr>
                        <tr><th>{{ __('prevention.f_cin') }}</th><td class="fiche-mono" dir="ltr">{{ $d->cin }}</td></tr>
                        <tr><th>{{ __('prevention.f_phone') }}</th><td class="fiche-mono" dir="ltr">{{ $d->phone ?? '—' }}</td></tr>
                        <tr><th>{{ __('prevention.f_email') }}</th><td dir="ltr">{{ $d->user?->email ?? '—' }}</td></tr>
                    </table>
                </section>
                <section class="fiche-sec">
                    <h2 class="fiche-h2">{{ __('prevention.fiche_building') }}</h2>
                    <table class="fiche-table fiche-kv">
                        <tr><th>{{ __('prevention.f_building_type') }}</th><td>{{ $d->buildingType?->name ?? '—' }}</td></tr>
                        <tr><th>{{ __('prevention.f_activity') }}</th><td>{{ $d->buildingActivity?->name ?? $d->activity_other ?? '—' }}</td></tr>
                        <tr><th>{{ __('prevention.f_delegation') }}</th><td>{{ $d->delegation?->name_fr ?? $d->delegation?->name_ar ?? '—' }}</td></tr>
                        <tr><th>{{ __('prevention.address') }}</th><td>{{ $d->building_address ?? '—' }}</td></tr>
                        @if ($d->latitude && $d->longitude)
                        <tr><th>{{ __('prevention.coordinates') }}</th><td class="fiche-mono" dir="ltr">{{ number_format((float)$d->latitude, 5) }}, {{ number_format((float)$d->longitude, 5) }}</td></tr>
                        @endif
                    </table>
                </section>
            </div>

            {{-- Références administratives --}}
            <section class="fiche-sec">
                <h2 class="fiche-h2">{{ __('prevention.admin_refs') }}</h2>
                <table class="fiche-table fiche-kv-4">
                    <tr>
                        <th>{{ __('prevention.request_no') }}</th>
                        <td class="fiche-mono" dir="ltr">{{ $d->num_demande ?: '—' }}</td>
                        <th>{{ __('prevention.internal_ref') }}</th>
                        <td class="fiche-mono" dir="ltr">{{ $d->api_reference ?: $d->reference ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('prevention.deposited_short') }}</th>
                        <td class="fiche-mono" dir="ltr">{{ $d->created_at->format('d/m/Y') }}</td>
                        <th>{{ __('prevention.office') }}</th>
                        <td>{{ $d->governorate?->name_fr ?? $d->governorate?->name_ar ?? '—' }}</td>
                    </tr>
                </table>
            </section>

            {{-- Certificat (si émis) --}}
            @if ($cert)
            <section class="fiche-sec">
                <div class="fiche-cert">
                    <div class="fiche-cert-title">{{ __('prevention.cert_title') }}</div>
                    {{ __('prevention.cert_ref') }} <strong class="fiche-mono">{{ $cert->reference }}</strong>
                    — {{ __('prevention.delivery') }}: {{ $cert->isPickup() ? __('prevention.pickup_office') : __('prevention.poste_tunisienne') }}
                    — {{ __('prevention.fiche_status') }}: {{ $certStatusCfg[0] }}
                    — {{ __('prevention.fiche_issued') }}: {{ $cert->issued_at->format('d/m/Y') }}
                    @if ($cert->valid_until) — {{ __('prevention.valid_until') }}: {{ $cert->valid_until->format('d/m/Y') }}@endif
                    @if ($cert->post_tracking) — {{ __('prevention.fiche_tracking') }}: <span class="fiche-mono">{{ $cert->post_tracking }}</span>@endif
                </div>
            </section>
            @endif

            {{-- Documents versés au dossier --}}
            <section class="fiche-sec">
                <h2 class="fiche-h2">
                    {{ __('prevention.supporting_docs') }}
                    <span dir="ltr" style="font-weight:700;">— {{ $uploadedTypes }}/{{ $documentTypes->count() }}</span>
                </h2>
                <table class="fiche-table fiche-table-dark">
                    <thead>
                        <tr>
                            <th style="width:6%;">{{ __('prevention.col_no') }}</th>
                            <th>{{ __('prevention.col_document') }}</th>
                            <th>{{ __('prevention.col_file') }}</th>
                            <th style="width:11%;">{{ __('prevention.col_size') }}</th>
                            <th style="width:13%;">{{ __('prevention.col_date') }}</th>
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
                                    <td class="fiche-missing">{{ __('prevention.missing_chip') }}{{ $docType->is_conditional ? '' : ' ' . __('prevention.required_suffix') }}</td>
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
                <div>{{ $d->hasPassedAppointment() ? __('prevention.fiche_foot_passed') : __('prevention.fiche_foot_not') }}</div>
                <div>{{ $viewRef }} · {{ $d->id }}</div>
            </div>
        </div>
    </div>
@endsection
