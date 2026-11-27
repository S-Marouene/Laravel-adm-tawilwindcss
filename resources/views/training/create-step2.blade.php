@php
    $tr = $trainingRequest;

    $valDepositor = old('depositor_type', $tr->depositor_type ?? 'individual');
    $valCompany    = old('company_name', $tr->company_name ?? '');
    $valNom  = old('nom_prenom', $tr->nom_prenom ?? '');
    $valCin  = old('cin', $tr->cin ?? '');
    $valPhone = old('phone', $tr->phone ?? '');
    $valKind  = old('training_kind', $tr->training_kind ?? 'new');

    $step1Lines = array_filter([
        __('training.type_' . $tr->training_type),
        $tr->governorate?->name,
        $tr->delegation?->name,
    ]);

    $wizardSteps = [
        ['state' => 'done', 'title' => __('training.step_one_title'),
         'data' => array_values($step1Lines),
         'editUrl' => route('training.step1.edit', $tr),
         'editLabel' => __('training.edit_step1')],
        ['state' => 'active', 'title' => __('training.step_two_title')],
        ['state' => 'upcoming', 'title' => __('training.step_three_title')],
    ];

    // Existing participants (saved draft) or old input after a failed submit
    $initialParticipants = old('participants')
        ?: ($tr->isCompany() ? $tr->participants()->get()->toArray() : []);

    $basePrice = \App\Models\TrainingRequest::basePrice($tr->training_type ?? '');
@endphp

<x-app-layout>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            <x-onpc.dossier-stepper :steps="$wizardSteps" />

            <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <span class="text-label-caps text-primary uppercase tracking-wider">{{ __('training.step_of', ['step' => 2, 'total' => 3]) }}</span>
                    <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('training.step2_heading') }} — {{ __('training.step_two_title') }}</h2>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-surface-container text-body-sm text-outline font-medium border border-surface-dim shrink-0">
                    <span class="text-red-500 font-bold" aria-hidden="true">*</span>
                    {{ __('training.required_note') }}
                </span>
            </div>

            <section class="space-y-6">
                <form method="POST" action="{{ route('training.step2.store', $tr) }}"
                      id="training-identity-form"
                      class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    @csrf

                    <div class="p-6 pb-0">
                        @if ($errors->any())
                            <div class="rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                                <p class="font-bold">{{ __('training.create_error_title') }}</p>
                                <ul class="mt-1.5 list-disc space-y-1 ps-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                    </div>

                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">1</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">{{ __('training.section_applicant') }}</h3>
                        </div>

                        {{-- Depositor type: real person or society --}}
                        <div class="space-y-1.5">
                            <label class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ __('training.depositor_type') }} <span class="text-primary">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach (['individual' => 'depositor_individual', 'company' => 'depositor_company'] as $dVal => $dLabel)
                                    <label class="depositor-type-card flex items-center gap-3 rounded-lg border border-surface-dim bg-surface-container px-4 py-3 cursor-pointer transition hover:border-primary/40 has-[:checked]:border-primary/60 has-[:checked]:bg-primary/5 dark:border-gray-700 dark:bg-gray-900/50" data-type="{{ $dVal }}">
                                        <input type="radio" name="depositor_type" value="{{ $dVal }}" @checked($valDepositor === $dVal)
                                            class="h-4 w-4 border-surface-dim text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-800">
                                        <span class="flex items-center gap-2 text-body-sm font-semibold text-on-surface dark:text-gray-100">
                                            <span class="material-symbols-outlined text-[20px] text-primary" aria-hidden="true">{{ $dVal === 'individual' ? 'person' : 'groups' }}</span>
                                            {{ __('training.' . $dLabel) }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('depositor_type')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>

                        {{-- Individual block --}}
                        <div id="individual-block" class="@if($valDepositor !== 'individual') hidden @endif space-y-5">
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
                                <div class="space-y-1.5">
                                    <label for="phone" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ __('training.phone') }} <span class="text-primary">*</span></label>
                                    <input id="phone" name="phone" type="tel" maxlength="20" inputmode="tel" dir="ltr" value="{{ $valPhone }}" placeholder="{{ __('training.phone_placeholder') }}" required
                                        class="num-ltr block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('phone') border-red-400 @enderror">
                                    <p class="text-xs text-secondary flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-sm text-outline" aria-hidden="true">info</span>{{ __('training.phone_notification_hint') }}
                                    </p>
                                    @error('phone')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                </div>

                                {{-- Training kind (new / recycling) — individual only --}}
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
                        </div>

                        {{-- Company block --}}
                        <div id="company-block" class="@if($valDepositor !== 'company') hidden @endif space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="space-y-1.5">
                                    <label for="company_name" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ __('training.company_name') }} <span class="text-primary">*</span></label>
                                    <input id="company_name" name="company_name" type="text" maxlength="255" value="{{ $valCompany }}" placeholder="{{ __('training.company_name_placeholder') }}" required
                                        class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('company_name') border-red-400 @enderror">
                                    @error('company_name')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label for="company_phone" class="block text-body-sm font-bold text-on-surface dark:text-gray-200">{{ __('training.company_contact_phone') }} <span class="text-primary">*</span></label>
                                    <input id="company_phone" name="phone" type="tel" maxlength="20" inputmode="tel" dir="ltr" value="{{ $valPhone }}" placeholder="{{ __('training.phone_placeholder') }}" required
                                        class="num-ltr block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest shadow-sm transition focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 @error('phone') border-red-400 @enderror">
                                    <p class="text-xs text-secondary flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-sm text-outline" aria-hidden="true">info</span>{{ __('training.phone_notification_hint') }}
                                    </p>
                                    @error('phone')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            {{-- Participants repeater --}}
                            <div class="rounded-lg border border-surface-dim dark:border-gray-700">
                                <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-surface-dim dark:border-gray-700">
                                    <div>
                                        <p class="text-body-sm font-bold text-on-surface dark:text-gray-100">{{ __('training.participants_section') }} <span class="text-primary">*</span></p>
                                        <p class="text-xs text-outline mt-0.5">{{ __('training.participants_hint') }}</p>
                                    </div>
                                    <button type="button" id="add-participant-btn"
                                        class="inline-flex items-center gap-1.5 rounded border border-primary/40 bg-primary/5 px-3 py-1.5 text-body-sm font-semibold text-primary transition hover:bg-primary/10 shrink-0">
                                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">add</span>
                                        {{ __('training.add_participant') }}
                                    </button>
                                </div>
                                <div id="participants-container" class="p-4 space-y-3"></div>
                                @error('participants')<p class="text-xs text-red-600 px-4 pb-3" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Fee estimate (per person / total) --}}
                        <div id="fee-estimate" class="hidden rounded border border-amber-200 bg-amber-50 p-4 dark:bg-amber-900/10 dark:border-amber-800/40">
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-amber-600 text-[20px] mt-px shrink-0" aria-hidden="true">payments</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wider mb-1" id="fee-label">{{ __('training.estimated_fee') }}</p>
                                    <p class="text-body-md font-black text-amber-700 dark:text-amber-400">
                                        <span id="fee-amount" dir="ltr">—</span> {{ __('training.fee_tnd') }}
                                    </p>
                                    <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-1">{{ __('training.fee_note') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim bg-surface-container px-6 py-5 dark:border-gray-700 dark:bg-gray-900/40">
                        <a href="{{ route('training.step1.edit', $tr) }}"
                           class="inline-flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                            <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            {{ __('training.back') }}
                        </a>
                        <button type="submit" id="identity-submit-btn"
                            class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-70"
                            data-loading-text="{{ __('training.submitting') }}">
                            <span id="identity-submit-label">{{ __('training.continue') }}</span>
                            <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <style>
        .participant-row { will-change: transform; }
        #participants-container { position: relative; overflow: visible; }
        .participant-row.drag-over { outline: 2px dashed var(--color-primary, #b91c1c); outline-offset: -2px; border-radius: .375rem; }
    </style>

    <script>
    (function () {
        var basePrice = {{ (int) $basePrice }};

        /* ── Depositor type toggle (individual / company) ── */
        var indBlock = document.getElementById('individual-block');
        var comBlock = document.getElementById('company-block');
        var depoRadios = document.querySelectorAll('input[name="depositor_type"]');

        function setDepositorInputs(block, enabled) {
            if (!block) return;
            block.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = !enabled; });
        }

        function setDepositorType(type) {
            var isCompany = (type === 'company');
            if (indBlock) { indBlock.classList.toggle('hidden', isCompany); setDepositorInputs(indBlock, !isCompany); }
            if (comBlock) { comBlock.classList.toggle('hidden', !isCompany); setDepositorInputs(comBlock, isCompany); }
            if (isCompany && participantsCount() === 0) addParticipant();
            updateFee();
        }
        depoRadios.forEach(function (r) { r.addEventListener('change', function () { setDepositorType(this.value); }); });

        /* ── Participants repeater ── */
        var pContainer = document.getElementById('participants-container');
        var addBtn = document.getElementById('add-participant-btn');
        var pIndex = 0;

        var lblNom  = @json(__('training.nom_prenom'));
        var lblCin  = @json(__('training.cin'));
        var lblTel  = @json(__('training.phone'));
        var phNom   = @json(__('training.nom_prenom_placeholder'));
        var phCin   = @json(__('training.cin_placeholder'));
        var phTel   = @json(__('training.phone_placeholder'));
        var lblKind = @json(__('training.training_kind'));
        var optNew  = @json(__('training.kind_new'));
        var optRec  = @json(__('training.kind_recycling'));
        var removeLbl = @json(__('training.remove_participant'));
        var lblPriority = @json(__('training.priority'));
        var lblUp   = @json(__('training.move_up'));
        var lblDown = @json(__('training.move_down'));
        var lblDrag = @json(__('training.drag_to_reorder'));

        function participantsCount() { return pContainer ? pContainer.querySelectorAll('.participant-row').length : 0; }

        /** Re-number every row's priority (badge + hidden input) after any change. */
        function renumberPriorities() {
            if (!pContainer) return;
            var rows = pContainer.querySelectorAll('.participant-row');
            rows.forEach(function (row, idx) {
                var n = idx + 1;
                var num = row.querySelector('.priority-num');
                var input = row.querySelector('.participant-priority-input');
                if (num) num.textContent = '#' + n;
                if (input) input.value = n;
                var up = row.querySelector('.participant-up');
                var down = row.querySelector('.participant-down');
                if (up) up.disabled = (idx === 0);
                if (down) down.disabled = (idx === rows.length - 1);
            });
        }

        /** Animate any DOM reordering inside the container (FLIP). */
        function flipReorder(mutation) {
            var rows = Array.prototype.slice.call(pContainer.querySelectorAll('.participant-row'));
            var firstTops = new Map();
            rows.forEach(function (r) { firstTops.set(r, r.getBoundingClientRect().top); });
            mutation();
            Array.prototype.slice.call(pContainer.querySelectorAll('.participant-row')).forEach(function (r) {
                var first = firstTops.get(r);
                if (first === undefined) return;
                var delta = first - r.getBoundingClientRect().top;
                if (delta) {
                    // Cancel any running animation on this row first (rapid clicks).
                    r.getAnimations().forEach(function (a) { a.cancel(); });
                    r.style.transform = '';
                    var anim = r.animate(
                        [{ transform: 'translateY(' + delta + 'px)' }, { transform: 'translateY(0px)' }],
                        { duration: 240, easing: 'cubic-bezier(.2,.8,.2,1)', fill: 'backwards' }
                    );
                    anim.onfinish = function () { r.style.transform = ''; };
                    anim.oncancel = function () { r.style.transform = ''; };
                }
            });
            renumberPriorities();
        }

        function moveRow(row, dir) {
            if (!row) return;
            var rows = Array.prototype.slice.call(pContainer.querySelectorAll('.participant-row'));
            var i = rows.indexOf(row);
            var j = dir === 'up' ? i - 1 : i + 1;
            if (j < 0 || j >= rows.length) return;
            flipReorder(function () {
                if (dir === 'up') pContainer.insertBefore(row, rows[j]);
                else pContainer.insertBefore(row, rows[j].nextSibling);
            });
            updateFee();
        }

        function addParticipant(data) {
            if (!pContainer) return;
            var i = pIndex++;
            data = data || {};
            var row = document.createElement('div');
            row.className = 'participant-row grid grid-cols-12 gap-x-2 gap-y-2 items-center rounded border border-surface-dim bg-surface-container-lowest p-2 dark:border-gray-700 dark:bg-gray-900/40';
            row.setAttribute('draggable', 'true');
            row.innerHTML =
                /* priority pill + up/down — 1 col mobile, 1 col md */
                '<div class="col-span-1 flex items-center gap-1 self-center">' +
                    '<div class="participant-priority drag-handle inline-flex items-center gap-0.5 rounded border border-primary/30 bg-primary/10 text-primary px-1 h-7 text-xs font-bold cursor-grab active:cursor-grabbing select-none shrink-0" title="' + lblDrag + '">' +
                        '<span class="material-symbols-outlined text-[13px] leading-none" aria-hidden="true">drag_indicator</span>' +
                        '<span class="priority-num text-[11px]">#</span>' +
                    '</div>' +
                    '<div class="flex flex-col -space-y-px">' +
                        '<button type="button" class="participant-up inline-flex items-center justify-center w-5 h-3.5 rounded-t border border-surface-dim bg-surface-container-lowest text-outline hover:border-primary hover:text-primary transition" title="' + lblUp + '" aria-label="' + lblUp + '"><span class="material-symbols-outlined text-[11px] leading-none">keyboard_arrow_up</span></button>' +
                        '<button type="button" class="participant-down inline-flex items-center justify-center w-5 h-3.5 rounded-b border border-surface-dim bg-surface-container-lowest text-outline hover:border-primary hover:text-primary transition" title="' + lblDown + '" aria-label="' + lblDown + '"><span class="material-symbols-outlined text-[11px] leading-none">keyboard_arrow_down</span></button>' +
                    '</div>' +
                    '<input type="hidden" name="participants[' + i + '][priority]" class="participant-priority-input" value="">' +
                '</div>' +
                /* nom — 5 cols mobile, 4 cols md */
                '<div class="col-span-5 md:col-span-4 space-y-0.5">' +
                    '<label class="block text-[11px] font-bold text-secondary dark:text-gray-400">' + lblNom + ' <span class="text-primary">*</span></label>' +
                    '<input type="text" name="participants[' + i + '][nom_prenom]" maxlength="255" value="' + (data.nom_prenom || '') + '" placeholder="' + phNom + '" required class="block w-full h-8 px-2 text-body-sm text-on-surface rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">' +
                '</div>' +
                /* cin — 3 cols mobile, 2 cols md */
                '<div class="col-span-3 md:col-span-2 space-y-0.5">' +
                    '<label class="block text-[11px] font-bold text-secondary dark:text-gray-400">' + lblCin + ' <span class="text-primary">*</span></label>' +
                    '<input type="text" inputmode="numeric" maxlength="20" name="participants[' + i + '][cin]" value="' + (data.cin || '') + '" placeholder="' + phCin + '" required class="block w-full h-8 px-2 text-body-sm text-on-surface rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">' +
                '</div>' +
                /* phone — hidden on mobile (xs), 2 cols md */
                '<div class="hidden md:block md:col-span-2 space-y-0.5">' +
                    '<label class="block text-[11px] font-bold text-secondary dark:text-gray-400">' + lblTel + '</label>' +
                    '<input type="tel" dir="ltr" maxlength="20" name="participants[' + i + '][phone]" value="' + (data.phone || '') + '" placeholder="' + phTel + '" class="num-ltr block w-full h-8 px-2 text-body-sm text-on-surface rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">' +
                '</div>' +
                /* kind — 2 cols mobile, 2 cols md */
                '<div class="col-span-2 space-y-0.5">' +
                    '<label class="block text-[11px] font-bold text-secondary dark:text-gray-400">' + lblKind + ' <span class="text-primary">*</span></label>' +
                    '<select name="participants[' + i + '][training_kind]" required class="participant-kind block w-full h-8 text-body-sm text-on-surface rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">' +
                        '<option value="new"' + (data.training_kind !== 'recycling' ? ' selected' : '') + '>' + optNew + '</option>' +
                        '<option value="recycling"' + (data.training_kind === 'recycling' ? ' selected' : '') + '>' + optRec + '</option>' +
                    '</select>' +
                '</div>' +
                /* delete — 1 col mobile, 1 col md */
                '<div class="col-span-1 flex items-center justify-center self-center">' +
                    '<button type="button" class="participant-remove inline-flex items-center justify-center w-7 h-7 rounded border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300 shrink-0" title="' + removeLbl + '" aria-label="' + removeLbl + '">' +
                        '<span class="material-symbols-outlined text-[16px]" aria-hidden="true">delete</span>' +
                    '</button>' +
                '</div>';
            row.querySelector('.participant-remove').addEventListener('click', function () {
                flipReorder(function () { row.remove(); });
                if (participantsCount() === 0) addParticipant();
                updateFee();
            });
            row.querySelector('.participant-up').addEventListener('click', function () { moveRow(row, 'up'); });
            row.querySelector('.participant-down').addEventListener('click', function () { moveRow(row, 'down'); });
            row.querySelector('.participant-kind').addEventListener('change', updateFee);
            pContainer.appendChild(row);
            // Form controls must not initiate a row drag — only the grip handle should.
            row.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                el.setAttribute('draggable', 'false');
            });
            renumberPriorities();
            updateFee();
        }

        /* ── Drag-and-drop reordering (via the grip handle) ── */
        (function setupDragDrop() {
            if (!pContainer) return;
            var dragRow = null;
            var allowDrag = false;
            var pressRow = null;
            var preDropTops = null; // positions snapshotted just before the drop lands

            // Remember whether the press started on the grip handle (dragstart's
            // e.target isn't reliable across browsers — it can be the row itself).
            pContainer.addEventListener('mousedown', function (e) {
                var handle = e.target.closest ? e.target.closest('.drag-handle') : null;
                allowDrag = !!handle;
                pressRow = handle ? (e.target.closest ? e.target.closest('.participant-row') : null) : null;
            });
            pContainer.addEventListener('dragstart', function (e) {
                if (!allowDrag || !pressRow) { e.preventDefault(); return; } // only drag from the handle
                dragRow = pressRow;
                e.dataTransfer.effectAllowed = 'move';
                try { e.dataTransfer.setData('text/plain', 'row'); } catch (_) {}
                dragRow.classList.add('opacity-50', 'ring-2', 'ring-primary');
            });
            pContainer.addEventListener('dragend', function () {
                if (dragRow) dragRow.classList.remove('opacity-50', 'ring-2', 'ring-primary');
                pContainer.querySelectorAll('.drag-over').forEach(function (el) { el.classList.remove('drag-over'); });
                dragRow = null;
                allowDrag = false;
            });
            pContainer.addEventListener('dragover', function (e) {
                if (!dragRow) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                var target = e.target.closest ? e.target.closest('.participant-row') : null;
                pContainer.querySelectorAll('.drag-over').forEach(function (el) { el.classList.remove('drag-over'); });
                if (target && target !== dragRow) {
                    target.classList.add('drag-over');
                    // Snapshot every row's position continuously so the drop handler
                    // has fresh "before" coordinates and the FLIP delta is correct.
                    preDropTops = new Map();
                    Array.prototype.slice.call(pContainer.querySelectorAll('.participant-row')).forEach(function (r) {
                        preDropTops.set(r, r.getBoundingClientRect().top);
                    });
                }
            });
            pContainer.addEventListener('drop', function (e) {
                if (!dragRow) return;
                e.preventDefault();
                var target = e.target.closest ? e.target.closest('.participant-row') : null;
                if (!target || target === dragRow) { preDropTops = null; return; }
                var rect = target.getBoundingClientRect();
                var after = (e.clientY - rect.top) > rect.height / 2;
                // Use the positions recorded during the last dragover, then do the DOM move,
                // then animate each row from its old position to its new one.
                var tops = preDropTops;
                preDropTops = null;
                if (after) pContainer.insertBefore(dragRow, target.nextSibling);
                else pContainer.insertBefore(dragRow, target);
                if (tops) {
                    Array.prototype.slice.call(pContainer.querySelectorAll('.participant-row')).forEach(function (r) {
                        var first = tops.get(r);
                        if (first === undefined) return;
                        var delta = first - r.getBoundingClientRect().top;
                        if (delta) {
                            r.getAnimations().forEach(function (a) { a.cancel(); });
                            r.style.transform = '';
                            r.animate(
                                [{ transform: 'translateY(' + delta + 'px)' }, { transform: 'translateY(0px)' }],
                                { duration: 240, easing: 'cubic-bezier(.2,.8,.2,1)', fill: 'backwards' }
                            );
                        }
                    });
                }
                renumberPriorities();
                updateFee();
            });
        })();

        if (addBtn) addBtn.addEventListener('click', function () { addParticipant(); });

        var initialParticipants = @json($initialParticipants ?? []);
        // Sort existing participants by priority before rendering
        (function () {
            var arr = Array.isArray(initialParticipants) ? initialParticipants.slice() : [{}];
            arr.sort(function (a, b) { return (a.priority || 0) - (b.priority || 0); });
            arr.forEach(function (p) {
                if (p && (p.nom_prenom || p.cin || p.training_kind)) { addParticipant(p); }
            });
        })();

        /* ── Fee estimate ── */
        var feeBox   = document.getElementById('fee-estimate');
        var feeAmt   = document.getElementById('fee-amount');
        var feeLabel = document.getElementById('fee-label');
        var lblPerPerson = @json(__('training.estimated_fee_per_person'));
        var lblTotal     = @json(__('training.estimated_fee'));

        function isCompanyMode() {
            var r = document.querySelector('input[name="depositor_type"]:checked');
            return r && r.value === 'company';
        }

        function updateFee() {
            if (!feeBox) return;
            if (!basePrice) { feeBox.classList.add('hidden'); return; }
            var fee, count;
            if (isCompanyMode() && pContainer) {
                fee = 0; count = 0;
                pContainer.querySelectorAll('.participant-row').forEach(function (row) {
                    count++;
                    var kind = row.querySelector('.participant-kind').value;
                    fee += kind === 'recycling' ? Math.round(basePrice * 0.75) : basePrice;
                });
                if (feeLabel) feeLabel.textContent = lblTotal;
            } else {
                count = 1;
                var kind = 'new';
                document.querySelectorAll('input[name="training_kind"]').forEach(function (r) { if (r.checked) kind = r.value; });
                fee = kind === 'recycling' ? Math.round(basePrice * 0.75) : basePrice;
                if (feeLabel) feeLabel.textContent = lblPerPerson;
            }
            feeAmt.textContent = fee.toLocaleString() + (count > 1 ? ' (' + count + ')' : '');
            feeBox.classList.remove('hidden');
        }
        document.querySelectorAll('input[name="training_kind"]').forEach(function (r) { r.addEventListener('change', updateFee); });
        setDepositorType(document.querySelector('input[name="depositor_type"]:checked')
            ? document.querySelector('input[name="depositor_type"]:checked').value : 'individual');

        /* ── Submit lock ── */
        var form = document.getElementById('training-identity-form');
        var btn = document.getElementById('identity-submit-btn');
        var lbl = document.getElementById('identity-submit-label');
        if (form) { form.addEventListener('submit', function () { if (btn) btn.disabled = true; var lt = btn ? btn.getAttribute('data-loading-text') : null; if (lbl && lt) lbl.textContent = lt; }); }
    }());
    </script>
</x-app-layout>
