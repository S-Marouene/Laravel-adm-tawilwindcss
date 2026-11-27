@php
/**
 * Full correction history — training service (same pattern as prevention).
 *
 * Variables expected:
 *   $allCorrectionRounds – array from $trainingRequest->allCorrectionRounds()
 *   $archivedDocuments   – Collection<TrainingDocument> (status = archived)
 *   $trainingRequest     – TrainingRequest (labels + download links)
 *
 * Each round: [ round, date, note, previous, archived_files ]
 */
if (empty($allCorrectionRounds)) return;

$fmtSize = fn (?int $bytes) => $bytes === null ? '—'
    : ($bytes >= 1048576 ? round($bytes / 1048576, 1) . ' Mo'
    : ($bytes >= 1024    ? round($bytes / 1024, 1)    . ' Ko'
    : $bytes . ' o'));

$fieldLabels = [
    'nom_prenom'     => __('training.nom_prenom'),
    'cin'            => __('training.cin'),
    'phone'          => __('training.phone'),
    'governorate_id' => __('training.code_structure'),
    'delegation_id'  => __('training.delegation'),
    'training_type'  => __('training.training_type'),
    'training_kind'  => __('training.training_kind'),
    'notes'          => __('training.notes'),
];

$displayValue = function (string $key, $value) {
    if ($key === 'training_type') return __('training.type_' . $value);
    if ($key === 'training_kind') return __('training.kind_' . $value);
    return $value;
};
@endphp

<div class="space-y-3">
    @foreach ($allCorrectionRounds as $round)
    @php
        $roundNum  = $round['round'];
        $hasParams = ! empty($round['previous']);
        $hasFiles  = ! empty($round['archived_files']);
        $prevValues = collect($fieldLabels)
            ->map(function ($label, $key) use ($round, $displayValue) {
                $raw = $round['previous'][$key] ?? null;
                return [
                    'label' => $label,
                    'value' => filled($raw) ? $displayValue($key, $raw) : null,
                ];
            })
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
                    {{ __('training.old_version_title', ['round' => $roundNum]) }}
                    @if (! empty($round['date']))
                        <span class="font-normal text-amber-700 dark:text-amber-400 normal-case tracking-normal ms-2">
                            {{ $round['date']->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </p>
                <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5 leading-snug">
                    @if ($hasParams)
                        {{ $prevValues->count() }} {{ __('training.correction_history_fields') }}
                    @endif
                    @if ($hasFiles)
                        @if ($hasParams) · @endif
                        {{ count($round['archived_files']) }} {{ __('training.correction_history_files') }}
                    @endif
                </p>
            </div>
            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 transition-transform flex-shrink-0"
                 :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        {{-- Body --}}
        <div x-show="open" x-cloak class="px-4 pb-4 pt-3 space-y-3 border-t border-amber-200 dark:border-amber-700/40">

            {{-- Admin note --}}
            @if (! empty($round['note']))
                <div class="rounded border border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20 px-3 py-2.5">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300 mb-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                        {{ __('training.admin_note_label') }}
                    </p>
                    <p class="text-xs text-on-surface dark:text-gray-200 whitespace-pre-line leading-relaxed">{{ $round['note'] }}</p>
                </div>
            @endif

            {{-- Old field values --}}
            @if ($hasParams && $prevValues->isNotEmpty())
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300 mb-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">person</span>
                        {{ __('training.old_params_title') }}
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

            {{-- Old uploaded files (metadata from the correction event) --}}
            @if ($hasFiles)
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300 mb-1.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">folder_open</span>
                        {{ __('training.old_files_title') }}
                    </p>
                    <div class="space-y-1.5">
                        @foreach ($round['archived_files'] as $fileMeta)
                            @php
                                $label = $fileMeta['type']
                                    ?? (! empty($fileMeta['document_key']) ? $trainingRequest->documentKeyLabel($fileMeta['document_key']) : null)
                                    ?? __('training.other_files');
                                $matchedDoc = ($archivedDocuments ?? collect())->first(
                                    fn ($d) => $d->original_name === ($fileMeta['file'] ?? null)
                                );
                            @endphp
                            <div class="flex items-center justify-between gap-2 bg-white/60 dark:bg-gray-800/40 rounded px-2.5 py-2 border border-amber-100 dark:border-amber-800/30">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-base" aria-hidden="true">description</span>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-bold text-on-surface dark:text-gray-200 truncate">{{ $label }}</p>
                                        <p class="text-[10px] text-secondary dark:text-gray-400" dir="ltr">
                                            <span class="font-mono">{{ $fmtSize($fileMeta['size'] ?? null) }}</span>
                                            @if (! empty($fileMeta['uploaded_at'])) · {{ $fileMeta['uploaded_at'] }} @endif
                                            @if (! empty($fileMeta['file'])) — <span class="truncate inline-block max-w-40 align-bottom" title="{{ $fileMeta['file'] }}">{{ $fileMeta['file'] }}</span> @endif
                                        </p>
                                    </div>
                                </div>
                                @if ($matchedDoc)
                                    <a href="{{ route($downloadRouteName ?? 'training.documents.download', $matchedDoc) }}"
                                        class="w-6 h-6 rounded bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:bg-amber-200 flex items-center justify-center flex-shrink-0 transition-colors"
                                        title="{{ __('training.download') }}">
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
                {{ __('training.old_version_footnote') }}
            </p>
        </div>
    </div>
    @endforeach
</div>
