@php
    $totalSteps = 3;
    $step1Lines = [
        $dossier->nom_prenom,
        $dossier->cin,
    ];

    // ── Récapitulatif des étapes pour la barre de progression ──
    $wizardSteps = [
        [
            'state'     => 'done',
            'title'     => __('prevention_request.step_one_title'),
            'data'      => $step1Lines,
            'editUrl'   => route('prevention-requests.step1.edit', $dossier),
            'editLabel' => __('prevention_request.edit_step1'),
        ],
        [
            'state' => 'active',
            'title' => __('prevention_request.step_two_title'),
        ],
        [
            'state' => 'upcoming',
            'title' => __('prevention_request.step_three_title'),
        ],
    ];
@endphp

<x-app-layout>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Barre de progression horizontale (gabarit design/nouvelle.html) --}}
            <x-onpc.dossier-stepper :steps="$wizardSteps" />

            {{-- ===========================================================
                 FORMULAIRE — pleine largeur
                 =========================================================== --}}
            <section class="space-y-6">

                    {{-- Bandeau de statut de l'étape (filet rouge ONPC) --}}
                    <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div>
                            <span class="text-label-caps text-primary uppercase tracking-wider">
                                {{ __('prevention_request.step_of', ['step' => 2, 'total' => $totalSteps]) }}
                            </span>
                            <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('prevention_request.step2_heading') }} — {{ __('prevention_request.step_two_title') }}</h2>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-surface-container text-body-sm text-outline font-medium border border-surface-dim shrink-0 dark:text-gray-400">
                            <span class="text-red-500 font-bold" aria-hidden="true">*</span>
                            {{ __('prevention_request.required_note') }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('prevention-requests.step2.store', $dossier) }}" id="prevention-step2-form"
                          class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        @csrf

                        <div class="p-6 space-y-5">
                            <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                                <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">B</span>
                                <h3 class="text-title-section text-on-surface uppercase tracking-wider">
                                    {{ __('prevention_request.section_building') }}
                                </h3>
                            </div>

                            {{-- ⚠️ Erreurs de validation (résumé en tête) --}}
                            @if ($errors->any())
                                <div class="rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                                    <p class="font-bold">{{ __('prevention_request.create_error_title') }}</p>
                                    <ul class="mt-1.5 list-disc space-y-1 ps-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- ── Type de bâtiment : choix en cartes ── --}}
                            <div>
                                <p class="block text-body-sm font-bold text-on-surface dark:text-gray-200 mb-3" id="type-cards-label">
                                    {{ __('prevention_request.building_type') }}
                                    <span class="text-red-500 ms-0.5" aria-hidden="true">*</span>
                                </p>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4" id="type-cards" role="radiogroup" aria-labelledby="type-cards-label">
                                    @foreach ($buildingTypes as $typeId => $typeName)
                                        <label
                                            class="type-card group relative flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 p-4 text-center text-body-sm font-medium transition
                                                   border-surface-dim bg-surface-container text-on-surface
                                                   hover:border-gov-400 hover:bg-gov-50
                                                   dark:border-gray-600 dark:bg-gray-900/30 dark:text-gray-200 dark:hover:border-gov-500 dark:hover:bg-gov-900/20"
                                            data-type-id="{{ $typeId }}"
                                        >
                                            <input
                                                type="radio"
                                                name="building_type_id"
                                                value="{{ $typeId }}"
                                                class="sr-only"
                                                {{ old('building_type_id') == $typeId ? 'checked' : '' }}
                                                required
                                            >
                                            <span class="type-icon material-symbols-outlined filled text-2xl text-outline" aria-hidden="true">apartment</span>
                                            <span class="type-label">{{ $typeName }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('building_type_id')
                                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- ── Activité — affichée uniquement si le type a des activités ── --}}
                            <div id="activity-section" class="hidden">
                                <div class="my-1 mb-5 pt-5 border-t border-dashed border-surface-dim dark:border-gray-700"></div>

                                <label class="block text-body-sm font-bold text-on-surface dark:text-gray-200 mb-2" for="activity-trigger">
                                    {{ __('prevention_request.building_activity') }}
                                    <span class="text-red-500 ms-0.5" aria-hidden="true">*</span>
                                </label>

                                {{-- Searchable custom select --}}
                                <div class="relative" id="activity-dropdown-wrap">
                                    <input type="hidden" name="building_activity_id" id="building_activity_id" value="{{ old('building_activity_id') }}">

                                    <button
                                        type="button"
                                        id="activity-trigger"
                                        class="flex w-full items-center justify-between rounded border border-surface-dim bg-surface-container-lowest px-3.5 h-11 text-body-md text-on-surface shadow-sm transition focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                        aria-haspopup="listbox"
                                        aria-expanded="false"
                                    >
                                        <span id="activity-trigger-label" class="text-outline dark:text-gray-500">{{ __('prevention_request.activity_select') }}</span>
                                        <svg class="h-4 w-4 text-outline flex-none transition-transform" id="activity-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <div
                                        id="activity-panel"
                                        class="absolute z-50 mt-1 hidden w-full overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                        role="listbox"
                                    >
                                        <div class="border-b border-surface-container-high px-3 py-2 dark:border-gray-700">
                                            <div class="relative">
                                                <span class="material-symbols-outlined absolute start-2.5 top-2.5 text-body-sm text-outline" aria-hidden="true">search</span>
                                                <input
                                                    type="text"
                                                    id="activity-search"
                                                    class="block w-full rounded bg-surface-container py-1.5 ps-9 pe-3 text-body-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                                    placeholder="{{ __('prevention_request.search_placeholder') }}"
                                                    autocomplete="off"
                                                >
                                            </div>
                                        </div>
                                        <ul id="activity-list" class="max-h-60 overflow-y-auto py-1 text-body-sm" role="listbox"></ul>
                                    </div>
                                </div>
                                @error('building_activity_id')
                                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                @enderror

                                {{-- Saisie libre « Autre » --}}
                                <div id="other-wrap" class="mt-4 hidden">
                                    <label for="activity_other" class="block text-body-sm font-bold text-on-surface dark:text-gray-200 mb-2">
                                        {{ __('prevention_request.activity_other_label') }}
                                        <span class="text-red-500 ms-0.5" aria-hidden="true">*</span>
                                    </label>
                                    <input
                                        id="activity_other"
                                        name="activity_other"
                                        type="text"
                                        maxlength="255"
                                        value="{{ old('activity_other') }}"
                                        placeholder="{{ __('prevention_request.activity_other_placeholder') }}"
                                        class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('activity_other') border-red-400 @enderror"
                                    >
                                    @error('activity_other')
                                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Pied de formulaire --}}
                        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim px-6 py-5 dark:border-gray-700">
                            <a href="{{ url('/') }}"
                               class="inline-flex items-center justify-center rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                {{ __('prevention_request.cancel') }}
                            </a>
                            <button
                                type="submit"
                                id="step2-submit-btn"
                                class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                                data-loading-text="{{ __('prevention_request.submitting') }}"
                            >
                                <span id="step2-submit-label">{{ __('prevention_request.save') }}</span>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </div>
                    </form>
            </section>
        </div>
    </div>

    <script>
    (function () {
        /* ── Data injected from server ─────────────────────────────────── */
        var activitiesMap  = @json($activitiesMap);   // { typeId: { has_activities, activities: [{id, name}] } }
        var otherLabel     = @json(__('prevention_request.activity_other'));
        var selectLabel    = @json(__('prevention_request.activity_select'));
        var searchLabel    = @json(__('prevention_request.search_placeholder'));
        var noResults      = @json(__('prevention_request.activity_no_results'));
        var oldActivityId  = @json(old('building_activity_id', ''));
        var oldTypeId      = @json(old('building_type_id', ''));

        /* ── DOM refs ───────────────────────────────────────────────────── */
        var typeCards      = document.querySelectorAll('.type-card');
        var actSection     = document.getElementById('activity-section');
        var actHidden      = document.getElementById('building_activity_id');
        var actTrigger     = document.getElementById('activity-trigger');
        var actTriggerLbl  = document.getElementById('activity-trigger-label');
        var actChevron     = document.getElementById('activity-chevron');
        var actPanel       = document.getElementById('activity-panel');
        var actSearch      = document.getElementById('activity-search');
        var actList        = document.getElementById('activity-list');
        var otherWrap      = document.getElementById('other-wrap');
        var otherInput     = document.getElementById('activity_other');
        var form           = document.getElementById('prevention-step2-form');
        var submitBtn      = document.getElementById('step2-submit-btn');
        var submitLbl      = document.getElementById('step2-submit-label');

        var currentActivities = [];
        var panelOpen = false;

        /* ── Helpers ────────────────────────────────────────────────────── */
        function openPanel() {
            actPanel.classList.remove('hidden');
            actChevron.style.transform = 'rotate(180deg)';
            actTrigger.setAttribute('aria-expanded', 'true');
            actSearch.value = '';
            renderList(currentActivities);
            actSearch.focus();
            panelOpen = true;
        }

        function closePanel() {
            actPanel.classList.add('hidden');
            actChevron.style.transform = '';
            actTrigger.setAttribute('aria-expanded', 'false');
            panelOpen = false;
        }

        function renderList(items) {
            actList.innerHTML = '';
            if (items.length === 0) {
                var li = document.createElement('li');
                li.className = 'px-4 py-2 text-outline text-body-sm italic';
                li.textContent = noResults;
                actList.appendChild(li);
                return;
            }
            items.forEach(function (item) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.setAttribute('data-id', item.id);
                li.setAttribute('data-name', item.name);
                var isSelected = String(item.id) === String(actHidden.value);
                li.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                li.className = 'flex cursor-pointer items-center gap-2 px-4 py-2 text-body-sm transition ' +
                    (isSelected
                        ? 'bg-gov-50 font-semibold text-gov-700 dark:bg-gov-900/30 dark:text-gov-300'
                        : 'text-on-surface hover:bg-surface-container dark:text-gray-200 dark:hover:bg-gray-700');
                if (isSelected) {
                    li.innerHTML = '<svg class="h-3.5 w-3.5 flex-none text-gov-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>' + item.name + '</span>';
                } else {
                    li.innerHTML = '<span class="h-3.5 w-3.5 flex-none"></span><span>' + item.name + '</span>';
                }
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    selectActivity(item.id, item.name);
                });
                actList.appendChild(li);
            });
        }

        function selectActivity(id, name) {
            if (String(id) === '__other__') {
                actHidden.value = '';
                actTriggerLbl.textContent = otherLabel;
                actTriggerLbl.classList.remove('text-outline', 'dark:text-gray-500');
                otherWrap.classList.remove('hidden');
                otherInput.required = true;
            } else {
                actHidden.value = id;
                actTriggerLbl.textContent = name;
                actTriggerLbl.classList.remove('text-outline', 'dark:text-gray-500');
                otherWrap.classList.add('hidden');
                otherInput.required = false;
                otherInput.value = '';
            }
            closePanel();
        }

        function loadType(typeId) {
            var typeData = activitiesMap[typeId];
            if (!typeData || !typeData.has_activities) {
                actSection.classList.add('hidden');
                actHidden.value = '';
                actTriggerLbl.textContent = selectLabel;
                return;
            }
            // Build list with "Other" appended
            currentActivities = typeData.activities.slice();
            currentActivities.push({ id: '__other__', name: otherLabel });

            actSection.classList.remove('hidden');
            // Reset selection
            actHidden.value = '';
            actTriggerLbl.textContent = selectLabel;
            actTriggerLbl.classList.add('text-outline', 'dark:text-gray-500');
            otherWrap.classList.add('hidden');
            otherInput.required = false;
        }

        /* ── Type card selection ─────────────────────────────────────────── */
        function selectTypeCard(typeId) {
            typeCards.forEach(function (card) {
                var isThis  = card.dataset.typeId === String(typeId);
                var radio   = card.querySelector('input[type="radio"]');
                var icon    = card.querySelector('.type-icon');

                if (isThis) {
                    radio.checked = true;
                    card.classList.remove('border-surface-dim', 'bg-surface-container', 'text-on-surface', 'dark:border-gray-600', 'dark:bg-gray-900/30', 'dark:text-gray-200');
                    card.classList.add('border-gov-600', 'bg-gov-50', 'text-gov-800', 'dark:border-gov-400', 'dark:bg-gov-900/30', 'dark:text-gov-300');
                    icon.classList.remove('text-outline');
                    icon.classList.add('text-gov-600', 'dark:text-gov-400');
                } else {
                    card.classList.add('border-surface-dim', 'bg-surface-container', 'text-on-surface', 'dark:border-gray-600', 'dark:bg-gray-900/30', 'dark:text-gray-200');
                    card.classList.remove('border-gov-600', 'bg-gov-50', 'text-gov-800', 'dark:border-gov-400', 'dark:bg-gov-900/30', 'dark:text-gov-300');
                    icon.classList.add('text-outline');
                    icon.classList.remove('text-gov-600', 'dark:text-gov-400');
                }
            });
            loadType(typeId);
        }

        typeCards.forEach(function (card) {
            card.addEventListener('click', function () {
                selectTypeCard(card.dataset.typeId);
            });
        });

        /* ── Trigger toggle ──────────────────────────────────────────────── */
        actTrigger.addEventListener('click', function () {
            if (panelOpen) { closePanel(); } else { openPanel(); }
        });

        /* ── Search filtering ────────────────────────────────────────────── */
        actSearch.addEventListener('input', function () {
            var q = this.value.toLowerCase();
            var filtered = currentActivities.filter(function (a) {
                return a.name.toLowerCase().includes(q);
            });
            renderList(filtered);
        });

        /* ── Close on outside click ──────────────────────────────────────── */
        document.addEventListener('click', function (e) {
            if (panelOpen && !actPanel.contains(e.target) && e.target !== actTrigger && !actTrigger.contains(e.target)) {
                closePanel();
            }
        });

        /* ── Submit lock ─────────────────────────────────────────────────── */
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitLbl.textContent = submitBtn.dataset.loadingText;
        });

        /* ── Restore old state after validation error ────────────────────── */
        if (oldTypeId) {
            selectTypeCard(oldTypeId);  // visually selects the card AND calls loadType

            // Check if "other" was selected — old('activity_other') would have text
            var oldOther = @json(old('activity_other', ''));
            if (oldOther) {
                // Simulate selecting "Other" from the dropdown
                actHidden.value = '';
                actTriggerLbl.textContent = otherLabel;
                actTriggerLbl.classList.remove('text-outline', 'dark:text-gray-500');
                otherWrap.classList.remove('hidden');
                otherInput.required = true;
                // The actual text value is already restored via old('activity_other') in the HTML input
            } else if (oldActivityId && oldActivityId !== '__other__') {
                var oldAct = currentActivities.find(function (a) { return String(a.id) === String(oldActivityId); });
                if (oldAct) {
                    actHidden.value = oldAct.id;
                    actTriggerLbl.textContent = oldAct.name;
                    actTriggerLbl.classList.remove('text-outline', 'dark:text-gray-500');
                }
            }
        }

    }());
    </script>
</x-app-layout>
