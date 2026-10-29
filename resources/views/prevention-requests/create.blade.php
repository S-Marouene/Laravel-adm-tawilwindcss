@php
    $totalSteps = 3;

    $isEditing  = isset($dossier);
    $formAction = $isEditing
        ? route('prevention-requests.step1.update', $dossier)
        : route('prevention-requests.step1.store');
    $valCin   = old('cin',            $isEditing ? $dossier->cin            : '');
    $valNom   = old('nom_prenom',     $isEditing ? $dossier->nom_prenom     : '');
    $valPhone = old('phone',          $isEditing ? $dossier->phone          : '');
    $valGov   = old('code_structure', $isEditing ? $dossier->governorate_id : '');
    $valDelId = old('delegation_id',  $isEditing ? $dossier->delegation_id  : '');
    $valAddress = old('building_address', $isEditing ? $dossier->building_address : '');

    // ── Récapitulatif des étapes pour la barre de progression ──
    $wizardSteps = [
        [
            'state'   => 'active',
            'title'   => __('prevention_request.step_one_title'),
            'editing' => $isEditing,
        ],
        [
            'state'   => $isEditing ? 'done' : 'upcoming',
            'title'   => __('prevention_request.step_two_title'),
            'data'    => $isEditing ? array_filter([
                            $dossier->buildingType?->name,
                            $dossier->buildingActivity?->name ?: $dossier->activity_other,
                        ]) : [],
            'editUrl' => $isEditing ? route('prevention-requests.step2.show', $dossier) : null,
            'editLabel' => __('prevention_request.step_two_title'),
        ],
        [
            'state' => ($isEditing && $dossier->current_step >= 2) ? 'done' : 'upcoming',
            'title' => __('prevention_request.step_three_title'),
        ],
    ];
@endphp

<x-app-layout>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Carte à onglets — votre dossier + détails officiels de la démarche --}}
            <x-onpc.service-details-tabs :drafts="$drafts" />

            {{-- ===========================================================
                 FORMULAIRE COMPLET (stepper + bandeau + formulaire)
                 Masqué par défaut : visible uniquement si l'utilisateur clique
                 sur « Créer un nouveau dossier », reprend un brouillon, ou après
                 une erreur de validation (afin de ne rien perdre).
                 =========================================================== --}}
            @php $showForm = $isEditing || $errors->any() || !empty(old()); @endphp
            <section id="prevention-form-section" class="space-y-6 @unless($showForm) hidden @endunless">

                {{-- Barre de progression horizontale (gabarit design/nouvelle.html) --}}
                <x-onpc.dossier-stepper :steps="$wizardSteps" />

                    {{-- Bandeau de statut de l'étape (filet rouge ONPC) --}}
                    <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div>
                            <span class="text-label-caps text-primary uppercase tracking-wider">
                                {{ __('prevention_request.step_of', ['step' => 1, 'total' => $totalSteps]) }}
                                @if ($isEditing) · {{ __('prevention_request.step_editing') }} @endif
                            </span>
                            <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('prevention_request.step_heading') }} — {{ __('prevention_request.step_one_title') }}</h2>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-surface-container text-body-sm text-outline font-medium border border-surface-dim shrink-0 dark:text-gray-400">
                            <span class="text-red-500 font-bold" aria-hidden="true">*</span>
                            {{ __('prevention_request.required_note') }}
                        </span>
                    </div>

                    <form method="POST" action="{{ $formAction }}" id="prevention-create-form"
                          class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        @csrf
                        @if ($isEditing) @method('PUT') @endif

                        {{-- ⚠️ Erreurs de validation (résumé en tête, WCAG) --}}
                        <div class="p-6 pb-0">
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
                        </div>

                        {{-- =====================================================
                             SECTION A — IDENTITÉ ET COORDONNÉES
                             ===================================================== --}}
                        <div class="p-6 space-y-5">
                            <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                                <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">A</span>
                                <h3 class="text-title-section text-on-surface uppercase tracking-wider">
                                    {{ __('prevention_request.section_identity') }}
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                {{-- CIN --}}
                                <div class="space-y-1.5">
                                    <label for="cin" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                        {{ __('prevention_request.cin') }} <span class="text-primary" aria-hidden="true">*</span>
                                    </label>
                                    <input id="cin" name="cin" type="text" inputmode="numeric" maxlength="20"
                                        value="{{ $valCin }}" placeholder="{{ __('prevention_request.cin_placeholder') }}" required
                                        class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gov-400 dark:focus:ring-gov-400/50 @error('cin') border-red-400 @enderror">
                                    @error('cin')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                                </div>

                                {{-- Nom et prénom --}}
                                <div class="space-y-1.5">
                                    <label for="nom_prenom" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                        {{ __('prevention_request.nom_prenom') }} <span class="text-primary" aria-hidden="true">*</span>
                                    </label>
                                    <input id="nom_prenom" name="nom_prenom" type="text" maxlength="255"
                                        value="{{ $valNom }}" placeholder="{{ __('prevention_request.nom_prenom_placeholder') }}" required
                                        class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gov-400 dark:focus:ring-gov-400/50 @error('nom_prenom') border-red-400 @enderror">
                                    @error('nom_prenom')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                                </div>

                                {{-- Téléphone (pleine largeur sur la même grille) --}}
                                <div class="space-y-1.5 md:col-span-2">
                                    <label for="phone" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                        {{ __('prevention_request.phone') }} <span class="text-primary" aria-hidden="true">*</span>
                                    </label>
                                    <input id="phone" name="phone" type="tel" maxlength="20" inputmode="tel" dir="ltr"
                                        value="{{ $valPhone }}" placeholder="{{ __('prevention_request.phone_placeholder') }}" required
                                        class="num-ltr block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gov-400 dark:focus:ring-gov-400/50 @error('phone') border-red-400 @enderror">
                                    @error('phone')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        {{-- Séparateur --}}
                        <div class="mx-6 border-t border-dashed border-surface-dim dark:border-gray-700"></div>

                        {{-- =====================================================
                             SECTION B — LOCALISATION DE L'INSPECTION
                             ===================================================== --}}
                        <div class="p-6 space-y-5">
                            <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                                <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">B</span>
                                <h3 class="text-title-section text-on-surface uppercase tracking-wider">
                                    {{ __('prevention_request.section_location') }}
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                {{-- ── Gouvernorat (liste déroulante recherche) ── --}}
                                <div class="space-y-1.5" id="gov-field">
                                    <label id="gov-label" class="block text-body-sm font-bold text-on-surface dark:text-gray-200" for="gov-trigger">
                                        {{ __('prevention_request.code_structure') }} <span class="text-primary" aria-hidden="true">*</span>
                                    </label>
                                    <input type="hidden" name="code_structure" id="gov-hidden" value="{{ $valGov }}">
                                    <div class="relative" id="gov-wrap">
                                        <button type="button" id="gov-trigger"
                                            class="flex w-full items-center justify-between rounded border border-surface-dim bg-surface-container-lowest px-3.5 h-11 text-body-md shadow-sm transition focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 @error('code_structure') border-red-400 @enderror"
                                            aria-haspopup="listbox" aria-expanded="false" aria-labelledby="gov-label">
                                            <span id="gov-trigger-label" class="truncate text-outline dark:text-gray-500">{{ __('prevention_request.code_structure_select') }}</span>
                                            <svg class="h-4 w-4 flex-none text-outline transition-transform" id="gov-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div id="gov-panel" class="absolute z-50 mt-1 hidden w-full overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-lg dark:border-gray-700 dark:bg-gray-800" role="listbox">
                                            <div class="border-b border-surface-container-high px-3 py-2 dark:border-gray-700">
                                                <div class="relative">
                                                    <span class="material-symbols-outlined absolute start-2.5 top-2.5 text-body-sm text-outline" aria-hidden="true">search</span>
                                                    <input type="text" id="gov-search" autocomplete="off"
                                                        class="block w-full rounded bg-surface-container py-1.5 ps-9 pe-3 text-body-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                                        placeholder="{{ __('prevention_request.search_placeholder') }}">
                                                </div>
                                            </div>
                                            <ul id="gov-list" class="max-h-56 overflow-y-auto py-1 text-body-sm" role="listbox"></ul>
                                        </div>
                                    </div>
                                    <p id="gov-inline-error" class="text-xs text-red-600 dark:text-red-400 hidden" role="alert">{{ __('prevention_request.select_gov') }}</p>
                                    @error('code_structure')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                                </div>

                                {{-- ── Délégation (liste déroulante recherche) ── --}}
                                <div class="space-y-1.5" id="del-field">
                                    <label id="del-label" class="block text-body-sm font-bold text-on-surface dark:text-gray-200" for="del-trigger">
                                        {{ __('prevention_request.delegation') }} <span class="text-primary" aria-hidden="true">*</span>
                                    </label>
                                    <input type="hidden" name="delegation_id" id="del-hidden" value="{{ $valDelId }}">
                                    <div class="relative" id="del-wrap">
                                        <button type="button" id="del-trigger" disabled
                                            class="flex w-full items-center justify-between rounded border border-surface-dim bg-surface-container-lowest px-3.5 h-11 text-body-md shadow-sm transition focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary transition focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 @error('delegation_id') border-red-400 @enderror"
                                            aria-haspopup="listbox" aria-expanded="false" aria-labelledby="del-label">
                                            <span id="del-trigger-label" class="truncate text-outline dark:text-gray-500">{{ __('prevention_request.delegation_select') }}</span>
                                            <svg class="h-4 w-4 flex-none text-outline transition-transform" id="del-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div id="del-panel" class="absolute z-40 mt-1 hidden w-full overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-lg dark:border-gray-700 dark:bg-gray-800" role="listbox">
                                            <div class="border-b border-surface-container-high px-3 py-2 dark:border-gray-700">
                                                <div class="relative">
                                                    <span class="material-symbols-outlined absolute start-2.5 top-2.5 text-body-sm text-outline" aria-hidden="true">search</span>
                                                    <input type="text" id="del-search" autocomplete="off"
                                                        class="block w-full rounded bg-surface-container py-1.5 ps-9 pe-3 text-body-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                                        placeholder="{{ __('prevention_request.search_placeholder') }}">
                                                </div>
                                            </div>
                                            <ul id="del-list" class="max-h-56 overflow-y-auto py-1 text-body-sm" role="listbox"></ul>
                                        </div>
                                    </div>
                                    <p id="del-inline-error" class="text-xs text-red-600 dark:text-red-400 hidden" role="alert">{{ __('prevention_request.select_del') }}</p>
                                    @error('delegation_id')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            {{-- ── Adresse du bâtiment (lieu du RDV de visite) ── --}}
                            <div class="space-y-1.5">
                                <label for="building_address" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">
                                    {{ __('prevention_request.building_address') }} <span class="text-primary" aria-hidden="true">*</span>
                                </label>
                                <p class="flex items-start gap-1.5 text-[11px] text-secondary">
                                    <span class="material-symbols-outlined text-sm text-outline" aria-hidden="true">info</span>
                                    {{ __('prevention_request.building_address_hint') }}
                                </p>
                                <div class="relative">
                                    <span class="material-symbols-outlined filled absolute start-3 top-3 text-lg text-outline" aria-hidden="true">location_on</span>
                                    <input id="building_address" name="building_address" type="text" maxlength="500"
                                        value="{{ $valAddress }}" placeholder="{{ __('prevention_request.building_address_placeholder') }}" required
                                        class="ps-10 block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gov-400 dark:focus:ring-gov-400/50 @error('building_address') border-red-400 @enderror">
                                </div>
                                @error('building_address')<p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Pied de formulaire --}}
                        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim px-6 py-5 dark:border-gray-700">
                            <a href="{{ $isEditing ? route('prevention-requests.step2.show', $dossier) : url('/') }}"
                               class="inline-flex items-center justify-center rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                {{ __('prevention_request.cancel') }}
                            </a>
                            <button type="submit" id="create-submit-btn"
                                class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                                data-loading-text="{{ __('prevention_request.submitting') }}">
                                <span id="create-submit-label">{{ $isEditing ? __('prevention_request.save_and_continue') : __('prevention_request.continue') }}</span>
                                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </section>
        </div>
    </div>

    <script>
    (function () {
        /* ── Révélation du formulaire (bouton « Créer un nouveau dossier ») ── */
        var formSection = document.getElementById('prevention-form-section');
        Array.prototype.slice.call(document.querySelectorAll('[data-start-new-dossier]')).forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!formSection) return;
                formSection.classList.remove('hidden');
                formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                var firstField = document.getElementById('cin');
                if (firstField) firstField.focus({ preventScroll: true });
            });
        });

        /* ── Data ────────────────────────────────────────────────────────── */
        var gouvernorats   = @json($gouvernorats);   // { id: "name", ... }
        var delegationsMap = @json($delegations);    // { govId: [{id,name}, ...] }
        var initGovId      = @json($valGov);         // pre-selected gov id (or '')
        var initDelId      = @json($valDelId);       // pre-selected del id (or '')
        var noResults      = @json(__('prevention_request.activity_no_results'));
        var govSelectLbl   = @json(__('prevention_request.code_structure_select'));
        var delSelectLbl   = @json(__('prevention_request.delegation_select'));
        var delEmptyLbl    = @json(__('prevention_request.delegation_empty'));

        /* ── DOM ─────────────────────────────────────────────────────────── */
        var govHidden = document.getElementById('gov-hidden');
        var govTrigger= document.getElementById('gov-trigger');
        var govTrigLbl= document.getElementById('gov-trigger-label');
        var govChevron= document.getElementById('gov-chevron');
        var govPanel  = document.getElementById('gov-panel');
        var govSearch = document.getElementById('gov-search');
        var govList   = document.getElementById('gov-list');
        var govError  = document.getElementById('gov-inline-error');

        var delHidden = document.getElementById('del-hidden');
        var delTrigger= document.getElementById('del-trigger');
        var delTrigLbl= document.getElementById('del-trigger-label');
        var delChevron= document.getElementById('del-chevron');
        var delPanel  = document.getElementById('del-panel');
        var delSearch = document.getElementById('del-search');
        var delList   = document.getElementById('del-list');
        var delError  = document.getElementById('del-inline-error');

        var form      = document.getElementById('prevention-create-form');
        var submitBtn = document.getElementById('create-submit-btn');
        var submitLbl = document.getElementById('create-submit-label');

        var govOpen = false, delOpen = false;
        var govItems = []; // [{id, name}]
        var delItems = []; // [{id, name}]

        /* ── Generic helpers ─────────────────────────────────────────────── */
        function buildItems(source) {
            // source can be object {id:name} or array [{id,name}]
            if (Array.isArray(source)) return source;
            return Object.keys(source).map(function(k) {
                return { id: k, name: source[k] };
            });
        }

        function renderList(ul, items, selectedId, onSelect) {
            ul.innerHTML = '';
            if (!items.length) {
                var li = document.createElement('li');
                li.className = 'px-4 py-2 text-outline text-body-sm italic';
                li.textContent = noResults;
                ul.appendChild(li);
                return;
            }
            items.forEach(function(item) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                var isSel = String(item.id) === String(selectedId);
                li.setAttribute('aria-selected', isSel ? 'true' : 'false');
                li.className = 'flex cursor-pointer items-center gap-2 px-4 py-2 text-body-sm transition ' +
                    (isSel
                        ? 'bg-gov-50 font-semibold text-gov-700 dark:bg-gov-900/30 dark:text-gov-300'
                        : 'text-on-surface hover:bg-surface-container dark:text-gray-200 dark:hover:bg-gray-700');
                li.innerHTML = isSel
                    ? '<svg class="h-3.5 w-3.5 flex-none text-gov-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>' + item.name + '</span>'
                    : '<span class="h-3.5 w-3.5 flex-none"></span><span>' + item.name + '</span>';
                li.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    onSelect(item);
                });
                ul.appendChild(li);
            });
        }

        function openDropdown(panel, chevron, search, openFlagSetter) {
            panel.classList.remove('hidden');
            chevron.style.transform = 'rotate(180deg)';
            search.value = '';
            openFlagSetter(true);
            search.focus();
        }

        function closeDropdown(panel, chevron, openFlagSetter) {
            panel.classList.add('hidden');
            chevron.style.transform = '';
            openFlagSetter(false);
        }

        /* ── Gouvernorat ─────────────────────────────────────────────────── */
        govItems = buildItems(gouvernorats);

        function renderGovList(filter) {
            var q = (filter || '').toLowerCase();
            var filtered = q ? govItems.filter(function(i){ return i.name.toLowerCase().includes(q); }) : govItems;
            renderList(govList, filtered, govHidden.value, selectGov);
        }

        function selectGov(item) {
            govHidden.value  = item.id;
            govTrigLbl.textContent = item.name;
            govTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
            govTrigLbl.classList.add('text-on-surface', 'dark:text-gray-100');
            if (govError) govError.classList.add('hidden');
            closeDropdown(govPanel, govChevron, function(v){ govOpen = v; });
            // Reset delegation
            delHidden.value = '';
            delTrigLbl.textContent = delSelectLbl;
            delTrigLbl.classList.add('text-outline', 'dark:text-gray-500');
            delTrigLbl.classList.remove('text-on-surface', 'dark:text-gray-100');
            delTrigger.disabled = false;
            loadDelegations(item.id, '');
        }

        govTrigger.addEventListener('click', function() {
            if (govOpen) { closeDropdown(govPanel, govChevron, function(v){ govOpen = v; }); }
            else { openDropdown(govPanel, govChevron, govSearch, function(v){ govOpen = v; }); renderGovList(''); }
        });
        govSearch.addEventListener('input', function() { renderGovList(this.value); });

        /* ── Délégation ──────────────────────────────────────────────────── */
        function loadDelegations(govId, selectedDelId) {
            delItems = delegationsMap[govId] || [];
            renderDelList('', selectedDelId);
            delTrigger.disabled = delItems.length === 0;
        }

        function renderDelList(filter, selectedDelId) {
            var sid = selectedDelId !== undefined ? selectedDelId : delHidden.value;
            var q   = (filter || '').toLowerCase();
            var filtered = q ? delItems.filter(function(i){ return i.name.toLowerCase().includes(q); }) : delItems;
            renderList(delList, filtered, sid, selectDel);
        }

        function selectDel(item) {
            delHidden.value = item.id;
            delTrigLbl.textContent = item.name;
            delTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
            delTrigLbl.classList.add('text-on-surface', 'dark:text-gray-100');
            if (delError) delError.classList.add('hidden');
            closeDropdown(delPanel, delChevron, function(v){ delOpen = v; });
        }

        delTrigger.addEventListener('click', function() {
            if (this.disabled) return;
            if (delOpen) { closeDropdown(delPanel, delChevron, function(v){ delOpen = v; }); }
            else { openDropdown(delPanel, delChevron, delSearch, function(v){ delOpen = v; }); renderDelList('', delHidden.value); }
        });
        delSearch.addEventListener('input', function() { renderDelList(this.value); });

        /* ── Close on outside click ──────────────────────────────────────── */
        document.addEventListener('click', function(e) {
            if (govOpen && !govPanel.contains(e.target) && !govTrigger.contains(e.target)) {
                closeDropdown(govPanel, govChevron, function(v){ govOpen = v; });
            }
            if (delOpen && !delPanel.contains(e.target) && !delTrigger.contains(e.target)) {
                closeDropdown(delPanel, delChevron, function(v){ delOpen = v; });
            }
        });

        /* ── Restore state on page load (edit mode / validation error) ───── */
        if (initGovId) {
            var govItem = govItems.find(function(i){ return String(i.id) === String(initGovId); });
            if (govItem) {
                govHidden.value = govItem.id;
                govTrigLbl.textContent = govItem.name;
                govTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
                govTrigLbl.classList.add('text-on-surface', 'dark:text-gray-100');
                delTrigger.disabled = false;
                loadDelegations(govItem.id, initDelId);
                // Also restore the delegation label
                if (initDelId) {
                    var delItem = (delegationsMap[govItem.id] || []).find(function(d){ return String(d.id) === String(initDelId); });
                    if (delItem) {
                        delHidden.value = delItem.id;
                        delTrigLbl.textContent = delItem.name;
                        delTrigLbl.classList.remove('text-outline', 'dark:text-gray-500');
                        delTrigLbl.classList.add('text-on-surface', 'dark:text-gray-100');
                    }
                }
            }
        }

        /* ── Submit lock + inline validation (no blocking dialog) ────────── */
        form.addEventListener('submit', function() {
            var valid = true;

            // Gouvernorat
            if (!govHidden.value) {
                if (govError) govError.classList.remove('hidden');
                govTrigger.classList.add('border-red-400');
                valid = false;
            }
            // Délégation
            if (!delHidden.value) {
                if (delError) delError.classList.remove('hidden');
                delTrigger.classList.add('border-red-400');
                valid = false;
            }
            // Focus the first offending dropdown, announce inline (WCAG)
            if (!valid) {
                if (!govHidden.value) {
                    govTrigger.focus();
                } else {
                    delTrigger.focus();
                }
                return; // stop native submit
            }

            submitBtn.disabled = true;
            submitLbl.textContent = submitBtn.dataset.loadingText;
        });

    }());
    </script>
</x-app-layout>
