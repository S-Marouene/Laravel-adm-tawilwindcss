@extends('admin.layouts.admin')

@section('title', 'Dashboard — Direction de la Prévention')

@php
    $ar = app()->getLocale() === 'ar';

    /**
     * Cool institutional status palette — indigo / violet / cyan / blue /
     * amber / slate. Deliberately no green + red duotone (boring) : the
     * brand red stays only on ≠0 CTAs and the primary accents.
     */
    $statusCfg = [
        'completed'          => [
            'label' => $ar ? 'بانتظار الدراسة' : 'À examiner',
            'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            'dot'   => 'bg-blue-500',
            'bar'   => 'bg-blue-500',
            'icon'  => 'inbox',
        ],
        'under_consultation' => [
            'label' => $ar ? 'قيد التدارس' : 'En consultation',
            'badge' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
            'dot'   => 'bg-violet-500',
            'bar'   => 'bg-violet-500',
            'icon'  => 'content_paste_search',
        ],
        'approved'           => [
            'label' => $ar ? 'موافقة عليا' : 'Avis favorable',
            'badge' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
            'dot'   => 'bg-cyan-500',
            'bar'   => 'bg-cyan-500',
            'icon'  => 'task_alt',
        ],
        'waiting_delivery'   => [
            'label' => $ar ? 'شهادة في انتظار التسليم' : 'Certificat à livrer',
            'badge' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
            'dot'   => 'bg-indigo-500',
            'bar'   => 'bg-indigo-500',
            'icon'  => 'workspace_premium',
        ],
        'needs_correction'   => [
            'label' => $ar ? 'تصحيح مطلوب' : 'Correction demandée',
            'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            'dot'   => 'bg-amber-500',
            'bar'   => 'bg-amber-500',
            'icon'  => 'assignment_late',
        ],
        'cancelled'          => [
            'label' => $ar ? 'ملغى' : 'Annulé',
            'badge' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
            'dot'   => 'bg-slate-400',
            'bar'   => 'bg-slate-400',
            'icon'  => 'cancel',
        ],
    ];

    $pct = fn ($n) => $totalDossiers > 0 ? (int) round(100 * $n / $totalDossiers) : 0;

    // Event feed iconography
    $eventStyle = function (?string $type) {
        return match ($type) {
            'submitted', 'resubmitted'   => ['badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', 'icon' => 'post_add',                 'track' => 'bg-blue-500'],
            'advance_to_consultation'    => ['badge' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300', 'icon' => 'sync_alt',        'track' => 'bg-violet-500'],
            'approved', 'validated'      => ['badge' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300', 'icon' => 'task_alt',               'track' => 'bg-cyan-500'],
            'correction_requested'       => ['badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'icon' => 'assignment_late',      'track' => 'bg-amber-500'],
            'appointment_scheduled'      => ['badge' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300', 'icon' => 'event_available',  'track' => 'bg-indigo-500'],
            'appointment_passed'         => ['badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', 'icon' => 'order_approve',            'track' => 'bg-blue-500'],
            'appointment_failed'         => ['badge' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300', 'icon' => 'report',                   'track' => 'bg-rose-500'],
            'certificate_issued', 'certificate_collected', 'certificate_post_sent', 'certificate_post_delivered' => ['badge' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300', 'icon' => 'workspace_premium', 'track' => 'bg-indigo-500'],
            'cancelled'                  => ['badge' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300', 'icon' => 'cancel', 'track' => 'bg-slate-400'],
            default                      => ['badge' => 'bg-surface-container text-secondary dark:bg-slate-800 dark:text-slate-300', 'icon' => 'history', 'track' => 'bg-secondary'],
        };
    };

    // Human-readable event labels (FR / AR, concise)
    $eventLabel = function (?string $type) use ($ar) {
        return match ($type) {
            'submitted'                  => $ar ? 'تقديم الملف' : 'Dépôt en ligne',
            'resubmitted'                => $ar ? 'إعادة تقديم بعد التصحيح' : 'Résubmit après correction',
            'advance_to_consultation'    => $ar ? 'الانتقال إلى التدارس' : 'Passage en consultation',
            'approved'                   => $ar ? 'رأي إيجابي' : 'Avis favorable émis',
            'correction_requested'       => $ar ? 'طلب تصحيح' : 'Correction demandée',
            'appointment_scheduled'      => $ar ? 'تحديد موعد زيارة' : 'Visite programmée',
            'appointment_passed'         => $ar ? 'الزيارة : مطابقة' : 'Visite : conforme',
            'appointment_failed'         => $ar ? 'الزيارة : غير مطابقة' : 'Visite : non conforme',
            'certificate_issued'         => $ar ? 'إصدار الشهادة' : 'Certificat émis',
            'certificate_collected'      => $ar ? 'تسليم بالكسان' : 'Certificat retiré au bureau',
            'certificate_post_sent'      => $ar ? 'إرسال بالبريد (B3)' : 'Certificat expédié (B3)',
            'certificate_post_delivered' => $ar ? 'تم التسليم بالبريد' : 'Livré par poste',
            'cancelled'                  => $ar ? 'إلغاء الملف' : 'Dossier annulé',
            default                      => ucfirst((string) $type),
        };
    };

    $certificateCfg = [
        'ready'          => [
            'label' => $ar ? 'بانتظار التسليم / التحضير' : 'Prêtes (au bureau / préparation)',
            'bar'   => 'bg-indigo-500',
            'color' => 'text-indigo-500',
            'chip'  => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
            'icon'  => 'schedule_send',
        ],
        'post_sent'      => [
            'label' => $ar ? 'في الطريق (بريد)' : 'En transit (Poste B3)',
            'bar'   => 'bg-blue-500',
            'color' => 'text-blue-500',
            'chip'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            'icon'  => 'local_post_office',
        ],
        'post_delivered' => [
            'label' => $ar ? 'تم التسليم بالبريد' : 'Livré par poste',
            'bar'   => 'bg-cyan-500',
            'color' => 'text-cyan-500',
            'chip'  => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
            'icon'  => 'mark_email_read',
        ],
        'collected'      => [
            'label' => $ar ? 'تم التسليم بالمكتب' : 'Retirés au bureau',
            'bar'   => 'bg-violet-500',
            'color' => 'text-violet-500',
            'chip'  => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
            'icon'  => 'check_circle',
        ],
    ];

    $wkMax   = max($weekly->max('count'), 1);
    $bkdMax  = max($breakdown->max('count'), 1);
    $certMax = max(max($certificates), 1);
@endphp

@section('content')

{{-- ══════════════════════════════════════════════════════════════════════════
     BANDEAU OPÉRATIONNEL — périmètre actif
═══════════════════════════════════════════════════════════════════════════ --}}
<div class="mb-5 overflow-hidden rounded border border-surface-dim bg-inverse-surface text-inverse-on-surface shadow-xs dark:border-outline-variant">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-2.5">
        <div class="flex min-w-0 flex-wrap items-center gap-2.5 text-xs">
            <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-primary-container animate-pulse" aria-hidden="true"></span>
            <span class="text-inverse-primary font-bold uppercase tracking-wider text-label-caps">
                {{ $ar ? 'قاعة العمليات' : 'Salle des Opérations' }}
            </span>
            <span class="text-surface-dim opacity-50" aria-hidden="true">|</span>
            <span class="truncate text-surface-dim font-medium">
                {{ $ar ? 'مديرية الوقاية المدنية' : 'Direction de la Prévention — ONPC' }}
            </span>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            {{-- Périmètre territorial actif (rôle → gouvernorat → délégation) --}}
            <span class="inline-flex items-center gap-1.5 rounded border border-secondary/40 bg-secondary/20 px-2 py-0.5 font-bold tracking-wide text-surface-bright">
                <span class="material-symbols-outlined text-[14px] text-primary-fixed" aria-hidden="true">@if($scope === 'delegation')location_on@elseif($scope === 'governorate')map@else public @endif</span>
                @if ($scope === 'all')
                    {{ $ar ? 'التغطية الوطنية (24 ولاية)' : 'Périmètre national — 24 gouvernorats' }}
                @elseif ($scope === 'governorate')
                    {{ $ar ? 'ولاية' : 'Gouvernorat' }} : {{ $scopeLabel }}
                @else
                    {{ $ar ? 'المعتمدية' : 'Délégation' }} : {{ $scopeLabel }}
                @endif
            </span>
            <span class="hidden items-center gap-1.5 rounded border border-secondary/40 px-2 py-0.5 font-mono font-bold text-surface-dim sm:inline-flex">
                <span class="material-symbols-outlined text-[14px] text-primary-fixed" aria-hidden="true">schedule</span>
                <span id="dashClock">{{ now()->locale('fr')->translatedFormat('d F Y') }}</span>
            </span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     1. KPI — INDICATEURS CLÉS DE L'INSTRUCTION
═══════════════════════════════════════════════════════════════════════════ --}}
<section class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

    {{-- KPI 1 — Dossiers en cours d'instruction --}}
    <div class="onpc-kpi">
        <div class="flex items-start justify-between gap-2">
            <span class="text-label-caps font-label-caps text-secondary uppercase font-bold tracking-wider">
                {{ $ar ? 'ملفات المعالجة' : "Dossiers en cours d'instruction" }}
            </span>
            <span class="rounded bg-blue-100 p-1.5 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                <span class="material-symbols-outlined text-xl" aria-hidden="true">fact_check</span>
            </span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-headline-lg font-black text-on-surface">{{ number_format($active) }}</span>
            <span class="rounded bg-blue-100 px-1.5 py-0.5 text-[11px] font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                {{ $ar ? "ملف هذا الأسبوع" : "cette semaine" }} +{{ $newThisWeek }}
            </span>
        </div>
        <div class="mt-3 flex items-center justify-between gap-3 border-t border-surface-dim pt-2 text-[11px] text-secondary">
            <span>{{ $ar ? 'إجمالي الملفات' : 'Total dossiers' }} :</span>
            <a href="{{ route('admin.prevention-dossiers.index') }}" class="font-bold text-primary hover:underline">{{ number_format($totalDossiers) }}</a>
        </div>
    </div>

    {{-- KPI 2 — Nouvelles demandes à accueillir --}}
    <div class="onpc-kpi">
        <div class="flex items-start justify-between gap-2">
            <span class="text-label-caps font-label-caps text-secondary uppercase font-bold tracking-wider">
                {{ $ar ? 'بانتظار الاستقبال' : 'Nouvelles demandes' }}
            </span>
            <span class="rounded bg-violet-100 p-1.5 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">
                <span class="material-symbols-outlined text-xl" aria-hidden="true">pending_actions</span>
            </span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-headline-lg font-black text-on-surface">{{ number_format($toReview) }}</span>
            @if ($lateDossiers > 0)
                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[11px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                    {{ $lateDossiers }} {{ $ar ? 'ملف > 7 أيام' : 'file(s) > 7 jours' }}
                </span>
            @endif
        </div>
        <div class="mt-3 flex items-center justify-between border-t border-surface-dim pt-2 text-[11px] text-secondary">
            <span>{{ $ar ? 'يتجاوز ملف ديناميكز المعالجة' : 'Dépassement délai réglementaire' }} :</span>
            <a href="{{ route('admin.prevention-dossiers.index', ['status' => 'completed']) }}"
               class="admin-action-link">{{ $ar ? 'فتح القائمة' : 'Traiter maintenant' }}</a>
        </div>
    </div>

    {{-- KPI 3 — Visites & commissions --}}
    <div class="onpc-kpi">
        <div class="flex items-start justify-between gap-2">
            <span class="text-label-caps font-label-caps text-secondary uppercase font-bold tracking-wider">
                {{ $ar ? 'الزيارات والتقنيات' : 'Visites & commissions' }}
            </span>
            <span class="rounded bg-cyan-100 p-1.5 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300">
                <span class="material-symbols-outlined text-xl" aria-hidden="true">event_available</span>
            </span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-headline-lg font-black text-primary">{{ number_format($visitPipeline) }}</span>
            <span class="text-xs font-medium text-secondary">{{ $ar ? 'ملف في مرحلة الزيارة' : 'dossiers en phase RDV' }}</span>
        </div>
        <div class="mt-3 flex items-center justify-between border-t border-surface-dim pt-2 text-[11px] text-secondary">
            <span>{{ $ar ? 'زيارات شهوتهم' : 'RV programmés' }} :</span>
            <span class="font-bold text-on-surface">{{ $upcomingVisits->count() }}</span>
        </div>
    </div>

    {{-- KPI 4 — Certificats à livrer --}}
    <div class="onpc-kpi">
        <div class="flex items-start justify-between gap-2">
            <span class="text-label-caps font-label-caps text-secondary uppercase font-bold tracking-wider">
                {{ $ar ? 'شهادات للتسليم' : 'Certificats à livrer' }}
            </span>
            <span class="rounded bg-indigo-100 p-1.5 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                <span class="material-symbols-outlined text-xl" aria-hidden="true">workspace_premium</span>
            </span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-headline-lg font-black text-on-surface">{{ number_format($certPendingDelivery) }}</span>
            <span class="text-xs font-medium text-secondary">{{ $certificates['ready'] > 0 ? '• ' . $certificates['ready'] . ' ' . ($ar ? 'جاهزة' : 'prête(s)') : '' }}</span>
        </div>
        <div class="mt-3 flex items-center justify-between border-t border-surface-dim pt-2 text-[11px] text-secondary">
            <span>{{ $ar ? 'شهادة مسلّمة' : 'Certificats délivrés' }} :</span>
            <span class="font-bold text-on-surface">{{ number_format($certificates['post_delivered'] + $certificates['collected']) }}</span>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════════
     2. GRILLE PRINCIPALE 60% / 40%
═══════════════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 gap-6 xl:grid-cols-12">

    {{-- ══════════════════════════════════════════════════════════════════
         COLONNE GAUCHE — distribution, tendance, file d'instruction
    ══════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-6 xl:col-span-7">

        {{-- ── Répartition réglementaire ── --}}
        <div class="ynex-card overflow-hidden">
            <div class="ynex-card-header flex flex-wrap items-start justify-between gap-3 pb-3">
                <div>
                    <h2 class="flex items-center gap-2 text-headline-sm font-headline-sm text-on-surface uppercase tracking-tight">
                        <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">donut_small</span>
                        {{ $ar ? 'التصنيف التنظيمي' : 'Répartition réglementaire des dossiers' }}
                    </h2>
                    <p class="mt-0.5 text-body-sm text-secondary">
                        {{ $ar ? 'حالة كل ملف في سلسلة الإصدار' : 'Position de chaque dossier dans la chaîne de délivrance' }}
                    </p>
                </div>
                <span class="rounded bg-surface-container px-2 py-1 font-mono text-[11px] font-bold text-on-surface shadow-xs dark:bg-slate-800">{{ number_format($totalDossiers) }}</span>
            </div>
            <div class="space-y-3 p-4 pt-3">
                @foreach ($statusCfg as $key => $cfg)
                    @php $n = $counts[$key]; @endphp
                    <div>
                        <div class="mb-1 flex items-center justify-between gap-2 text-xs font-bold">
                            <span class="flex items-center gap-1.5 text-on-surface">
                                <span class="flex h-3 w-3 items-center justify-center rounded {{ $cfg['bar'] }}" aria-hidden="true"><span class="h-1 w-1 rounded-full bg-white/90"></span></span>
                                {{ $cfg['label'] }}
                                <span class="rounded {{ $cfg['badge'] }} px-1.5 py-px text-[10px]">{{ $pct($n) }}%</span>
                            </span>
                            <a href="{{ route('admin.prevention-dossiers.index', ['status' => $key]) }}"
                               class="font-mono text-secondary hover:text-primary hover:underline">{{ number_format($n) }} {{ $ar ? 'ملف' : 'dossiers' }}</a>
                        </div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-surface-container-high dark:bg-slate-700/60">
                            <div class="h-full rounded-full {{ $cfg['bar'] }}" style="width: {{ $n > 0 ? max($pct($n), 4) : 0 }}%;"></div>
                        </div>
                    </div>
                    @endforeach
                    @if ($totalDossiers === 0)
                    <x-onpc.empty-state icon="folder_open" class="mt-3" :title="$ar ? 'لا ملفات بعد' : 'Aucun dossier pour le moment'" />
                @endif
            </div>
        </div>

        {{-- ── Tendance hebdomadaire (8 semaines) ── --}}
        <div class="ynex-card">
            <div class="ynex-card-header flex items-center justify-between pb-3">
                <div>
                    <h3 class="text-title-section font-title-section text-on-surface uppercase tracking-wider">
                        {{ $ar ? 'الترند الأسبوعي للإيداع' : 'Intake hebdomadaire des demandes' }}
                    </h3>
                    <p class="mt-0.5 text-xs text-secondary">
                        {{ $ar ? 'آخر 8 أسابيع' : 'Dernières 8 semaines' }} —
                        {{ $ar ? 'هذا الأسبوع' : 'cette semaine' }} : <strong class="text-on-surface">{{ $newThisWeek }}</strong>
                    </p>
                </div>
                <span class="material-symbols-outlined text-icon-size text-outline dark:text-slate-400" aria-hidden="true">monitoring</span>
            </div>
            <div class="px-4 pb-4">
                @if ($weekly->sum('count') === 0)
                    <x-onpc.empty-state icon="monitoring" :title="$ar ? 'لا مندات أسبوعيا' : 'Aucun dépôt sur la période'" />
                @else
                    <div class="flex h-32 items-end gap-2">
                        @foreach ($weekly as $week)
                            @php
                                $h = (int) round(100 * $week['count'] / $wkMax);
                                $isCurrent = $loop->last;
                            @endphp
                            <div class="group relative flex flex-1 flex-col items-center gap-1.5">
                                <span class="text-[10px] font-bold text-secondary opacity-0 transition-opacity group-hover:opacity-100">{{ $week['count'] }}</span>
                                <div class="flex h-full w-full items-end justify-center">
                                    <div class="w-full max-w-8 rounded-t transition-all duration-300 {{ $isCurrent ? 'bg-primary' : 'bg-blue-400 dark:bg-blue-600' }} group-hover:bg-primary/80"
                                         style="height: {{ max($h, 3) }}%;"></div>
                                </div>
                                <span class="text-[10px] font-mono font-semibold text-secondary {{ $isCurrent ? 'text-primary' : '' }}">{{ $week['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ── File d'instruction — derniers dossiers ── --}}
        <div class="ynex-card overflow-hidden">
            <div class="bg-surface-container-low/40 px-4 py-3.5 dark:bg-slate-800/40">
                <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-headline-sm font-headline-sm text-on-surface uppercase tracking-tight">
                                {{ $ar ? 'ملف المعالجة الأخير' : "File d'instruction — derniers dossiers" }}
                            </h2>
                            <span class="rounded bg-primary px-2 py-0.5 text-[11px] font-bold text-on-primary">
                                {{ $ar ? 'الطابور الحقيقي' : 'File active' }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-body-sm text-secondary">
                            {{ $ar ? 'طلب شهادات الوقاية و المطابقة' : 'Demandes de certificats de prévention — périmètre' }}
                            @if ($scope !== 'all') « {{ $scopeLabel }} » @endif
                        </p>
                    </div>
                    <a href="{{ route('admin.prevention-dossiers.index') }}"
                       class="inline-flex items-center gap-1 self-start rounded border border-surface-dim bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface transition hover:bg-surface-container-high dark:border-outline-variant dark:bg-slate-800 dark:hover:bg-slate-700">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">open_in_full</span>
                        {{ $ar ? 'كامل الملفات' : 'Voir tous les dossiers' }}
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-body-sm">
                    <thead>
                        <tr class="border-b border-surface-dim bg-surface-container-low text-secondary text-[11px] font-label-caps uppercase dark:bg-slate-800/60">
                            <th class="px-4 py-2.5 text-start font-bold">{{ $ar ? 'المرجع' : 'Référence' }}</th>
                            <th class="px-3 py-2.5 text-start font-bold">{{ $ar ? 'المقدم / المؤسسة' : 'Demandeur / Établissement' }}</th>
                            <th class="hidden px-3 py-2.5 text-start font-bold lg:table-cell">{{ $ar ? 'الموقع' : 'Territoire' }}</th>
                            <th class="px-3 py-2.5 text-start font-bold">{{ $ar ? 'الوضع القانوني' : 'Statut' }}</th>
                            <th class="px-4 py-2.5 text-end font-bold">{{ $ar ? 'الإجراء' : 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-dim font-medium dark:divide-slate-700">
                        @forelse ($recentDossiers as $dossier)
                            @php
                                $cfg = $statusCfg[$dossier->status] ?? [
                                    'label' => $dossier->statusLabel(),
                                    'badge' => 'bg-surface-container text-secondary dark:bg-slate-800 dark:text-slate-300',
                                    'dot'   => 'bg-slate-400',
                                    'bar'   => 'bg-slate-400',
                                ];
                            @endphp
                            <tr class="transition-colors hover:bg-surface-container-low/60 dark:hover:bg-slate-800/40">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-primary">
                                    {{ $dossier->reference ?: ('#' . $dossier->id) }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-bold text-on-surface">{{ $dossier->nom_prenom }}</div>
                                    <div class="text-[11px] text-secondary">{{ $dossier->buildingType?->name ?? ($ar ? 'نوع غير محدد' : 'Type non précisé') }}</div>
                                </td>
                                <td class="hidden px-3 py-3 text-xs lg:table-cell">
                                    <div class="font-semibold text-on-surface">{{ $dossier->governorate?->name_fr ?? $dossier->governorate?->name_ar ?? '—' }}</div>
                                    <div class="text-[11px] text-secondary">{{ $dossier->delegation?->name_fr ?? $dossier->delegation?->name_ar ?? '' }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[11px] font-bold {{ $cfg['badge'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $cfg['dot'] }}"></span>
                                        {{ $cfg['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <a href="{{ route('admin.prevention-dossiers.show', $dossier) }}"
                                       class="inline-flex items-center gap-1 rounded border border-surface-dim bg-surface-container px-2.5 py-1 text-xs font-bold text-on-surface transition hover:bg-primary hover:text-on-primary dark:border-outline-variant dark:bg-slate-800">
                                        {{ $ar ? 'فتح' : 'Ouvrir' }}
                                        <span class="material-symbols-outlined text-[13px] rtl:rotate-180" aria-hidden="true">arrow_forward</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-onpc.empty-state icon="folder_open" :title="$ar ? 'لا ملف في النطاق' : 'Aucun dossier dans votre périmètre'" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         COLONNE DROITE — territoires, visites, main courante, certificats
    ══════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-6 xl:col-span-5">

        {{-- ── Répartition territoriale ── --}}
        <div class="ynex-card p-4">
            <div class="flex items-center justify-between border-b border-surface-dim pb-3">
                <h3 class="flex items-center gap-2 text-title-section font-title-section text-on-surface uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">map</span>
                    {{ $scope === 'all' ? ($ar ? 'التوزيع حسب الولايات' : 'Répartition par gouvernorat') : ($ar ? 'التوزيع حسب المعتمديات' : 'Répartition par délégation') }}
                </h3>
                <span class="text-[11px] font-bold text-secondary">{{ $breakdown->count() > 0 ? $breakdown->count() . ($ar ? ' منطقة' : ' territoires') : '' }}</span>
            </div>
            @if ($breakdown->isEmpty())
                <x-onpc.empty-state icon="public_off" :title="$ar ? 'لا بيانات إقليمية' : 'Aucune donnée territoriale'" />
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($breakdown as $b)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs font-bold">
                                <span class="truncate text-on-surface">{{ $b['name'] }}</span>
                                <span class="font-mono text-secondary">{{ number_format($b['count']) }}</span>
                            </div>
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-surface-container-high dark:bg-slate-700/60">
                                @php $wpct = (int) round(100 * $b['count'] / $bkdMax); @endphp
                                <div class="h-full rounded-full {{ $loop->first ? 'bg-primary' : 'bg-blue-500' }}" style="width: {{ max($wpct, 4) }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Commissions & visites à venir ── --}}
        <div class="ynex-card p-4">
            <div class="flex items-center justify-between border-b border-surface-dim pb-3">
                <h3 class="flex items-center gap-2 text-title-section font-title-section text-on-surface uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">calendar_month</span>
                    {{ $ar ? 'الزيارات القادمة' : 'Visites régionales à venir' }}
                </h3>
                <span class="text-[11px] font-bold text-secondary">{{ $ar ? 'القادمة' : ($upcomingVisits->count() === 1 ? 'Prochaine' : $upcomingVisits->count() . ' RDV') }}</span>
            </div>
            @if ($upcomingVisits->isEmpty())
                <x-onpc.empty-state icon="event_available" class="mt-3" :title="$ar ? 'لا زيارات قادمة' : 'Aucune visite programmée'" />
            @else
                <div class="mt-4 space-y-2.5">
                    @foreach ($upcomingVisits as $appt)
                        <div class="flex items-start justify-between gap-3 rounded border-s-4 border-s-primary bg-surface-container-low p-2.5 dark:bg-slate-800/60">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 text-xs font-bold text-on-surface">
                                    <span class="rounded bg-surface-container px-1.5 py-0.5 font-mono text-primary dark:bg-slate-700">{{ substr($appt->visit_time, 0, 5) }}</span>
                                    <span class="truncate">{{ $appt->dossier->nom_prenom }}</span>
                                </div>
                                <div class="mt-0.5 text-[11px] text-secondary truncate">
                                    {{ $appt->dossier->buildingType?->name }} • {{ $appt->dossier->governorate?->name_fr ?? $appt->dossier->governorate?->name_ar }}
                                    • {{ $appt->dossier->reference ?: ('#' . $appt->dossier->id) }}
                                </div>
                            </div>
                            <a href="{{ route('admin.prevention-dossiers.show', $appt->dossier) }}"
                               class="shrink-0 rounded bg-surface-container px-2 py-1 text-xs font-bold text-on-surface transition hover:bg-primary hover:text-on-primary dark:bg-slate-700">
                                {{ $ar ? 'الملف' : 'Dossier' }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Main courante — derniers mouvements ── --}}
        <div class="ynex-card overflow-hidden">
            <div class="flex items-center justify-between bg-inverse-surface p-3.5 text-inverse-on-surface dark:bg-slate-800">
                <div class="flex items-center gap-2">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-primary-container animate-pulse" aria-hidden="true"></span>
                    <h3 class="text-title-section font-title-section text-surface-bright uppercase tracking-wider">
                        {{ $ar ? 'تيار العمليات' : 'Main courante opérationnelle' }}
                    </h3>
                </div>
                <span class="rounded bg-secondary/30 px-2 py-0.5 text-[11px] font-mono font-bold text-surface-dim">
                    {{ $ar ? 'آخر الأحداث' : 'FLUX ADMIN' }}
                </span>
            </div>
            @if ($recentEvents->isEmpty())
                <x-onpc.empty-state icon="history" :title="$ar ? 'لا أحداث بعد' : 'Aucune activité sur vos dossiers'" />
            @else
                <div class="divide-y divide-surface-dim p-4 dark:divide-slate-700">
                    @foreach ($recentEvents as $event)
                        @php
                            $styling = $eventStyle($event->type);
                            $dossier = $event->dossier;
                        @endphp
                        <div class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $styling['badge'] }}" aria-hidden="true">
                                <span class="material-symbols-outlined text-[15px]">{{ $styling['icon'] }}</span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wide {{ $styling['badge'] }}">
                                        {{ $eventLabel($event->type) }}
                                    </span>
                                    <time class="shrink-0 font-mono text-[10px] font-medium text-secondary dark:text-slate-400">
                                        {{ $event->created_at->format('d/m H:i') }}
                                    </time>
                                </div>
                                <a href="{{ $dossier ? route('admin.prevention-dossiers.show', $dossier) : '#' }}"
                                   class="mt-0.5 block truncate text-body-sm font-bold text-on-surface hover:text-primary dark:text-slate-100 dark:hover:text-gov-400">
                                    {{ $dossier ? ($dossier->reference ?: ('#' . $dossier->id)) . ' — ' . $dossier->nom_prenom : '—' }}
                                </a>
                                <p class="mt-0.5 truncate text-[11px] text-secondary dark:text-slate-400">
                                    {{ $event->actor?->name ?? ($ar ? 'النظام' : 'Système') }}
                                    • {{ $dossier?->governorate?->name_fr ?? $dossier?->governorate?->name_ar ?? '—' }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Certificats de prévention — état de livraison ── --}}
        <div class="ynex-card p-4">
            <div class="flex items-center justify-between border-b border-surface-dim pb-3">
                <h3 class="flex items-center gap-2 text-title-section font-title-section text-on-surface uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">verified</span>
                    {{ $ar ? 'شهادات الوقاية' : 'Certificats de prévention' }}
                </h3>
                <span class="rounded bg-surface-container px-2 py-1 font-mono text-[11px] font-bold text-on-surface dark:bg-slate-800">{{ number_format($certificatesTotal) }} {{ $ar ? 'إصدار' : 'émis' }}</span>
            </div>
            <div class="mt-4 space-y-3">
                @foreach ($certificateCfg as $key => $cfg)
                    @php $n = $certificates[$key]; @endphp
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs font-bold">
                            <span class="flex items-center gap-1.5 text-on-surface">
                                <span class="material-symbols-outlined text-[15px] {{ $cfg['color'] }}" aria-hidden="true">{{ $cfg['icon'] }}</span>
                                {{ $cfg['label'] }}
                            </span>
                            <span class="font-mono text-secondary">{{ number_format($n) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-surface-container-high dark:bg-slate-700/60">
                            <div class="h-full rounded-full {{ $cfg['bar'] }}" style="width: {{ $n > 0 ? max((int) round(100 * $n / $certMax), 4) : 0 }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 rounded border border-surface-dim bg-surface-container-low p-3 text-[11px] leading-relaxed text-secondary dark:border-outline-variant dark:bg-slate-800/60">
                <span class="font-bold text-on-surface">{{ $ar ? 'مود: معالجة الشهادة نعمل جاهزة' : 'Cycle de délivrance' }} :</span>
                @if ($ar)
                    جاهزة → تُسلَّم بالمكتب / في طريقها (B3) → مستلمة
                @else
                    prête → collectée au bureau / expédiée (B3) → livrée
                @endif
            </div>
        </div>

        {{-- ── Système (rôles, utilisateurs, permissions) — full access only ── --}}
        @if ($system)
            <div class="ynex-card p-4">
                <div class="flex items-center justify-between border-b border-surface-dim pb-3">
                    <h3 class="flex items-center gap-2 text-title-section font-title-section text-on-surface uppercase tracking-wider">
                        <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">admin_panel_settings</span>
                        {{ $ar ? 'النظام' : 'Plateforme & Système' }}
                    </h3>
                    <a href="{{ route('admin.users.index') }}" class="admin-action-link text-xs">{{ $ar ? 'الكل' : 'Tout voir' }}</a>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                    <a href="{{ route('admin.users.index') }}" class="rounded border border-surface-dim bg-surface-container-low p-2.5 transition hover:border-primary/40 dark:border-outline-variant dark:bg-slate-800/60">
                        <span class="material-symbols-outlined text-base text-secondary" aria-hidden="true">group</span>
                        <span class="font-black text-on-surface block">{{ number_format($system['total_users']) }}</span>
                        <span class="text-secondary">{{ $ar ? 'مستخدمون' : 'Utilisateurs' }}</span>
                    </a>
                    <a href="{{ route('admin.roles.index') }}" class="rounded border border-surface-dim bg-surface-container-low p-2.5 transition hover:border-primary/40 dark:border-outline-variant dark:bg-slate-800/60">
                        <span class="material-symbols-outlined text-base text-secondary" aria-hidden="true">manage_accounts</span>
                        <span class="font-black text-on-surface block">{{ number_format($system['total_roles']) }}</span>
                        <span class="text-secondary">{{ $ar ? 'أدوار' : 'Rôles' }}</span>
                    </a>
                    <a href="{{ route('admin.permissions.index') }}" class="rounded border border-surface-dim bg-surface-container-low p-2.5 transition hover:border-primary/40 dark:border-outline-variant dark:bg-slate-800/60">
                        <span class="material-symbols-outlined text-base text-secondary" aria-hidden="true">key</span>
                        <span class="font-black text-on-surface block">{{ number_format($system['total_permissions']) }}</span>
                        <span class="text-secondary">{{ $ar ? 'صلاحيات' : 'Permissions' }}</span>
                    </a>
                </div>
                <div class="mt-3 flex items-center justify-between rounded border border-surface-dim bg-surface-container-low py-2 px-3 text-xs dark:border-outline-variant dark:bg-slate-800/60">
                    <span class="font-bold text-secondary">{{ $ar ? 'النشاط اليومي' : 'Journal d\'activité' }}</span>
                    <span class="flex items-center gap-3">
                        <span>{{ $ar ? 'اليوم' : 'Jour' }}: <strong class="text-on-surface">{{ $system['activity_counts']['today'] }}</strong></span>
                        <span>{{ $ar ? 'الأسبوع' : 'Sem.' }}: <strong class="text-on-surface">{{ $system['activity_counts']['week'] }}</strong></span>
                        <span>{{ $ar ? 'الشهر' : 'Mois' }}: <strong class="text-on-surface">{{ $system['activity_counts']['month'] }}</strong></span>
                    </span>
                </div>
                <div class="mt-4 space-y-2.5">
                    @foreach ($system['recent_activities'] as $log)
                        <div class="flex items-center justify-between gap-2 border-b border-surface-dim pb-2 text-xs last:border-none dark:border-slate-700">
                            <div class="flex min-w-0 items-center gap-2">
                                <div class="admin-avatar flex h-7 w-7 shrink-0 items-center justify-center rounded">
                                    <span class="text-[10px] font-bold text-white">{{ mb_substr($log->user?->name ?? '—', 0, 1) }}</span>
                                </div>
                                <p class="truncate text-secondary">{{ $log->description }}</p>
                            </div>
                            <time class="shrink-0 font-mono text-[10px] text-secondary dark:text-slate-400">{{ $log->created_at->format('H:i') }}</time>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</div>

<style>
    #dashClock {
        font-variant-numeric: tabular-nums;
    }
</style>
<script>
    // Horloge live — bandeau opérationnel (Africa/Tunis)
    (function () {
        var el = document.getElementById('dashClock');
        if (!el) return;
        var fmt = new Intl.DateTimeFormat('fr-FR', {
            day: '2-digit', month: 'long', year: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
            timeZone: 'Africa/Tunis',
        });
        function tick() { el.textContent = fmt.format(new Date()); }
        tick();
        setInterval(tick, 1000);
    })();
</script>
@endsection
