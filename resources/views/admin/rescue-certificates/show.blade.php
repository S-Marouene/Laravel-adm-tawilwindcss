@extends('admin.layouts.admin')
@section('title', __('rescue_certificate.card_title') . ' — ' . ($certificate->reference ?: 'SEC26-####'))

@php
    $status = $certificate->status;

    // ── Référence affichée : référence stockée > provisoire ──
    $viewRef = $certificate->reference ?: \App\Support\Reference::provisional('rescue');

    /* ── Preuves issues du journal d'audit (dates des étapes) ── */
    $advancedEvent  = $certificate->events->last(fn ($e) => $e->type === 'advanced_to_consultation');
    $approvedEvent  = $certificate->events->last(fn ($e) => $e->type === 'approved');
    $issuedEvent    = $certificate->events->last(fn ($e) => $e->type === 'certificate_issued');
    $hadCorrection  = $certificate->events->contains(fn ($e) => $e->type === 'correction_requested');

    /* ── Stepper 4 étapes : dépôt → vérification → approuvé → certificat ── */
    $currentStep = match ($status) {
        'draft'              => 1,
        'completed'          => 2,
        'under_consultation' => 2,
        'needs_correction'   => 2,
        'approved'           => 3,
        'served'             => 5, // sentinel — all 4 done
        'cancelled'          => match (true) {
            $certificate->certIsCollected() || $certificate->certIsPostDelivered() => 5,
            $certificate->hasCertificate()                                          => 4,
            $approvedEvent !== null                                                 => 3,
            $advancedEvent !== null || $hadCorrection                               => 2,
            default                                                                 => 2,
        },
        default              => null,
    };
    if ($certificate->hasCertificate() && $currentStep < 4) {
        $currentStep = 4;
    }
    if (($certificate->certIsCollected() || $certificate->certIsPostDelivered())) {
        $currentStep = 5;
    }
    $isCancelled = $status === 'cancelled';

    $stepDates = [];
    $stepDates[1] = $certificate->created_at;
    $stepDates[2] = $advancedEvent?->created_at;
    $stepDates[3] = $approvedEvent?->created_at;
    $stepDates[4] = $certificate->issued_at;

    // Étape qui porte la marque « annulé »
    $cancelledAt = $isCancelled ? min($currentStep, 4) : null;

    // Étape 4 subtitle
    $certSub4 = match (true) {
        $certificate->certIsCollected() || $certificate->certIsPostDelivered()
            => __('rescue_certificate.stepper_done'),
        $certificate->certIsPostSent()
            => __('rescue_certificate.admin_cert_cs_sent'),
        $certificate->hasCertificate() && $certificate->isPickup()
            => __('rescue_certificate.admin_cert_cs_ready_office'),
        $certificate->hasCertificate() && $certificate->isPost()
            => __('rescue_certificate.admin_cert_cs_ready'),
        default
            => __('rescue_certificate.stepper_delivery_pending'),
    };

    $steps = [];
    foreach ([1, 2, 3, 4] as $i) {
        if ($isCancelled) {
            if ($i < $cancelledAt)       $state = 'done';
            elseif ($i === $cancelledAt) $state = 'cancelled';
            else                         $state = 'pending';
        } else {
            if ($currentStep === 5 || $i < $currentStep) $state = 'done';
            elseif ($i === $currentStep)                 $state = $status === 'needs_correction' ? 'error' : 'current';
            else                                         $state = 'pending';
        }

        if ($state === 'done' && $i === 2 && $hadCorrection) $state = 'warning';

        $sub = match (true) {
            $state === 'cancelled'           => __('rescue_certificate.stepper_cancelled'),
            $state === 'warning' && $i === 2 => __('rescue_certificate.stepper_had_correction'),
            $status === 'needs_correction' && $state === 'error' => __('rescue_certificate.stepper_correction'),
            $state === 'done'                => __('rescue_certificate.stepper_done'),
            $state === 'pending'             => __('rescue_certificate.stepper_pending'),
            $i === 2 && $status === 'completed'          => __('rescue_certificate.stepper_verify_pending'),
            $i === 2 && $status === 'under_consultation' => __('rescue_certificate.stepper_verify_waiting'),
            $i === 3                         => __('rescue_certificate.stepper_cert_approved'),
            $i === 4                         => $certSub4,
            default                          => __('rescue_certificate.stepper_current'),
        };

        $alert = in_array($state, ['error', 'cancelled']);

        $steps[] = [
            'num'   => $i,
            'title' => __('rescue_certificate.stepper_' . $i),
            'sub'   => $sub,
            'state' => $state,
            'alert' => $alert,
        ];
    }
    $doneSteps = collect($steps)->whereIn('state', ['done', 'warning'])->count();
    $progress  = (int) round($doneSteps / 4 * 100);
    $phaseNum  = $currentStep > 0 ? min($currentStep, 4) : 0;

    /* ── Timeline chips ── */
    $eventChip = fn ($type) => match (true) {
        in_array($type, ['submitted', 'resubmitted'])
            => ['bg-secondary-container text-on-secondary-container', 'post_add',         __('rescue_certificate.chip_depot'),      'bg-surface-dim text-secondary'],
        $type === 'advanced_to_consultation'
            => ['bg-violet-600 text-white',                           'sync_alt',          __('rescue_certificate.chip_phase'),      'bg-surface-dim text-secondary'],
        $type === 'correction_requested'
            => ['bg-amber-500 text-white',                            'edit_note',         __('rescue_certificate.chip_correction'), 'bg-amber-100 text-amber-800'],
        $type === 'approved'
            => ['bg-amber-600 text-white',                            'task_alt',          __('rescue_certificate.chip_favorable'),  'bg-amber-100 text-amber-800'],
        in_array($type, ['certificate_issued'])
            => ['bg-amber-500 text-white',                            'workspace_premium', __('rescue_certificate.chip_cert'),       'bg-amber-100 text-amber-800'],
        in_array($type, ['certificate_delivered', 'certificate_collected'])
            => ['bg-amber-600 text-white',                            'handshake',         __('rescue_certificate.chip_done'),       'bg-amber-100 text-amber-900'],
        $type === 'certificate_post_sent'
            => ['bg-blue-600 text-white',                             'local_post_office', __('rescue_certificate.chip_post'),       'bg-blue-100 text-blue-800'],
        $type === 'certificate_post_delivered'
            => ['bg-cyan-600 text-white',                             'mark_email_read',   __('rescue_certificate.chip_done'),       'bg-cyan-100 text-cyan-800'],
        $type === 'certificate_reset'
            => ['bg-slate-400 text-white',                            'undo',              __('rescue_certificate.chip_phase'),      'bg-surface-dim text-secondary'],
        $type === 'certificate_deleted'
            => ['bg-red-600 text-white',                              'delete',            __('rescue_certificate.chip_alert'),      'bg-red-100 text-red-700'],
        $type === 'delivery_chosen'
            => ['bg-indigo-600 text-white',                           'local_shipping',    __('rescue_certificate.chip_phase'),      'bg-indigo-100 text-indigo-800'],
        default
            => ['bg-red-600 text-white',                              'error',             __('rescue_certificate.chip_alert'),      'bg-red-100 text-red-700'],
    };

    /* ── Status pill ── */
    $statusPill = match ($status) {
        'draft', 'completed'  => ['bg-surface-container-high text-secondary', 'edit_note'],
        'under_consultation'  => ['bg-blue-100 text-blue-800', 'manage_search'],
        'needs_correction'    => ['bg-error-container text-error', 'error'],
        'approved'            => ['bg-amber-100 text-amber-900', 'task_alt'],
        'served'              => ['bg-teal-100 text-teal-800', 'handshake'],
        'cancelled'           => ['bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300', 'cancel'],
        default               => ['bg-surface-container-high text-secondary', 'info'],
    };
    [$statusPillCls, $statusPillIcon] = $statusPill;
    $statusLabel = [
        'draft'              => __('rescue_certificate.status_draft'),
        'completed'          => __('rescue_certificate.status_completed'),
        'under_consultation' => __('rescue_certificate.status_under_consultation'),
        'needs_correction'   => __('rescue_certificate.status_needs_correction'),
        'approved'           => __('rescue_certificate.status_approved'),
        'served'             => __('rescue_certificate.status_served'),
        'cancelled'          => __('rescue_certificate.status_cancelled'),
    ];

    /* ── Cert status config (for pipeline section) ── */
    $certStatusCfg = $certificate->hasCertificate() ? match ($certificate->cert_status) {
        \App\Models\RescueCertificate::CERT_STATUS_READY          => [__('rescue_certificate.cert_status_ready'),          'bg-amber-500 text-white'],
        \App\Models\RescueCertificate::CERT_STATUS_POST_SENT      => [__('rescue_certificate.cert_status_post_sent'),      'bg-blue-100 text-blue-800'],
        \App\Models\RescueCertificate::CERT_STATUS_POST_DELIVERED => [__('rescue_certificate.cert_status_post_delivered'), 'bg-cyan-100 text-cyan-800'],
        \App\Models\RescueCertificate::CERT_STATUS_COLLECTED      => [__('rescue_certificate.cert_status_collected'),      'bg-violet-100 text-violet-800'],
        default => ['—', 'bg-surface-dim text-secondary'],
    } : null;

    /* ── Hero alert ── */
    $heroAlert = match (true) {
        $status === 'cancelled'         => 'error',
        $status === 'needs_correction'  => 'error',
        $status === 'under_consultation' => 'delivery',
        in_array($status, ['approved']) && ! $certificate->hasCertificate() => 'delivery',
        default                         => null,
    };

    /* ── Documents completeness ── */
    $uploadedDocs  = $certificate->documents->keyBy('document_type_id');
    $uploadedTypes = $uploadedDocs->filter(fn ($doc) => $documentTypes->contains('id', $doc->document_type_id))->count();
    $fmtSize = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1) . ' Mo' : number_format($b / 1024, 0) . ' Ko';
    $isDocComplete = $documentTypes->count() > 0 && $uploadedTypes >= $documentTypes->count();
    $docPct        = $documentTypes->count() > 0 ? (int) round(min($uploadedTypes, $documentTypes->count()) / $documentTypes->count() * 100) : 0;

    /* ── Print date ── */
    $printDate = now()->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY, HH:mm');
@endphp

<style>
    .print-sheet { display: none; }
    @media print {
        @page { size: A4; margin: 11mm 11mm 14mm; }
        header, footer, .no-print { display: none !important; }
        html, body { background: #ffffff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .print-sheet { display: block !important; }
    }
    .fiche { font-family: var(--font-sans,'Roboto',Arial,sans-serif); font-size:11.5px; line-height:1.5; color:#1a1d29; background:#fff; }
    .fiche-band { background:#9e001f; color:#fff; padding:12px 18px; display:flex; align-items:center; justify-content:space-between; gap:16px; border-radius:3px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .fiche-band-logo { width:46px; height:46px; object-fit:contain; border-radius:4px; background:#fff; padding:3px; }
    .fiche-band-org  { font-size:14.5px; font-weight:800; letter-spacing:.02em; line-height:1.25; }
    .fiche-band-sub  { font-size:10px; opacity:.92; font-weight:400; }
    .fiche-band-ref  { text-align:end; line-height:1.3; }
    .fiche-band-ref-label { font-size:9px; text-transform:uppercase; letter-spacing:.14em; opacity:.85; font-weight:600; }
    .fiche-band-ref-value { font-family:'Roboto Mono',Menlo,Consolas,monospace; font-size:15px; font-weight:700; letter-spacing:.03em; }
    .fiche-title-row { display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin:14px 0 16px; padding-bottom:8px; border-bottom:2px solid #1a1d29; }
    .fiche-title  { font-size:15px; font-weight:800; letter-spacing:.01em; }
    .fiche-printed{ font-size:10px; color:#4b5563; font-weight:500; white-space:nowrap; }
    .fiche-h2 { font-size:11.5px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin:0 0 6px; padding-inline-start:8px; border-inline-start:3.5px solid #9e001f; color:#1a1d29; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .fiche-sec { break-inside:avoid; margin-bottom:16px; }
    .fiche-table { width:100%; border-collapse:collapse; }
    .fiche-table th,.fiche-table td { border:1px solid #c7cad1; padding:4.5px 9px; text-align:start; vertical-align:top; font-size:10.5px; }
    .fiche-table th { background:#f4f4f7; font-weight:700; color:#1a1d29; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .fiche-kv th:first-child { width:37%; }
    .fiche-table td { color:#24273a; }
    .fiche-mono { font-family:'Roboto Mono',Menlo,Consolas,monospace; letter-spacing:.02em; }
    .fiche-foot { margin-top:10px; padding-top:7px; border-top:1px solid #c7cad1; display:flex; justify-content:space-between; gap:12px; font-size:9px; color:#6b7280; }
</style>

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- ══════════════ ÉCRAN ══════════════ --}}
    <div class="dossier-screen no-print space-y-6">

        @if ($errors->any())
            <div class="bg-surface-container-lowest border border-red-200 rounded-lg text-body-sm text-red-800 px-4 py-3 shadow-sm flex items-start gap-2.5 border-s-4 border-s-red-600" role="alert">
                <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">error</span>
                <div>@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════
             BANDES D'ACTION — contextuelles, en tête de page.
             Chaque bande = le prochain acte possible.
        ══════════════════════════════════════════════════════════════════ --}}

        {{-- ── ACTE 1 : Étude — avancer / correction (before consultation) ── --}}
        @can('manage rescue-certificates')
        @if (in_array($status, ['completed', 'needs_correction']))
        <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden" x-data="{ fixOpen: false }">
            <div class="border-s-4 border-s-primary p-5">
                <div class="flex flex-wrap items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">fact_check</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('rescue_certificate.admin_actions_title') }}</h2>
                            <span class="text-[10px] bg-primary text-on-primary font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('rescue_certificate.admin_actions_subtitle') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('rescue_certificate.admin_advance_confirm') }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    {{-- Advance to verification --}}
                    <form method="POST" action="{{ route('admin.rescue-certificates.advance', $certificate) }}"
                          data-confirm data-confirm-style="info"
                          data-confirm-title="{{ __('rescue_certificate.admin_advance_confirm') }}"
                          data-confirm-ok="{{ __('rescue_certificate.admin_advance_confirm_ok') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-3 px-6 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_circle_right</span>
                            {{ __('rescue_certificate.admin_advance_btn') }}
                        </button>
                    </form>

                    {{-- Request correction --}}
                    <button type="button" @click="fixOpen = !fixOpen"
                        class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">edit_note</span>
                        <span x-text="fixOpen ? '{{ __('rescue_certificate.admin_hide_note') }}' : '{{ __('rescue_certificate.admin_correction_btn') }}'">{{ __('rescue_certificate.admin_correction_btn') }}</span>
                    </button>
                </div>

                {{-- Correction form ── --}}
                <div x-show="fixOpen" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mt-4 rounded-lg border border-amber-200 bg-amber-50/60 dark:border-amber-800/40 dark:bg-amber-900/10 p-4">
                    <form method="POST" action="{{ route('admin.rescue-certificates.correction', $certificate) }}">
                        @csrf
                        <div class="mb-3 flex items-start gap-3">
                            <span class="grid h-9 w-9 flex-none place-items-center rounded-lg bg-amber-500/15 text-amber-700" aria-hidden="true">
                                <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">edit_note</span>
                            </span>
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">{{ __('rescue_certificate.admin_correction_btn') }}</p>
                                <p class="text-[11px] text-secondary mt-0.5">{{ __('rescue_certificate.admin_correction_hint') }}</p>
                            </div>
                        </div>
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300 mb-1.5">
                            {{ __('rescue_certificate.admin_note_to_applicant') }} <span class="text-red-500">*</span>
                        </label>
                        <x-admin.bilingual-note name="admin_note" :rows="3" required
                            placeholder-key="rescue_certificate.note_fix_placeholder" />

                        {{-- Fields to correct ── --}}
                        @php
                            $fixableFields = [
                                'cin'                  => ['label' => __('rescue_certificate.cin'),                  'icon' => 'badge'],
                                'nom_prenom'           => ['label' => __('rescue_certificate.nom_prenom'),           'icon' => 'person'],
                                'phone'                => ['label' => __('rescue_certificate.phone'),                'icon' => 'call'],
                                'victim_is_minor'      => ['label' => __('rescue_certificate.victim_is_minor'),      'icon' => 'escalator_warning'],
                                'spouse_case'          => ['label' => __('rescue_certificate.spouse_case'),          'icon' => 'favorite'],
                                'governorate_id'       => ['label' => __('rescue_certificate.code_structure'),       'icon' => 'public'],
                                'delegation_id'        => ['label' => __('rescue_certificate.delegation'),           'icon' => 'account_balance'],
                                'incident_address'     => ['label' => __('rescue_certificate.incident_address'),     'icon' => 'location_on'],
                                'incident_type'        => ['label' => __('rescue_certificate.incident_type'),        'icon' => 'category'],
                                'incident_date'        => ['label' => __('rescue_certificate.incident_date'),        'icon' => 'event'],
                                'incident_time'        => ['label' => __('rescue_certificate.incident_time'),        'icon' => 'schedule'],
                                'incident_description' => ['label' => __('rescue_certificate.incident_description'), 'icon' => 'subject'],
                                'notes'                => ['label' => __('rescue_certificate.notes'),                'icon' => 'edit_note'],
                            ];

                            $currentFieldVals = [
                                'cin'                  => $certificate->cin,
                                'nom_prenom'           => $certificate->nom_prenom,
                                'phone'                => $certificate->phone,
                                'victim_is_minor'      => $certificate->victim_is_minor ? ($certificate->parent_name ?? null) : null,
                                'spouse_case'          => $certificate->spouse_case ? __('rescue_certificate.spouse_case') : null,
                                'governorate_id'       => $certificate->governorate?->name_fr ?? $certificate->governorate?->name_ar,
                                'delegation_id'        => $certificate->delegation?->name_fr ?? $certificate->delegation?->name_ar,
                                'incident_address'     => $certificate->incident_address,
                                'incident_type'        => $certificate->incidentTypeLabel(),
                                'incident_date'        => $certificate->incident_date?->format('d/m/Y'),
                                'incident_time'        => $certificate->incident_time?->format('H:i'),
                                'incident_description' => str($certificate->incident_description)->limit(40),
                                'notes'                => str($certificate->notes)->limit(40),
                            ];
                        @endphp
                        <p class="mt-4 mb-2 flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">fact_check</span>
                            <span>{{ __('rescue_certificate.admin_fields_to_fix') }}</span>
                            <span class="font-normal normal-case tracking-normal text-secondary dark:text-gray-500">{{ __('rescue_certificate.admin_select_what_fix') }}</span>
                        </p>
                        <div class="grid gap-1.5">
                            @foreach ($fixableFields as $fieldKey => $fieldDef)
                            <label class="group flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-lowest px-3 py-2 transition
                                          hover:border-amber-400/70 hover:bg-surface-container
                                          has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/80 has-[:checked]:shadow-sm
                                          dark:border-gray-600 dark:bg-gray-800/50
                                          dark:has-[:checked]:border-amber-500/70 dark:has-[:checked]:bg-amber-900/20">
                                <input type="checkbox" name="fields_to_fix[]" value="{{ $fieldKey }}"
                                       class="h-4 w-4 shrink-0 cursor-pointer rounded border-surface-dim text-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                       {{ in_array($fieldKey, old('fields_to_fix', [])) ? 'checked' : '' }}>
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-surface-container text-secondary transition
                                             group-has-[:checked]:bg-amber-100 group-has-[:checked]:text-amber-700
                                             dark:bg-gray-700 dark:text-gray-300" aria-hidden="true">
                                    <span class="material-symbols-outlined text-sm">{{ $fieldDef['icon'] }}</span>
                                </span>
                                <span class="flex-1 text-xs font-semibold text-on-surface dark:text-gray-200">{{ $fieldDef['label'] }}</span>
                                @if ($currentFieldVals[$fieldKey])
                                    <span class="truncate max-w-28 text-[10px] text-outline dark:text-gray-500" title="{{ $currentFieldVals[$fieldKey] }}">{{ $currentFieldVals[$fieldKey] }}</span>
                                @endif
                            </label>
                            @endforeach
                        </div>

                        {{-- Documents to re-upload ── --}}
                        @if ($documentTypes->isNotEmpty())
                        <p class="mt-4 mb-2 flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">folder_open</span>
                            {{ __('rescue_certificate.documents_title') }}
                        </p>
                        <div class="grid gap-1.5">
                            @foreach ($documentTypes as $docType)
                            <label class="group flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-surface-dim/70 bg-surface-container-lowest px-3 py-2 transition
                                          hover:border-amber-400/70 hover:bg-surface-container
                                          has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/80 has-[:checked]:shadow-sm
                                          dark:border-gray-600 dark:bg-gray-800/50
                                          dark:has-[:checked]:border-amber-500/70 dark:has-[:checked]:bg-amber-900/20">
                                <input type="checkbox" name="docs_to_fix[]" value="{{ $docType->id }}"
                                       class="h-4 w-4 shrink-0 cursor-pointer rounded border-surface-dim text-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                       {{ in_array($docType->id, old('docs_to_fix', [])) ? 'checked' : '' }}>
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-surface-container text-secondary transition
                                             group-has-[:checked]:bg-amber-100 group-has-[:checked]:text-amber-700
                                             dark:bg-gray-700 dark:text-gray-300" aria-hidden="true">
                                    <span class="material-symbols-outlined text-sm">description</span>
                                </span>
                                <span class="flex-1 text-xs font-semibold text-on-surface dark:text-gray-200">{{ $docType->name }}</span>
                                @if ($uploadedDocs->has($docType->id))
                                    <span class="text-[10px] rounded-full bg-blue-100 dark:bg-blue-900/30 px-1.5 py-0.5 text-blue-700 dark:text-blue-300 font-bold whitespace-nowrap">{{ __('rescue_certificate.download_label') }}</span>
                                @else
                                    <span class="text-[10px] rounded-full bg-red-100 dark:bg-red-900/30 px-1.5 py-0.5 text-red-600 dark:text-red-400 font-bold whitespace-nowrap">{{ __('rescue_certificate.documents_not_uploaded') }}</span>
                                @endif
                            </label>
                            @endforeach
                        </div>
                        @endif

                        <button type="submit"
                            class="mt-4 w-full inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-2.5 px-5 rounded-lg transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">send</span>
                            {{ __('rescue_certificate.admin_send_correction') }}
                        </button>
                    </form>
                </div>
            </div>
        </section>
        @endif
        @endcan

        {{-- ── ACTE 2 : Correction demandée — note envoyée ── --}}
        @if ($status === 'needs_correction' && $certificate->admin_note)
        <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden">
            <div class="border-s-4 border-s-red-600 p-5">
                <div class="flex items-start gap-3.5 mb-3">
                    <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">assignment_late</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('rescue_certificate.admin_correction_btn') }}</h2>
                            <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded">{{ __('rescue_certificate.chip_alert') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('rescue_certificate.admin_note_visible') }}</p>
                    </div>
                </div>
                <div class="rounded border border-red-200 bg-red-50 dark:bg-red-900/10 px-4 py-3">
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $certificate->admin_note }}</p>
                </div>
            </div>
        </section>
        @endif

        {{-- ── ACTE 3 : Vérification en cours ── --}}
        @if ($status === 'under_consultation')
        <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden" x-data="{ approveOpen: false }">
            <div class="border-s-4 border-s-blue-600 p-5">
                <div class="flex flex-wrap items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded bg-blue-100 text-blue-700 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">manage_search</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('rescue_certificate.stepper_verify_waiting') }}</h2>
                            <span class="text-[10px] bg-primary text-on-primary font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('rescue_certificate.admin_actions_subtitle') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('rescue_certificate.phone_notification_hint') }}</p>
                    </div>
                </div>

                {{-- Phone contact strip ── --}}
                <div class="flex flex-wrap items-center gap-3 mb-4 rounded-lg border border-blue-200 bg-blue-50/60 dark:border-blue-800/40 dark:bg-blue-900/10 px-4 py-2.5 text-xs">
                    <span class="material-symbols-outlined text-blue-600 text-base" aria-hidden="true">phone_in_talk</span>
                    <span class="text-secondary">{{ __('rescue_certificate.admin_phone_hint') }}</span>
                    <strong class="font-mono num-ltr text-on-surface" dir="ltr">{{ $certificate->phone ?? '—' }}</strong>
                </div>

                @can('manage rescue-certificates')
                <div class="flex flex-col gap-3">
                    {{-- Approve ── --}}
                    <form method="POST" action="{{ route('admin.rescue-certificates.approve', $certificate) }}"
                          data-confirm data-confirm-style="success"
                          data-confirm-title="{{ __('rescue_certificate.admin_approve_confirm') }}"
                          data-confirm-ok="{{ __('rescue_certificate.admin_approve_confirm_ok') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-3 px-6 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">task_alt</span>
                            {{ __('rescue_certificate.admin_approve_btn') }}
                        </button>
                    </form>
                    {{-- Request correction ── --}}
                    <button type="button" @click="approveOpen = !approveOpen"
                        class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">edit_note</span>
                        <span x-text="approveOpen ? '{{ __('rescue_certificate.admin_hide_note') }}' : '{{ __('rescue_certificate.admin_correction_btn') }}'">{{ __('rescue_certificate.admin_correction_btn') }}</span>
                    </button>
                </div>
                <div x-show="approveOpen" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mt-4 rounded-lg border border-amber-200 bg-amber-50/60 dark:border-amber-800/40 dark:bg-amber-900/10 p-4">
                    <form method="POST" action="{{ route('admin.rescue-certificates.correction', $certificate) }}">
                        @csrf
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300 mb-1.5">
                            {{ __('rescue_certificate.admin_note_to_applicant') }} <span class="text-red-500">*</span>
                        </label>
                        <x-admin.bilingual-note name="admin_note" :rows="3" required
                            placeholder-key="rescue_certificate.note_fix_placeholder" />
                        <button type="submit" class="mt-3 w-full inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-2.5 px-5 rounded-lg transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">send</span>
                            {{ __('rescue_certificate.admin_send_correction') }}
                        </button>
                    </form>
                </div>
                @endcan
            </div>
        </section>
        @endif

        {{-- ── ACTE 4 : Émettre le certificat (approuvé, pas encore émis) ── --}}
        @can('manage rescue-certificates')
        @if (in_array($status, ['approved', 'under_consultation']) && ! $certificate->hasCertificate())
        <section id="act-issue-cert" class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden" x-data="{ open: true }">
            <div class="border-s-4 border-s-red-600 p-5">
                <div class="flex flex-wrap items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">workspace_premium</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('rescue_certificate.admin_issue_cert_title') }}</h2>
                            <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded whitespace-nowrap">{{ __('rescue_certificate.admin_actions_subtitle') }}</span>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">{{ __('rescue_certificate.admin_issue_cert_intro') }}</p>
                    </div>
                    <button type="button" @click="open = !open" class="text-secondary hover:text-on-surface transition-colors" :aria-expanded="open">
                        <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>

                {{-- Free procedure note ── --}}
                <div class="mb-4 flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50/60 dark:border-emerald-800/30 dark:bg-emerald-900/10 px-3 py-2 text-xs text-secondary">
                    <span class="material-symbols-outlined text-base text-emerald-600 dark:text-emerald-400 mt-px shrink-0" aria-hidden="true">payments</span>
                    <span>{{ __('rescue_certificate.free_info') }}</span>
                </div>

                @if ($certificate->delivery_method)
                    <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 dark:border-red-800/30 bg-red-50/60 dark:bg-red-900/10 px-3 py-2 text-xs text-secondary">
                        <span class="material-symbols-outlined text-base text-red-600 dark:text-red-400 mt-px shrink-0" aria-hidden="true">touch_app</span>
                        <span>{{ __('rescue_certificate.admin_post_phone') }}: <strong class="text-on-surface">{{ ['pickup' => __('rescue_certificate.delivery_pickup_title'), 'post' => __('rescue_certificate.delivery_post_title')][$certificate->delivery_method] ?? ucfirst($certificate->delivery_method) }}</strong></span>
                    </div>
                @endif

                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <form method="POST" action="{{ route('admin.rescue-certificates.certificate.store', $certificate) }}">
                        @csrf
                        <div class="space-y-3"
                             x-data="{
                                method: '{{ old('delivery_method', $certificate->delivery_method ?? 'pickup') }}',
                                gov: {{ old('pickup_governorate_id', $certificate->governorate_id) ?? 'null' }},
                                del: {{ old('pickup_delegation_id', $certificate->delegation_id) ?? 'null' }},
                                map: {{ \Illuminate\Support\Js::from($governorates->mapWithKeys(fn ($g) => [
                                    $g->id => [
                                        'name'   => $g->name_fr ?? $g->name_ar,
                                        'office' => ['address' => $g->office_address ?? null, 'phone' => $g->office_phone ?? null, 'hours' => $g->office_hours ?? null],
                                        'delegations' => $g->delegations->map(fn ($del) => [
                                            'id'     => $del->id,
                                            'name'   => $del->name_fr ?? $del->name_ar,
                                            'office' => ['address' => $del->office_address ?? null, 'phone' => $del->office_phone ?? null, 'hours' => $del->office_hours ?? null],
                                        ])->values()->all(),
                                    ],
                                ])->toArray()) }},
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

                            {{-- Delivery method selector — digital & post disabled ── --}}
                            <div class="overflow-hidden rounded-lg border border-surface-dim dark:border-gray-600">
                                <p class="px-3 pb-1.5 pt-2.5 text-[10px] font-extrabold uppercase tracking-wider text-secondary">{{ __('rescue_certificate.admin_cert_delivery_method') }}</p>
                                {{-- Pickup (active) ── --}}
                                <label class="flex cursor-pointer items-center gap-2.5 px-3 py-2 hover:bg-surface-container dark:hover:bg-gray-700/40"
                                       :class="method === 'pickup' ? 'bg-amber-50 dark:bg-amber-900/20' : ''">
                                    <input type="radio" name="delivery_method" value="pickup" x-model="method" required class="text-amber-600">
                                    <span class="material-symbols-outlined text-amber-600 dark:text-amber-400" aria-hidden="true">storefront</span>
                                    <span class="flex-1 text-sm font-bold text-on-surface">{{ __('rescue_certificate.delivery_pickup_title') }}</span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-600 text-white">{{ __('rescue_certificate.delivery_available') }}</span>
                                </label>
                                {{-- Post (disabled — bientôt) ── --}}
                                <div class="flex select-none items-center gap-2.5 border-t border-surface-dim px-3 py-2 opacity-55 dark:border-gray-600">
                                    <input type="radio" disabled class="text-gray-400">
                                    <span class="material-symbols-outlined text-secondary" aria-hidden="true">local_shipping</span>
                                    <span class="flex-1 text-sm font-bold text-outline dark:text-gray-500">{{ __('rescue_certificate.delivery_post_title') }}</span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ __('rescue_certificate.delivery_soon') }}</span>
                                </div>
                                {{-- Digital (disabled — bientôt) ── --}}
                                <div class="flex select-none items-center gap-2.5 border-t border-surface-dim px-3 py-2 opacity-55 dark:border-gray-600">
                                    <input type="radio" disabled class="text-gray-400">
                                    <span class="material-symbols-outlined text-secondary" aria-hidden="true">download</span>
                                    <span class="flex-1 text-sm font-bold text-outline dark:text-gray-500">{{ __('rescue_certificate.delivery_digital_title') }}</span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ __('rescue_certificate.delivery_soon') }}</span>
                                </div>
                            </div>

                            {{-- Pickup: gouvernorat + délégation ── --}}
                            <div x-show="method === 'pickup'">
                                <div>
                                    <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.admin_pickup_gov') }} <span class="text-red-500">*</span></label>
                                    <select name="pickup_governorate_id" :required="method === 'pickup'" x-model="gov"
                                            x-on:change="if (!(map[gov]?.delegations || []).some(d => String(d.id) === String(del))) del = ''"
                                            class="w-full text-sm">
                                        <option value="">{{ __('rescue_certificate.code_structure_select') }}</option>
                                        <template x-for="(entry, govId) in map" :key="govId">
                                            <option :value="govId" :selected="String(govId) === String(gov)" x-text="entry.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="mt-3">
                                    <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">
                                        {{ __('rescue_certificate.admin_pickup_del') }}
                                        <span class="font-normal normal-case text-secondary dark:text-gray-500"> ({{ __('rescue_certificate.admin_cert_valid_until') === __('rescue_certificate.admin_cert_valid_until') ? 'optionnel' : '' }})</span>
                                    </label>
                                    <select name="pickup_delegation_id" x-model="del" class="w-full text-sm">
                                        <option value="">{{ __('rescue_certificate.delegation_empty') }}</option>
                                        <template x-for="dele in (map[gov] ? map[gov].delegations : [])" :key="dele.id">
                                            <option :value="dele.id" :selected="String(dele.id) === String(del)" x-text="dele.name"></option>
                                        </template>
                                    </select>
                                    <p class="mt-1 flex items-start gap-1 text-[10px] text-secondary">
                                        <span class="material-symbols-outlined text-xs mt-px shrink-0" aria-hidden="true">info</span>
                                        <span x-show="!del">{{ __('rescue_certificate.delegation_empty') }}</span>
                                        <span x-show="del" x-cloak>{{ __('rescue_certificate.delegation') }}</span>
                                    </p>
                                </div>

                                {{-- Office preview card ── --}}
                                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50/60 p-3 dark:border-amber-800/40 dark:bg-amber-900/10">
                                    <template x-if="pickedOffice">
                                        <div>
                                            <p class="mb-1 flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">storefront</span>
                                                {{ __('rescue_certificate.admin_pickup_gov') }} —
                                                <span class="normal-case tracking-normal" x-text="pickedOffice.name"></span>
                                            </p>
                                            <div class="grid grid-cols-1 gap-1 text-[11px] text-amber-900 dark:text-amber-100 sm:grid-cols-2">
                                                <span x-show="pickedOffice.office.address" class="flex items-start gap-1.5 min-w-0">
                                                    <span class="material-symbols-outlined text-sm mt-px shrink-0" aria-hidden="true">location_on</span>
                                                    <span x-text="pickedOffice.office.address"></span>
                                                </span>
                                                <span x-show="pickedOffice.office.phone" class="flex items-start gap-1.5 min-w-0">
                                                    <span class="material-symbols-outlined text-sm mt-px shrink-0" aria-hidden="true">call</span>
                                                    <span class="font-mono font-bold" dir="ltr" x-text="pickedOffice.office.phone"></span>
                                                </span>
                                                <span x-show="pickedOffice.office.hours" class="flex items-start gap-1.5 min-w-0 sm:col-span-2">
                                                    <span class="material-symbols-outlined text-sm mt-px shrink-0" aria-hidden="true">schedule</span>
                                                    <span x-text="pickedOffice.office.hours"></span>
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                    <p x-show="!pickedOffice" class="flex items-start gap-1.5 text-[11px] leading-relaxed text-amber-800 dark:text-amber-300">
                                        <span class="material-symbols-outlined text-sm mt-px shrink-0" aria-hidden="true">info</span>
                                        {{ __('rescue_certificate.admin_pickup_gov') }}
                                    </p>
                                </div>
                            </div>

                            {{-- Valid until + note ── --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">
                                        {{ __('rescue_certificate.admin_cert_valid_until') }}
                                        <span class="font-normal normal-case text-secondary">(optionnel)</span>
                                    </label>
                                    <input type="date" name="valid_until" min="{{ date('Y-m-d') }}" value="{{ old('valid_until') }}" class="w-full text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">
                                        {{ __('rescue_certificate.admin_cert_note') }}
                                        <span class="font-normal normal-case text-primary">({{ __('rescue_certificate.delivery_available') }})</span>
                                    </label>
                                    <x-admin.bilingual-note name="note" :rows="2" class="mt-1" />
                                </div>
                            </div>

                            <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">workspace_premium</span>
                                {{ __('rescue_certificate.admin_issue_btn') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        @endif
        @endcan

        {{-- ── ACTE 5 : Pipeline certificat émis ── --}}
        @can('manage rescue-certificates')
        @if ($certificate->hasCertificate() && ! $certificate->certIsCollected() && ! $certificate->certIsPostDelivered())
        @php
            $certSteps = $certificate->isPickup()
                ? [[__('rescue_certificate.admin_cert_cs_ready_office'), 'schedule_send', $certificate->issued_at],
                   [__('rescue_certificate.admin_cert_cs_collected'),    'check',         null]]
                : [[__('rescue_certificate.admin_cert_cs_ready'),  'schedule_send',   $certificate->issued_at],
                   [__('rescue_certificate.admin_cert_cs_sent'),   'local_post_office', $certificate->post_sent_at],
                   [__('rescue_certificate.admin_cert_cs_delivered'), 'mark_email_read', $certificate->post_delivered_at]];
            $stepIdx = $certificate->isPickup()
                ? ($certificate->certIsCollected() ? 1 : 0)
                : match ($certificate->cert_status) {
                    \App\Models\RescueCertificate::CERT_STATUS_POST_SENT      => 1,
                    \App\Models\RescueCertificate::CERT_STATUS_POST_DELIVERED => 2,
                    default => 0,
                };
            $actTheme = match (true) {
                $certificate->isPickup() && $certificate->certIsReady() => ['amber', 'handshake',     __('rescue_certificate.admin_mark_collected')],
                $certificate->isPickup()                                => ['amber', 'undo',          __('rescue_certificate.admin_mark_uncollect')],
                $certificate->isPost() && $certificate->certIsReady()   => ['blue',  'local_post_office', __('rescue_certificate.admin_register_post')],
                $certificate->certIsPostSent()                          => ['cyan',  'mark_email_read', __('rescue_certificate.admin_confirm_post_delivery')],
                default                                                 => ['violet','verified',      __('rescue_certificate.cert_status_post_delivered')],
            };
            [$actColor, $actIcon, $actTitle] = $actTheme;
            $actBorderCls = match($actColor) { 'amber' => 'border-amber-200 dark:border-amber-800/40', 'blue' => 'border-blue-200 dark:border-blue-800/40', 'cyan' => 'border-cyan-200 dark:border-cyan-800/40', default => 'border-violet-200 dark:border-violet-800/40' };
            $actBarCls    = match($actColor) { 'amber' => 'border-s-amber-600', 'blue' => 'border-s-blue-600', 'cyan' => 'border-s-cyan-600', default => 'border-s-violet-600' };
            $actIconCls   = match($actColor) { 'amber' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300', 'blue' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300', 'cyan' => 'bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-300', default => 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300' };
            $actBadgeCls  = match($actColor) { 'amber' => 'bg-amber-600 text-white', 'blue' => 'bg-blue-600 text-white', 'cyan' => 'bg-cyan-600 text-white', default => 'bg-violet-600 text-white' };
        @endphp
        <section id="act-cert-action" class="bg-surface-container-lowest border rounded-lg shadow-sm overflow-hidden {{ $actBorderCls }}">
            <div class="border-s-4 p-5 {{ $actBarCls }}">

                {{-- Header ── --}}
                <div class="flex flex-wrap items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded flex items-center justify-center flex-shrink-0 {{ $actIconCls }}">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">{{ $actIcon }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('rescue_certificate.admin_cert_title') }} — <span class="font-mono" dir="ltr">{{ $certificate->certificate_number }}</span></h2>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-surface-dim text-secondary">{{ $certificate->isPickup() ? __('rescue_certificate.admin_cert_pickup_chip') : __('rescue_certificate.admin_cert_post_chip') }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $certStatusCfg[1] }}">{{ $certStatusCfg[0] }}</span>
                            </div>
                        </div>
                        <p class="text-body-sm text-secondary mt-1">
                            {{ $certificate->isPickup() ? __('rescue_certificate.admin_pickup_workflow_short') : __('rescue_certificate.admin_post_workflow_short') }}
                            — {{ __('rescue_certificate.cert_issued_on') }} {{ $certificate->issued_at?->format('d/m/Y') }}
                        </p>
                    </div>
                </div>

                {{-- Delivery pipeline stepper ── --}}
                <div class="rounded-lg border border-surface-dim bg-surface-container-low/60 p-3.5 mb-4">
                    <p class="mb-2.5 text-[10px] font-extrabold uppercase tracking-wider text-secondary">
                        {{ $certificate->isPickup() ? __('rescue_certificate.admin_pickup_workflow_short') : __('rescue_certificate.admin_post_workflow_short') }}
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
                                    <p class="mt-0.5 font-mono text-[10px] text-secondary" dir="ltr">{{ ($done || $current) && $step[2] ? $step[2]->format('d/m/Y') : __('rescue_certificate.admin_pending_dots') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Delivery details ── --}}
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2.5 text-xs mb-4">
                    <div><dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.certificate_number') }}</dt><dd class="font-mono font-bold text-on-surface" dir="ltr">{{ $certificate->certificate_number }}</dd></div>
                    <div class="text-end"><dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.admin_delivery_label') }}</dt><dd class="font-semibold text-on-surface">{{ $certificate->isPickup() ? __('rescue_certificate.delivery_pickup_title') : __('rescue_certificate.delivery_post_title') }}</dd></div>
                    @if ($certificate->isPickup())
                        <div><dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.admin_pickup_gov') }}</dt><dd class="font-semibold text-on-surface">{{ $certificate->pickupGovernorate?->name ?? '—' }}</dd></div>
                        <div><dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.admin_pickup_del') }}</dt><dd class="font-semibold text-on-surface">{{ $certificate->pickupDelegation?->name ?? '—' }}</dd></div>
                    @else
                        <div><dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.admin_post_recipient') }}</dt><dd class="font-semibold text-on-surface">{{ $certificate->post_recipient ?? '—' }}</dd></div>
                        <div><dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.admin_post_phone') }}</dt><dd class="font-mono font-semibold num-ltr text-on-surface" dir="ltr">{{ $certificate->post_phone ?? '—' }}</dd></div>
                        <div class="col-span-2"><dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.admin_post_address') }}</dt><dd class="mt-1 rounded-lg bg-surface-container px-2.5 py-2 text-[11px] leading-relaxed break-words text-on-surface dark:bg-gray-700/30">{{ $certificate->post_address ?? '—' }}</dd></div>
                        @if ($certificate->post_tracking)<div><dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.admin_post_tracking') }}</dt><dd class="font-mono font-semibold text-on-surface" dir="ltr">{{ $certificate->post_tracking }}</dd></div>@endif
                    @endif
                    @if ($certificate->valid_until)<div><dt class="text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.cert_valid_until') }}</dt><dd class="font-semibold text-on-surface" dir="ltr">{{ $certificate->valid_until->format('d/m/Y') }}</dd></div>@endif
                </dl>

                @if ($certificate->delivery_note)
                <div class="rounded-lg border border-surface-dim bg-surface-container px-2.5 py-2 mb-4 dark:bg-gray-700/30">
                    <p class="mb-0.5 text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.admin_cert_note') }}</p>
                    <p class="whitespace-pre-line text-xs text-secondary dark:text-gray-300">{{ $certificate->delivery_note }}</p>
                </div>
                @endif

                {{-- Actions ── --}}
                <div class="space-y-2 pt-1 border-t border-surface-dim">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-secondary pt-2">{{ $actTitle }}</p>
                    @if ($certificate->isPickup() && $certificate->certIsReady())
                        <form method="POST" action="{{ route('admin.rescue-certificates.certificate.update', $certificate) }}"
                              data-confirm data-confirm-style="success"
                              data-confirm-title="{{ __('rescue_certificate.admin_mark_collected_confirm') }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="collected">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">handshake</span>
                                {{ __('rescue_certificate.admin_mark_collected') }}
                            </button>
                        </form>
                    @elseif ($certificate->isPickup())
                        <form method="POST" action="{{ route('admin.rescue-certificates.certificate.update', $certificate) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="ready">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">undo</span>
                                {{ __('rescue_certificate.admin_mark_uncollect') }}
                            </button>
                        </form>
                    @elseif ($certificate->isPost() && $certificate->certIsReady())
                        <form method="POST" action="{{ route('admin.rescue-certificates.certificate.update', $certificate) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="sent">
                            <label class="mb-1 block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.admin_post_tracking') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="post_tracking" required maxlength="60" value="{{ old('post_tracking', $certificate->post_tracking) }}" placeholder="ex. 3029 8765 4321" class="w-full text-sm mb-2">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">local_post_office</span>
                                {{ __('rescue_certificate.admin_register_post') }}
                            </button>
                        </form>
                    @elseif ($certificate->certIsPostSent())
                        <form method="POST" action="{{ route('admin.rescue-certificates.certificate.update', $certificate) }}"
                              data-confirm data-confirm-style="success"
                              data-confirm-title="{{ __('rescue_certificate.admin_confirm_post_delivery') }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="delivered">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">mark_email_read</span>
                                {{ __('rescue_certificate.admin_confirm_post_delivery') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.rescue-certificates.certificate.update', $certificate) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="ready">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 border border-surface-dim bg-surface-container-lowest hover:bg-surface-container text-secondary text-[11px] font-bold py-2 px-4 rounded transition-colors dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">undo</span>
                                {{ __('rescue_certificate.admin_rollback_ready') }}
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Revoke ── --}}
                <div class="border-t border-surface-dim pt-3 mt-3" x-data="{ revokeOpen: false }">
                    <button type="button" @click="revokeOpen = !revokeOpen"
                            class="flex w-full items-center justify-between text-start" :aria-expanded="revokeOpen">
                        <span class="flex items-center gap-1.5 text-xs font-bold text-red-700 dark:text-red-400">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">warning</span>
                            {{ __('rescue_certificate.admin_revoke_cert') }}
                        </span>
                        <svg class="h-3.5 w-3.5 text-red-400 transition-transform" :class="revokeOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="revokeOpen" x-cloak class="mt-2 rounded-lg border border-red-200 bg-red-50 dark:border-red-800/40 dark:bg-red-900/20 p-3">
                        <p class="text-[11px] leading-relaxed text-red-800 dark:text-red-300 mb-2">
                            {{ __('rescue_certificate.admin_revoke_warning', ['ref' => $certificate->certificate_number]) }}
                        </p>
                        <form method="POST" action="{{ route('admin.rescue-certificates.certificate.destroy', $certificate) }}"
                              data-confirm data-confirm-style="danger"
                              data-confirm-title="{{ __('rescue_certificate.admin_confirm_revoke', ['ref' => $certificate->certificate_number]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2 px-4 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">delete</span>
                                {{ __('rescue_certificate.admin_confirm_revoke_btn') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        @endif
        @endcan

        {{-- ══════════════ STEPPER + CYCLE DE VIE ══════════════ --}}
        <section class="bg-surface-container-lowest border {{ $isCancelled ? 'border-red-200 dark:border-red-800/40' : 'border-surface-dim' }} rounded-lg shadow-sm overflow-hidden">
            @if ($isCancelled)
            <div class="border-b border-red-200 dark:border-red-800/40 bg-red-50 dark:bg-red-900/20 border-s-4 border-s-red-600 p-5 md:px-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">cancel</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('rescue_certificate.cancelled_banner') }}</h3>
                        <p class="text-body-sm text-secondary mt-1">{{ __('rescue_certificate.admin_cancelled_panel_d') }}</p>
                        @if ($certificate->admin_note)
                            <div class="mt-3 rounded border border-red-200 bg-red-50 dark:bg-red-900/10 px-4 py-3">
                                <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                                    {{ __('rescue_certificate.admin_reason_sent') }}
                                </p>
                                <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $certificate->admin_note }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            <div class="p-5 md:px-6">
                {{-- Stepper header ── --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-surface-dim">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">route</span>
                        {{ __('rescue_certificate.stepper_title') }}
                    </h3>
                    <div class="flex items-center gap-3 flex-1 sm:flex-none sm:min-w-64">
                        @if ($isCancelled)
                        <span class="text-xs font-bold text-white bg-red-600 px-2 py-0.5 rounded whitespace-nowrap">{{ __('rescue_certificate.stepper_cancelled') }}</span>
                        @else
                        <span class="text-xs font-bold text-primary bg-primary-fixed/40 px-2 py-0.5 rounded whitespace-nowrap">
                            {{ __('rescue_certificate.phase_instruction', ['step' => $phaseNum ?: 1]) }}
                        </span>
                        @endif
                        <div class="hidden sm:flex-1 h-1.5 bg-surface-dim/50 rounded-full overflow-hidden">
                            <div class="h-full {{ $isCancelled ? 'bg-red-500' : 'bg-gradient-to-r from-primary-container to-amber-500' }} rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                        </div>
                        <span class="text-[11px] font-mono font-bold text-secondary hidden sm:inline" dir="ltr">{{ $progress }}%</span>
                    </div>
                </div>

                {{-- Steps grid ── --}}
                <div class="relative grid grid-cols-1 md:grid-cols-4 gap-5 md:gap-3">
                    <div class="hidden md:block absolute top-5 [inset-inline-start:10%] [inset-inline-end:10%] h-0.5 bg-surface-dim" aria-hidden="true"></div>
                    @foreach ($steps as $step)
                        <div class="relative flex md:flex-col items-start md:items-center gap-3 md:gap-2 md:text-center">
                            @if ($step['state'] === 'done')
                                <span class="relative z-10 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-sm ring-4 ring-amber-100 dark:ring-amber-900/40">
                                    <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;" aria-hidden="true">check</span>
                                </span>
                            @elseif ($step['state'] === 'warning')
                                <span class="relative z-10 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-sm ring-4 ring-amber-100 dark:ring-amber-900/40">
                                    <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;" aria-hidden="true">check</span>
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
                                <span class="relative z-10 w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow-sm ring-4 ring-red-100 dark:ring-red-900/40">
                                    <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;" aria-hidden="true">cancel</span>
                                </span>
                            @else
                                <span class="relative z-10 w-10 h-10 rounded-full bg-surface-container text-secondary flex items-center justify-center text-sm font-bold border border-surface-dim dark:bg-gray-700">
                                    {{ $step['num'] }}
                                </span>
                            @endif
                            <div class="min-w-0 md:px-1">
                                <div class="flex md:justify-center items-center gap-2">
                                    <h4 class="text-xs font-bold {{ $step['state'] === 'pending' ? 'text-secondary' : 'text-on-surface' }}">{{ $step['title'] }}</h4>
                                    @if ($step['state'] === 'current')
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                                    @elseif ($step['state'] === 'error' || $step['state'] === 'cancelled')
                                        <span class="text-[10px] uppercase font-black text-red-800 bg-red-100 dark:bg-red-900/40 dark:text-red-300 px-1.5 py-px rounded">{{ __('rescue_certificate.chip_alert') }}</span>
                                    @endif
                                </div>
                                <p class="text-[11px] mt-0.5 leading-snug {{ $step['alert'] ? 'text-red-700 dark:text-red-400 font-medium' : ($step['state'] === 'current' ? 'text-amber-700 dark:text-amber-400 font-medium' : ($step['state'] === 'done' || $step['state'] === 'warning' ? 'text-amber-800 dark:text-amber-400' : 'text-secondary')) }}">
                                    {{ $step['sub'] }}
                                </p>
                                <p class="text-[10px] mt-1 font-semibold {{ in_array($step['state'],['done','warning']) ? 'text-amber-700 dark:text-amber-500' : ($step['state'] === 'current' ? 'text-amber-600' : 'text-secondary') }}" dir="ltr">
                                    @if ($stepDates[$step['num']] ?? null)
                                        {{ $stepDates[$step['num']]->format('d/m/Y') }}
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

        {{-- ══════════════ HÉROS — Fiche identité de la demande ══════════════ --}}
        <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
            <div class="h-1 bg-gradient-to-r from-primary via-primary-container/70 to-transparent" aria-hidden="true"></div>

            <div class="p-5 md:p-6">
                {{-- Title row ── --}}
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                    <div class="min-w-0">
                        <h1 class="text-headline-md font-headline-md text-on-surface tracking-tight leading-tight flex items-center gap-2" dir="ltr">
                            <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">emergency</span>
                            {{ __('rescue_certificate.card_title') }} · <span class="font-mono">{{ $viewRef }}</span>
                        </h1>
                        <p class="text-xs text-secondary mt-1 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-xs" aria-hidden="true">calendar_today</span>
                            {{ __('rescue_certificate.deposited_on', ['date' => $certificate->created_at->locale(app()->getLocale())->isoFormat('D MMMM YYYY')]) }}
                            @if ($certificate->reviewed_at && $certificate->reviewer)
                                · {{ __('rescue_certificate.admin_by_line', ['name' => $certificate->reviewer->name, 'date' => $certificate->reviewed_at->format('d/m/Y')]) }}
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2.5 shrink-0">
                        <span class="inline-flex items-center gap-1.5 text-label-caps font-label-caps px-2.5 py-1 rounded font-bold {{ $statusPillCls }}">
                            <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $statusPillIcon }}</span>
                            {{ $statusLabel[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                        </span>
                        <a href="{{ route('admin.rescue-certificates.index') }}"
                            class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-2.5 rounded transition-colors">
                            <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                        </a>
                        <button type="button" onclick="window.print()"
                            class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                        </button>
                    </div>
                </div>

                {{-- Key facts grid ── --}}
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">badge</span>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-secondary">{{ __('rescue_certificate.admin_applicant_info') }}</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
                    @foreach ([
                        ['badge',       __('rescue_certificate.admin_full_name'),       $certificate->nom_prenom],
                        ['fingerprint', __('rescue_certificate.cin'),                   $certificate->cin],
                        ['call',        __('rescue_certificate.phone'),                 $certificate->phone],
                        ['mail',        __('rescue_certificate.admin_account_email'),   $certificate->user?->email],
                        ['account_balance', __('rescue_certificate.code_structure'),    $certificate->governorate?->name_fr ?? $certificate->governorate?->name_ar],
                        ['location_city',   __('rescue_certificate.delegation'),        $certificate->delegation?->name_fr ?? $certificate->delegation?->name_ar],
                        ['emergency',   __('rescue_certificate.incident_type'),         $certificate->incidentTypeLabel()],
                        ['place',       __('rescue_certificate.incident_address'),      $certificate->incident_address],
                        ['event',       __('rescue_certificate.incident_date'),         $certificate->incident_date?->format('d/m/Y') . ($certificate->incident_time ? ' · ' . $certificate->incident_time->format('H:i') : '')],
                        ['local_shipping', __('rescue_certificate.admin_cert_delivery_method'), $certificate->delivery_method ? __('rescue_certificate.delivery_' . $certificate->delivery_method . '_title') : __('rescue_certificate.delivery_not_available_yet')],
                    ] as [$icon, $label, $value])
                    <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0 dark:bg-gray-900/30">
                        <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">{{ $icon }}</span>
                        <div class="min-w-0">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ $label }}</span>
                            <span class="block text-xs font-bold text-on-surface truncate {{ in_array($icon, ['fingerprint','call']) ? 'font-mono' : '' }}" dir="{{ in_array($icon, ['fingerprint','call','mail']) ? 'ltr' : '' }}" title="{{ $value ?? '' }}">{{ $value ?? '—' }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Admin note (outside correction/cancelled context) ── --}}
                @if ($certificate->admin_note && ! in_array($status, ['needs_correction', 'cancelled']))
                <div class="mt-4 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-3">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">sticky_note_2</span>
                        {{ __('rescue_certificate.admin_note_visible') }}
                    </p>
                    <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $certificate->admin_note }}</p>
                </div>
                @endif

                {{-- Contextual alert ── --}}
                @if ($heroAlert === 'delivery')
                    <div class="mt-3 flex items-start gap-2.5 bg-amber-50 dark:bg-amber-900/10 border border-amber-300 dark:border-amber-800/40 rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-base mt-px" aria-hidden="true">workspace_premium</span>
                        <div class="font-bold text-amber-900 dark:text-amber-200 leading-relaxed">
                            {{ __('rescue_certificate.admin_issue_cert_intro') }}
                        </div>
                    </div>
                @elseif ($heroAlert === 'info')
                    <div class="mt-3 flex items-start gap-2.5 bg-blue-50 dark:bg-blue-900/10 border border-blue-300 dark:border-blue-800/40 rounded px-3.5 py-2.5 text-xs">
                        <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-base mt-px" aria-hidden="true">phone_in_talk</span>
                        <div class="font-bold text-blue-900 dark:text-blue-200 leading-relaxed">
                            {{ __('rescue_certificate.under_consultation_banner') }}
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- ══════════════ GRILLE 8/4 ══════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- ══════ COLONNE GAUCHE (8 colonnes) ══════ --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- ══════ OPÉRATION DE SECOURS ══════ --}}
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden"
                     x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="w-full flex flex-wrap items-center justify-between gap-3 p-5 border-b border-surface-dim hover:bg-surface-container transition-colors text-start">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">emergency</span>
                            </span>
                            <div>
                                <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('rescue_certificate.incident_section') }}</h3>
                                <p class="text-xs text-secondary mt-0.5">{{ $certificate->incidentTypeLabel() }} · {{ $certificate->incident_date?->format('d/m/Y') ?? '—' }}</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach ([
                                [__('rescue_certificate.incident_type'),        $certificate->incidentTypeLabel(),                                                        false],
                                [__('rescue_certificate.incident_date'),        ($certificate->incident_date?->format('d/m/Y') ?? '—') . ($certificate->incident_time ? ' · ' . $certificate->incident_time->format('H:i') : ''), true],
                                [__('rescue_certificate.incident_address'),     $certificate->incident_address,   false],
                                [__('rescue_certificate.victim_status'),
                                    $certificate->victim_is_minor
                                        ? __('rescue_certificate.victim_is_minor') . ' — ' . $certificate->parent_name . ' (' . $certificate->parent_cin . ')'
                                        : ($certificate->spouse_case ? __('rescue_certificate.spouse_case') : __('rescue_certificate.victim_normal')),
                                    false],
                            ] as [$lbl, $val, $mono])
                            <div class="bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ $lbl }}</span>
                                <span class="block text-xs font-bold text-on-surface mt-0.5 {{ $mono ? 'font-mono' : '' }}">{{ $val ?? '—' }}</span>
                            </div>
                            @endforeach
                            <div class="sm:col-span-2 bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.incident_description') }}</span>
                                <span class="block text-xs text-on-surface mt-0.5 whitespace-pre-line leading-relaxed">{{ $certificate->incident_description ?? '—' }}</span>
                            </div>
                            @if ($certificate->notes)
                            <div class="sm:col-span-2 bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.notes') }}</span>
                                <span class="block text-xs text-on-surface mt-0.5 whitespace-pre-line leading-relaxed">{{ $certificate->notes }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- Free procedure badge ── --}}
                        <div class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50/60 px-4 py-3 dark:border-emerald-800/40 dark:bg-emerald-900/10">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-emerald-600 text-lg" aria-hidden="true">payments</span>
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">{{ __('rescue_certificate.fees_info') }}</span>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 whitespace-nowrap">{{ __('rescue_certificate.free') }}</span>
                        </div>
                    </div>
                </div>

                {{-- ══════ TIMELINE ══════ --}}
                @if ($certificate->events->isNotEmpty())
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print"
                     x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="w-full flex flex-wrap items-center justify-between gap-3 p-5 border-b border-surface-dim hover:bg-surface-container transition-colors text-start">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">history</span>
                            </span>
                            <div>
                                <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('rescue_certificate.timeline_title') }}</h3>
                                <p class="text-xs text-secondary mt-0.5">{{ __('rescue_certificate.events_count', ['n' => $certificate->events->count()]) }}</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak class="p-5">
                        {{-- Linear timeline ── --}}
                        <div class="relative ps-6 sm:ps-8 space-y-6 before:absolute before:start-3 sm:before:start-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                            @foreach ($certificate->events->reverse()->values() as $event)
                                @php [$dot, $icon, $chip, $chipCls] = $eventChip($event->type); @endphp
                                <div class="relative">
                                    {{-- Dot ── --}}
                                    <div class="absolute -start-6 sm:-start-8 top-0 w-6 sm:w-8 flex items-center justify-center">
                                        <div class="w-5 h-5 rounded-full {{ $dot }} flex items-center justify-center ring-4 ring-surface-container-lowest shadow-xs">
                                            <span class="material-symbols-outlined text-[11px]" aria-hidden="true">{{ $icon }}</span>
                                        </div>
                                    </div>
                                    {{-- Content ── --}}
                                    <div class="bg-surface-container-low/60 border border-surface-dim rounded-lg p-4 transition-all hover:border-primary/40">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold {{ $chipCls }} px-2 py-0.5 rounded uppercase">{{ $chip }}</span>
                                                <span class="text-xs font-semibold text-on-surface">
                                                    {{ in_array($event->type, ['submitted', 'resubmitted', 'delivery_chosen']) ? ($event->actor?->name ?? __('rescue_certificate.timeline_actor_you')) : ($event->actor?->name ?? __('rescue_certificate.timeline_actor_admin')) }}
                                                </span>
                                            </div>
                                            <time class="text-xs font-mono font-medium text-secondary" dir="ltr">{{ $event->created_at->format('d/m/Y H:i') }}</time>
                                        </div>
                                        <h5 class="text-xs font-bold text-on-surface mb-1">
                                            @php $tlKey = 'rescue_certificate.tl_' . $event->type; @endphp
                                            {{ __($tlKey) === $tlKey ? ucfirst(str_replace('_', ' ', $event->type)) : __($tlKey) }}
                                        </h5>
                                        {{-- Certificate number chip ── --}}
                                        @if (in_array($event->type, ['certificate_issued', 'certificate_collected', 'certificate_delivered', 'certificate_post_delivered']) && is_array($event->data) && ! empty($event->data['certificate_number']))
                                            <p class="text-xs inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-0.5 font-mono font-bold text-amber-900 mb-1.5" dir="ltr">
                                                <span class="material-symbols-outlined text-[13px]" aria-hidden="true">workspace_premium</span>
                                                {{ $event->data['certificate_number'] }}
                                            </p>
                                        @endif
                                        {{-- Tracking chip ── --}}
                                        @if ($event->type === 'certificate_post_sent' && is_array($event->data) && ! empty($event->data['tracking']))
                                            <p class="text-xs inline-flex items-center gap-1 rounded bg-blue-100 px-2 py-0.5 font-mono font-bold text-blue-900 mb-1.5" dir="ltr">
                                                <span class="material-symbols-outlined text-[13px]" aria-hidden="true">qr_code</span>
                                                {{ $event->data['tracking'] }}
                                            </p>
                                        @endif
                                        {{-- Note ── --}}
                                        @if ($event->note)
                                            <p class="text-body-sm font-body-sm text-secondary">
                                                <span class="font-bold text-on-surface">{{ __('rescue_certificate.timeline_note_label') }}</span>
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

            </div>{{-- /colonne gauche --}}

            {{-- ══════ COLONNE DROITE (4 colonnes) ══════ --}}
            <div class="lg:col-span-4 space-y-6">

                {{-- DOCUMENTS ── --}}
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden"
                     x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center gap-2 p-5 hover:bg-surface-container transition-colors text-start border-b border-surface-dim">
                        <span class="w-8 h-8 rounded bg-primary-fixed/50 text-primary flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">attach_file</span>
                        </span>
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex-1">{{ __('rescue_certificate.documents_title') }}</h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded whitespace-nowrap {{ $isDocComplete ? 'text-amber-900 bg-amber-100 dark:bg-amber-900/40 dark:text-amber-300' : 'text-secondary bg-surface-dim' }}" dir="ltr">{{ $uploadedTypes }}/{{ $documentTypes->count() }}</span>
                        <svg class="w-4 h-4 text-secondary transition-transform shrink-0 ms-1" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak class="p-5">
                        {{-- Progress bar ── --}}
                        <div class="h-2 bg-surface-dim/50 rounded-full overflow-hidden mb-1" role="progressbar" aria-valuenow="{{ $docPct }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full transition-all duration-500 {{ $isDocComplete ? 'bg-amber-500' : 'bg-primary' }}" style="width: {{ $docPct }}%"></div>
                        </div>
                        <p class="text-[10px] font-semibold mb-4 {{ $isDocComplete ? 'text-amber-800 dark:text-amber-400' : 'text-secondary' }}">
                            {{ $isDocComplete ? __('rescue_certificate.documents_title') : $uploadedTypes . ' / ' . $documentTypes->count() }}
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
                                        <a href="{{ route('admin.rescue-certificates.documents.download', $doc) }}"
                                           class="w-7 h-7 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-600 hover:text-white flex items-center justify-center shrink-0 transition-colors"
                                           title="{{ __('rescue_certificate.download_label') }}">
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
                                                <h4 class="text-xs font-bold {{ $docType->is_conditional ? 'text-secondary' : 'text-on-surface' }}">{{ $docType->name }}</h4>
                                                <div class="flex items-center gap-2 text-[11px] text-secondary mt-0.5 flex-wrap">
                                                    @if ($docType->is_conditional)
                                                        <span class="font-semibold text-amber-900 bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40 px-1.5 rounded">{{ __('rescue_certificate.document_conditional') }}</span>
                                                    @endif
                                                    <span class="{{ $docType->is_conditional ? '' : 'text-red-600 dark:text-red-400' }}">{{ __('rescue_certificate.documents_not_uploaded') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <div class="p-4 text-center">
                                    <span class="material-symbols-outlined text-2xl text-outline" aria-hidden="true">folder_off</span>
                                    <p class="text-xs text-secondary mt-1">{{ __('rescue_certificate.admin_no_doctypes') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- QUICK INFO ── --}}
                <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm px-5 py-4">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">info</span>
                        {{ __('rescue_certificate.admin_quick_info') }}
                    </h3>
                    <dl class="space-y-2 text-xs">
                        <div class="flex justify-between gap-2">
                            <dt class="text-secondary shrink-0">{{ __('rescue_certificate.print_ref_label') }}</dt>
                            <dd class="font-mono font-semibold text-on-surface" dir="ltr">{{ $viewRef }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-secondary shrink-0">{{ __('rescue_certificate.admin_step_reached') }}</dt>
                            <dd class="font-semibold text-on-surface" dir="ltr">{{ $phaseNum ?: '—' }}/4</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-secondary shrink-0">{{ __('rescue_certificate.admin_docs_uploaded') }}</dt>
                            <dd class="font-semibold text-on-surface" dir="ltr">{{ $uploadedTypes }} / {{ $documentTypes->count() }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-secondary shrink-0">{{ __('rescue_certificate.deposited_on', ['date' => '']) }}</dt>
                            <dd class="text-on-surface">{{ $certificate->created_at->diffForHumans() }}</dd>
                        </div>
                        @if ($certificate->hasCertificate())
                        <div class="flex justify-between gap-2">
                            <dt class="text-secondary shrink-0">{{ __('rescue_certificate.cert_issued_on') }}</dt>
                            <dd class="font-semibold text-on-surface" dir="ltr">{{ $certificate->issued_at?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                        @endif
                        @if ($certificate->cert_status)
                        <div class="flex justify-between gap-2">
                            <dt class="text-secondary shrink-0">{{ __('rescue_certificate.admin_cert_title') }}</dt>
                            <dd><span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $certStatusCfg[1] }}">{{ $certStatusCfg[0] }}</span></dd>
                        </div>
                        @endif
                    </dl>
                </div>

            </div>{{-- /colonne droite --}}
        </div>{{-- /grille 8/4 --}}

        {{-- ══════════════════════════════════════════════════════════
             ZONE DANGEREUSE — annulation (bas de page)
        ══════════════════════════════════════════════════════════════ --}}
        @can('manage rescue-certificates')
        @if ($certificate->isActionable())
        <section class="mt-6 border border-red-300 dark:border-red-800/50 rounded-lg bg-red-50/40 dark:bg-red-900/5 overflow-hidden" x-data="{ cancelOpen: false }">
            <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0 dark:bg-red-900/30 dark:text-red-300">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">warning</span>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-extrabold text-red-700 dark:text-red-300 uppercase tracking-wide">{{ __('rescue_certificate.admin_cancel_btn') }}</h2>
                        <p class="text-xs text-secondary mt-0.5">{{ __('rescue_certificate.admin_cancel_reason') }}</p>
                    </div>
                </div>
                <button type="button" @click="cancelOpen = !cancelOpen"
                    class="inline-flex items-center justify-center gap-2 border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold py-2.5 px-5 rounded transition-colors dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 shrink-0">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">cancel</span>
                    <span x-text="cancelOpen ? '{{ __('rescue_certificate.admin_hide_note') }}' : '{{ __('rescue_certificate.admin_cancel_btn') }}'">{{ __('rescue_certificate.admin_cancel_btn') }}</span>
                </button>
            </div>
            <div x-show="cancelOpen" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="border-t border-red-200 dark:border-red-800/40 p-5">
                <form method="POST" action="{{ route('admin.rescue-certificates.cancel', $certificate) }}"
                      data-confirm data-confirm-style="danger"
                      data-confirm-title="{{ __('rescue_certificate.admin_cancel_confirm') }}"
                      data-confirm-focus-cancel="false">
                    @csrf
                    <label class="block text-xs font-semibold text-red-800 dark:text-red-200 mb-1.5">{{ __('rescue_certificate.admin_cancel_reason') }} <span class="text-red-500">*</span></label>
                    <x-admin.bilingual-note name="admin_note" :rows="3" required
                        placeholder-key="rescue_certificate.note_cancel_placeholder" />
                    <button type="submit" class="mt-3 w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">cancel</span>
                        {{ __('rescue_certificate.admin_confirm_cancel') }}
                    </button>
                </form>
            </div>
        </section>
        @endif
        @endcan

    </div>{{-- /dossier-screen --}}

    {{-- ══════════════ FICHE IMPRIMABLE (A4) ══════════════ --}}
    <div class="print-sheet" aria-hidden="true">
        <div class="fiche">
            <div class="fiche-band">
                <div style="display:flex;align-items:center;gap:14px;min-width:0;">
                    <img src="/images/logo.png" alt="" class="fiche-band-logo">
                    <div style="min-width:0;">
                        <div class="fiche-band-org">{{ setting('app_name', 'ONPC — Office National de la Protection Civile') }}</div>
                        <div class="fiche-band-sub">{{ __('rescue_certificate.print_header_service', ['place' => $certificate->governorate?->name ?? '—']) }}</div>
                    </div>
                </div>
                <div class="fiche-band-ref">
                    <div class="fiche-band-ref-label">{{ __('rescue_certificate.print_ref_label') }}</div>
                    <div class="fiche-band-ref-value" dir="ltr">{{ $viewRef }}</div>
                </div>
            </div>
            <div class="fiche-title-row">
                <div>
                    <div class="fiche-title">{{ __('rescue_certificate.print_title') }}</div>
                    <div class="fiche-title-ar" style="font-size:13px;font-weight:700;color:#4b5563;" dir="rtl">{{ __('rescue_certificate.print_title_ar') }}</div>
                </div>
                <div class="fiche-printed" dir="ltr">{{ __('rescue_certificate.print_edited_on') }} {{ $printDate }}</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <section class="fiche-sec">
                    <h2 class="fiche-h2">{{ __('rescue_certificate.print_applicant') }}</h2>
                    <table class="fiche-table fiche-kv">
                        <tr><th>{{ __('rescue_certificate.nom_prenom') }}</th><td style="font-weight:700;">{{ $certificate->nom_prenom }}</td></tr>
                        <tr><th>{{ __('rescue_certificate.cin') }}</th><td class="fiche-mono" dir="ltr">{{ $certificate->cin }}</td></tr>
                        <tr><th>{{ __('rescue_certificate.phone') }}</th><td class="fiche-mono" dir="ltr">{{ $certificate->phone ?? '—' }}</td></tr>
                        <tr><th>{{ __('rescue_certificate.admin_account_email') }}</th><td dir="ltr">{{ $certificate->user?->email ?? '—' }}</td></tr>
                    </table>
                </section>
                <section class="fiche-sec">
                    <h2 class="fiche-h2">{{ __('rescue_certificate.incident_section') }}</h2>
                    <table class="fiche-table fiche-kv">
                        <tr><th>{{ __('rescue_certificate.incident_type') }}</th><td>{{ $certificate->incidentTypeLabel() }}</td></tr>
                        <tr><th>{{ __('rescue_certificate.incident_date') }}</th><td class="fiche-mono" dir="ltr">{{ $certificate->incident_date?->format('d/m/Y') ?? '—' }}{{ $certificate->incident_time ? ' ' . $certificate->incident_time->format('H:i') : '' }}</td></tr>
                        <tr><th>{{ __('rescue_certificate.incident_address') }}</th><td>{{ $certificate->incident_address ?? '—' }}</td></tr>
                        <tr><th>{{ __('rescue_certificate.code_structure') }}</th><td>{{ $certificate->governorate?->name ?? '—' }} / {{ $certificate->delegation?->name ?? '—' }}</td></tr>
                    </table>
                </section>
            </div>

            <section class="fiche-sec">
                <h2 class="fiche-h2">{{ __('rescue_certificate.incident_description') }}</h2>
                <p style="font-size:11px;line-height:1.5;">{{ $certificate->incident_description ?? '—' }}</p>
            </section>

            @if ($certificate->hasCertificate())
            <div class="fiche-cert">
                <div class="fiche-cert-title">{{ __('rescue_certificate.admin_cert_title') }}</div>
                <table class="fiche-table fiche-kv">
                    <tr><th>{{ __('rescue_certificate.certificate_number') }}</th><td class="fiche-mono" dir="ltr">{{ $certificate->certificate_number ?? '—' }}</td></tr>
                    <tr><th>{{ __('rescue_certificate.cert_issued_on') }}</th><td dir="ltr">{{ $certificate->issued_at?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><th>{{ __('rescue_certificate.admin_cert_delivery_method') }}</th><td>{{ $certificate->isPickup() ? __('rescue_certificate.delivery_pickup_title') : __('rescue_certificate.delivery_post_title') }}</td></tr>
                    <tr><th>{{ __('rescue_certificate.print_stamp') }}</th><td>—</td></tr>
                </table>
            </div>
            @endif

            <div class="fiche-foot">
                <span>{{ setting('app_name', 'ONPC') }} — {{ setting('app_url', url('/')) }}</span>
                <span>{{ __('rescue_certificate.print_edited_on') }} {{ $printDate }}</span>
                <span>{{ __('rescue_certificate.print_ref_label') }}: {{ $viewRef }}</span>
            </div>
        </div>
    </div>

</div>
@endsection
