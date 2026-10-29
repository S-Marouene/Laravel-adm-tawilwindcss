<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface dark:text-gray-100">{{ __('prevention_request.create_title') }}</h2>
                <p class="mt-0.5 text-sm text-secondary dark:text-gray-400">{{ __('prevention_request.create_subtitle') }}</p>
            </div>
            <a href="{{ route('prevention-requests.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2 text-sm font-medium text-on-surface transition hover:bg-surface-container dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                {{ __('prevention_request.back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[280px,1fr]">

                {{-- Step sidebar --}}
                <aside class="rounded-2xl border border-surface-dim bg-surface-container-lowest p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <ol class="space-y-0">
                        {{-- Step 1 — done --}}
                        <li class="flex items-start gap-3">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <div class="min-w-0 flex-1 pb-3">
                                <p class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ __('prevention_request.step_one_title') }}</p>
                                <p class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ __('prevention_request.step_done') }}</p>
                                {{-- Step 1 data --}}
                                <div class="mt-2 rounded-lg bg-surface-container px-3 py-2 text-xs space-y-1 dark:bg-gray-900/40">
                                    <p class="font-medium text-on-surface dark:text-gray-200">{{ $dossier->nom_prenom }}</p>
                                    <p class="text-secondary dark:text-gray-400">{{ $dossier->cin }}</p>
                                    @if($dossier->phone)
                                        <p class="text-secondary dark:text-gray-400"><span class="num-ltr">{{ $dossier->phone }}</span></p>
                                    @endif
                                    <p class="text-secondary dark:text-gray-400">{{ $dossier->governorate?->name }}</p>
                                    <p class="text-secondary dark:text-gray-400">{{ $dossier->delegation?->name }}</p>
                                </div>
                                <a href="{{ route('prevention-requests.step1.edit', $dossier) }}"
                                   class="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-gov-200 bg-surface-container-lowest px-3 py-1.5 text-xs font-semibold text-gov-700 transition hover:bg-gov-50 dark:border-gov-700 dark:bg-gray-800 dark:text-gov-400 dark:hover:bg-gov-900/20">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    {{ __('prevention_request.edit_step1') }}
                                </a>
                            </div>
                        </li>
                        {{-- Connector (green = completed) --}}
                        <li class="flex"><span class="ms-[17px] w-0.5 h-6 bg-emerald-300 dark:bg-emerald-700"></span></li>
                        {{-- Step 2 — active --}}
                        <li class="flex items-start gap-3">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-gov-600 text-sm font-bold text-white ring-4 ring-gov-100 dark:ring-gov-900/40">2</span>
                            <div class="min-w-0 pt-1">
                                <p class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ __('prevention_request.step_two_title') }}</p>
                                <p class="mt-0.5 text-xs text-gov-600 dark:text-gov-400 font-medium">{{ __('prevention_request.step_status') }}</p>
                            </div>
                        </li>
                        {{-- Connector (gray = not yet) --}}
                        <li class="flex"><span class="ms-[17px] w-0.5 h-6 bg-gray-200 dark:bg-gray-700"></span></li>
                        {{-- Step 3 — upcoming --}}
                        <li class="flex items-start gap-3 opacity-40">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full border-2 border-surface-dim text-sm font-bold text-outline dark:border-gray-600 dark:text-gray-500">3</span>
                            <div class="min-w-0 pt-1">
                                <p class="text-sm font-semibold text-secondary dark:text-gray-400">{{ __('prevention_request.step_three_title') }}</p>
                            </div>
                        </li>
                    </ol>
                </aside>

                {{-- Form card --}}
                <section class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-surface-dim px-6 py-5 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-on-surface dark:text-gray-100">{{ __('prevention_request.step2_heading') }}</h3>
                        <p class="mt-1 text-sm text-secondary dark:text-gray-400">{{ __('prevention_request.step2_description') }}</p>
                    </div>

                    <form method="POST" action="{{ route('prevention-requests.step2.store', $dossier) }}" class="px-6 py-6" id="prevention-step2-form">
                        @csrf

                        @if ($errors->any())
                            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                                <p class="font-semibold">{{ __('prevention_request.create_error_title') }}</p>
                                <ul class="mt-2 list-disc space-y-1 ps-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Building type --}}
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-on-surface dark:text-gray-200 mb-3">
                                {{ __('prevention_request.building_type') }}
                                <span class="text-red-500 ms-0.5" aria-hidden="true">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4" id="type-cards">
                                @foreach ($buildingTypes as $typeId => $typeName)
                                    <label
                                        class="type-card group relative flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 p-4 text-center text-sm font-medium transition
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
                                        <svg class="type-icon h-7 w-7 text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span class="type-label">{{ $typeName }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('building_type_id')
                                <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Activity section — shown only when type has_activities --}}
                        <div id="activity-section" class="hidden mb-6">
                            <div class="my-1 border-t border-dashed border-surface-dim dark:border-gray-700 mb-5"></div>
                            <label class="block text-sm font-semibold text-on-surface dark:text-gray-200 mb-2">
                                {{ __('prevention_request.building_activity') }}
                                <span class="text-red-500 ms-0.5" aria-hidden="true">*</span>
                            </label>

                            {{-- Searchable custom select --}}
                            <div class="relative" id="activity-dropdown-wrap">
                                {{-- Hidden real input --}}
                                <input type="hidden" name="building_activity_id" id="building_activity_id" value="{{ old('building_activity_id') }}">

                                {{-- Trigger button --}}
                                <button
                                    type="button"
                                    id="activity-trigger"
                                    class="flex w-full items-center justify-between rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface shadow-sm focus:border-gov-500 focus:outline-none focus:ring-1 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                >
                                    <span id="activity-trigger-label" class="text-outline dark:text-gray-500">{{ __('prevention_request.activity_select') }}</span>
                                    <svg class="h-4 w-4 text-outline flex-none transition-transform" id="activity-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                {{-- Dropdown panel --}}
                                <div
                                    id="activity-panel"
                                    class="absolute z-50 mt-1 hidden w-full overflow-hidden rounded-xl border border-surface-dim bg-surface-container-lowest shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                    role="listbox"
                                >
                                    {{-- Search box --}}
                                    <div class="border-b border-surface-container-high px-3 py-2 dark:border-gray-700">
                                        <div class="relative">
                                            <svg class="absolute start-2.5 top-2.5 h-3.5 w-3.5 text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                            <input
                                                type="text"
                                                id="activity-search"
                                                class="block w-full rounded-md border-0 bg-surface-container py-1.5 ps-8 pe-3 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-gov-400 dark:bg-gray-700 dark:text-gray-200"
                                                placeholder="{{ __('prevention_request.search_placeholder') }}"
                                                autocomplete="off"
                                            >
                                        </div>
                                    </div>
                                    {{-- Options list --}}
                                    <ul id="activity-list" class="max-h-60 overflow-y-auto py-1 text-sm" role="listbox"></ul>
                                </div>
                            </div>
                            @error('building_activity_id')
                                <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror

                            {{-- "Other" free-text --}}
                            <div id="other-wrap" class="mt-4 hidden">
                                <label for="activity_other" class="block text-sm font-semibold text-on-surface dark:text-gray-200 mb-2">
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
                                    class="block w-full rounded-lg border-surface-dim text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('activity_other') border-red-400 @enderror"
                                >
                                @error('activity_other')
                                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="mt-8 flex items-center justify-between gap-3 border-t border-surface-dim pt-5 dark:border-gray-700">
                            <a href="{{ route('prevention-requests.index') }}" class="inline-flex items-center justify-center rounded-lg border border-surface-dim bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                {{ __('prevention_request.cancel') }}
                            </a>
                            <button
                                type="submit"
                                id="step2-submit-btn"
                                class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
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
                li.className = 'px-4 py-2 text-outline text-sm italic';
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
                li.className = 'flex cursor-pointer items-center gap-2 px-4 py-2 text-sm transition ' +
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
