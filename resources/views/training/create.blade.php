@php
    $isEditing = isset($trainingRequest);
    $tr        = $isEditing ? $trainingRequest : null;

    $formAction = $isEditing
        ? route('training.step1.update', $tr)
        : route('training.step1.store');

    $valGov    = old('governorate_id',  $tr?->governorate_id ?? '');
    $valDelId  = old('delegation_id',   $tr?->delegation_id  ?? '');
    $valType   = old('training_type',   $tr?->training_type  ?? '');

    $wizardSteps = [
        ['state' => 'active',  'title' => __('training.step_one_title'), 'editing' => $isEditing],
        ['state' => 'upcoming', 'title' => __('training.step_two_title')],
        ['state' => 'upcoming', 'title' => __('training.step_three_title')],
    ];

    $showForm = $isEditing || $errors->any() || !empty(old());
@endphp

<x-app-layout>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            @unless ($isEditing)
                <x-onpc.training-service-details-tabs :drafts="$drafts ?? collect()" />
            @endunless

            <section id="training-form-section" class="space-y-6 @unless($showForm) hidden @endunless">

                <x-onpc.dossier-stepper :steps="$wizardSteps" />

                <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div>
                        <span class="text-label-caps text-primary uppercase tracking-wider">
                            {{ __('training.step_of', ['step' => 1, 'total' => 3]) }}
                            @if ($isEditing) · {{ __('training.step_editing') }} @endif
                        </span>
                        <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('training.step_heading') }} — {{ __('training.step_one_title') }}</h2>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-surface-container text-body-sm text-outline font-medium border border-surface-dim shrink-0">
                        <span class="text-red-500 font-bold" aria-hidden="true">*</span>
                        {{ __('training.required_note') }}
                    </span>
                </div>

                <form method="POST" action="{{ $formAction }}" id="training-create-form"
                      class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    @csrf
                    @if ($isEditing) @method('PUT') @endif

                    <div class="p-6 pb-0">
                        @if ($errors->any())
                            <div class="rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800 dark:bg-red-900/30 dark:text-red-200 mb-4" role="alert">
                                <p class="font-bold">{{ __('training.create_error_title') }}</p>
                                <ul class="mt-1.5 list-disc space-y-1 ps-5">
                                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    {{-- ═══ SECTION 1 — DIRECTION RÉGIONALE / DÉLÉGATION ═══ --}}
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">1</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">{{ __('training.code_structure') }}</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Governorate searchable dropdown --}}
                            <div class="space-y-1.5">
                                <label id="gov-label" class="block text-body-sm font-bold text-on-surface dark:text-gray-200" for="gov-trigger">{{ __('training.code_structure') }} <span class="text-primary">*</span></label>
                                <input type="hidden" name="governorate_id" id="gov-hidden" value="{{ $valGov }}">
                                <div class="relative" id="gov-wrap">
                                    <button type="button" id="gov-trigger"
                                        class="flex w-full items-center justify-between rounded border border-surface-dim bg-surface-container-lowest px-3.5 h-11 text-body-md shadow-sm transition focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 @error('governorate_id') border-red-400 @enderror"
                                        aria-haspopup="listbox" aria-expanded="false">
                                        <span id="gov-trigger-label" class="truncate text-outline dark:text-gray-500">{{ __('training.code_structure_select') }}</span>
                                        <svg class="h-4 w-4 flex-none text-outline transition-transform" id="gov-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div id="gov-panel" class="absolute z-50 mt-1 hidden w-full overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                        <div class="border-b border-surface-container-high px-3 py-2 dark:border-gray-700">
                                            <div class="relative">
                                                <span class="material-symbols-outlined absolute start-2.5 top-2.5 text-body-sm text-outline" aria-hidden="true">search</span>
                                                <input type="text" id="gov-search" autocomplete="off" class="block w-full rounded bg-surface-container py-1.5 ps-9 pe-3 text-body-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:outline-none dark:bg-gray-700 dark:text-gray-200" placeholder="{{ __('training.search_placeholder') }}">
                                            </div>
                                        </div>
                                        <ul id="gov-list" role="listbox" class="max-h-56 overflow-y-auto py-1"></ul>
                                    </div>
                                </div>
                                @error('governorate_id')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>

                            {{-- Delegation searchable dropdown --}}
                            <div class="space-y-1.5">
                                <label id="del-label" class="block text-body-sm font-bold text-on-surface dark:text-gray-200" for="del-trigger">{{ __('training.delegation') }} <span class="text-primary">*</span></label>
                                <input type="hidden" name="delegation_id" id="del-hidden" value="{{ $valDelId }}">
                                <div class="relative" id="del-wrap">
                                    <button type="button" id="del-trigger" disabled
                                        class="flex w-full items-center justify-between rounded border border-surface-dim bg-surface-container-lowest px-3.5 h-11 text-body-md shadow-sm transition focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 disabled:opacity-60 @error('delegation_id') border-red-400 @enderror"
                                        aria-haspopup="listbox" aria-expanded="false">
                                        <span id="del-trigger-label" class="truncate text-outline dark:text-gray-500">{{ __('training.delegation_select') }}</span>
                                        <svg class="h-4 w-4 flex-none text-outline transition-transform" id="del-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div id="del-panel" class="absolute z-50 mt-1 hidden w-full overflow-hidden rounded border border-surface-dim bg-surface-container-lowest shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                        <div class="border-b border-surface-container-high px-3 py-2 dark:border-gray-700">
                                            <div class="relative">
                                                <span class="material-symbols-outlined absolute start-2.5 top-2.5 text-body-sm text-outline" aria-hidden="true">search</span>
                                                <input type="text" id="del-search" autocomplete="off" class="block w-full rounded bg-surface-container py-1.5 ps-9 pe-3 text-body-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:outline-none dark:bg-gray-700 dark:text-gray-200" placeholder="{{ __('training.search_placeholder') }}">
                                            </div>
                                        </div>
                                        <ul id="del-list" role="listbox" class="max-h-56 overflow-y-auto py-1"></ul>
                                    </div>
                                </div>
                                @error('delegation_id')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <p id="region-visavis" class="hidden flex items-start gap-2 rounded border border-primary/30 bg-primary/5 p-3 text-body-sm text-on-surface dark:border-primary/40 dark:bg-primary/10 dark:text-gray-200">
                            <span class="material-symbols-outlined text-primary text-[18px] mt-px shrink-0" aria-hidden="true">forward_to_inbox</span>
                            <span>{{ __('training.region_visavis_hint') }}</span>
                        </p>
                    </div>

                    <div class="mx-6 border-t border-dashed border-surface-dim dark:border-gray-700"></div>

                    {{-- ═══ SECTION 2 — TYPE DE FORMATION (locked until location is set) ═══ --}}
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">2</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">{{ __('training.section_training') }}</h3>
                        </div>

                        <p id="formation-locked-hint" class="flex items-start gap-2 rounded border border-surface-dim bg-surface-container-low p-3 text-body-sm text-outline dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
                            <span class="material-symbols-outlined text-outline text-[18px] mt-px shrink-0" aria-hidden="true">location_on</span>
                            <span>{{ __('training.formation_locked_hint') }}</span>
                        </p>

                        <div id="formation-fields" class="space-y-5 opacity-60 pointer-events-none">
                            <div class="space-y-1.5">
                                <label for="training_type" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ __('training.training_type') }} <span class="text-primary">*</span></label>
                                <select id="training_type" name="training_type" required disabled
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 disabled:cursor-not-allowed @error('training_type') border-red-400 @enderror">
                                    <option value="" disabled @selected(! $valType)>{{ __('training.training_type_select') }}</option>
                                    @foreach (\App\Models\TrainingRequest::TYPES as $key => $data)
                                        <option value="{{ $key }}" @selected($valType === $key)
                                            data-price="{{ $data['price'] }}" data-weeks="{{ $data['weeks'] }}">
                                            {{ __('training.type_' . $key) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('training_type')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>

                            {{-- Centralized formations (prévention 1/2) — centrale + session picker --}}
                            <div id="centralized-box" class="hidden space-y-4 rounded border border-primary/30 bg-primary/5 p-4 dark:border-primary/40 dark:bg-primary/10">
                                <div class="flex items-start gap-3">
                                    <span class="material-symbols-outlined text-primary text-[20px] mt-px shrink-0" aria-hidden="true">hub</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-primary uppercase tracking-wider mb-1">{{ __('training.centralized_title') }}</p>
                                        <p class="text-body-sm text-on-surface dark:text-gray-200 leading-relaxed">{{ __('training.centralized_hint_intro') }}</p>
                                    </div>
                                </div>

                                {{-- Planned sessions of the selected type --}}
                                <div id="planned-sessions-list" class="space-y-2"></div>

                                {{-- Wait list notice --}}
                                <div id="waitlist-notice" class="hidden flex items-start gap-3 rounded border border-amber-300 bg-amber-50 p-3 dark:border-amber-700/50 dark:bg-amber-900/20">
                                    <span class="material-symbols-outlined text-amber-600 text-[18px] mt-px shrink-0" aria-hidden="true">hourglass_top</span>
                                    <p class="text-body-sm text-amber-900 dark:text-amber-200 leading-relaxed">{{ __('training.waitlist_notice') }}</p>
                                </div>
                                @error('planned_session_id')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim px-6 py-5 dark:border-gray-700">
                        <a href="{{ $isEditing ? route('training.index') : route('training.index') }}"
                           class="inline-flex items-center justify-center rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                            {{ __('training.cancel') }}
                        </a>
                        <button type="submit" id="training-submit-btn"
                            class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-70"
                            data-loading-text="{{ __('training.submitting') }}">
                            <span id="training-submit-label">{{ $isEditing ? __('training.save_and_continue') : __('training.continue') }}</span>
                            <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
    (function () {
        /* ── Reveal form ── */
        var formSection = document.getElementById('training-form-section');
        document.querySelectorAll('[data-start-new-training]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!formSection) return;
                formSection.classList.remove('hidden');
                formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                setTimeout(function () { var s = document.getElementById('gov-trigger'); if (s) s.focus({ preventScroll: true }); }, 350);
            });
        });

        /* ── Governorate/Delegation cascade ── */
        var gouvernorats   = @json($gouvernorats);
        var delegationsMap = @json($delegations);
        var initGovId      = @json((string) $valGov);
        var initDelId      = @json((string) $valDelId);
        var noResults      = @json(__('training.activity_no_results'));
        var govSelectLbl   = @json(__('training.code_structure_select'));
        var delSelectLbl   = @json(__('training.delegation_select'));

        var govHidden  = document.getElementById('gov-hidden');
        var govTrigger = document.getElementById('gov-trigger');
        var govTrigLbl = document.getElementById('gov-trigger-label');
        var govChevron = document.getElementById('gov-chevron');
        var govPanel   = document.getElementById('gov-panel');
        var govSearch  = document.getElementById('gov-search');
        var govList    = document.getElementById('gov-list');
        var delHidden  = document.getElementById('del-hidden');
        var delTrigger = document.getElementById('del-trigger');
        var delTrigLbl = document.getElementById('del-trigger-label');
        var delChevron = document.getElementById('del-chevron');
        var delPanel   = document.getElementById('del-panel');
        var delSearch  = document.getElementById('del-search');
        var delList    = document.getElementById('del-list');
        var submitBtn  = document.getElementById('training-submit-btn');
        var submitLbl  = document.getElementById('training-submit-label');
        var typeSelect      = document.getElementById('training_type');
        var formationFields = document.getElementById('formation-fields');
        var formationLocked = document.getElementById('formation-locked-hint');
        var govOpen = false, delOpen = false, govItems = [], delItems = [];

        // Step 2 stays locked until both location fields have a value.
        function syncFormationAvailability() {
            var ready = Boolean(govHidden.value) && Boolean(delHidden.value);
            if (typeSelect) typeSelect.disabled = !ready;
            if (formationFields) {
                formationFields.classList.toggle('opacity-60', !ready);
                formationFields.classList.toggle('pointer-events-none', !ready);
            }
            if (formationLocked) formationLocked.classList.toggle('hidden', ready);
        }

        function buildItems(src) { return Array.isArray(src) ? src : Object.keys(src).map(function(k){ return {id:k,name:src[k]}; }); }
        function renderList(ul, items, selId, onSel) {
            ul.innerHTML = '';
            if (!items.length) { var li=document.createElement('li'); li.className='px-4 py-2 text-outline text-body-sm italic'; li.textContent=noResults; ul.appendChild(li); return; }
            items.forEach(function(item){
                var li=document.createElement('li'); li.setAttribute('role','option');
                var isSel=String(item.id)===String(selId); li.setAttribute('aria-selected',isSel?'true':'false');
                li.className='flex cursor-pointer items-center gap-2 px-4 py-2 text-body-sm transition '+(isSel?'bg-gov-50 font-semibold text-gov-700 dark:bg-gov-900/30 dark:text-gov-300':'text-on-surface hover:bg-surface-container dark:text-gray-200 dark:hover:bg-gray-700');
                li.innerHTML=isSel?'<svg class="h-3.5 w-3.5 flex-none text-gov-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>'+item.name+'</span>':'<span class="h-3.5 w-3.5 flex-none"></span><span>'+item.name+'</span>';
                li.addEventListener('mousedown',function(e){e.preventDefault();onSel(item);});
                ul.appendChild(li);
            });
        }
        function openPanel(p,ch,s,setO){p.classList.remove('hidden');ch.style.transform='rotate(180deg)';s.value='';setO(true);s.focus();}
        function closePanel(p,ch,setO){p.classList.add('hidden');ch.style.transform='';setO(false);}

        govItems=buildItems(gouvernorats);
        function renderGov(f){ var items=f?govItems.filter(function(i){return i.name.toLowerCase().includes(f.toLowerCase());}):govItems; renderList(govList,items,govHidden.value,function(item){govHidden.value=item.id;govTrigLbl.textContent=item.name;govTrigLbl.classList.remove('text-outline','dark:text-gray-500');closePanel(govPanel,govChevron,function(v){govOpen=v;});loadDel(item.id);}); }
        govTrigger.addEventListener('click',function(){if(govOpen){closePanel(govPanel,govChevron,function(v){govOpen=v;});return;}openPanel(govPanel,govChevron,govSearch,function(v){govOpen=v;});renderGov('');});
        govSearch.addEventListener('input',function(){renderGov(this.value);});
        document.addEventListener('click',function(e){if(govOpen&&!document.getElementById('gov-wrap').contains(e.target))closePanel(govPanel,govChevron,function(v){govOpen=v;});});

        function loadDel(gId){var list=delegationsMap[String(gId)]||[];delItems=Array.isArray(list)?list:buildItems(list);delHidden.value='';delTrigLbl.textContent=delSelectLbl;delTrigLbl.classList.add('text-outline','dark:text-gray-500');delTrigger.disabled=(delItems.length===0);syncFormationAvailability();}
        function renderDel(f){ var items=f?delItems.filter(function(i){return i.name.toLowerCase().includes(f.toLowerCase());}):delItems;renderList(delList,items,delHidden.value,function(item){delHidden.value=item.id;delTrigLbl.textContent=item.name;delTrigLbl.classList.remove('text-outline','dark:text-gray-500');closePanel(delPanel,delChevron,function(v){delOpen=v;});syncFormationAvailability();}); }
        delTrigger.addEventListener('click',function(){if(delOpen){closePanel(delPanel,delChevron,function(v){delOpen=v;});return;}openPanel(delPanel,delChevron,delSearch,function(v){delOpen=v;});renderDel('');});
        delSearch.addEventListener('input',function(){renderDel(this.value);});
        document.addEventListener('click',function(e){if(delOpen&&!document.getElementById('del-wrap').contains(e.target))closePanel(delPanel,delChevron,function(v){delOpen=v;});});

        (function init(){
            if(initGovId){var gov=govItems.find(function(i){return String(i.id)===String(initGovId);});if(gov){govHidden.value=gov.id;govTrigLbl.textContent=gov.name;govTrigLbl.classList.remove('text-outline','dark:text-gray-500');loadDel(gov.id);if(initDelId){var del=delItems.find(function(i){return String(i.id)===String(initDelId);});if(del){delHidden.value=del.id;delTrigLbl.textContent=del.name;delTrigLbl.classList.remove('text-outline','dark:text-gray-500');}}}}
            syncFormationAvailability();
        })();

        /* ── Type select: centralized hint & session picker ── */
        var CENTRALIZED    = @json(\App\Models\TrainingRequest::CENTRALIZED_TYPES);
        var PLANNED         = @json($plannedSessions ?? []);
        var initialSession  = @json((string) ($tr?->planned_session_id ?? old('planned_session_id') ?? ''));
        var lblPlace        = @json(__('training.planned_place'));
        var lblDate         = @json(__('training.planned_starts_at'));
        var lblTraining     = @json(__('training.training_type'));
        var lblChoose       = @json(__('training.choose_planned_session'));

        // Governorate id => localized name, so a session's place is always
        // resolved through its ID and follows the active language.
        var PLACE_NAMES = @json($gouvernorats);

        function resolvePlaceName(id) {
            if (id === null || id === undefined || id === '') return null;
            return PLACE_NAMES[String(id)] || null;
        }

        var centBox   = document.getElementById('centralized-box');
        var sessList  = document.getElementById('planned-sessions-list');
        var waitBox   = document.getElementById('waitlist-notice');
        var regionV   = document.getElementById('region-visavis');
        var sessionHidden = document.querySelector('input[name="planned_session_id"]');

        // A session's place always comes from its governorate id (localized),
        // never from a hard-coded default. No extra hint is shown on selection.
        function sessionPlace(s) {
            return resolvePlaceName(s.governorate_id) || s.place_name || s.place_label || '';
        }

        function buildSessionRadios(type) {
            if (!sessList) return;
            sessList.innerHTML = '';
            waitBox.classList.add('hidden');
            var list = (PLANNED && PLANNED[type]) || [];
            if (!list.length) { waitBox.classList.remove('hidden'); return; }

            var title = document.createElement('p');
            title.className = 'text-body-sm font-bold text-on-surface dark:text-gray-100 mb-1';
            title.textContent = lblChoose;
            sessList.appendChild(title);

            list.forEach(function (s) {
                var id = String(s.id);
                var chosen = (id === initialSession);
                var wrap = document.createElement('label');
                wrap.className = 'flex items-center gap-3 rounded border p-3 cursor-pointer transition '
                    + (chosen ? 'border-primary bg-primary/10 dark:bg-primary/20'
                        : 'border-surface-dim hover:border-primary/50 dark:border-gray-700');
                // Keep the raw IDs on the card: the selected session is posted by
                // id, and the place is always resolved through its governorate id.
                wrap.dataset.governorateId = s.governorate_id || '';
                wrap.dataset.trainingType  = s.training_type || '';
                var radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = 'planned_session_id';
                radio.value = id;
                radio.className = 'h-4 w-4 text-primary focus:ring-primary';
                if (chosen) radio.checked = true;

                var info = document.createElement('div');
                info.className = 'min-w-0 flex-1';

                // Training type (already translated server-side)
                var trainingLine = document.createElement('p');
                trainingLine.className = 'text-body-sm font-semibold text-on-surface dark:text-gray-100';
                trainingLine.textContent = lblTraining + ': ' + (s.training_type_label || s.training_type || '');

                // Date — dd/mm/yyyy, forced LTR so it stays readable in Arabic
                var dateLine = document.createElement('p');
                dateLine.className = 'text-[11px] text-outline mt-0.5';
                dateLine.textContent = lblDate + ': ';
                var when = document.createElement('span');
                when.dir = 'ltr';
                when.textContent = s.date_label || s.starts_at || '';
                dateLine.appendChild(when);

                // Place — resolved from the governorate ID so it follows the
                // current locale instead of a frozen string.
                var placeLine = document.createElement('p');
                placeLine.className = 'text-[11px] text-outline mt-0.5';
                var placeName = sessionPlace(s);
                placeLine.textContent = placeName
                    ? lblPlace + ': ' + placeName
                    : '';

                info.appendChild(trainingLine);
                info.appendChild(dateLine);
                info.appendChild(placeLine);
                wrap.appendChild(radio);
                wrap.appendChild(info);
                sessList.appendChild(wrap);
            });
        }

        function handleTypeChange() {
            if (!typeSelect) return;
            var type = typeSelect.value;
            var isCentralized = CENTRALIZED.indexOf(type) !== -1;

            // Region contact-point hint (full width)
            if (regionV) regionV.classList.toggle('hidden', !isCentralized);

            // Centralized hint + session picker
            if (centBox) centBox.classList.toggle('hidden', !isCentralized);
            if (isCentralized) buildSessionRadios(type);
        }
        if (typeSelect) typeSelect.addEventListener('change', handleTypeChange);
        handleTypeChange();

        /* ── Submit lock ── */
        var form=document.getElementById('training-create-form');
        if(form){form.addEventListener('submit',function(){if(submitBtn)submitBtn.disabled=true;var lt=submitBtn?submitBtn.getAttribute('data-loading-text'):null;if(submitLbl&&lt)submitLbl.textContent=lt;});}
    }());
    </script>
</x-app-layout>
