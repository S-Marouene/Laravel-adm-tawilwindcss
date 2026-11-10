@php
    $step1Lines = array_filter([
        $rescueCertificate->nom_prenom,
        $rescueCertificate->cin,
        $rescueCertificate->phone,
        $rescueCertificate->governorate?->name,
    ]);
    $wizardSteps = [
        [
            'state'     => 'done',
            'title'     => __('rescue_certificate.step_one_title'),
            'data'      => array_values($step1Lines),
            'editUrl'   => route('rescue-certificates.step1.edit', $rescueCertificate),
            'editLabel' => __('rescue_certificate.edit_step1'),
        ],
        [
            'state'   => 'active',
            'title'   => __('rescue_certificate.step_two_title'),
        ],
    ];

    $uploadedCount = $uploadedDocs->count();
@endphp

<x-app-layout>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Barre de progression horizontale --}}
            <x-onpc.dossier-stepper :steps="$wizardSteps" />

            {{-- Bandeau de statut de l'étape + compteur --}}
            <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <span class="text-label-caps text-primary uppercase tracking-wider">
                        {{ __('rescue_certificate.step_of', ['step' => 2, 'total' => 2]) }}
                    </span>
                    <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('rescue_certificate.step2_heading') }} — {{ __('rescue_certificate.step_two_title') }}</h2>
                </div>
                <span id="docs-progress" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-gov-50 text-gov-800 text-body-sm font-semibold border border-gov-300 dark:bg-gov-900/30 dark:text-gov-300 dark:border-gov-800">
                    <span class="material-symbols-outlined filled text-[15px]" aria-hidden="true">task_alt</span>
                    <span id="docs-progress-count">{{ $uploadedCount }}/{{ $documentTypes->count() }}</span>
                </span>
            </div>

            {{-- ================================================================
                 FORMULAIRE — document upload
                 ================================================================ --}}
            <section class="space-y-6">

                <form method="POST" action="{{ route('rescue-certificates.step2.store', $rescueCertificate) }}"
                      id="rescue-step2-form" enctype="multipart/form-data"
                      class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    @csrf

                    {{-- ⚠️ Erreurs de validation --}}
                    <div class="p-6 pb-0">
                        @if ($errors->any())
                            <div class="rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                                <p class="font-bold">{{ __('rescue_certificate.create_error_title') }}</p>
                                <ul class="mt-1.5 list-disc space-y-1 ps-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    {{-- =====================================================
                         SECTION C — PIÈCES JUSTIFICATIVES
                         ===================================================== --}}
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                            <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">C</span>
                            <h3 class="text-title-section text-on-surface uppercase tracking-wider">
                                {{ __('rescue_certificate.step_two_title') }}
                            </h3>
                        </div>

                        {{-- Info banner --}}
                        <div class="flex items-start gap-3 rounded border border-surface-dim bg-surface-container-low p-4 text-body-sm dark:border-gray-700 dark:bg-gray-900/40" role="note">
                            <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">info</span>
                            <p class="text-secondary dark:text-gray-300">{{ __('rescue_certificate.documents_info') }}</p>
                        </div>

                        {{-- Document list --}}
                        <ol id="doc-list" class="space-y-3">
                            @foreach ($documentTypes as $doc)
                                @php
                                    $uploadedFile  = $uploadedDocs->get($doc->id);
                                    $hasUploaded   = $uploadedFile !== null;
                                @endphp
                                <li
                                    class="doc-card rounded border p-4 transition dark:border-gray-700 {{ $hasUploaded ? 'border-gov-300 bg-gov-50/30 dark:border-gov-800/40 dark:bg-gov-900/10' : 'border-surface-dim' }}"
                                    data-doc-id="{{ $doc->id }}"
                                    data-has-server="{{ $hasUploaded ? '1' : '0' }}"
                                >
                                    <div class="flex items-start gap-3">
                                        {{-- Status icon --}}
                                        <span class="status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full {{ $hasUploaded ? 'bg-gov-100 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300' : 'bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500' }}">
                                            @if ($hasUploaded)
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            @else
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                            @endif
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            {{-- Document name + badges --}}
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-body-sm font-bold text-on-surface dark:text-gray-100">
                                                    {{ $loop->iteration }}. {{ $doc->name }}
                                                </p>
                                                @if ($doc->is_conditional)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-accent-600 bg-accent-50 rounded border border-accent-500/20 dark:bg-accent-900/30 dark:text-accent-500">
                                                        <span class="material-symbols-outlined text-[13px]" aria-hidden="true">help</span>
                                                        {{ __('rescue_certificate.conditional') }}
                                                    </span>
                                                @endif
                                                @if ($doc->template_path)
                                                    <a href="{{ route('rescue-certificates.document-template.download', $doc) }}"
                                                       class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold
                                                              text-primary bg-primary/10 rounded border border-primary/25 transition
                                                              hover:bg-primary/15 cursor-pointer dark:bg-primary/15 dark:border-primary/40"
                                                       title="{{ __('rescue_certificate.download_form_hint') }}">
                                                        <span class="material-symbols-outlined text-[13px]" aria-hidden="true">download</span>
                                                        {{ __('rescue_certificate.download_form') }}
                                                        <span class="text-primary/60">({{ $doc->templateExtension() }})</span>
                                                    </a>
                                                @endif
                                            </div>

                                            {{-- Already-uploaded file from server --}}
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

                                            {{-- Staged file previews (JS-populated) --}}
                                            <div class="preview-list mt-3 hidden space-y-1.5"></div>

                                            {{-- Upload button — cumulative add --}}
                                            <div class="mt-3 upload-area">
                                                <label class="inline-flex cursor-pointer items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-3 py-2 text-xs font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                    <span class="material-symbols-outlined text-[15px] text-primary" aria-hidden="true">upload_file</span>
                                                    <span class="upload-label">{{ $hasUploaded ? __('rescue_certificate.replace_file') : __('rescue_certificate.upload_file') }}</span>
                                                    <input
                                                        type="file"
                                                        name="documents[{{ $doc->id }}][]"
                                                        class="sr-only file-input"
                                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                        multiple
                                                    >
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
                        <div class="text-xs text-secondary dark:text-gray-400">
                            {{ __('rescue_certificate.required_note') }}
                        </div>
                        <div class="flex flex-col-reverse sm:flex-row items-center gap-3">
                            <a href="{{ route('rescue-certificates.step1.edit', $rescueCertificate) }}"
                               class="inline-flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                {{ __('rescue_certificate.back') }}
                            </a>
                            <button
                                type="submit"
                                id="step2-submit-btn"
                                class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                                data-loading-text="{{ __('rescue_certificate.submitting') }}"
                            >
                                <span class="material-symbols-outlined filled text-[18px]" aria-hidden="true">outbox</span>
                                <span id="step2-submit-label">{{ __('rescue_certificate.submit_dossier') }}</span>
                            </button>
                        </div>
                    </div>
                </form>

            </section>
        </div>
    </div>

    <script>
    (function () {
        var form         = document.getElementById('rescue-step2-form');
        var btn          = document.getElementById('step2-submit-btn');
        var lbl          = document.getElementById('step2-submit-label');
        var fileInputs   = document.querySelectorAll('.file-input');
        var progressCount = document.getElementById('docs-progress-count');
        var totalDocs    = document.querySelectorAll('.doc-card').length;

        var LABEL_UPLOAD = '{{ __('rescue_certificate.upload_file') }}';
        var LABEL_ADD    = '{{ __('rescue_certificate.replace_file') }}';
        var LABEL_REMOVE = '{{ __('rescue_certificate.remove_file') }}';

        /* ── Live counter ── */
        function updateProgress() {
            if (!progressCount) return;
            var done = 0;
            document.querySelectorAll('.doc-card').forEach(function (card) {
                var input = card.querySelector('.file-input');
                var staged = input && input.files ? input.files.length : 0;
                var hasServer = card.dataset.hasServer === '1';
                if (staged > 0 || hasServer) done++;
            });
            progressCount.textContent = done + '/' + totalDocs;
        }

        fileInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var files = Array.prototype.slice.call(this.files);
                var card = this.closest('.doc-card');
                if (!card || !files.length) return;

                // Cumulative add
                stagedFiles(input).push.apply(stagedFiles(input), files);
                syncInputFiles(input);
                renderPreviewList(card);
                refreshCardState(card);
            });
        });

        /* Staged files registry */
        function stagedFiles(input) {
            if (!input._staged) input._staged = [];
            return input._staged;
        }

        function syncInputFiles(input) {
            var dt = new DataTransfer();
            stagedFiles(input).forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
        }

        function refreshCardState(card) {
            var input      = card.querySelector('.file-input');
            var hasServer  = card.dataset.hasServer === '1';
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

        /* Render per-file preview rows */
        function renderPreviewList(card) {
            var input = card.querySelector('.file-input');
            var list  = stagedFiles(input);
            var container = card.querySelector('.preview-list');
            if (!container) return;
            container.innerHTML = '';

            list.forEach(function (file, idx) {
                var row = document.createElement('div');
                row.className = 'flex items-center gap-3 rounded-lg border border-surface-dim bg-surface-container-lowest p-2.5 dark:border-gray-700 dark:bg-gray-800/60';

                /* Thumbnail or icon */
                var isImage = file.type.startsWith('image/');
                var ext = (file.name.split('.').pop() || '').toLowerCase();
                var iconColor = ext === 'pdf' ? 'text-red-500' : (ext === 'doc' || ext === 'docx' ? 'text-blue-500' : 'text-outline');

                row.innerHTML =
                    '<div class="flex h-10 w-10 flex-none items-center justify-center overflow-hidden rounded-lg bg-gov-50 text-gov-700 dark:bg-gov-900/40 dark:text-gov-300">' +
                        (isImage
                            ? '<img class="h-full w-full object-cover staged-img" data-idx="' + idx + '" alt="">'
                            : '<svg class="h-5 w-5 ' + iconColor + '" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg>') +
                    '</div>' +
                    '<div class="min-w-0 flex-1">' +
                        '<p class="truncate text-xs font-semibold text-on-surface dark:text-gray-100">' + file.name + '</p>' +
                        '<p class="text-[11px] text-outline">' + formatSize(file.size) + '</p>' +
                    '</div>' +
                    '<button type="button" data-remove-idx="' + idx + '"' +
                        ' class="flex h-8 w-8 items-center justify-center rounded-lg text-outline transition hover:bg-red-50 hover:text-red-600 cursor-pointer dark:hover:bg-red-900/20"' +
                        ' title="' + LABEL_REMOVE + '">' +
                        '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' +
                    '</button>';

                /* Load image thumbnail */
                if (isImage) {
                    var img = row.querySelector('.staged-img');
                    var reader = new FileReader();
                    reader.onload = (function (i) { return function (e) { i.src = e.target.result; }; })(img);
                    reader.readAsDataURL(file);
                }

                /* Remove button */
                row.querySelector('[data-remove-idx]').addEventListener('click', function () {
                    var removeIdx = parseInt(this.dataset.removeIdx, 10);
                    stagedFiles(input).splice(removeIdx, 1);
                    syncInputFiles(input);
                    renderPreviewList(card);
                    refreshCardState(card);
                });

                container.appendChild(row);
            });
        }

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(0) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        /* Submit lock */
        form.addEventListener('submit', function () {
            btn.disabled = true;
            var loadingText = btn.getAttribute('data-loading-text');
            if (lbl && loadingText) lbl.textContent = loadingText;
        });
    }());
    </script>
</x-app-layout>
