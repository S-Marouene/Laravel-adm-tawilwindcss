@php
    $si = $specialIntervention;

    $step1Lines = array_filter([
        $si->nom_prenom,
        $si->cin,
        $si->phone,
        $si->governorate?->name,
    ]);

    $wizardSteps = [
        [
            'state'     => 'done',
            'title'     => __('special_intervention.step_one_title'),
            'data'      => array_values($step1Lines),
            'editUrl'   => route('special-interventions.step1.edit', $si),
            'editLabel' => __('special_intervention.edit_step1'),
        ],
        [
            'state'     => 'done',
            'title'     => __('special_intervention.step_two_title'),
            'data'      => [__('special_intervention.total_amount') . ': ' . number_format($si->total_amount ?? 0, 0) . ' ' . __('special_intervention.currency_tnd')],
            'editUrl'   => route('special-interventions.step2.show', $si),
            'editLabel' => __('special_intervention.step_two_title'),
        ],
        [
            'state' => 'active',
            'title' => __('special_intervention.step_three_title'),
        ],
    ];

    $uploadedCount = $uploadedDocs->count();
@endphp

<x-app-layout>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            <x-onpc.dossier-stepper :steps="$wizardSteps" />

            <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <span class="text-label-caps text-primary uppercase tracking-wider">
                        {{ __('special_intervention.step_of', ['step' => 3, 'total' => 3]) }}
                    </span>
                    <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('special_intervention.step3_heading') }} — {{ __('special_intervention.step_three_title') }}</h2>
                </div>
                <span id="docs-progress" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-gov-50 text-gov-800 text-body-sm font-semibold border border-gov-300 dark:bg-gov-900/30 dark:text-gov-300 dark:border-gov-800">
                    <span class="material-symbols-outlined filled text-[15px]" aria-hidden="true">task_alt</span>
                    <span id="docs-progress-count">{{ $uploadedCount }}/{{ $documentTypes->count() }}</span>
                </span>
            </div>

            <section class="space-y-6">
                <form method="POST" action="{{ route('special-interventions.step3.store', $si) }}"
                      id="si-step3-form" enctype="multipart/form-data"
                      class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    @csrf

                    <div class="p-6 pb-0">
                        @if ($errors->any())
                            <div class="rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                                <p class="font-bold">{{ __('special_intervention.create_error_title') }}</p>
                                <ul class="mt-1.5 list-disc space-y-1 ps-5">
                                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">A</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">{{ __('special_intervention.step_three_title') }}</h3>
                        </div>

                        {{-- Info banner --}}
                        <div class="flex items-start gap-3 rounded border border-surface-dim bg-surface-container-low p-4 text-body-sm dark:border-gray-700 dark:bg-gray-900/40" role="note">
                            <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">info</span>
                            <p class="text-secondary dark:text-gray-300">{{ __('special_intervention.documents_info') }}</p>
                        </div>

                        {{-- Document list --}}
                        <ol id="doc-list" class="space-y-3">
                            @foreach ($documentTypes as $doc)
                                @php
                                    $uploadedFile = $uploadedDocs->get($doc->id);
                                    $hasUploaded  = $uploadedFile !== null;
                                @endphp
                                <li class="doc-card rounded border p-4 transition dark:border-gray-700 {{ $hasUploaded ? 'border-gov-300 bg-gov-50/30 dark:border-gov-800/40 dark:bg-gov-900/10' : 'border-surface-dim' }}"
                                    data-doc-id="{{ $doc->id }}"
                                    data-has-server="{{ $hasUploaded ? '1' : '0' }}">
                                    <div class="flex items-start gap-3">
                                        <span class="status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full {{ $hasUploaded ? 'bg-gov-100 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300' : 'bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500' }}">
                                            @if ($hasUploaded)
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            @else
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                            @endif
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-body-sm font-bold text-on-surface dark:text-gray-100">
                                                    {{ $loop->iteration }}. {{ $doc->name }}
                                                </p>
                                                @if ($doc->is_conditional)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-accent-600 bg-accent-50 rounded border border-accent-500/20">
                                                        <span class="material-symbols-outlined text-[13px]" aria-hidden="true">help</span>
                                                        {{ __('special_intervention.conditional') }}
                                                    </span>
                                                @endif
                                                @if ($doc->template_path)
                                                    <a href="{{ route('special-interventions.document-template.download', $doc) }}"
                                                       class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-primary bg-primary/10 rounded border border-primary/25 transition hover:bg-primary/15 cursor-pointer">
                                                        <span class="material-symbols-outlined text-[13px]" aria-hidden="true">download</span>
                                                        {{ __('special_intervention.download_form') }}
                                                        <span class="text-primary/60">({{ $doc->templateExtension() }})</span>
                                                    </a>
                                                @endif
                                            </div>

                                            @if ($hasUploaded)
                                                <div class="mt-2 flex items-center gap-3 rounded-lg border border-surface-dim bg-surface-container-lowest p-2.5 dark:border-gray-700 dark:bg-gray-800/60">
                                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-gov-50 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg>
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
                                                    <span class="upload-label">{{ $hasUploaded ? __('special_intervention.replace_file') : __('special_intervention.upload_file') }}</span>
                                                    <input type="file"
                                                           name="documents[{{ $doc->id }}][]"
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

                    {{-- Footer --}}
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim bg-surface-container px-6 py-5 dark:border-gray-700 dark:bg-gray-900/40">
                        <div class="text-xs text-secondary dark:text-gray-400">{{ __('special_intervention.required_note') }}</div>
                        <div class="flex flex-col-reverse sm:flex-row items-center gap-3">
                            <a href="{{ route('special-interventions.step2.show', $si) }}"
                               class="inline-flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                {{ __('special_intervention.back') }}
                            </a>
                            <button type="submit" id="step3-submit-btn"
                                class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                                data-loading-text="{{ __('special_intervention.submitting') }}">
                                <span class="material-symbols-outlined filled text-[18px]" aria-hidden="true">outbox</span>
                                <span id="step3-submit-label">{{ __('special_intervention.submit_dossier') }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
    (function () {
        var form          = document.getElementById('si-step3-form');
        var btn           = document.getElementById('step3-submit-btn');
        var lbl           = document.getElementById('step3-submit-label');
        var progressCount = document.getElementById('docs-progress-count');
        var totalDocs     = document.querySelectorAll('.doc-card').length;

        var LABEL_UPLOAD  = @json(__('special_intervention.upload_file'));
        var LABEL_REPLACE = @json(__('special_intervention.replace_file'));
        var LABEL_REMOVE  = @json(__('special_intervention.remove_file'));

        function updateProgress() {
            if (!progressCount) return;
            var done = 0;
            document.querySelectorAll('.doc-card').forEach(function (card) {
                var input     = card.querySelector('.file-input');
                var staged    = input && input.files ? input.files.length : 0;
                var hasServer = card.dataset.hasServer === '1';
                if (staged > 0 || hasServer) done++;
            });
            progressCount.textContent = done + '/' + totalDocs;
        }

        function stagedFiles(input) {
            if (!input._staged) input._staged = [];
            return input._staged;
        }
        function syncInputFiles(input) {
            var dt = new DataTransfer();
            stagedFiles(input).forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
        }
        function renderPreviewList(card) {
            var input     = card.querySelector('.file-input');
            var previewEl = card.querySelector('.preview-list');
            if (!previewEl) return;
            var staged = stagedFiles(input);
            previewEl.innerHTML = '';
            if (!staged.length) { previewEl.classList.add('hidden'); return; }
            previewEl.classList.remove('hidden');
            staged.forEach(function (file, idx) {
                var row = document.createElement('div');
                row.className = 'flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-800';
                var isImg = file.type.startsWith('image/');
                var imgEl = '';
                if (isImg) {
                    imgEl = '<img class="h-7 w-7 rounded object-cover shrink-0" alt="" />';
                } else {
                    imgEl = '<svg class="h-5 w-5 shrink-0 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg>';
                }
                var kbSize = (file.size / 1024).toFixed(0) + ' KB';
                row.innerHTML = imgEl +
                    '<span class="flex-1 min-w-0 truncate text-on-surface dark:text-gray-200 font-medium">' + file.name + '</span>' +
                    '<span class="text-outline shrink-0">' + kbSize + '</span>' +
                    '<button type="button" class="shrink-0 remove-staged text-outline hover:text-red-600 dark:hover:text-red-400 transition" data-idx="' + idx + '" title="' + LABEL_REMOVE + '">' +
                    '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' +
                    '</button>';
                if (isImg) {
                    var reader = new FileReader();
                    reader.onload = function (e) { row.querySelector('img').src = e.target.result; };
                    reader.readAsDataURL(file);
                }
                row.querySelector('.remove-staged').addEventListener('click', function () {
                    stagedFiles(input).splice(parseInt(this.dataset.idx, 10), 1);
                    syncInputFiles(input);
                    renderPreviewList(card);
                    refreshCardState(card);
                });
                previewEl.appendChild(row);
            });
        }

        function refreshCardState(card) {
            var input      = card.querySelector('.file-input');
            var hasServer  = card.dataset.hasServer === '1';
            var stagedCount = stagedFiles(input).length;
            var statusIcon  = card.querySelector('.status-icon');
            var uploadLabel = card.querySelector('.upload-label');

            if (stagedCount === 0 && !hasServer) {
                card.classList.add('border-surface-dim');
                card.classList.remove('border-gov-300', 'bg-gov-50/30', 'dark:border-gov-800/40', 'dark:bg-gov-900/10');
                statusIcon.className = 'status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500';
                statusIcon.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>';
            } else {
                card.classList.remove('border-surface-dim');
                card.classList.add('border-gov-300', 'bg-gov-50/30', 'dark:border-gov-800/40', 'dark:bg-gov-900/10');
                statusIcon.className = 'status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-gov-100 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300';
                statusIcon.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
            }
            if (uploadLabel) {
                uploadLabel.textContent = (stagedCount > 0 || hasServer) ? LABEL_REPLACE : LABEL_UPLOAD;
            }
            updateProgress();
        }

        document.querySelectorAll('.file-input').forEach(function (input) {
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

        if (form) {
            form.addEventListener('submit', function () {
                if (btn) btn.disabled = true;
                var lt = btn ? btn.getAttribute('data-loading-text') : null;
                if (lbl && lt) lbl.textContent = lt;
            });
        }

        updateProgress();
    }());
    </script>
</x-app-layout>
