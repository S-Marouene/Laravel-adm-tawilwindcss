@php
    $certificate->loadMissing('user');
    $status  = $certificate->status;
    // ── Référence affichée : référence stockée > provisoire ──
    $viewRef = $certificate->reference ?: \App\Support\Reference::provisional('rescue');

    /* ── Preuves issues du journal d'audit (dates des étapes) ── */
    $advancedEvent  = $certificate->events->last(fn ($e) => $e->type === 'advanced_to_consultation');
    $approvedEvent  = $certificate->events->last(fn ($e) => $e->type === 'approved');

    /* ── Stepper 4 étapes : dépôt → vérification → certificat approuvé → remise ── */
    $currentStep = match ($status) {
        'draft'              => 1,
        'completed'          => 2,
        'under_consultation' => 2,
        'needs_correction'   => 2,
        'approved'           => 3,
        // Demande servie : les 4 étapes sont terminées (valeur sentinelle 5)
        'served'             => 5,
        // Demande annulée : l'avancement est figé là où il en était — déduit
        // des preuves (remise, approbation, journal) puisque le statut ne
        // progresse plus. L'étape figée portera la marque rouge « annulé ».
        'cancelled'          => match (true) {
            $certificate->isDelivered()                                        => 5,
            $approvedEvent !== null                                            => 3,
            $advancedEvent !== null
                || $certificate->events->contains(fn ($e) => $e->type === 'correction_requested') => 2,
            default                                                            => 2,
        },
        default              => null,
    };
    $isCancelled = $status === 'cancelled';

    $stepDates = [];
    $stepDates[1] = $certificate->created_at;
    $stepDates[2] = $advancedEvent?->created_at;
    $stepDates[3] = $approvedEvent?->created_at;
    $stepDates[4] = $certificate->delivered_at;

    $steps = [];
    // Incident historique : correction demandée puis corrigée
    $hadCorrection = $certificate->events->contains(fn ($e) => $e->type === 'correction_requested');

    // Étape qui porte la marque « annulé » (demande annulée uniquement)
    $cancelledAt = $isCancelled ? min($currentStep, 4) : null;

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

        // Override done state if the step had an incident in history
        if ($state === 'done' && $i === 2 && $hadCorrection) $state = 'warning';

        $sub = match (true) {
            $state === 'cancelled'          => __('rescue_certificate.stepper_cancelled'),
            $state === 'warning' && $i === 2 => __('rescue_certificate.stepper_had_correction'),
            $status === 'needs_correction' && $state === 'error' => __('rescue_certificate.stepper_correction'),
            $state === 'done'               => __('rescue_certificate.stepper_done'),
            $state === 'pending'            => __('rescue_certificate.stepper_pending'),
            $i === 1                        => __('rescue_certificate.draft_notice'),
            $i === 2 && $status === 'completed' => __('rescue_certificate.stepper_verify_pending'),
            $i === 2 && $status === 'under_consultation' => __('rescue_certificate.stepper_verify_waiting'),
            $i === 3                        => __('rescue_certificate.stepper_cert_approved'),
            $i === 4                        => __('rescue_certificate.stepper_delivery_pending'),
            default                         => __('rescue_certificate.stepper_current'),
        };

        // Sous-titre en rouge : correction requise, annulation
        $alert = in_array($state, ['error', 'cancelled']);

        $steps[] = [
            'num' => $i, 'title' => __('rescue_certificate.stepper_' . $i),
            'sub' => $sub, 'state' => $state, 'alert' => $alert,
        ];
    }
    // Progression : les étapes « done » et « warning » (terminées malgré un
    // incident résolu) comptent comme réalisées ; annulé = avancement figé.
    $doneSteps = collect($steps)->whereIn('state', ['done', 'warning'])->count();
    $progress  = (int) round($doneSteps / 4 * 100);

    /* ── Pastille de statut (héros) : couleur + icône ── */
    [$statusPillCls, $statusPillIcon] = match ($status) {
        'draft', 'completed'  => ['bg-surface-container-high text-secondary', 'edit_note'],
        'under_consultation'  => ['bg-blue-100 text-blue-800', 'manage_search'],
        'needs_correction'    => ['bg-red-100 text-red-800', 'error'],
        'approved'            => ['bg-amber-100 text-amber-900', 'task_alt'],
        'served'              => ['bg-teal-100 text-teal-800', 'handshake'],
        'cancelled'           => ['bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300', 'cancel'],
        default               => ['bg-surface-container-high text-secondary', 'info'],
    };

    /* ── Bandeau d'état du héros (contextuel) ── */
    $heroAlert = match (true) {
        $status === 'cancelled'          => 'error',
        $status === 'draft'              => 'draft',
        $status === 'needs_correction'   => 'error',
        $status === 'under_consultation' => 'info',
        $status === 'approved'           => 'info',
        $status === 'served'             => 'neutral',
        default                          => 'neutral', // completed
    };

    /* ── Documents : complétude ── */
    $uploadedDocs  = $certificate->documents->keyBy('document_type_id');
    $uploadedTypes = $uploadedDocs->filter(fn ($doc) => $documentTypes->contains('id', $doc->document_type_id))->count();
    $fmtSize = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1) . ' Mo' : number_format($b / 1024, 0) . ' Ko';
    $isDocComplete = $documentTypes->count() > 0 && $uploadedTypes >= $documentTypes->count();
    $docPct        = $documentTypes->count() > 0 ? (int) round(min($uploadedTypes, $documentTypes->count()) / $documentTypes->count() * 100) : 0;

    /*
     ── Timeline : pastilles + badges « journal d'audit » ──
       ● or/ambre  = validé, officialisé (certificat remis, approuvé)
       ● rouge ONPC = annulation / incident
       ● ardoise    = dépôt, re-soumission
    */
    $eventChip = fn ($type) => match (true) {
        in_array($type, ['submitted', 'resubmitted'])
            => ['bg-secondary-container text-on-secondary-container', 'post_add', __('rescue_certificate.chip_depot'), 'bg-surface-dim text-secondary'],
        $type === 'advanced_to_consultation'
            => ['bg-violet-600 text-white', 'sync_alt', __('rescue_certificate.chip_phase'), 'bg-surface-dim text-secondary'],
        $type === 'correction_requested'
            => ['bg-amber-500 text-white', 'edit_note', __('rescue_certificate.chip_correction'), 'bg-amber-100 text-amber-800'],
        $type === 'approved'
            => ['bg-amber-600 text-white', 'task_alt', __('rescue_certificate.chip_favorable'), 'bg-amber-100 text-amber-800'],
        $type === 'certificate_delivered'
            => ['bg-amber-600 text-white', 'handshake', __('rescue_certificate.chip_done'), 'bg-amber-100 text-amber-900'],
        // default: cancelled
        default
            => ['bg-red-600 text-white', 'error', __('rescue_certificate.chip_alert'), 'bg-red-100 text-red-700'],
    };

    /* ── Fiche impression : édité aujourd'hui ── */
    $printDate = now()->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY, HH:mm');
@endphp

<style>
    /* ═══════════════════════════════════════════════════════════════════
       FICHE DE LA DEMANDE — impression professionnelle (A4)
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
    .fiche-title      { font-size: 15px; font-weight: 800; letter-spacing: .01em; }
    .fiche-title-ar   { font-size: 13px; font-weight: 700; }
    .fiche-date       { font-size: 9.5px; color: #6b7280; text-align: end; line-height: 1.4; }

    .fiche-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .fiche-table th, .fiche-table td {
        border: 1px solid #c7cad1;
        padding: 6px 9px;
        text-align: start;
        vertical-align: top;
        font-size: 11px;
    }
    .fiche-table th  { background: #f4f4f7; font-weight: 700; width: 34%; }
    .fiche-table-dark thead th { background: #1a1d29; color: #fff; }
    .fiche-dim      { color: #6b7280; }
    .fiche-missing  { color: #9ca3af; font-style: italic; }

    .fiche-cert {
        border: 1.5px solid #b45309; border-radius: 3px; padding: 8px 12px;
        background: #fffbeb; margin-bottom: 16px;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .fiche-cert-title { font-weight: 800; font-size: 11px; color: #92400e; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 3px; }

    .fiche-sign-row  { display: flex; gap: 28px; margin: 6px 0 10px; align-items: flex-end; }
    .fiche-sign-col  { flex: 1; text-align: center; }
    .fiche-sign-name { font-size: 10.5px; font-weight: 700; margin-bottom: 46px; text-transform: uppercase; letter-spacing: .04em; }
    .fiche-sign-line { border-top: 1px solid #6b7280; margin: 0 8px; padding-top: 3px; font-size: 9px; color: #6b7280; }

    .fiche-foot {
        margin-top: 10px; padding-top: 7px; border-top: 1px solid #c7cad1;
        display: flex; justify-content: space-between; gap: 12px;
        font-size: 9px; color: #6b7280;
    }
</style>

<x-app-layout>
    <main class="flex-1 w-full mx-auto sm:px-6 lg:px-8 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-0">

            {{-- ══════════════ CONTENU ÉCRAN ══════════════ --}}
            <div class="dossier-screen no-print">

                {{-- ══════════════ 4. ACTUALITÉ DE LA DEMANDE — toujours en tête ══════════════ --}}

                {{-- ── Correction requise ── --}}
                @if ($status === 'needs_correction' && $certificate->admin_note)
                <section class="bg-surface-container-lowest border border-red-200 dark:border-red-800/40 rounded-lg shadow-sm overflow-hidden mb-6 no-print">
                    <div class="border-s-4 border-s-red-600 p-5">
                        <div class="flex items-start gap-3.5 mb-3">
                            <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">assignment_late</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                        {{ __('rescue_certificate.correction_title') }}
                                    </h2>
                                    <span class="text-[10px] bg-red-600 text-white font-bold px-2 py-0.5 rounded">{{ __('rescue_certificate.chip_alert') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="rounded border border-red-200 bg-red-50 dark:bg-red-900/10 px-4 py-3 mb-4">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                                {{ __('rescue_certificate.admin_note_label') }}
                            </p>
                            <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $certificate->admin_note }}</p>
                        </div>
                        <form method="POST" action="{{ route('rescue-certificates.resubmit', $certificate) }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold py-3 px-5 rounded transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">edit_note</span>
                                {{ __('rescue_certificate.fix_and_resubmit') }}
                            </button>
                        </form>
                    </div>
                </section>
                @endif

                {{-- ── Certificat remis / approuvé — panneau de remise ── --}}
                @if (in_array($status, ['approved', 'served', 'under_consultation']))
                @php $hasCert = $certificate->hasCertificate(); @endphp
                <section class="bg-surface-container-lowest border {{ $status === 'served' ? 'border-teal-200 dark:border-teal-800/40' : 'border-amber-200 dark:border-amber-700/40' }} rounded-lg shadow-sm overflow-hidden mb-6 no-print">
                    <div class="border-s-4 border-s-{{ $status === 'served' ? 'teal' : 'amber' }}-500 p-5">
                        <div class="flex items-start gap-3.5 mb-3">
                            <div class="w-10 h-10 rounded {{ $status === 'served' ? 'bg-teal-100 text-teal-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">{{ $status === 'served' ? 'handshake' : 'workspace_premium' }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">
                                        {{ __('rescue_certificate.delivery_title') }}@if ($certificate->certificate_number) — <span class="font-mono" dir="ltr">{{ $certificate->certificate_number }}</span>@endif
                                    </h2>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $status === 'served' ? 'bg-teal-100 text-teal-800' : 'bg-amber-500 text-white' }}">
                                        {{ $status === 'served' ? __('rescue_certificate.delivery_status_done') : __('rescue_certificate.delivery_status_pending') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if ($hasCert)
                            {{-- Certificate is issued — show delivery state --}}
                            @if ($certificate->isPickup() && $certificate->certIsCollected())
                                <div class="flex items-start gap-2 bg-teal-50 border border-teal-200 p-3 rounded text-xs mb-3 dark:bg-teal-900/10 dark:border-teal-800/40">
                                    <span class="material-symbols-outlined text-teal-600 text-base mt-px" aria-hidden="true">check_circle</span>
                                    <div>
                                        <span class="font-bold text-teal-900 dark:text-teal-200 block">{{ __('rescue_certificate.certificate_collected_banner') }}</span>
                                        @if ($certificate->delivered_at)
                                            <span class="block text-teal-700 text-[11px] mt-1">{{ __('rescue_certificate.certificate_collected_on') }}: <strong>{{ $certificate->delivered_at->format('d/m/Y') }}</strong></span>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($certificate->isPickup())
                                <div class="flex items-start gap-2 bg-amber-50 border border-amber-200 p-3 rounded text-xs mb-3">
                                    <span class="material-symbols-outlined text-amber-700 text-base mt-px" aria-hidden="true">storefront</span>
                                    <div>
                                        <span class="font-bold text-amber-900 block">{{ __('rescue_certificate.certificate_pickup_text', ['place' => $certificate->pickupDelegation?->name ?? $certificate->pickupGovernorate?->name ?? '—']) }}</span>
                                        <span class="block text-secondary text-[11px] mt-1 font-semibold">{{ __('rescue_certificate.certificate_pickup_id_note') }}@if ($certificate->issued_at) — {{ __('rescue_certificate.certificate_issued_on') }} {{ $certificate->issued_at->format('d/m/Y') }}@endif</span>
                                    </div>
                                </div>
                            @elseif ($certificate->isPost() && $certificate->certIsPostDelivered())
                                <div class="flex items-start gap-2 bg-teal-50 border border-teal-200 p-3 rounded text-xs mb-3 dark:bg-teal-900/10 dark:border-teal-800/40">
                                    <span class="material-symbols-outlined text-teal-600 text-base mt-px" aria-hidden="true">check_circle</span>
                                    <div>
                                        <span class="font-bold text-teal-900 dark:text-teal-200 block">{{ __('rescue_certificate.certificate_post_delivered_text') }}</span>
                                        @if ($certificate->post_delivered_at)
                                            <span class="block text-teal-700 text-[11px] mt-1">{{ __('rescue_certificate.certificate_delivered_on_date') }}: <strong>{{ $certificate->post_delivered_at->format('d/m/Y') }}</strong></span>
                                        @endif
                                        @if ($certificate->post_tracking)<span class="inline-flex items-center gap-1.5 mt-1.5 rounded bg-teal-100 px-2 py-0.5 font-mono font-bold text-teal-900" dir="ltr"><span class="material-symbols-outlined text-[13px]" aria-hidden="true">qr_code</span>{{ __('rescue_certificate.certificate_post_tracking') }}: {{ $certificate->post_tracking }}</span>@endif
                                    </div>
                                </div>
                            @elseif ($certificate->isPost())
                                <div class="flex items-start gap-2 bg-blue-50 border border-blue-200 p-3 rounded text-xs mb-3">
                                    <span class="material-symbols-outlined text-blue-700 text-base mt-px" aria-hidden="true">local_shipping</span>
                                    <div class="min-w-0">
                                        <span class="font-bold text-blue-900 block">{{ __('rescue_certificate.certificate_post_text') }}</span>
                                        <span class="block text-secondary text-[11px] mt-1">{{ __('rescue_certificate.certificate_post_recipient') }}: <span class="font-semibold text-on-surface">{{ $certificate->post_recipient ?? '—' }}</span>@if ($certificate->post_phone) — <span class="font-mono font-bold text-blue-900" dir="ltr">{{ $certificate->post_phone }}</span>@endif</span>
                                        @if ($certificate->post_address)<span class="block text-secondary text-[11px] mt-0.5">{{ __('rescue_certificate.certificate_post_address_label') }}: <span class="font-medium text-on-surface">{{ $certificate->post_address }}</span></span>@endif
                                        @if ($certificate->post_tracking)<span class="inline-flex items-center gap-1.5 mt-1.5 rounded bg-blue-100 px-2 py-0.5 font-mono font-bold text-blue-900" dir="ltr"><span class="material-symbols-outlined text-[13px]" aria-hidden="true">qr_code</span>{{ __('rescue_certificate.certificate_post_tracking') }}: {{ $certificate->post_tracking }}</span>@endif
                                    </div>
                                </div>
                            @endif

                            {{-- Certificate meta --}}
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-secondary">
                                {{ __('rescue_certificate.certificate_brigade', ['name' => $certificate->pickupGovernorate?->name ?? $certificate->governorate?->name ?? '—']) }}
                                @if ($certificate->valid_until) • {{ __('rescue_certificate.certificate_valid_until') }} {{ $certificate->valid_until->format('d/m/Y') }}@endif
                                @if ($certificate->certIsCollected() && $certificate->delivered_at) • {{ __('rescue_certificate.certificate_collected_on') }} {{ $certificate->delivered_at->format('d/m/Y') }}@endif
                                @if ($certificate->certIsPostSent() && $certificate->post_sent_at) • {{ __('rescue_certificate.certificate_post_sent_on') }} {{ $certificate->post_sent_at->format('d/m/Y') }}@endif
                            </div>

                        @else
                            {{-- No certificate issued yet — show delivery choice or chosen state --}}
                            @if (! $certificate->delivery_method)
                                {{-- Delivery choice form --}}
                                <div class="mb-4 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/60 px-4 py-3">
                                    <span class="material-symbols-outlined text-amber-700 text-lg mt-px shrink-0" aria-hidden="true">info</span>
                                    <p class="text-[11px] text-amber-900 leading-relaxed">{{ __('rescue_certificate.delivery_choice_intro') }}</p>
                                </div>
                                <form method="POST" action="{{ route('rescue-certificates.delivery', $certificate) }}" class="space-y-2">
                                    @csrf
                                    {{-- Pickup (active) --}}
                                    <label class="flex items-start gap-3 bg-surface-container-low border border-surface-dim rounded p-3 cursor-pointer transition hover:border-amber-400/50 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/40">
                                        <input type="radio" name="delivery_method" value="pickup" required class="mt-0.5 rounded-full border-surface-dim text-amber-600 focus:ring-amber-500">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="material-symbols-outlined text-amber-700 text-lg" aria-hidden="true">storefront</span>
                                                <span class="text-xs font-bold text-on-surface">{{ __('rescue_certificate.delivery_pickup_title') }}</span>
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-600 text-white">{{ __('rescue_certificate.delivery_available') }}</span>
                                            </div>
                                            <p class="text-[11px] text-secondary mt-1">{{ __('rescue_certificate.delivery_pickup_desc') }}</p>
                                        </div>
                                    </label>
                                    {{-- Post (disabled) --}}
                                    <div class="flex items-start gap-3 bg-surface-container-low border border-surface-dim rounded p-3 opacity-60 select-none cursor-not-allowed">
                                        <input type="radio" disabled class="mt-0.5 rounded-full text-gray-400" aria-disabled="true">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="material-symbols-outlined text-secondary text-lg" aria-hidden="true">local_shipping</span>
                                                <span class="text-xs font-bold text-outline dark:text-gray-500">{{ __('rescue_certificate.delivery_post_title') }}</span>
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ __('rescue_certificate.delivery_soon') }}</span>
                                            </div>
                                            <p class="text-[11px] text-secondary mt-1">{{ __('rescue_certificate.delivery_post_desc') }}</p>
                                        </div>
                                    </div>
                                    {{-- Digital (disabled) --}}
                                    <div class="flex items-start gap-3 bg-surface-container-low border border-surface-dim rounded p-3 opacity-60 select-none cursor-not-allowed">
                                        <input type="radio" disabled class="mt-0.5 rounded-full text-gray-400" aria-disabled="true">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="material-symbols-outlined text-secondary text-lg" aria-hidden="true">download</span>
                                                <span class="text-xs font-bold text-outline dark:text-gray-500">{{ __('rescue_certificate.delivery_digital_title') }}</span>
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ __('rescue_certificate.delivery_soon') }}</span>
                                            </div>
                                            <p class="text-[11px] text-secondary mt-1">{{ __('rescue_certificate.delivery_digital_desc') }}</p>
                                        </div>
                                    </div>
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold py-2.5 px-5 rounded transition-colors shadow-sm">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">check_circle</span>
                                        {{ __('rescue_certificate.delivery_save_btn') }}
                                    </button>
                                    @error('delivery_method')<p class="text-[11px] font-semibold text-red-600 flex items-center gap-1"><span class="material-symbols-outlined text-sm" aria-hidden="true">error</span>{{ $message }}</p>@enderror
                                </form>
                            @else
                                {{-- Delivery chosen, certificate not yet issued --}}
                                <div class="flex items-center gap-3 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-3 mb-3">
                                    <span class="material-symbols-outlined text-amber-700 text-xl" aria-hidden="true">{{ $certificate->delivery_method === 'post' ? 'local_shipping' : 'storefront' }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-bold text-on-surface">{{ $certificate->delivery_method === 'post' ? __('rescue_certificate.delivery_method_post') : __('rescue_certificate.delivery_method_pickup') }}</span>
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-600 text-white">{{ __('rescue_certificate.delivery_chip_chosen') }}</span>
                                        </div>
                                        @if ($certificate->delivery_choice_at)
                                            <p class="text-[11px] text-secondary mt-0.5">{{ __('rescue_certificate.delivery_chosen_on', ['date' => $certificate->delivery_choice_at->format('d/m/Y à H:i')]) }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/60 px-4 py-3">
                                    <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                                        <span class="material-symbols-outlined text-[15px]" style="font-variation-settings:'FILL' 1" aria-hidden="true">schedule</span>
                                    </div>
                                    <p class="text-[11px] text-amber-900 leading-relaxed">
                                        <span class="block font-bold">{{ __('rescue_certificate.delivery_awaiting_title') }}</span>
                                        {{ __('rescue_certificate.delivery_awaiting_cert') }}
                                    </p>
                                </div>
                            @endif
                        @endif

                        {{-- Free procedure reminder --}}
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-secondary mt-3">
                            {{ __('rescue_certificate.free_info') }}
                        </div>
                    </div>
                </section>
                @endif

                {{-- ── Brouillon ── --}}
                @if ($status === 'draft')
                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden mb-6 no-print">
                    <div class="border-s-4 border-s-red-600 p-5 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded bg-red-100 text-red-700 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-xl" aria-hidden="true">edit_note</span>
                            </div>
                            <div>
                                <h2 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('rescue_certificate.status_draft') }}</h2>
                                <p class="text-body-sm text-secondary mt-0.5">{{ __('rescue_certificate.draft_notice') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('rescue-certificates.step2.show', $certificate) }}"
                            class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-4 rounded transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">play_arrow</span>
                            {{ __('rescue_certificate.continue') }}
                        </a>
                    </div>
                </section>
                @endif

                {{-- ══════════════ 3. Cycle de Vie de la Demande (Stepper + progression) ══════════════ --}}
                <section class="bg-surface-container-lowest border {{ $isCancelled ? 'border-red-200 dark:border-red-800/40' : 'border-surface-dim' }} rounded-lg shadow-sm mb-6 overflow-hidden">
                    @if ($isCancelled)
                    {{-- Demande annulée : bandeau rouge au-dessus du cycle figé --}}
                    <div class="border-b border-red-200 dark:border-red-800/40 bg-red-50 dark:bg-red-900/20 border-s-4 border-s-red-600 p-5 md:px-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">cancel</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('rescue_certificate.cancelled_banner') }}</h3>
                                <p class="text-body-sm text-secondary mt-1">{{ __('rescue_certificate.status_cancelled') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="p-5 md:px-6">
                        {{-- En-tête : titre + phase + barre de progression --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-surface-dim">
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">route</span>
                                {{ __('rescue_certificate.stepper_title') }}
                            </h3>
                            <div class="flex items-center gap-3 flex-1 sm:flex-none sm:min-w-64">
                                @if ($isCancelled)
                                <span class="text-xs font-bold text-white bg-red-600 px-2 py-0.5 rounded whitespace-nowrap">
                                    {{ __('rescue_certificate.stepper_cancelled') }}
                                </span>
                                @else
                                <span class="text-xs font-bold text-primary bg-primary-fixed/40 px-2 py-0.5 rounded whitespace-nowrap">
                                    {{ __('rescue_certificate.phase_instruction', ['step' => $currentStep ? min($currentStep, 4) : 1]) }}
                                </span>
                                @endif
                                <div class="hidden sm:flex-1 h-1.5 bg-surface-dim/50 rounded-full overflow-hidden">
                                    <div class="h-full {{ $isCancelled ? 'bg-red-500' : 'bg-gradient-to-r from-primary-container to-amber-500' }} rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                                </div>
                                <span class="text-[11px] font-mono font-bold text-secondary hidden sm:inline" dir="ltr">{{ $progress }}%</span>
                            </div>
                        </div>

                        {{-- Étapes reliées par une ligne de conduite --}}
                        <div class="relative grid grid-cols-1 md:grid-cols-4 gap-5 md:gap-3">
                            {{-- Ligne de conduite : uniquement ≥ md, symétrique (compatible RTL) --}}
                            <div class="hidden md:block absolute top-5 [inset-inline-start:10%] [inset-inline-end:10%] h-0.5 bg-surface-dim" aria-hidden="true"></div>

                            @foreach ($steps as $step)
                                <div class="relative flex md:flex-col items-start md:items-center gap-3 md:gap-2 md:text-center">
                                    {{-- Pastille circulaire --}}
                                    @if ($step['state'] === 'done')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-sm ring-4 ring-amber-100">
                                            <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">check</span>
                                        </span>
                                    @elseif ($step['state'] === 'warning')
                                        {{-- Terminé malgré un incident résolu (correction) --}}
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-sm ring-4 ring-amber-100">
                                            <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">check</span>
                                        </span>
                                    @elseif ($step['state'] === 'current')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center text-sm font-black shadow-sm ring-4 ring-amber-200 animate-pulse">
                                            {{ $step['num'] }}
                                        </span>
                                    @elseif ($step['state'] === 'error')
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow-sm ring-4 ring-red-100">
                                            <span class="material-symbols-outlined text-lg" aria-hidden="true">edit_note</span>
                                        </span>
                                    @elseif ($step['state'] === 'cancelled')
                                        {{-- Demande annulée à cette étape --}}
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow-sm ring-4 ring-red-100">
                                            <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">cancel</span>
                                        </span>
                                    @else
                                        <span class="relative z-10 w-10 h-10 rounded-full bg-surface-container text-secondary flex items-center justify-center text-sm font-bold border border-surface-dim">
                                            {{ $step['num'] }}
                                        </span>
                                    @endif

                                    {{-- Libellé --}}
                                    <div class="min-w-0 md:px-1">
                                        <div class="flex md:justify-center items-center gap-2">
                                            <h4 class="text-xs font-bold {{ $step['state'] === 'pending' ? 'text-secondary' : 'text-on-surface' }}">
                                                {{ $step['title'] }}
                                            </h4>
                                            @if ($step['state'] === 'current')
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                                            @elseif ($step['state'] === 'error' || $step['state'] === 'cancelled')
                                                <span class="text-[10px] uppercase font-black text-red-800 bg-red-100 px-1.5 py-px rounded">{{ __('rescue_certificate.chip_alert') }}</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] mt-0.5 leading-snug {{ $step['alert'] ? 'text-red-700 font-medium' : ($step['state'] === 'current' ? 'text-amber-700 font-medium' : ($step['state'] === 'done' || $step['state'] === 'warning' ? 'text-amber-700 font-medium' : 'text-secondary')) }}">
                                            {{ $step['sub'] }}
                                        </p>
                                        <p class="text-[10px] mt-1 font-semibold {{ in_array($step['state'], ['done','warning']) ? 'text-amber-700' : ($step['state'] === 'current' ? 'text-amber-600' : 'text-secondary') }}" dir="ltr">
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

                <section class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm mb-6 overflow-hidden">
                    <div class="h-1 bg-gradient-to-r from-primary via-primary-container/70 to-transparent" aria-hidden="true"></div>

                    <div class="p-5 md:p-6">
                        {{-- Ligne haute : titre + actions --}}
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                            <div class="min-w-0">
                                <h1 class="text-headline-md font-headline-md text-on-surface tracking-tight leading-tight" dir="ltr">
                                    {{ __('rescue_certificate.card_title') }} · <span class="font-mono">{{ $viewRef }}</span>
                                </h1>
                                <p class="text-xs text-secondary mt-1 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">calendar_today</span>
                                    {{ __('rescue_certificate.deposited_on', ['date' => $certificate->created_at->isoFormat('D MMMM YYYY')]) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2.5 shrink-0">
                                <span class="inline-flex items-center gap-1.5 text-label-caps font-label-caps px-2.5 py-1 rounded font-bold {{ $statusPillCls }}">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">{{ $statusPillIcon }}</span>
                                    {{ __('rescue_certificate.status_' . $status) }}
                                </span>
                                <a href="{{ route('rescue-certificates.index') }}"
                                    class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-2.5 rounded transition-colors">
                                    <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                                </a>
                                <button type="button" onclick="window.print()"
                                    class="flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2 px-3.5 rounded transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">print</span>
                                </button>
                            </div>
                        </div>

                        {{-- Faits clés — détails complets de la demande --}}
                        <div class="flex items-center gap-2 mb-3">
                            <span class="material-symbols-outlined text-primary text-sm" aria-hidden="true">fact_check</span>
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-secondary">{{ __('rescue_certificate.details_title') }}</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">badge</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('rescue_certificate.nom_prenom') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate" title="{{ $certificate->nom_prenom ?? '' }}">{{ $certificate->nom_prenom ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">fingerprint</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('rescue_certificate.cin') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate font-mono" dir="ltr" title="{{ $certificate->cin ?? '' }}">{{ $certificate->cin ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">call</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('rescue_certificate.phone') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate font-mono num-ltr" dir="ltr" title="{{ $certificate->phone ?? '' }}">{{ $certificate->phone ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">mail</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('rescue_certificate.email') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate" dir="ltr" title="{{ $certificate->user?->email ?? '' }}">{{ $certificate->user?->email ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">account_balance</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('rescue_certificate.code_structure') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate">{{ $certificate->governorate?->name ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">location_city</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('rescue_certificate.delegation') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate">{{ $certificate->delegation?->name ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">emergency</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('rescue_certificate.incident_type') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate">{{ $certificate->incidentTypeLabel() }}</span>
                                </div>
                            </div>
                            <div class="bg-surface-container-low border border-surface-dim rounded p-2.5 flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-primary text-base mt-px shrink-0" aria-hidden="true">place</span>
                                <div class="min-w-0">
                                    <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary truncate">{{ __('rescue_certificate.incident_address') }}</span>
                                    <span class="block text-xs font-bold text-on-surface truncate" title="{{ $certificate->incident_address ?? '' }}">{{ $certificate->incident_address ?? '—' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Note de l'administration (si présente, hors correction) — espace dédié sous les détails --}}
                        @if ($certificate->admin_note && $status !== 'needs_correction')
                        <div class="mt-4 rounded-lg border border-surface-dim bg-surface-container-low px-4 py-3">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-1 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">sticky_note_2</span>
                                {{ __('rescue_certificate.admin_note_label') }}
                            </p>
                            <p class="text-body-sm text-on-surface whitespace-pre-line leading-relaxed">{{ $certificate->admin_note }}</p>
                        </div>
                        @endif

                        {{-- Espacement avant l'alerte d'état --}}
                        <div class="h-2"></div>

                        {{-- Alerte d'état opérationnel — contextuelle --}}
                        @if ($heroAlert === 'info')
                            <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-300 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-amber-600 text-base mt-px" aria-hidden="true">hourglass_bottom</span>
                                <div class="font-bold text-amber-900 leading-relaxed">
                                    {{ $status === 'under_consultation' ? __('rescue_certificate.under_consultation_banner') : __('rescue_certificate.approved_banner') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'error')
                            <div class="flex items-start gap-2.5 bg-red-50 border border-red-300 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">notification_important</span>
                                <div class="font-bold text-red-900 leading-relaxed">
                                    {{ $status === 'needs_correction' ? __('rescue_certificate.needs_correction_banner') : __('rescue_certificate.cancelled_banner') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'draft')
                            <div class="flex items-start gap-2.5 bg-red-50 border border-red-300 rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-red-600 text-base mt-px" aria-hidden="true">edit_note</span>
                                <div class="font-bold text-red-900 leading-relaxed">
                                    {{ __('rescue_certificate.draft_notice') }}
                                </div>
                            </div>
                        @elseif ($heroAlert === 'neutral')
                            <div class="flex items-start gap-2.5 bg-surface-container-low border border-surface-dim rounded px-3.5 py-2.5 text-xs">
                                <span class="material-symbols-outlined text-secondary text-base mt-px" aria-hidden="true">{{ $status === 'served' ? 'handshake' : 'hourglass_top' }}</span>
                                <div class="font-bold text-on-surface leading-relaxed">
                                    {{ __('rescue_certificate.status_' . $status) }}
                                    <span class="font-normal text-secondary"> — {{ $status === 'served' ? __('rescue_certificate.served_banner') : __('rescue_certificate.review_note') }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- ══════════════ 5. Grille asymétrique 8/4 ══════════════ --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">

                    {{-- ══════ COLONNE GAUCHE (8 colonnes) ══════ --}}
                    <div class="lg:col-span-8 space-y-6">

                        {{-- ══════ DÉTAILS DE L'OPÉRATION DE SECOURS ══════ --}}
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden">
                            <div class="flex items-center gap-2.5 p-5 md:px-6 border-b border-surface-dim">
                                <span class="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">emergency</span>
                                </span>
                                <div>
                                    <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ __('rescue_certificate.incident_section') }}</h3>
                                </div>
                            </div>
                            <div class="p-5 md:p-6 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div class="bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                        <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.incident_type') }}</span>
                                        <span class="block text-xs font-bold text-on-surface mt-0.5">{{ $certificate->incidentTypeLabel() }}</span>
                                    </div>
                                    <div class="bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                        <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.incident_date') }}</span>
                                        <span class="block text-xs font-bold text-on-surface mt-0.5" dir="ltr">
                                            {{ $certificate->incident_date?->format('d/m/Y') ?? '—' }}@if ($certificate->incident_time) · {{ $certificate->incident_time->format('H:i') }}@endif
                                        </span>
                                    </div>
                                    <div class="sm:col-span-2 bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                        <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.incident_address') }}</span>
                                        <span class="block text-xs font-bold text-on-surface mt-0.5">{{ $certificate->incident_address ?? '—' }}</span>
                                    </div>
                                    <div class="sm:col-span-2 bg-surface-container-low/60 border border-surface-dim rounded p-3">
                                        <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary">{{ __('rescue_certificate.victim_status') }}</span>
                                        <span class="block text-xs font-bold text-on-surface mt-0.5">
                                            @if ($certificate->victim_is_minor)
                                                {{ __('rescue_certificate.victim_is_minor') }}
                                                — {{ __('rescue_certificate.parent_name') }}: {{ $certificate->parent_name }} ({{ __('rescue_certificate.parent_cin') }}: {{ $certificate->parent_cin }})
                                            @elseif ($certificate->spouse_case)
                                                {{ __('rescue_certificate.spouse_case') }}
                                            @else
                                                {{ __('rescue_certificate.victim_normal') }}
                                            @endif
                                        </span>
                                    </div>
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

                                {{-- Frais : procédure gratuite --}}
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50/60 px-4 py-3 dark:border-emerald-800/40 dark:bg-emerald-900/10">
                                    <div class="flex items-center gap-2.5">
                                        <span class="material-symbols-outlined text-emerald-600 text-lg" aria-hidden="true">payments</span>
                                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">{{ __('rescue_certificate.fees_info') }}</span>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 whitespace-nowrap">{{ __('rescue_certificate.free') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- ══════ TIMELINE — Historique & Journal d'audit ══════ --}}
                        @if ($certificate->events->isNotEmpty())
                            <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print"
                                 x-data="{ open: false }">
                                <button type="button" @click="open = !open"
                                        class="w-full flex flex-wrap items-center justify-between gap-3 p-5 md:px-6 pb-4 border-b border-surface-dim hover:bg-surface-container dark:hover:bg-gray-700/40 transition-colors text-start">
                                    <div>
                                        <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">
                                            <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">history</span>
                                            {{ __('rescue_certificate.timeline_title') }}
                                        </h3>
                                        <p class="text-xs text-secondary mt-0.5">{{ __('rescue_certificate.timeline_subtitle') }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-[11px] text-secondary font-semibold flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">receipt_long</span>
                                            {{ __('rescue_certificate.events_count', ['n' => $certificate->events->count()]) }}
                                        </span>
                                        <svg class="w-4 h-4 text-secondary transition-transform shrink-0" :class="open ? 'rotate-180' : ''"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </button>

                                <div x-show="open" x-cloak class="p-5 md:p-6 pt-5">
                                    {{-- Chronologie linéaire haute densité --}}
                                    <div class="relative ps-6 sm:ps-8 space-y-6 before:absolute before:start-3 sm:before:start-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-dim">
                                        @foreach ($certificate->events->reverse()->values() as $event)
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
                                                            <span class="text-xs font-bold {{ $chipCls }} px-2 py-0.5 rounded uppercase">{{ $chip }}</span>
                                                            <span class="text-xs font-semibold text-on-surface">{{ in_array($event->type, ['submitted', 'resubmitted']) ? __('rescue_certificate.timeline_actor_you') : __('rescue_certificate.timeline_actor_admin') }}</span>
                                                        </div>
                                                        <time class="text-xs font-mono font-medium text-secondary" dir="ltr">{{ $event->created_at->format('d/m/Y H:i') }}</time>
                                                    </div>
                                                    <h5 class="text-xs font-bold text-on-surface mb-1">
                                                        {{ __('rescue_certificate.tl_' . $event->type) }}
                                                    </h5>
                                                    @if ($event->type === 'certificate_delivered' && is_array($event->data) && ! empty($event->data['certificate_number']))
                                                        <p class="text-xs inline-flex items-center gap-1 rounded bg-teal-100 px-2 py-0.5 font-mono font-bold text-teal-900 mb-1.5" dir="ltr">
                                                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">workspace_premium</span>
                                                            {{ $event->data['certificate_number'] }}
                                                        </p>
                                                    @endif
                                                    @if ($event->note)
                                                        <p class="text-body-sm font-body-sm text-secondary">
                                                            <span class="font-bold text-on-surface">{{ __('rescue_certificate.timeline_note_label') }}</span>
                                                            {{ $event->note }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>{{-- /x-show --}}
                            </div>
                        @endif
                    </div>

                    {{-- ══════ COLONNE DROITE (4 colonnes) ══════ --}}
                    <div class="lg:col-span-4 space-y-6">

                        {{-- ══════ DOCUMENTS JOINTS ══════ --}}
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print">
                            <div class="p-5 md:px-6 border-b border-surface-dim">
                                <div class="flex items-center justify-between gap-3 mb-2">
                                    <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">folder_open</span>
                                        {{ __('rescue_certificate.documents_title') }}
                                    </h3>
                                    <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded {{ $isDocComplete ? 'bg-emerald-100 text-emerald-800' : 'bg-surface-dim text-secondary' }}" dir="ltr">{{ $uploadedTypes }}/{{ $documentTypes->count() }}</span>
                                </div>
                                {{-- Progression des pièces --}}
                                <div class="h-1.5 bg-surface-dim/50 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $isDocComplete ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $docPct }}%"></div>
                                </div>
                            </div>
                            <div class="divide-y divide-surface-dim/60">
                                @forelse ($documentTypes as $docType)
                                    @php $doc = $uploadedDocs->get($docType->id); @endphp
                                    <div class="flex items-center justify-between gap-3 px-5 md:px-6 py-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="w-7 h-7 rounded-full flex-none flex items-center justify-center {{ $doc ? 'bg-emerald-100 text-emerald-700' : 'bg-surface-dim text-secondary' }}">
                                                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">{{ $doc ? 'check' : 'close' }}</span>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-on-surface truncate">{{ $docType->name }}</p>
                                                @if ($doc)
                                                    <p class="text-[10px] text-secondary truncate">{{ $doc->original_name }} · {{ $fmtSize($doc->size) }}</p>
                                                @elseif ($docType->is_conditional)
                                                    <p class="text-[10px] text-secondary">{{ __('rescue_certificate.document_conditional') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        @if ($doc)
                                            <a href="{{ route('rescue-certificates.documents.download', $doc) }}"
                                               class="inline-flex items-center gap-1 text-[11px] font-bold text-primary hover:text-primary-container px-2 py-1 rounded hover:bg-primary/5 transition-colors whitespace-nowrap">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                                {{ __('rescue_certificate.download_label') }}
                                            </a>
                                        @endif
                                    </div>
                                @empty
                                    <div class="px-6 py-6 text-center text-xs text-secondary">—</div>
                                @endforelse
                            </div>
                        </div>

                        {{-- ══════ RAPPEL : SUIVI PAR TÉLÉPHONE ══════ --}}
                        <div class="bg-surface-container-lowest border border-surface-dim rounded-lg shadow-sm overflow-hidden no-print">
                            <div class="p-5 md:px-6">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary flex items-center gap-2 mb-3">
                                    <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">phone_in_talk</span>
                                    {{ __('rescue_certificate.phone_notification_hint') }}
                                </h3>
                                <p class="text-xs text-secondary leading-relaxed">{{ __('rescue_certificate.review_note') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>{{-- /dossier-screen --}}

            {{-- ══════════════ FICHE IMPRESSION (A4) ══════════════ --}}
            <div class="print-sheet">
                <div class="fiche">
                    {{-- Bandeau institutionnel --}}
                    <div class="fiche-band">
                        <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                            <img src="/images/logo.png" alt="" class="fiche-band-logo">
                            <div style="min-width:0;">
                                <div class="fiche-band-org">{{ setting('app_name', __('app.name')) }}</div>
                                <div class="fiche-band-sub">{{ __('rescue_certificate.print_header_service', ['place' => $certificate->governorate?->name ?? '—']) }}</div>
                            </div>
                        </div>
                        <div class="fiche-band-ref">
                            <div class="fiche-band-ref-label">{{ __('rescue_certificate.print_ref_label') }}</div>
                            <div class="fiche-band-ref-value" dir="ltr">{{ $viewRef }}</div>
                        </div>
                    </div>

                    {{-- Titre bilingue + date d'édition --}}
                    <div class="fiche-title-row">
                        <div>
                            <div class="fiche-title">{{ __('rescue_certificate.print_title') }}</div>
                            <div class="fiche-title-ar" dir="rtl">{{ __('rescue_certificate.print_title_ar') }}</div>
                        </div>
                        <div class="fiche-date">
                            {{ __('rescue_certificate.print_edited_on') }}<br>{{ $printDate }}
                        </div>
                    </div>

                    {{-- Identité du demandeur --}}
                    <table class="fiche-table fiche-table-dark">
                        <thead><tr><th colspan="2">{{ __('rescue_certificate.print_applicant') }}</th></tr></thead>
                        <tbody>
                            <tr><th>{{ __('rescue_certificate.nom_prenom') }}</th><td>{{ $certificate->nom_prenom ?? __('rescue_certificate.print_missing') }}</td></tr>
                            <tr><th>{{ __('rescue_certificate.cin') }}</th><td dir="ltr">{{ $certificate->cin ?? __('rescue_certificate.print_missing') }}</td></tr>
                            <tr><th>{{ __('rescue_certificate.phone') }}</th><td dir="ltr">{{ $certificate->phone ?? __('rescue_certificate.print_missing') }}</td></tr>
                            <tr><th>{{ __('rescue_certificate.code_structure') }} / {{ __('rescue_certificate.delegation') }}</th>
                                <td>{{ $certificate->governorate?->name ?? '—' }} — {{ $certificate->delegation?->name ?? '—' }}</td></tr>
                        </tbody>
                    </table>

                    {{-- Opération de secours --}}
                    <table class="fiche-table">
                        <thead><tr><th colspan="2">{{ __('rescue_certificate.incident_section') }}</th></tr></thead>
                        <tbody>
                            <tr><th>{{ __('rescue_certificate.incident_type') }}</th><td>{{ $certificate->incidentTypeLabel() }}</td></tr>
                            <tr><th>{{ __('rescue_certificate.incident_date') }}</th>
                                <td dir="ltr">{{ $certificate->incident_date?->format('d/m/Y') ?? '—' }}@if ($certificate->incident_time) {{ $certificate->incident_time->format('H:i') }}@endif</td></tr>
                            <tr><th>{{ __('rescue_certificate.incident_address') }}</th><td>{{ $certificate->incident_address ?? '—' }}</td></tr>
                            <tr><th>{{ __('rescue_certificate.incident_description') }}</th><td class="fiche-dim">{{ \Illuminate\Support\Str::limit((string) $certificate->incident_description, 400, '…') }}</td></tr>
                        </tbody>
                    </table>

                    {{-- Encart certificat (remise) --}}
                    @if ($certificate->certificate_number || $certificate->delivery_date)
                    <div class="fiche-cert">
                        <div class="fiche-cert-title">{{ __('rescue_certificate.delivery_title') }}</div>
                        <strong dir="ltr">{{ $certificate->certificate_number ?? '—' }}</strong>
                        @if ($certificate->delivery_date) — {{ __('rescue_certificate.delivered_on_label', ['date' => $certificate->delivery_date->format('d/m/Y')]) }}@endif
                    </div>
                    @endif

                    {{-- Signatures --}}
                    <div class="fiche-sign-row">
                        <div class="fiche-sign-col">
                            <div class="fiche-sign-name">{{ __('rescue_certificate.print_sign_applicant') }}</div>
                            <div class="fiche-sign-line">{{ __('rescue_certificate.print_read_and_agreed') }}</div>
                        </div>
                        <div class="fiche-sign-col">
                            <div class="fiche-sign-name">{{ __('rescue_certificate.print_sign_office') }}</div>
                            <div class="fiche-sign-line">{{ __('rescue_certificate.print_stamp') }}</div>
                        </div>
                    </div>

                    {{-- Pied de page --}}
                    <div class="fiche-foot">
                        <span>{{ __('rescue_certificate.free_info') }}</span>
                        <span dir="ltr">{{ $viewRef }}</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</x-app-layout>
