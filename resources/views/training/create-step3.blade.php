@php
    $tr = $trainingRequest;

    $step1Lines = array_filter([
        __('training.type_' . $tr->training_type),
        $tr->governorate?->name,
        $tr->delegation?->name,
    ]);

    $step2Lines = $tr->isCompany()
        ? array_filter([$tr->company_name, __('training.participants_count', ['count' => $tr->participants()->count()])])
        : array_filter([$tr->nom_prenom, $tr->cin, $tr->phone]);

    $wizardSteps = [
        ['state' => 'done', 'title' => __('training.step_one_title'),
         'data' => array_values($step1Lines),
         'editUrl' => route('training.step1.edit', $tr),
         'editLabel' => __('training.edit_step1')],
        ['state' => 'done', 'title' => __('training.step_two_title'),
         'data' => array_values($step2Lines),
         'editUrl' => route('training.step2.show', $tr),
         'editLabel' => __('training.edit_step2')],
        ['state' => 'active', 'title' => __('training.step_three_title')],
    ];
    $uploadedCount = $uploadedDocs->count();
@endphp

<x-app-layout>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            <x-onpc.dossier-stepper :steps="$wizardSteps" />

            <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <span class="text-label-caps text-primary uppercase tracking-wider">{{ __('training.step_of', ['step' => 3, 'total' => 3]) }}</span>
                    <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('training.step3_heading') }} — {{ __('training.step_three_title') }}</h2>
                </div>
                <span id="docs-progress" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-gov-50 text-gov-800 text-body-sm font-semibold border border-gov-300 dark:bg-gov-900/30 dark:text-gov-300 dark:border-gov-800">
                    <span class="material-symbols-outlined filled text-[15px]" aria-hidden="true">task_alt</span>
                    <span id="docs-progress-count">{{ $uploadedCount }}/{{ $documentTypes->count() }}</span>
                </span>
            </div>

            <section class="space-y-6">
                <form method="POST" action="{{ route('training.step3.store', $tr) }}"
                      id="training-step3-form" enctype="multipart/form-data"
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
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">3</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">{{ __('training.step_three_title') }}</h3>
                        </div>

                        {{-- Fee reminder --}}
                        <div class="flex items-start gap-3 rounded border border-amber-200 bg-amber-50 p-4 text-body-sm dark:border-amber-800/40 dark:bg-amber-900/10">
                            <span class="material-symbols-outlined filled text-amber-600 text-[22px]" aria-hidden="true">payments</span>
                            <div>
                                <p class="font-bold text-amber-800 dark:text-amber-300">{{ __('training.fees_title') }} — {{ __('training.payment_proof') }}</p>
                                <p class="text-secondary dark:text-gray-300 mt-0.5">{{ __('training.fees_info') }}</p>
                            </div>
                        </div>

                        {{-- Info banner --}}
                        <div class="flex items-start gap-3 rounded border border-surface-dim bg-surface-container-low p-4 text-body-sm dark:border-gray-700 dark:bg-gray-900/40">
                            <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">info</span>
                            <p class="text-secondary dark:text-gray-300">{{ __('training.documents_info') }}</p>
                        </div>

                        {{-- Document list --}}
                        <ol id="doc-list" class="space-y-3">
                            @foreach ($documentTypes as $doc)
                                @php $uploadedFile = $uploadedDocs->get($doc->id); $hasUploaded = $uploadedFile !== null; @endphp
                                <li class="doc-card rounded border p-4 transition dark:border-gray-700 {{ $hasUploaded ? 'border-gov-300 bg-gov-50/30 dark:border-gov-800/40 dark:bg-gov-900/10' : 'border-surface-dim' }}"
                                    data-doc-id="{{ $doc->id }}" data-has-server="{{ $hasUploaded ? '1' : '0' }}">
                                    <div class="flex items-start gap-3">
                                        <span class="status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full {{ $hasUploaded ? 'bg-gov-100 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300' : 'bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500' }}">
                                            @if ($hasUploaded)
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            @else
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                            @endif
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-body-sm font-bold text-on-surface dark:text-gray-100">{{ $loop->iteration }}. {{ $doc->name }}</p>
                                                @if ($doc->is_conditional)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-accent-600 bg-accent-50 rounded border border-accent-500/20">
                                                        <span class="material-symbols-outlined text-[13px]" aria-hidden="true">help</span>{{ __('training.conditional') }}
                                                    </span>
                                                @endif
                                                @if ($doc->template_path)
                                                    <a href="{{ route('training.document-template.download', $doc) }}"
                                                       class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-primary bg-primary/10 rounded border border-primary/25 hover:bg-primary/15 transition">
                                                        <span class="material-symbols-outlined text-[13px]" aria-hidden="true">download</span>{{ __('training.download_form') }}
                                                    </a>
                                                @endif
                                            </div>
                                            @if ($hasUploaded)
                                                <div class="mt-2 flex items-center gap-3 rounded-lg border border-surface-dim bg-surface-container-lowest p-2.5 dark:border-gray-700 dark:bg-gray-800/60">
                                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-gov-50 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg>
                                                    </span>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="truncate text-xs font-semibold text-on-surface dark:text-gray-100">{{ $uploadedFile->original_name }}</p>
                                                        <p class="text-[11px] text-outline">{{ number_format($uploadedFile->size / 1024, 0) }} KB</p>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="preview-list mt-3 hidden space-y-1.5"></div>
                                            <div class="mt-3 upload-area">
                                                <label class="inline-flex cursor-pointer items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-3 py-2 text-xs font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                    <span class="material-symbols-outlined text-[15px] text-primary" aria-hidden="true">upload_file</span>
                                                    <span class="upload-label">{{ $hasUploaded ? __('training.replace_file') : __('training.upload_file') }}</span>
                                                    <input type="file" name="documents[{{ $doc->id }}][]" class="sr-only file-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    {{-- Footer --}}
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim bg-surface-container px-6 py-5 dark:border-gray-700 dark:bg-gray-900/40">
                        <div class="text-xs text-secondary dark:text-gray-400">{{ __('training.required_note') }}</div>
                        <div class="flex flex-col-reverse sm:flex-row items-center gap-3">
                            <a href="{{ route('training.step2.show', $tr) }}"
                               class="inline-flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                {{ __('training.back') }}
                            </a>
                            <button type="submit" id="step3-submit-btn"
                                class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-70"
                                data-loading-text="{{ __('training.submitting') }}">
                                <span class="material-symbols-outlined filled text-[18px]" aria-hidden="true">outbox</span>
                                <span id="step3-submit-label">{{ __('training.submit_dossier') }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
    (function () {
        var form  = document.getElementById('training-step3-form');
        var btn   = document.getElementById('step3-submit-btn');
        var lbl   = document.getElementById('step3-submit-label');
        var prog  = document.getElementById('docs-progress-count');
        var total = document.querySelectorAll('.doc-card').length;
        var LABEL_UPLOAD  = @json(__('training.upload_file'));
        var LABEL_REPLACE = @json(__('training.replace_file'));
        var LABEL_REMOVE  = @json(__('training.remove_file'));

        function updateProgress() {
            if (!prog) return;
            var done = 0;
            document.querySelectorAll('.doc-card').forEach(function (card) {
                var input = card.querySelector('.file-input');
                var staged = input && input.files ? input.files.length : 0;
                if (staged > 0 || card.dataset.hasServer === '1') done++;
            });
            prog.textContent = done + '/' + total;
        }
        function stagedFiles(inp) { if (!inp._staged) inp._staged = []; return inp._staged; }
        function syncFiles(inp) { var dt = new DataTransfer(); stagedFiles(inp).forEach(function(f){ dt.items.add(f); }); inp.files = dt.files; }
        function renderPreview(card) {
            var inp = card.querySelector('.file-input');
            var pl  = card.querySelector('.preview-list');
            if (!pl) return;
            pl.innerHTML = '';
            var staged = stagedFiles(inp);
            if (!staged.length) { pl.classList.add('hidden'); return; }
            pl.classList.remove('hidden');
            staged.forEach(function (file, idx) {
                var row = document.createElement('div');
                row.className = 'flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-800';
                var kbSize = (file.size/1024).toFixed(0) + ' KB';
                row.innerHTML = '<svg class="h-5 w-5 shrink-0 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg><span class="flex-1 min-w-0 truncate text-on-surface dark:text-gray-200 font-medium">'+file.name+'</span><span class="text-outline shrink-0">'+kbSize+'</span><button type="button" class="shrink-0 remove-staged text-outline hover:text-red-600 transition" data-idx="'+idx+'" title="'+LABEL_REMOVE+'"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>';
                row.querySelector('.remove-staged').addEventListener('click', function(){
                    stagedFiles(inp).splice(parseInt(this.dataset.idx,10),1);
                    syncFiles(inp); renderPreview(card); refreshCard(card);
                });
                pl.appendChild(row);
            });
        }
        function refreshCard(card) {
            var inp = card.querySelector('.file-input');
            var hasServer = card.dataset.hasServer === '1';
            var stagedCount = stagedFiles(inp).length;
            var statusIcon = card.querySelector('.status-icon');
            var uploadLabel = card.querySelector('.upload-label');
            if (stagedCount === 0 && !hasServer) {
                card.classList.add('border-surface-dim'); card.classList.remove('border-gov-300','bg-gov-50/30','dark:border-gov-800/40','dark:bg-gov-900/10');
                statusIcon.className='status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500';
                statusIcon.innerHTML='<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>';
            } else {
                card.classList.remove('border-surface-dim'); card.classList.add('border-gov-300','bg-gov-50/30','dark:border-gov-800/40','dark:bg-gov-900/10');
                statusIcon.className='status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-gov-100 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300';
                statusIcon.innerHTML='<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
            }
            if (uploadLabel) uploadLabel.textContent = (stagedCount > 0 || hasServer) ? LABEL_REPLACE : LABEL_UPLOAD;
            updateProgress();
        }
        document.querySelectorAll('.file-input').forEach(function (inp) {
            inp.addEventListener('change', function () {
                var files = Array.prototype.slice.call(this.files);
                var card = this.closest('.doc-card');
                if (!card || !files.length) return;
                stagedFiles(inp).push.apply(stagedFiles(inp), files);
                syncFiles(inp); renderPreview(card); refreshCard(card);
            });
        });
        if (form) { form.addEventListener('submit', function(){ if(btn)btn.disabled=true; var lt=btn?btn.getAttribute('data-loading-text'):null; if(lbl&&lt)lbl.textContent=lt; }); }
        updateProgress();
    }());
    </script>
</x-app-layout>
