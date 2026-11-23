@php
    $isEditing = true;
    $intervention = $specialIntervention;
    $correction = $correction ?? null;
    $fieldsToFix = $fieldsToFix ?? [];
    $docsToFix = $docsToFix ?? [];
    $round = $correction['round'] ?? 1;
    $note = $correction['note'] ?? null;
    $previous = $correction['previous'] ?? null;
    $archivedFiles = $correction['archived_files'] ?? null;
    $allFieldKeys = ['cin', 'nom_prenom', 'phone', 'governorate_id', 'delegation_id', 'intervention_date', 'intervention_time', 'notes', 'interventions'];
    $interventionItems = $specialIntervention->interventions ?? [];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface dark:text-gray-100">{{ __('special_intervention.correction_page_title') }}</h2>
                <p class="mt-0.5 text-sm text-secondary dark:text-gray-400">
                    {{ __('special_intervention.correction_page_subtitle', ['round' => $round]) }}
                </p>
            </div>
            <a href="{{ route('special-interventions.show', $specialIntervention) }}"
               class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-3 rounded transition-colors">
                <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                {{ __('special_intervention.back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Admin note / correction banner --}}
            @if ($note)
            <section class="mb-6 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800/30 dark:bg-amber-900/10">
                <div class="border-l-4 border-l-amber-600 p-5">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-10 h-10 rounded bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1" aria-hidden="true">rate_review</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('special_intervention.correction_title') }}</h3>
                            <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">{{ __('special_intervention.admin_correction_hint') }}</p>
                        </div>
                    </div>
                    <p class="text-body-sm text-amber-900 dark:text-amber-200 whitespace-pre-line leading-relaxed">{{ $note }}</p>
                </div>
            </section>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('special-interventions.correct.submit', $specialIntervention) }}"
                  class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800"
                  enctype="multipart/form-data">

                @csrf

                <div class="p-6 sm:p-8 space-y-6">

                    {{-- Valeurs précédentes (info) --}}
                    @if ($previous && is_array($previous))
                    <div class="bg-surface-container-low border border-surface-dim rounded p-4">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary text-base" aria-hidden="true">history</span>
                            {{ __('special_intervention.previous_values') }}
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-secondary">
                            @if (isset($previous['cin']))
                                <div><span class="font-medium text-on-surface">{{ __('special_intervention.cin') }}:</span> {{ $previous['cin'] }}</div>
                            @endif
                            @if (isset($previous['nom_prenom']))
                                <div><span class="font-medium text-on-surface">{{ __('special_intervention.nom_prenom') }}:</span> {{ $previous['nom_prenom'] }}</div>
                            @endif
                            @if (isset($previous['intervention_date']))
                                <div><span class="font-medium text-on-surface">{{ __('special_intervention.intervention_date') }}:</span> {{ $previous['intervention_date'] }}</div>
                            @endif
                            @if (isset($previous['total_amount']))
                                <div><span class="font-medium text-on-surface">{{ __('special_intervention.total_amount') }}:</span> {{ $previous['total_amount'] }} TND</div>
                            @endif
                            @if (isset($previous['interventions']) && is_array($previous['interventions']))
                                <div class="col-span-full truncate">
                                    <span class="font-medium text-on-surface">{{ __('special_intervention.intervention_section') }}:</span>
                                    {{ collect($previous['interventions'])->map(fn ($i) => app()->getLocale() === 'ar' ? ($i['name_ar'] ?? $i['name_fr'] ?? $i['key']) : ($i['name_fr'] ?? $i['name_ar'] ?? $i['key']))->join(', ') }}
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Champs à corriger --}}
                    @if (! empty($fieldsToFix))
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">edit</span>
                            {{ __('special_intervention.correction_fields_section') }}
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @if (in_array('cin', $fieldsToFix))
                                <x-text-input
                                    label="{{ __('special_intervention.cin') }}"
                                    name="cin"
                                    value="{{ old('cin', $specialIntervention->cin) }}"
                                    required
                                    :error="$errors->first('cin')"
                                />
                            @endif
                            @if (in_array('nom_prenom', $fieldsToFix))
                                <x-text-input
                                    label="{{ __('special_intervention.nom_prenom') }}"
                                    name="nom_prenom"
                                    value="{{ old('nom_prenom', $specialIntervention->nom_prenom) }}"
                                    required
                                    :error="$errors->first('nom_prenom')"
                                />
                            @endif
                            @if (in_array('phone', $fieldsToFix))
                                <x-text-input
                                    label="{{ __('special_intervention.phone') }}"
                                    name="phone"
                                    type="tel"
                                    value="{{ old('phone', $specialIntervention->phone) }}"
                                    required
                                    :error="$errors->first('phone')"
                                />
                            @endif
                            @if (in_array('governorate_id', $fieldsToFix))
                                <x-text-input
                                    label="{{ __('special_intervention.code_structure') }}"
                                    name="governorate_id"
                                    type="select"
                                    required
                                    :error="$errors->first('governorate_id')"
                                >
                                    <option value="">{{ __('special_intervention.code_structure_select') }}</option>
                                    @foreach ($gouvernorats as $id => $name)
                                        <option value="{{ $id }}" {{ old('governorate_id', $specialIntervention->governorate_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </x-text-input>
                            @endif
                            @if (in_array('delegation_id', $fieldsToFix))
                                <x-text-input
                                    label="{{ __('special_intervention.delegation') }}"
                                    name="delegation_id"
                                    type="select"
                                    required
                                    :error="$errors->first('delegation_id')"
                                >
                                    <option value="">{{ __('special_intervention.delegation_select') }}</option>
                                    @foreach ($delegations[$specialIntervention->governorate_id] ?? [] as $delId => $delName)
                                        <option value="{{ $delId }}" {{ old('delegation_id', $specialIntervention->delegation_id) == $delId ? 'selected' : '' }}>{{ $delName }}</option>
                                    @endforeach
                                </x-text-input>
                            @endif
                            @if (in_array('intervention_date', $fieldsToFix))
                                <x-text-input
                                    label="{{ __('special_intervention.intervention_date') }}"
                                    name="intervention_date"
                                    type="date"
                                    value="{{ old('intervention_date', $specialIntervention->intervention_date?->format('Y-m-d')) }}"
                                    min="{{ now()->format('Y-m-d') }}"
                                    required
                                    :error="$errors->first('intervention_date')"
                                />
                            @endif
                            @if (in_array('intervention_time', $fieldsToFix))
                                <x-text-input
                                    label="{{ __('special_intervention.intervention_time') }}"
                                    name="intervention_time"
                                    type="time"
                                    value="{{ old('intervention_time', $specialIntervention->intervention_time?->format('H:i')) }}"
                                    :error="$errors->first('intervention_time')"
                                />
                            @endif
                            @if (in_array('notes', $fieldsToFix))
                                <x-text-input
                                    label="{{ __('special_intervention.notes') }}"
                                    name="notes"
                                    type="textarea"
                                    value="{{ old('notes', $specialIntervention->notes) }}"
                                    rows="3"
                                    :error="$errors->first('notes')"
                                />
                            @endif
                        </div>

                        {{-- Interventions à corriger --}}
                        @if (in_array('interventions', $fieldsToFix))
                        <div class="mt-4">
                            <h5 class="text-xs font-bold uppercase tracking-wider text-secondary mb-3">
                                {{ __('special_intervention.intervention_section') }}
                            </h5>
                            <div class="space-y-3" id="correction-interventions-list">
                                @foreach (SpecialIntervention::interventionList() as $key => $interventionItem)
                                    @php
                                        $existing = collect($interventionItems)->firstWhere('key', $key);
                                        $checked  = $existing ? true : false;
                                        $hours    = $existing ? $existing['hours'] : 1;
                                    @endphp
                                    <div class="p-3 rounded border border-surface-dim bg-surface-container-low flex items-start justify-between gap-3 group hover:border-primary/40 transition-colors"
                                         data-key="{{ $key }}"
                                         data-price="{{ $interventionItem['price'] }}">
                                        <div class="flex items-center gap-3 w-full cursor-pointer">
                                            <input type="checkbox"
                                                   name="interventions[{{ $key }}][key]"
                                                   value="{{ $key }}"
                                                   class="mt-0.5 rounded text-primary focus:ring-primary"
                                                   {{ $checked ? 'checked' : '' }}
                                                   data-intervention-key="{{ $key }}">
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-xs font-bold text-on-surface leading-snug">{{ $interventionItem['name'] }}</h4>
                                                <p class="text-[10px] text-secondary mt-0.5">
                                                    {{ __('special_intervention.intervention_unit_price') }}: <span class="font-mono font-bold" dir="ltr">{{ number_format($interventionItem['price'], 0) }}</span> {{ __('special_intervention.currency_tnd') }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label class="flex items-center gap-2 bg-surface-container border border-surface-dim rounded px-2 py-1.5 cursor-pointer">
                                                    <span class="text-[10px] font-bold text-secondary uppercase">{{ __('special_intervention.intervention_qty') }}</span>
                                                    <input type="number"
                                                           name="interventions[{{ $key }}][hours]"
                                                           value="{{ $hours }}"
                                                           min="1"
                                                           max="24"
                                                           class="w-12 text-center text-xs font-mono font-bold text-on-surface border-b border-surface-dim focus:border-primary bg-transparent"
                                                           {{ $checked ? '' : 'disabled' }}>
                                                </label>
                                                <button type="button"
                                                        class="text-xs font-bold px-2 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100 transition-colors remove-intervention"
                                                        {{ $checked ? '' : 'disabled' }} title="{{ __('special_intervention.remove_intervention') }}">
                                                    <span class="material-symbols-outlined text-[14px]" aria-hidden="true">remove_circle</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Documents à corriger --}}
                    @if (! empty($docsToFix))
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">upload_file</span>
                            {{ __('special_intervention.correction_docs_section') }}
                        </h4>

                        <div class="space-y-3">
                            @foreach ($documentTypes as $docType)
                                @php
                                    $doc = $specialIntervention->documents->where('document_type_id', $docType->id)->where('status', '!=', 'archived')->first();
                                    $hasFile = $doc && $doc->status === 'uploaded';
                                @endphp
                                <div class="p-3 rounded border {{ $hasFile ? 'border-emerald-200 bg-emerald-50/60' : 'border-surface-dim bg-surface-container-low' }} flex items-start justify-between gap-3 group hover:border-primary/40 transition-colors">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <span class="material-symbols-outlined {{ $hasFile ? 'text-emerald-600' : 'text-secondary' }} text-lg mt-0.5 shrink-0" aria-hidden="true">
                                            {{ str_contains((string) ($doc->mime_type ?? ''), 'pdf') ? 'picture_as_pdf' : 'description' }}
                                        </span>
                                        <div class="min-w-0">
                                            <h4 class="text-xs font-bold text-on-surface leading-snug">{{ $docType->name }}</h4>
                                            @if ($hasFile)
                                                <div class="flex items-center gap-2 text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5 flex-wrap">
                                                    <span class="font-mono">{{ number_format($doc->size / 1024, 0) }} KB</span>
                                                    <span class="text-emerald-500">•</span>
                                                    <span>{{ $doc->updated_at->format('d/m/Y') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <label class="inline-flex cursor-pointer items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-3 py-2 text-xs font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                            <span class="material-symbols-outlined text-[15px] text-primary" aria-hidden="true">{{ $hasFile ? 'upload_file' : 'description' }}</span>
                                            <span class="upload-label">{{ $hasFile ? __('special_intervention.replace_file') : __('special_intervention.upload_file') }}</span>
                                            <input type="file"
                                                   name="documents[{{ $docType->id }}][]"
                                                   class="sr-only file-input"
                                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                   multiple>
                                        </label>
                                        @if ($hasFile)
                                            <a href="{{ route('special-interventions.documents.download', $doc) }}"
                                               class="w-7 h-7 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center justify-center shrink-0 transition-colors"
                                               title="{{ __('special_intervention.download_label') }}">
                                                <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Fichiers archivés (informations) --}}
                    @if ($archivedFiles && is_array($archivedFiles) && ! empty($archivedFiles))
                    <div class="mt-4">
                        <h5 class="text-xs font-bold uppercase tracking-wider text-secondary mb-2">
                            {{ __('special_intervention.archived_files_title') }}
                        </h5>
                        <div class="space-y-2">
                            @foreach ($archivedFiles as $file)
                                <div class="p-2 bg-surface-container-lowest rounded border border-surface-dim flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="material-symbols-outlined text-outline text-sm" aria-hidden="true">inventory_2</span>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-secondary">{{ $file['type'] ?? $file['file'] ?? __('special_intervention.documents_title') }}</span>
                                            <div class="text-[10px] text-secondary/80 mt-0.5">{{ $file['uploaded_at'] ?? '' }}</div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-secondary font-mono" dir="ltr">{{ $file['size'] ? number_format($file['size'] / 1024, 0) . ' KB' : '' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-3 pt-6 border-t border-surface-dim dark:border-gray-700">
                    <a href="{{ route('special-interventions.show', $specialIntervention) }}"
                       class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-3 rounded transition-colors">
                        <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                        {{ __('special_intervention.back') }}
                    </a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary text-xs font-bold py-2.5 px-6 rounded transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">send</span>
                        {{ __('special_intervention.correction_submit_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const list = document.getElementById('correction-interventions-list');
            if (!list) return;

            list.addEventListener('change', function (e) {
                if (e.target.matches('input[name$="[key]"]')) {
                    const card = e.target.closest('[data-key]');
                    const hoursInput = card.querySelector('input[name$="[hours]"]');
                    const removeBtn = card.querySelector('.remove-intervention');
                    hoursInput.disabled = !e.target.checked;
                    removeBtn.disabled = !e.target.checked;
                    card.classList.toggle('bg-amber-50/40', e.target.checked);
                    card.classList.toggle('border-amber-300', e.target.checked);
                }
            });

            list.addEventListener('click', function (e) {
                const btn = e.target.closest('.remove-intervention');
                if (btn) {
                    const card = btn.closest('[data-key]');
                    const checkbox = card.querySelector('input[name$="[key]"]');
                    const hoursInput = card.querySelector('input[name$="[hours]"]');
                    checkbox.checked = false;
                    hoursInput.disabled = true;
                    btn.disabled = true;
                    card.classList.remove('bg-amber-50/40', 'border-amber-300');
                }
            });
        });
    </script>
</x-app-layout>
