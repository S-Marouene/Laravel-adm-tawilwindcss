@php
/**
 * Old-version card — shown during correction mode (step 1 / 2 / 3).
 *
 * Variables:
 *   $correction        – from $dossier->lastCorrectionData() — the CURRENT round
 *   $archivedDocs      – Collection of DossierDocument (status=archived, all rounds)
 *   $allCorrectionRounds – array from $dossier->allCorrectionRounds() (optional)
 *   $documentTypes     – Collection of DocumentType (for labels)
 *
 * Shows the last/current correction round prominently.
 * If there are prior rounds, shows a collapsible "previous corrections" section.
 */

$showFiles  = $archivedDocs !== null && $archivedDocs->isNotEmpty();
$showParams = ! empty($correction['previous']);

if (! $showParams && ! $showFiles) return;

$fmtSize = fn (?int $bytes) => $bytes === null ? '—'
    : ($bytes >= 1048576 ? round($bytes / 1048576, 1) . ' Mo'
    : ($bytes >= 1024    ? round($bytes / 1024, 1)    . ' Ko'
    : $bytes . ' o'));

$previousValues = collect([
    [__('prevention_request.field_name'),         $correction['previous']['nom_prenom']        ?? null],
    [__('prevention_request.field_cin'),          $correction['previous']['cin']               ?? null],
    [__('prevention_request.field_phone'),        $correction['previous']['phone']             ?? null],
    [__('prevention_request.field_governorate'),  $correction['previous']['governorate']       ?? null],
    [__('prevention_request.field_delegation'),   $correction['previous']['delegation']        ?? null],
    [__('prevention_request.field_address'),      $correction['previous']['building_address']  ?? null],
    [__('prevention_request.field_building'),     $correction['previous']['building_type']     ?? null],
    [__('prevention_request.field_activity'),     $correction['previous']['building_activity'] ?? null],
])->filter(fn ($row) => filled($row[1]));

// Prior rounds: all except the current (last) round
$priorRounds = isset($allCorrectionRounds) && count($allCorrectionRounds) > 1
    ? array_slice($allCorrectionRounds, 0, -1)  // all but the last
    : [];
$hasPriorRounds = count($priorRounds) > 0;
@endphp

{{-- ── Current correction round ── --}}
<div class="bg-surface-container-lowest rounded border border-amber-300 shadow-sm dark:border-amber-700 dark:bg-amber-900/10"
     x-data="{ openOld: false }">
    <button type="button" @click="openOld = !openOld"
        class="w-full flex items-start sm:items-center gap-3 px-5 py-4 text-start hover:bg-amber-50/50 dark:hover:bg-amber-900/20 transition-colors">
        <div class="w-9 h-9 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">history</span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                {{ __('prevention_request.old_version_title', ['round' => $correction['round'] ?? 1]) }}
            </p>
            <p class="text-[11px] text-secondary mt-0.5 leading-relaxed">
                {{ __('prevention_request.old_version_desc') }}
            </p>
        </div>
        <svg class="w-4 h-4 text-amber-800 dark:text-amber-400 transition-transform flex-shrink-0 mt-1 sm:mt-0"
             :class="openOld ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="openOld" x-cloak class="px-5 pb-5 space-y-3">

        {{-- Admin correction note --}}
        @if ($correction['note'])
            <div class="rounded border border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20 px-3.5 py-2.5">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300 mb-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                    {{ __('prevention_request.admin_note_label') }}
                </p>
                <p class="text-xs text-on-surface dark:text-gray-200 whitespace-pre-line leading-relaxed">{{ $correction['note'] }}</p>
            </div>
        @endif

        {{-- Old field values --}}
        @if ($showParams && $previousValues->isNotEmpty())
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-secondary dark:text-gray-400 mb-1.5">
                    {{ __('prevention_request.old_params_title') }}
                </p>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-1.5 text-[11px]">
                    @foreach ($previousValues as [$label, $value])
                        <div class="flex justify-between gap-2 bg-surface-container-low dark:bg-gray-700/30 rounded px-2.5 py-1.5">
                            <dt class="text-secondary dark:text-gray-400">{{ $label }}</dt>
                            <dd class="font-semibold text-on-surface dark:text-gray-200 text-end break-words">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

        {{-- Old uploaded files (current round archives) --}}
        @if ($showFiles)
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-secondary dark:text-gray-400 mb-1.5">
                    {{ __('prevention_request.old_files_title') }}
                </p>
                <div class="space-y-1.5">
                    @php $typeNames = $documentTypes ?? collect(); @endphp
                    @foreach ($archivedDocs->filter(fn ($d) => $d->status === 'archived') as $doc)
                        <div class="flex items-start justify-between gap-2.5 bg-surface-container-low dark:bg-gray-700/30 rounded px-2.5 py-2">
                            <div class="flex items-start gap-2 min-w-0">
                                <span class="material-symbols-outlined text-secondary dark:text-gray-400 text-base mt-0.5" aria-hidden="true">description</span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold text-on-surface dark:text-gray-200 truncate">
                                        {{ $typeNames->firstWhere('id', $doc->document_type_id)?->name ?? __('prevention_request.other_files') }}
                                    </p>
                                    <p class="text-[10px] text-secondary dark:text-gray-400" dir="ltr">
                                        <span class="font-mono">{{ $fmtSize($doc->size) }}</span>
                                        • {{ $doc->created_at->format('d/m/Y') }}
                                        @if ($doc->original_name)
                                            — <span class="truncate inline-block max-w-40 align-bottom" title="{{ $doc->original_name }}">{{ $doc->original_name }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('prevention-requests.documents.download', [$doc->dossier, $doc]) }}"
                                class="w-6 h-6 rounded bg-surface-container dark:bg-gray-700 text-secondary hover:bg-primary hover:text-on-primary flex items-center justify-center flex-shrink-0 transition-colors"
                                title="{{ __('prevention_request.download') }}">
                                <span class="material-symbols-outlined text-xs" aria-hidden="true">download</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <p class="text-[10px] text-secondary dark:text-gray-400 flex items-start gap-1 leading-relaxed">
            <span class="material-symbols-outlined text-sm mt-px text-amber-700 dark:text-amber-500" aria-hidden="true">save</span>
            {{ __('prevention_request.old_version_footnote') }}
        </p>
    </div>
</div>

{{-- ── Prior correction rounds (only shown when there were previous rounds before this one) ── --}}
@if ($hasPriorRounds)
<div class="bg-surface-container-lowest rounded border border-surface-dim dark:border-gray-700 shadow-sm"
     x-data="{ openPrior: false }">
    <button type="button" @click="openPrior = !openPrior"
        class="w-full flex items-center gap-3 px-5 py-3 text-start hover:bg-surface-container dark:hover:bg-gray-700/40 transition-colors">
        <div class="w-7 h-7 rounded-full bg-surface-container dark:bg-gray-700 text-secondary flex items-center justify-center flex-shrink-0 text-xs">
            <span class="material-symbols-outlined text-sm" aria-hidden="true">manage_history</span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-secondary dark:text-gray-400">
                {{ __('prevention_request.prior_corrections_title', ['count' => count($priorRounds)]) }}
            </p>
        </div>
        <svg class="w-4 h-4 text-secondary transition-transform flex-shrink-0"
             :class="openPrior ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="openPrior" x-cloak class="px-5 pb-4 border-t border-surface-dim dark:border-gray-700 pt-3">
        @include('prevention-requests.partials.correction-history', [
            'allCorrectionRounds' => $priorRounds,
            'archivedDocuments'   => $archivedDocs,
            'documentTypes'       => $documentTypes ?? collect(),
        ])
    </div>
</div>
@endif
