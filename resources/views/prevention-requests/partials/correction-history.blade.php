@php
/**
 * Full correction history — shows ALL past correction rounds, not just the last.
 *
 * Variables expected:
 *   $allCorrectionRounds   – array from $dossier->allCorrectionRounds()
 *   $archivedDocuments     – Collection of DossierDocument (status=archived)
 *   $documentTypes         – Collection of DocumentType (for labels)
 *
 * Each round entry: [ round, date, note, previous, archived_files (metadata) ]
 */

if (empty($allCorrectionRounds)) return;

$fmtSize = fn (?int $bytes) => $bytes === null ? '—'
    : ($bytes >= 1048576 ? round($bytes / 1048576, 1) . ' Mo'
    : ($bytes >= 1024    ? round($bytes / 1024, 1)    . ' Ko'
    : $bytes . ' o'));

$fieldLabels = [
    'nom_prenom'       => __('prevention_request.field_name'),
    'cin'              => __('prevention_request.field_cin'),
    'phone'            => __('prevention_request.field_phone'),
    'governorate'      => __('prevention_request.field_governorate'),
    'delegation'       => __('prevention_request.field_delegation'),
    'building_address' => __('prevention_request.field_address'),
    'building_type'    => __('prevention_request.field_building'),
    'building_activity'=> __('prevention_request.field_activity'),
];

// Keep rounds in chronological order — the oldest (first) version appears
// at the top, collapsed by default.
$rounds = $allCorrectionRounds;
@endphp

<div class="space-y-3">
    @foreach ($rounds as $round)
    @php
        $roundNum = $round['round'];
        $hasParams = !empty($round['previous']);
        $hasFiles  = !empty($round['archived_files']);
        // Archived DossierDocument records for this round (metadata only if not loaded separately)
        // We try to match by the metadata stored in archived_files array
        $prevValues = collect($fieldLabels)
            ->map(fn ($label, $key) => ['label' => $label, 'value' => $round['previous'][$key] ?? null])
            ->filter(fn ($row) => filled($row['value']))
            ->values();
    @endphp
    <div class="rounded-lg border border-amber-200 bg-amber-50/60 dark:border-amber-700/50 dark:bg-amber-900/10 shadow-sm"
         x-data="{ open: false }">

        {{-- Header --}}
        <button type="button" @click="open = !open"
            class="w-full flex items-center gap-3 px-4 py-3 text-start hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors rounded-lg">
            <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 flex items-center justify-center flex-shrink-0 text-xs font-black border border-amber-300 dark:border-amber-600">
                {{ $roundNum }}
            </div>
            <div class="flex-1 min-w-0 text-start">
                <p class="text-xs font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                    @if (isset($round['date']))
                        {{ __('prevention_request.old_version_title', ['round' => $roundNum]) }}
                        <span class="font-normal text-amber-700 dark:text-amber-400 normal-case tracking-normal ms-2">
                            {{ $round['date']->format('d/m/Y H:i') }}
                        </span>
                    @else
                        {{ __('prevention_request.old_version_title', ['round' => $roundNum]) }}
                    @endif
                </p>
                <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5 leading-snug">
                    @if ($hasParams && $hasFiles)
                        {{ $prevValues->count() }} {{ __('prevention_request.correction_history_fields') }} · {{ count($round['archived_files']) }} {{ __('prevention_request.correction_history_files') }}
                    @elseif ($hasParams)
                        {{ $prevValues->count() }} {{ __('prevention_request.correction_history_fields') }}
                    @elseif ($hasFiles)
                        {{ count($round['archived_files']) }} {{ __('prevention_request.correction_history_files') }}
                    @endif
                </p>
            </div>
            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 transition-transform flex-shrink-0"
                 :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        {{-- Body --}}
        <div x-show="open" x-cloak class="px-4 pb-4 space-y-3 border-t border-amber-200 dark:border-amber-700/40 pt-3">

            {{-- Admin correction note --}}
            @if ($round['note'])
                <div class="rounded border border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20 px-3 py-2.5">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300 mb-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                        {{ __('prevention_request.admin_note_label') }}
                    </p>
                    <p class="text-xs text-on-surface dark:text-gray-200 whitespace-pre-line leading-relaxed">{{ $round['note'] }}</p>
                </div>
            @endif

            {{-- Old field values (previous parameters) --}}
            @if ($hasParams && $prevValues->isNotEmpty())
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300 mb-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">person</span>
                        {{ __('prevention_request.old_params_title') }}
                    </p>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 text-[11px]">
                        @foreach ($prevValues as $row)
                            <div class="flex justify-between gap-2 bg-white/60 dark:bg-gray-800/40 rounded px-2.5 py-1.5 border border-amber-100 dark:border-amber-800/30">
                                <dt class="text-secondary dark:text-gray-400">{{ $row['label'] }}</dt>
                                <dd class="font-semibold text-on-surface dark:text-gray-200 text-end break-words">{{ $row['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Old files (from metadata stored in the event) --}}
            @if ($hasFiles)
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300 mb-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">folder_open</span>
                        {{ __('prevention_request.old_files_title') }}
                    </p>
                    <div class="space-y-1.5">
                        @foreach ($round['archived_files'] as $fileMeta)
                            <div class="flex items-center justify-between gap-2 bg-white/60 dark:bg-gray-800/40 rounded px-2.5 py-2 border border-amber-100 dark:border-amber-800/30">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-base" aria-hidden="true">description</span>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-bold text-on-surface dark:text-gray-200 truncate">{{ $fileMeta['type'] ?? __('prevention_request.other_files') }}</p>
                                        <p class="text-[10px] text-secondary dark:text-gray-400" dir="ltr">
                                            <span class="font-mono">{{ $fmtSize($fileMeta['size'] ?? null) }}</span>
                                            @if (!empty($fileMeta['uploaded_at'])) · {{ $fileMeta['uploaded_at'] }} @endif
                                            @if (!empty($fileMeta['file'])) — <span class="truncate inline-block max-w-40 align-bottom" title="{{ $fileMeta['file'] }}">{{ $fileMeta['file'] }}</span> @endif
                                        </p>
                                    </div>
                                </div>
                                {{-- Try to find the actual archived DossierDocument for download --}}
                                @php
                                    $matchedDoc = isset($archivedDocuments)
                                        ? $archivedDocuments->first(fn ($d) => $d->original_name === ($fileMeta['file'] ?? null))
                                        : null;
                                @endphp
                                @if ($matchedDoc)
                                    <a href="{{ route('prevention-requests.documents.download', [$matchedDoc->dossier, $matchedDoc]) }}"
                                        class="w-6 h-6 rounded bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:bg-amber-200 flex items-center justify-center flex-shrink-0 transition-colors"
                                        title="{{ __('prevention_request.download') }}">
                                        <span class="material-symbols-outlined text-xs" aria-hidden="true">download</span>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <p class="text-[10px] text-amber-700 dark:text-amber-400 flex items-start gap-1 leading-relaxed">
                <span class="material-symbols-outlined text-sm mt-px" aria-hidden="true">save</span>
                {{ __('prevention_request.old_version_footnote') }}
            </p>
        </div>
    </div>
    @endforeach
</div>
