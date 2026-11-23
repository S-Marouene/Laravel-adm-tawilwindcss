@php
    $isEditing = isset($trainingRequest);
    $tr        = $isEditing ? $trainingRequest : null;

    $formAction = $isEditing
        ? route('training.step1.update', $tr)
        : route('training.step1.store');

    $valCin    = old('cin',            $tr?->cin       ?? '');
    $valNom    = old('nom_prenom',      $tr?->nom_prenom ?? '');
    $valPhone  = old('phone',           $tr?->phone     ?? '');
    $valGov    = old('governorate_id',  $tr?->governorate_id ?? '');
    $valDelId  = old('delegation_id',   $tr?->delegation_id  ?? '');
    $valType   = old('training_type',   $tr?->training_type  ?? '');
    $valKind   = old('training_kind',   $tr?->training_kind  ?? 'new');
    $valNotes  = old('notes',           $tr?->notes     ?? '');

    $wizardSteps = [
        ['state' => 'active',  'title' => __('training.step_one_title'), 'editing' => $isEditing],
        ['state' => $isEditing ? 'done' : 'upcoming', 'title' => __('training.step_two_title'),
         'editUrl'  => $isEditing ? route('training.step2.show', $tr) : null,
         'editLabel'=> __('training.step_two_title')],
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
                            {{ __('training.step_of', ['step' => 1, 'total' => 2]) }}
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

                    {{-- ═══ SECTION A — IDENTITÉ ═══ --}}
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">A</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">{{ __('training.section_applicant') }}</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label for="nom_prenom" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ __('training.nom_prenom') }} <span class="text-primary">*</span></label>
                                <input id="nom_prenom" name="nom_prenom" type="text" maxlength="255" value="{{ $valNom }}" placeholder="{{ __('training.nom_prenom_placeholder') }}" required
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('nom_prenom') border-red-400 @enderror">
                                @error('nom_prenom')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>
                            <div class="space-y-1.5">
                                <label for="cin" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ __('training.cin') }} <span class="text-primary">*</span></label>
                                <input id="cin" name="cin" type="text" inputmode="numeric" maxlength="20" value="{{ $valCin }}" placeholder="{{ __('training.cin_placeholder') }}" required
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('cin') border-red-400 @enderror">
                                @error('cin')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>
                            <div class="space-y-1.5 md:col-span-2">
                                <label for="phone" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ __('training.phone') }} <span class="text-primary">*</span></label>
                                <input id="phone" name="phone" type="tel" maxlength="20" inputmode="tel" dir="ltr" value="{{ $valPhone }}" placeholder="{{ __('training.phone_placeholder') }}" required
                                    class="num-ltr block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('phone') border-red-400 @enderror">
                                <p class="text-xs text-secondary flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm text-outline" aria-hidden="true">info</span>{{ __('training.phone_notification_hint') }}
                                </p>
                                @error('phone')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mx-6 border-t border-dashed border-surface-dim dark:border-gray-700"></div>

                    {{-- ═══ SECTION B — LOCALISATION ═══ --}}
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">B</span>
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
                    </div>

                    <div class="mx-6 border-t border-dashed border-surface-dim dark:border-gray-700"></div>

                    {{-- ═══ SECTION C — TYPE DE FORMATION ═══ --}}
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">C</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">{{ __('training.section_training') }}</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Training type --}}
                            <div class="space-y-1.5">
                                <label for="training_type" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ __('training.training_type') }} <span class="text-primary">*</span></label>
                                <select id="training_type" name="training_type" required
                                    class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('training_type') border-red-400 @enderror">
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

                            {{-- Training kind (new / recycling) --}}
                            <div class="space-y-1.5">
                                <label class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ __('training.training_kind') }} <span class="text-primary">*</span></label>
                                <div class="grid grid-cols-2 gap-3 mt-1">
                                    @foreach (['new' => 'kind_new', 'recycling' => 'kind_recycling'] as $val => $labelKey)
                                        <label class="flex items-center gap-3 rounded-lg border border-surface-dim bg-surface-container px-4 py-3 cursor-pointer transition hover:border-primary/40 has-[:checked]:border-primary/60 has-[:checked]:bg-primary/5 dark:border-gray-700 dark:bg-gray-900/50">
                                            <input type="radio" name="training_kind" value="{{ $val }}" @checked($valKind === $val)
                                                class="h-4 w-4 border-surface-dim text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-800">
                                            <span class="text-body-sm font-semibold text-on-surface dark:text-gray-100">{{ __('training.' . $labelKey) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('training_kind')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Live fee estimate --}}
                        <div id="fee-estimate" class="hidden rounded border border-amber-200 bg-amber-50 p-4 dark:bg-amber-900/10 dark:border-amber-800/40">
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-amber-600 text-[20px] mt-px shrink-0" aria-hidden="true">payments</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wider mb-1">{{ __('training.estimated_fee') }}</p>
                                    <p class="text-body-md font-black text-amber-700 dark:text-amber-400">
                                        <span id="fee-amount" dir="ltr">—</span> {{ __('training.fee_tnd') }}
                                        &nbsp;·&nbsp; <span id="fee-duration">—</span> {{ __('training.fee_weeks') }}
                                    </p>
                                    <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-1">{{ __('training.fee_note') }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div class="space-y-1.5">
                            <label for="notes" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ __('training.notes') }}</label>
                            <textarea id="notes" name="notes" rows="3" maxlength="3000" placeholder="{{ __('training.notes_placeholder') }}"
                                class="block w-full px-3.5 py-2.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('notes') border-red-400 @enderror">{{ $valNotes }}</textarea>
                            @error('notes')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim px-6 py-5 dark:border-gray-700">
                        <a href="{{ $isEditing ? route('training.step2.show', $tr) : route('training.index') }}"
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
                setTimeout(function () { var f = document.getElementById('nom_prenom'); if (f) f.focus({ preventScroll: true }); }, 350);
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
        var govOpen = false, delOpen = false, govItems = [], delItems = [];

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

        function loadDel(gId){var list=delegationsMap[String(gId)]||[];delItems=Array.isArray(list)?list:buildItems(list);delHidden.value='';delTrigLbl.textContent=delSelectLbl;delTrigLbl.classList.add('text-outline','dark:text-gray-500');delTrigger.disabled=(delItems.length===0);}
        function renderDel(f){var items=f?delItems.filter(function(i){return i.name.toLowerCase().includes(f.toLowerCase());}):delItems;renderList(delList,items,delHidden.value,function(item){delHidden.value=item.id;delTrigLbl.textContent=item.name;delTrigLbl.classList.remove('text-outline','dark:text-gray-500');closePanel(delPanel,delChevron,function(v){delOpen=v;});}); }
        delTrigger.addEventListener('click',function(){if(delOpen){closePanel(delPanel,delChevron,function(v){delOpen=v;});return;}openPanel(delPanel,delChevron,delSearch,function(v){delOpen=v;});renderDel('');});
        delSearch.addEventListener('input',function(){renderDel(this.value);});
        document.addEventListener('click',function(e){if(delOpen&&!document.getElementById('del-wrap').contains(e.target))closePanel(delPanel,delChevron,function(v){delOpen=v;});});

        (function init(){
            if(initGovId){var gov=govItems.find(function(i){return String(i.id)===String(initGovId);});if(gov){govHidden.value=gov.id;govTrigLbl.textContent=gov.name;govTrigLbl.classList.remove('text-outline','dark:text-gray-500');loadDel(gov.id);if(initDelId){var del=delItems.find(function(i){return String(i.id)===String(initDelId);});if(del){delHidden.value=del.id;delTrigLbl.textContent=del.name;delTrigLbl.classList.remove('text-outline','dark:text-gray-500');}}}}
        })();

        /* ── Live fee estimate ── */
        var typeSelect = document.getElementById('training_type');
        var kindRadios = document.querySelectorAll('input[name="training_kind"]');
        var feeBox     = document.getElementById('fee-estimate');
        var feeAmt     = document.getElementById('fee-amount');
        var feeDur     = document.getElementById('fee-duration');

        function updateFee(){
            var opt=typeSelect.options[typeSelect.selectedIndex];
            var price=opt?parseInt(opt.dataset.price||'0',10):0;
            var weeks=opt?parseInt(opt.dataset.weeks||'1',10):1;
            var kind='new';
            kindRadios.forEach(function(r){if(r.checked)kind=r.value;});
            if(!price){feeBox.classList.add('hidden');return;}
            var fee=kind==='recycling'?Math.round(price*0.75):price;
            feeAmt.textContent=fee.toLocaleString();
            feeDur.textContent=weeks;
            feeBox.classList.remove('hidden');
        }
        typeSelect.addEventListener('change',updateFee);
        kindRadios.forEach(function(r){r.addEventListener('change',updateFee);});
        updateFee();

        /* ── Submit lock ── */
        var form=document.getElementById('training-create-form');
        if(form){form.addEventListener('submit',function(){if(submitBtn)submitBtn.disabled=true;var lt=submitBtn?submitBtn.getAttribute('data-loading-text'):null;if(submitLbl&&lt)submitLbl.textContent=lt;});}
    }());
    </script>
</x-app-layout>
