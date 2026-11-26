@php
/**
 * Targeted correction form — training service.
 * UI aligned with the prevention correction page and the training create wizard.
 *
 * Variables (from TrainingController@showCorrectForm):
 *   $trainingRequest – TrainingRequest (loaded: governorate, delegation, documents, participants, events)
 *   $correction      – array from $trainingRequest->lastCorrectionData()
 *   $fieldsToFix     – string[] field keys that need editing
 *   $docsToFix       – string[] document keys that need re-upload
 *   $documentGroups  – Collection<array> flagged document requirements, grouped per participant
 *   $archivedDocs    – Collection<TrainingDocument> (status = archived)
 *   $gouvernorats    – Collection<Governorate> with delegations eager-loaded
 */

$tr       = $trainingRequest;
$round    = $correction['round'] ?? 1;
$note     = $correction['note'] ?? null;
$previous = is_array($correction['previous'] ?? null) ? $correction['previous'] : [];

$isField = fn (string $key) => in_array($key, $fieldsToFix, true);

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

$hasFields = ! empty($fieldsToFix);
$hasDocs   = $documentGroups->isNotEmpty();
$totalDocs = $documentGroups->sum(fn (array $group) => count($group['documents']));

// Read-only snapshot — every field that is NOT part of this correction round.
$snapshotRows = collect([
    ['depositor_type', __('training.depositor_type'), $tr->isCompany() ? __('training.depositor_company') : __('training.depositor_individual')],
    ['nom_prenom',     $fieldLabels['nom_prenom'],     $tr->nom_prenom],
    ['cin',            $fieldLabels['cin'],            $tr->cin],
    ['phone',          $fieldLabels['phone'],          $tr->phone],
    ['governorate_id', $fieldLabels['governorate_id'], $tr->governorate?->name],
    ['delegation_id',  $fieldLabels['delegation_id'],  $tr->delegation?->name],
    ['training_type',  $fieldLabels['training_type'],  $tr->training_type ? __('training.type_' . $tr->training_type) : null],
    ['training_kind',  $fieldLabels['training_kind'],  $tr->training_kind ? __('training.kind_' . $tr->training_kind) : null],
])->filter(fn (array $row) => filled($row[2]) && ! $isField($row[0]))->values();

// Previous values recorded when the admin requested the correction.
$previousRows = collect([
    [__('training.nom_prenom'),     $previous['nom_prenom'] ?? null],
    [__('training.cin'),            $previous['cin'] ?? null],
    [__('training.phone'),          $previous['phone'] ?? null],
    [__('training.code_structure'), $previous['governorate_id'] ?? null],
    [__('training.delegation'),     $previous['delegation_id'] ?? null],
    [__('training.training_type'),  ! empty($previous['training_type']) ? __('training.type_' . $previous['training_type']) : null],
    [__('training.training_kind'),  ! empty($previous['training_kind']) ? __('training.kind_' . $previous['training_kind']) : null],
    [__('training.notes'),          $previous['notes'] ?? null],
])->filter(fn (array $row) => filled($row[1]));

$fmtSize = fn (?int $bytes) => $bytes === null ? '—'
    : ($bytes >= 1048576 ? round($bytes / 1048576, 1) . ' Mo'
    : ($bytes >= 1024    ? round($bytes / 1024, 1)    . ' Ko'
    : $bytes . ' o'));

$showOldCard = $previousRows->isNotEmpty() || $archivedDocs->isNotEmpty();
@endphp

<x-app-layout>
<div class="py-8 sm:py-10">
<div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- ══════════════ BREADCRUMB ══════════════ --}}
    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-secondary overflow-x-auto whitespace-nowrap">
        <a class="hover:text-primary transition-colors flex items-center gap-1" href="{{ route('dashboard') }}">
            <span class="material-symbols-outlined text-xs" aria-hidden="true">home</span>
            {{ __('training.dashboard_title') }}
        </a>
        <span class="material-symbols-outlined text-xs text-surface-dim rtl:rotate-180" aria-hidden="true">chevron_right</span>
        <a class="hover:text-primary transition-colors" href="{{ route('training.index') }}">{{ __('training.my_requests') }}</a>
        <span class="material-symbols-outlined text-xs text-surface-dim rtl:rotate-180" aria-hidden="true">chevron_right</span>
        <a class="hover:text-primary transition-colors" href="{{ route('training.show', $tr) }}">
            {{ $tr->reference ?: \App\Support\Reference::provisional('training') }}
        </a>
        <span class="material-symbols-outlined text-xs text-surface-dim rtl:rotate-180" aria-hidden="true">chevron_right</span>
        <span class="text-on-surface font-bold">{{ __('training.correction_page_title') }}</span>
    </nav>

    {{-- ══════════════ CORRECTION STATUS BANNER ══════════════ --}}
    <div class="bg-surface-container-lowest rounded border border-surface-dim border-l-4 border-l-primary shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
                <div class="w-12 h-12 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 dark:bg-primary/20">
                    <span class="material-symbols-outlined text-2xl" aria-hidden="true">edit_note</span>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-primary">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">sync</span>
                            {{ __('training.step_editing') }}
                        </span>
                        <span class="text-outline dark:text-gray-600 text-xs">·</span>
                        <span class="text-[11px] text-secondary dark:text-gray-400">
                            {{ __('training.correction_page_subtitle', ['round' => $round]) }}
                        </span>
                    </div>
                    <h1 class="text-headline-sm font-bold text-on-surface dark:text-gray-100 mt-0.5">
                        {{ __('training.correction_page_title') }}
                    </h1>

                    {{-- Admin correction note — first thing the applicant reads --}}
                    @if ($note)
                    <div class="mt-3 rounded border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-3.5 py-2.5">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-red-700 dark:text-red-400 mb-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">rate_review</span>
                            {{ __('training.admin_note_label') }}
                        </p>
                        <p class="text-sm text-red-900 dark:text-red-200 whitespace-pre-line leading-relaxed">{{ $note }}</p>
                    </div>
                    @endif

                    {{-- Summary chips --}}
                    @if ($hasFields || $hasDocs)
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($fieldsToFix as $fk)
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full
                                         bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700/40">
                                <span class="material-symbols-outlined text-[11px]" aria-hidden="true">edit</span>
                                {{ $fieldLabels[$fk] ?? $fk }}
                            </span>
                        @endforeach
                        @foreach ($documentGroups as $group)
                            @foreach ($group['documents'] as $requirement)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full
                                             bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-700/40">
                                    <span class="material-symbols-outlined text-[11px]" aria-hidden="true">upload_file</span>
                                    {{ $requirement['name'] }}
                                </span>
                            @endforeach
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- Doc progress counter --}}
            @if ($hasDocs)
            <span id="docs-progress"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gov-50 text-gov-800 text-body-sm font-semibold
                         border border-gov-300 shrink-0 dark:bg-gov-900/30 dark:text-gov-300 dark:border-gov-800">
                <span class="material-symbols-outlined filled text-[16px]" aria-hidden="true">task_alt</span>
                <span id="docs-progress-count">0/{{ $totalDocs }}</span>
            </span>
            @endif
        </div>
    </div>

    {{-- ══════════════ VALIDATION ERRORS ══════════════ --}}
    @if ($errors->any())
    <div class="rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800
                dark:bg-red-900/30 dark:text-red-200" role="alert">
        <p class="font-bold">{{ __('training.create_error_title') }}</p>
        <ul class="mt-1.5 list-disc space-y-1 ps-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ══════════════ MAIN FORM ══════════════ --}}
    <form method="POST"
          action="{{ route('training.correct.submit', $tr) }}"
          enctype="multipart/form-data"
          id="correction-form"
          class="space-y-6">
        @csrf

        {{-- ── SECTION A: Read-only snapshot — collapsible ── --}}
        @if ($snapshotRows->isNotEmpty())
        <div class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800"
             x-data="{ openA: false }">

            <button type="button" @click="openA = !openA"
                    class="w-full flex items-center gap-2.5 px-6 py-4 text-start
                           hover:bg-surface-container dark:hover:bg-gray-700/40 transition-colors rounded">
                <span class="w-7 h-7 rounded bg-surface-container dark:bg-gray-700 text-secondary flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">A</span>
                <h2 class="text-title-section text-on-surface uppercase tracking-wider flex-1">
                    {{ __('training.correction_current_values') }}
                </h2>
                <span class="text-[10px] text-outline dark:text-gray-500 hidden sm:block">— {{ __('training.correction_readonly_note') }}</span>
                <svg class="w-4 h-4 text-secondary dark:text-gray-400 shrink-0 transition-transform"
                     :class="openA ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="openA" x-cloak
                 class="px-6 pb-5 border-t border-surface-dim dark:border-gray-700">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-0 divide-y sm:divide-y-0 divide-surface-dim dark:divide-gray-700/60 pt-4">
                    @foreach ($snapshotRows as [$key, $label, $value])
                        <div class="py-2.5">
                            <dt class="text-[10px] uppercase tracking-wide font-bold text-secondary dark:text-gray-500">{{ $label }}</dt>
                            <dd class="text-xs font-semibold text-on-surface dark:text-gray-200 mt-0.5 {{ in_array($key, ['cin', 'phone'], true) ? 'font-mono' : '' }}"
                                {{ in_array($key, ['cin', 'phone'], true) ? 'dir=ltr' : '' }}>{{ $value }}</dd>
                        </div>
                    @endforeach
                    @if (filled($tr->notes) && ! $isField('notes'))
                        <div class="py-2.5 sm:col-span-2">
                            <dt class="text-[10px] uppercase tracking-wide font-bold text-secondary dark:text-gray-500">{{ $fieldLabels['notes'] }}</dt>
                            <dd class="text-xs font-semibold text-on-surface dark:text-gray-200 mt-0.5 whitespace-pre-line leading-relaxed">{{ $tr->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
        @endif

        {{-- ── SECTION B: Editable fields ── --}}
        @if ($hasFields)
        <div class="bg-surface-container-lowest rounded border border-amber-200 dark:border-amber-700/50 shadow-sm dark:bg-gray-800">
            <div class="p-6 space-y-5">
                <div class="flex items-center gap-2.5 pb-3 border-b border-amber-100 dark:border-amber-800/30">
                    <span class="w-7 h-7 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">edit</span>
                    </span>
                    <h2 class="text-title-section text-on-surface uppercase tracking-wider">
                        {{ __('training.correction_fields_section') }}
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    @if ($isField('nom_prenom'))
                    <div class="space-y-1.5">
                        <label for="nom_prenom" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['nom_prenom'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <input id="nom_prenom" type="text" name="nom_prenom"
                               value="{{ old('nom_prenom', $tr->nom_prenom) }}"
                               required maxlength="255" autocomplete="name"
                               class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gov-400 dark:focus:ring-gov-400/50 @error('nom_prenom') border-red-400 @enderror">
                        @error('nom_prenom')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    @if ($isField('cin'))
                    <div class="space-y-1.5">
                        <label for="cin" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['cin'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <input id="cin" type="text" name="cin"
                               value="{{ old('cin', $tr->cin) }}"
                               required maxlength="20" inputmode="numeric"
                               class="block w-full h-11 px-3.5 text-body-md font-mono text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gov-400 dark:focus:ring-gov-400/50 @error('cin') border-red-400 @enderror">
                        @error('cin')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    @if ($isField('phone'))
                    <div class="space-y-1.5">
                        <label for="phone" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['phone'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <input id="phone" type="tel" name="phone"
                               value="{{ old('phone', $tr->phone) }}"
                               required maxlength="20" inputmode="tel" dir="ltr"
                               class="num-ltr block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gov-400 dark:focus:ring-gov-400/50 @error('phone') border-red-400 @enderror">
                        @error('phone')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif

                </div>{{-- /grid --}}

                {{-- Governorate / Delegation cascading pair --}}
                @if ($isField('governorate_id') || $isField('delegation_id'))
                <div
                    x-data="{
                        gov: '{{ old('governorate_id', $tr->governorate_id) }}',
                        del: '{{ old('delegation_id', $tr->delegation_id) }}',
                        delegationsMap: {{ Illuminate\Support\Js::from(
                            $gouvernorats->mapWithKeys(fn ($g) => [
                                $g->id => $g->delegations->map(fn ($d) => [
                                    'id'   => $d->id,
                                    'name' => $d->name,
                                ])->values()->all(),
                            ])->toArray()
                        ) }}
                    }"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                    @if ($isField('governorate_id'))
                    <div class="space-y-1.5">
                        <label for="governorate_id" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['governorate_id'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <select id="governorate_id" name="governorate_id" x-model="gov" required
                                class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('governorate_id') border-red-400 @enderror">
                            <option value="">{{ __('training.code_structure_select') }}</option>
                            @foreach ($gouvernorats as $govItem)
                                <option value="{{ $govItem->id }}" @selected(old('governorate_id', $tr->governorate_id) == $govItem->id)>
                                    {{ $govItem->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('governorate_id')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    @if ($isField('delegation_id'))
                    <div class="space-y-1.5">
                        <label for="delegation_id" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                            {{ $fieldLabels['delegation_id'] }} <span class="text-primary" aria-hidden="true">*</span>
                        </label>
                        <select id="delegation_id" name="delegation_id" x-model="del" required
                                class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('delegation_id') border-red-400 @enderror">
                            <option value="">{{ __('training.delegation_select') }}</option>
                            <template x-for="d in (delegationsMap[gov] || [])" :key="d.id">
                                <option :value="d.id" :selected="d.id == del" x-text="d.name"></option>
                            </template>
                        </select>
                        @error('delegation_id')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                    @endif
                </div>
                @endif

                {{-- Training type --}}
                @if ($isField('training_type'))
                <div class="space-y-1.5">
                    <label for="training_type" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                        {{ $fieldLabels['training_type'] }} <span class="text-primary" aria-hidden="true">*</span>
                    </label>
                    <select id="training_type" name="training_type" required
                            class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('training_type') border-red-400 @enderror">
                        <option value="" disabled @selected(! old('training_type', $tr->training_type))>{{ __('training.training_type_select') }}</option>
                        @foreach (\App\Models\TrainingRequest::TYPES as $key => $data)
                            <option value="{{ $key }}" @selected(old('training_type', $tr->training_type) === $key)>{{ __('training.type_' . $key) }}</option>
                        @endforeach
                    </select>
                    @error('training_type')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                </div>
                @endif

                {{-- Training kind --}}
                @if ($isField('training_kind'))
                <div class="space-y-1.5">
                    <label class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                        {{ $fieldLabels['training_kind'] }} <span class="text-primary" aria-hidden="true">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach (['new' => 'kind_new', 'recycling' => 'kind_recycling'] as $val => $lk)
                            <label class="flex items-center gap-3 rounded-lg border border-surface-dim bg-surface-container px-4 py-3 cursor-pointer transition hover:border-primary/40 has-[:checked]:border-primary/60 has-[:checked]:bg-primary/5 dark:border-gray-700 dark:bg-gray-900/50">
                                <input type="radio" name="training_kind" value="{{ $val }}" @checked(old('training_kind', $tr->training_kind) === $val) class="h-4 w-4 border-surface-dim text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-800">
                                <span class="text-body-sm font-semibold text-on-surface dark:text-gray-100">{{ __('training.' . $lk) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('training_kind')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                </div>
                @endif

                {{-- Notes --}}
                @if ($isField('notes'))
                <div class="space-y-1.5">
                    <label for="notes" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ $fieldLabels['notes'] }}</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="3000" placeholder="{{ __('training.notes_placeholder') }}"
                              class="block w-full px-3.5 py-2.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('notes') border-red-400 @enderror">{{ old('notes', $tr->notes) }}</textarea>
                    @error('notes')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                </div>
                @endif

            </div>
        </div>
        @endif

        {{-- ── SECTION C: Documents to re-upload ── --}}
        @if ($hasDocs)
        <div class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="p-6 space-y-5">

                <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                    <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">C</span>
                    <h2 class="text-title-section text-on-surface uppercase tracking-wider">
                        {{ __('training.correction_docs_section') }}
                    </h2>
                </div>

                {{-- Info bar --}}
                <div class="flex items-start gap-3 rounded border border-surface-dim bg-surface-container-low p-4 text-body-sm dark:border-gray-700 dark:bg-gray-900/40" role="note">
                    <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">info</span>
                    <p class="text-secondary dark:text-gray-300">{{ __('training.documents_info') }}</p>
                </div>

                <div class="space-y-4">
                    @foreach ($documentGroups as $group)
                        <div class="rounded-lg border border-surface-dim dark:border-gray-700 overflow-hidden">
                            {{-- Participant header --}}
                            <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-surface-dim bg-surface-container-low dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">person</span>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-body-sm font-bold text-on-surface dark:text-gray-100">
                                            {{ $group['name'] ?: __('training.participants_section') }}
                                        </p>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[11px] text-secondary">
                                            @if ($group['cin'])
                                                <span dir="ltr">{{ __('training.cin') }} : {{ $group['cin'] }}</span>
                                            @endif
                                            @if ($group['training_kind'] === \App\Models\TrainingRequest::KIND_RECYCLING)
                                                <span class="inline-flex items-center rounded border border-amber-200 bg-amber-100 px-1.5 py-0.5 font-bold text-amber-800 dark:border-amber-800/40 dark:bg-amber-900/30 dark:text-amber-300">
                                                    {{ __('training.kind_recycling') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded border border-gov-200 bg-gov-50 px-1.5 py-0.5 font-bold text-gov-800 dark:border-gov-800/40 dark:bg-gov-900/30 dark:text-gov-300">
                                                    {{ __('training.kind_new') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1 rounded bg-surface-container px-2 py-1 text-[11px] font-semibold text-secondary dark:bg-gray-800">
                                    <span class="material-symbols-outlined text-[14px]" aria-hidden="true">attach_file</span>
                                    {{ count($group['documents']) }}
                                </span>
                            </div>

                            <ol class="doc-list space-y-3 p-3 sm:p-4">
                                @foreach ($group['documents'] as $requirement)
                                    @php
                                        $oldDoc = $archivedDocs->first(fn ($d) => $d->document_key === $requirement['key']);
                                    @endphp
                                    <li class="doc-card rounded border border-surface-dim p-4 transition dark:border-gray-700"
                                        data-doc-key="{{ $requirement['key'] }}"
                                        data-has-server="0">

                                        <div class="flex items-start gap-3">

                                            {{-- Status icon --}}
                                            <span class="status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full
                                                         bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                                </svg>
                                            </span>

                                            <div class="min-w-0 flex-1">

                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-body-sm font-bold text-on-surface dark:text-gray-100">
                                                        {{ $loop->iteration }}. {{ $requirement['name'] }}
                                                    </p>
                                                </div>

                                                {{-- Archived (old) version of this document --}}
                                                @if ($oldDoc)
                                                <div class="mt-2 flex items-center gap-2 rounded bg-surface-container dark:bg-gray-700/40
                                                            border border-surface-dim dark:border-gray-600 px-3 py-2 text-[11px]">
                                                    <span class="material-symbols-outlined text-outline dark:text-gray-500 text-sm" aria-hidden="true">inventory_2</span>
                                                    <span class="text-secondary dark:text-gray-400 flex-1 truncate">
                                                        {{ __('training.correction_old_file') }}:
                                                        <span class="font-semibold">{{ $oldDoc->original_name }}</span>
                                                    </span>
                                                    <span class="text-outline dark:text-gray-500 font-mono">{{ $fmtSize($oldDoc->size) }}</span>
                                                    <a href="{{ route('training.documents.download', $oldDoc) }}"
                                                       class="w-6 h-6 rounded bg-surface-container-low dark:bg-gray-700 text-secondary
                                                              hover:bg-primary hover:text-on-primary flex items-center justify-center flex-shrink-0 transition-colors"
                                                       title="{{ __('training.download') }}">
                                                        <span class="material-symbols-outlined text-xs" aria-hidden="true">download</span>
                                                    </a>
                                                </div>
                                                @endif

                                                {{-- Preview list (filled by JS) --}}
                                                <div class="preview-list mt-3 hidden space-y-1.5"></div>

                                                {{-- Upload button --}}
                                                <div class="mt-3 upload-area">
                                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded border border-surface-dim
                                                                  bg-surface-container-lowest px-3 py-2 text-xs font-semibold text-on-surface
                                                                  transition hover:bg-surface-container
                                                                  dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                        <span class="material-symbols-outlined text-[15px] text-primary" aria-hidden="true">upload_file</span>
                                                        <span class="upload-label">{{ __('training.upload_file') }}</span>
                                                        <input type="file"
                                                               name="documents[{{ $requirement['key'] }}][]"
                                                               class="sr-only file-input"
                                                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                               multiple>
                                                    </label>
                                                </div>

                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
        @endif

        {{-- ── Footer bar: back + submit ── --}}
        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3
                    border-t border-surface-dim pt-4 dark:border-gray-700">
            <a href="{{ route('training.show', $tr) }}"
               class="inline-flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest
                      px-4 py-2.5 text-body-sm font-semibold text-on-surface transition
                      hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('training.back') }}
            </a>

            <div class="flex flex-col-reverse sm:flex-row items-center gap-3">
                <p class="text-xs text-secondary dark:text-gray-400 hidden sm:block">{{ __('training.required_note') }}</p>
                <button type="submit"
                        id="correction-submit-btn"
                        class="ui-btn-primary inline-flex items-center justify-center gap-2 px-6 py-2.5 text-body-sm
                               focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer
                               disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                        data-loading-text="{{ __('training.submitting') }}">
                    <span class="material-symbols-outlined filled text-[18px]" aria-hidden="true">send</span>
                    <span id="correction-submit-label">{{ __('training.correction_submit_btn') }}</span>
                </button>
            </div>
        </div>

    </form>

    {{-- ══════════════ OLD VERSION CARD — previous values + archived documents ══════════════ --}}
    @if ($showOldCard)
    <div class="bg-surface-container-lowest rounded border border-amber-300 shadow-sm dark:border-amber-700 dark:bg-amber-900/10"
         x-data="{ openOld: false }">
        <button type="button" @click="openOld = !openOld"
            class="w-full flex items-start sm:items-center gap-3 px-5 py-4 text-start hover:bg-amber-50/50 dark:hover:bg-amber-900/20 transition-colors">
            <div class="w-9 h-9 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">history</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                    {{ __('training.old_version_title', ['round' => $round]) }}
                </p>
                <p class="text-[11px] text-secondary mt-0.5 leading-relaxed">{{ __('training.old_version_desc') }}</p>
            </div>
            <svg class="w-4 h-4 text-amber-800 dark:text-amber-400 transition-transform flex-shrink-0 mt-1 sm:mt-0"
                 :class="openOld ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="openOld" x-cloak class="px-5 pb-5 border-t border-amber-200/70 dark:border-amber-800/40 space-y-4">

            {{-- Previous field values --}}
            @if ($previousRows->isNotEmpty())
            <div class="pt-4">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300 mb-2 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">edit_note</span>
                    {{ __('training.previous_values') }}
                </p>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                    @foreach ($previousRows as [$label, $value])
                        <div class="rounded bg-surface-container-lowest/70 dark:bg-gray-800/40 border border-surface-dim dark:border-gray-700 px-3 py-2">
                            <dt class="text-[10px] uppercase tracking-wide font-bold text-secondary dark:text-gray-500">{{ $label }}</dt>
                            <dd class="text-xs font-semibold text-on-surface dark:text-gray-200 mt-0.5 whitespace-pre-line">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            @endif

            {{-- Archived documents --}}
            @if ($archivedDocs->isNotEmpty())
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-900 dark:text-amber-300 mb-2 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">inventory_2</span>
                    {{ __('training.archive_title') }}
                </p>
                <div class="space-y-2">
                    @foreach ($archivedDocs as $doc)
                        <div class="flex items-center gap-2.5 rounded border border-surface-dim dark:border-gray-700 bg-surface-container-lowest/70 dark:bg-gray-800/40 px-3 py-2 text-[11px]">
                            <span class="material-symbols-outlined text-outline dark:text-gray-500 text-base" aria-hidden="true">description</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-on-surface dark:text-gray-200">
                                    {{ $doc->document_key ? $tr->documentKeyLabel($doc->document_key) : __('training.documents_title') }}
                                </p>
                                <p class="text-secondary truncate" dir="ltr">{{ $doc->original_name }}</p>
                            </div>
                            <span class="text-outline dark:text-gray-500 font-mono whitespace-nowrap">{{ $fmtSize($doc->size) }}</span>
                            <span class="text-outline dark:text-gray-500 whitespace-nowrap">{{ $doc->created_at?->format('d/m/Y') }}</span>
                            <a href="{{ route('training.documents.download', $doc) }}"
                               class="w-7 h-7 rounded bg-surface-container dark:bg-gray-700 text-secondary hover:bg-primary hover:text-on-primary flex items-center justify-center flex-shrink-0 transition-colors"
                               title="{{ __('training.download') }}">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     Upload JS — cumulative staging, preview, card state, progress counter.
     Mirrors the prevention correction page.
     ══════════════════════════════════════════════════════════════════════════ --}}
@if ($hasDocs)
<script>
(function () {
    var form          = document.getElementById('correction-form');
    var btn           = document.getElementById('correction-submit-btn');
    var lbl           = document.getElementById('correction-submit-label');
    var fileInputs    = document.querySelectorAll('.file-input');
    var progressCount = document.getElementById('docs-progress-count');
    var totalDocs     = document.querySelectorAll('.doc-card').length;

    var LABEL_UPLOAD = @json(__('training.upload_file'));
    var LABEL_ADD    = @json(__('training.add_file'));
    var LABEL_REMOVE = @json(__('training.remove_file'));

    /* ── Live counter ── */
    function updateProgress() {
        if (!progressCount) return;
        var done = 0;
        document.querySelectorAll('.doc-card').forEach(function (card) {
            var input  = card.querySelector('.file-input');
            var staged = input && input.files ? input.files.length : 0;
            var hasSrv = card.dataset.hasServer === '1';
            if (staged > 0 || hasSrv) done++;
        });
        progressCount.textContent = done + '/' + totalDocs;
    }

    /* ── Attach change listeners ── */
    fileInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            var files = Array.prototype.slice.call(this.files);
            var card  = this.closest('.doc-card');
            if (!card || !files.length) return;

            stagedFiles(input).push.apply(stagedFiles(input), files);
            syncInputFiles(input);
            renderPreviewList(card);
            refreshCardState(card);
        });
    });

    /* ── Cumulative staging helpers ── */
    function stagedFiles(input) {
        if (!input._staged) input._staged = [];
        return input._staged;
    }

    function syncInputFiles(input) {
        var dt = new DataTransfer();
        stagedFiles(input).forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
    }

    /* ── Card visual state ── */
    function refreshCardState(card) {
        var input       = card.querySelector('.file-input');
        var hasServer   = card.dataset.hasServer === '1';
        var stagedCount = stagedFiles(input).length;
        var statusIcon  = card.querySelector('.status-icon');
        var uploadLabel = card.querySelector('.upload-label');
        var previewList = card.querySelector('.preview-list');

        if (previewList) previewList.classList.toggle('hidden', stagedCount === 0);

        if (stagedCount === 0 && !hasServer) {
            card.classList.add('border-surface-dim');
            card.classList.remove('border-gov-300', 'bg-gov-50/30', 'dark:border-gov-800/40', 'dark:bg-gov-900/10');
            statusIcon.className = 'status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500';
            statusIcon.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>';
        } else {
            card.classList.remove('border-surface-dim');
            card.classList.add('border-gov-300', 'bg-gov-50/30', 'dark:border-gov-800/40', 'dark:bg-gov-900/10');
            statusIcon.className = 'status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-gov-100 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300';
            statusIcon.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
        }

        if (uploadLabel) {
            uploadLabel.textContent = (stagedCount > 0 || hasServer) ? LABEL_ADD : LABEL_UPLOAD;
        }

        updateProgress();
    }

    /* ── Per-file preview rows ── */
    function renderPreviewList(card) {
        var input     = card.querySelector('.file-input');
        var list      = stagedFiles(input);
        var container = card.querySelector('.preview-list');
        if (!container) return;

        container.innerHTML = '';

        list.forEach(function (file, idx) {
            var row = document.createElement('div');
            row.className = 'flex items-center gap-3 rounded-lg border border-surface-dim bg-surface-container-lowest p-2.5 dark:border-gray-700 dark:bg-gray-800/60';

            row.innerHTML =
                '<div class="staged-thumb flex h-10 w-10 flex-none items-center justify-center overflow-hidden rounded-lg bg-gov-50 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300">' +
                    '<img class="hidden h-full w-full object-cover" alt="">' +
                    '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>' +
                    '</svg>' +
                '</div>' +
                '<div class="min-w-0 flex-1">' +
                    '<p class="truncate text-xs font-semibold text-on-surface dark:text-gray-100"></p>' +
                    '<p class="text-[11px] text-outline"></p>' +
                '</div>' +
                '<span class="flex h-5 w-5 flex-none items-center justify-center rounded-full bg-gov-100 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300">' +
                    '<svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>' +
                '</span>';

            // Filename + size via textContent (XSS-safe)
            row.querySelectorAll('p')[0].textContent = file.name;
            row.querySelectorAll('p')[1].textContent = formatSize(file.size);

            // Thumbnail for images
            var badge = row.querySelector('.staged-thumb');
            var img   = badge.querySelector('img');
            if (file.type && file.type.indexOf('image/') === 0) {
                img.classList.remove('hidden');
                var reader = new FileReader();
                reader.onload = function (e) { img.src = e.target.result; };
                reader.readAsDataURL(file);
            }

            // Remove button
            var rm = document.createElement('button');
            rm.type      = 'button';
            rm.className = 'remove-file flex h-8 w-8 flex-none items-center justify-center rounded-lg text-outline transition hover:bg-red-50 hover:text-red-600 cursor-pointer dark:hover:bg-red-900/20';
            rm.title     = LABEL_REMOVE;
            rm.dataset.idx = String(idx);
            rm.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
            row.appendChild(rm);

            container.appendChild(row);
        });
    }

    /* ── Remove button (event-delegated so it survives re-renders) ── */
    var docList = document.getElementById('correction-form');
    if (docList) {
        docList.addEventListener('click', function (e) {
            var btnRemove = e.target.closest('.remove-file');
            if (!btnRemove || btnRemove.dataset.idx === undefined) return;

            var card = btnRemove.closest('.doc-card');
            if (!card) return;

            var input = card.querySelector('.file-input');
            stagedFiles(input).splice(Number(btnRemove.dataset.idx), 1);
            syncInputFiles(input);
            renderPreviewList(card);
            refreshCardState(card);
        });
    }

    function formatSize(bytes) {
        if (bytes < 1024)    return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    /* ── Submit lock ── */
    if (form) {
        form.addEventListener('submit', function () {
            btn.disabled  = true;
            lbl.textContent = btn.dataset.loadingText;
        });
    }
}());
</script>
@endif

</x-app-layout>
