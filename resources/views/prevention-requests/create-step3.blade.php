@php
    $totalSteps = 3;
    $step1Lines = [
        $dossier->nom_prenom,
        $dossier->cin,
    ];
    $step2Lines = array_filter([
        $dossier->buildingType?->name,
        $dossier->buildingActivity?->name ?: $dossier->activity_other,
    ]);

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
            'state'     => 'done',
            'title'     => __('prevention_request.step_two_title'),
            'data'      => $step2Lines,
            'editUrl'   => route('prevention-requests.step2.show', $dossier),
            'editLabel' => __('prevention_request.edit_step2'),
        ],
        [
            'state' => 'active',
            'title' => __('prevention_request.step_three_title'),
        ],
    ];

    $uploadedCount = $uploadedDocs->count();
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

                    {{-- Bandeau de statut de l'étape (filet rouge ONPC) + compteur --}}
                    <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div>
                            <span class="text-label-caps text-primary uppercase tracking-wider">
                                {{ __('prevention_request.step_of', ['step' => 3, 'total' => $totalSteps]) }}
                            </span>
                            <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('prevention_request.step3_heading') }} — {{ __('prevention_request.step_three_title') }}</h2>
                        </div>
                        <span id="docs-progress" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-emerald-50 text-emerald-800 text-body-sm font-semibold border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900">
                            <span class="material-symbols-outlined filled text-[15px]" aria-hidden="true">task_alt</span>
                            <span id="docs-progress-count">{{ $uploadedCount }}/{{ $documentTypes->count() }}</span>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('prevention-requests.step3.store', $dossier) }}" id="prevention-step3-form" enctype="multipart/form-data"
                          class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        @csrf

                        {{-- ⚠️ Erreurs de validation (résumé en tête) --}}
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
                             SECTION C — PIÈCES JUSTIFICATIVES
                             ===================================================== --}}
                        <div class="p-6 space-y-5">
                            <div class="flex items-center gap-2.5 pb-3 border-b border-surface-dim dark:border-gray-700">
                                <span class="w-7 h-7 rounded bg-primary text-white flex items-center justify-center text-label-caps shrink-0" aria-hidden="true">C</span>
                                <h3 class="text-title-section text-on-surface uppercase tracking-wider">
                                    {{ __('prevention_request.step_three_title') }}
                                </h3>
                            </div>

                            {{-- Baton d'information format --}}
                            <div class="flex items-start gap-3 rounded border border-surface-dim bg-surface-container-low p-4 text-body-sm dark:border-gray-700 dark:bg-gray-900/40" role="note">
                                <span class="material-symbols-outlined filled text-primary text-[22px]" aria-hidden="true">info</span>
                                <p class="text-secondary dark:text-gray-300">{{ __('prevention_request.documents_info') }}</p>
                            </div>

                            {{-- Liste des documents — réf. gabarit nouvelle.html (chainage A->C) --}}
                            <ol id="doc-list" class="space-y-3">
                                @foreach ($documentTypes as $doc)
                                    @php
                                        $uploaded = $uploadedDocs->get($doc->id);
                                    @endphp
                                    <li
                                        class="doc-card rounded border p-4 transition dark:border-gray-700 {{ $uploaded ? 'border-emerald-300 bg-emerald-50/30 dark:border-emerald-800/40 dark:bg-emerald-900/10' : 'border-surface-dim' }}"
                                        data-doc-id="{{ $doc->id }}"
                                    >
                                        <div class="flex items-start gap-3">
                                            {{-- Icône de statut --}}
                                            <span class="status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full {{ $uploaded ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500' }}">
                                                @if ($uploaded)
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                @else
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                @endif
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                {{-- Numéro + nom du document --}}
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-body-sm font-bold text-on-surface dark:text-gray-100">
                                                        {{ $loop->iteration }}. {{ $doc->name }}
                                                    </p>
                                                    @if ($doc->is_conditional)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-accent-600 bg-accent-50 rounded border border-accent-500/20 dark:bg-accent-900/30 dark:text-accent-500">
                                                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">help</span>
                                                            {{ __('prevention_request.conditional') }}
                                                        </span>
                                                    @endif
                                                </div>

                                                {{-- Fichier déjà enregistré (côté serveur) --}}
                                                @if ($uploaded)
                                                    <div class="mt-2 flex items-center gap-2 text-xs text-emerald-600 dark:text-emerald-400">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg>
                                                        <span class="truncate">{{ $uploaded->original_name }}</span>
                                                        <span class="flex-none text-outline">({{ number_format($uploaded->size / 1024, 0) }} KB)</span>
                                                    </div>
                                                @endif

                                                {{-- Zone d'aperçu (remplie par le JS à la sélection) --}}
                                                <div class="preview-area mt-3 hidden">
                                                    <div class="flex items-center gap-3 rounded border border-emerald-200 bg-emerald-50 p-2.5 dark:border-emerald-800/40 dark:bg-emerald-900/10">
                                                        <div class="preview-thumb flex h-14 w-14 flex-none items-center justify-center overflow-hidden rounded bg-surface-container-lowest dark:bg-gray-800">
                                                            <img class="preview-img hidden h-full w-full object-cover" alt="">
                                                            <svg class="preview-icon h-7 w-7 text-outline hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <p class="preview-name truncate text-xs font-semibold text-on-surface dark:text-gray-100"></p>
                                                            <p class="preview-size text-[11px] text-outline"></p>
                                                        </div>
                                                        <button type="button" class="remove-file flex-none rounded p-1 text-outline transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 cursor-pointer" title="{{ __('prevention_request.remove_file') }}">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- Bouton d'envoi --}}
                                                <div class="mt-3 upload-area">
                                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-3 py-2 text-xs font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                        <span class="material-symbols-outlined text-[15px] text-primary" aria-hidden="true">upload_file</span>
                                                        <span class="upload-label">{{ $uploaded ? __('prevention_request.replace_file') : __('prevention_request.upload_file') }}</span>
                                                        <input
                                                            type="file"
                                                            name="documents[{{ $doc->id }}]"
                                                            class="sr-only file-input"
                                                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                        >
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        {{-- Pied — déposer la demande (banneau rouge du gabarit) --}}
                        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim bg-surface-container px-6 py-5 dark:border-gray-700 dark:bg-gray-900/40">
                            <div class="text-xs text-secondary dark:text-gray-400">
                                {{ __('prevention_request.required_note') }}
                            </div>
                            <div class="flex flex-col-reverse sm:flex-row items-center gap-3">
                                <a href="{{ route('prevention-requests.step2.show', $dossier) }}"
                                   class="inline-flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    {{ __('prevention_request.back') }}
                                </a>
                                <button
                                    type="submit"
                                    id="step3-submit-btn"
                                    class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                                    data-loading-text="{{ __('prevention_request.submitting') }}"
                                >
                                    <span class="material-symbols-outlined filled text-[18px]" aria-hidden="true">outbox</span>
                                    <span id="step3-submit-label">{{ __('prevention_request.submit_dossier') }}</span>
                                </button>
                            </div>
                        </div>
                    </form>
            </section>
        </div>
    </div>

    <script>
    (function () {
        var form = document.getElementById('prevention-step3-form');
        var btn = document.getElementById('step3-submit-btn');
        var lbl = document.getElementById('step3-submit-label');
        var fileInputs = document.querySelectorAll('.file-input');
        var progressCount = document.getElementById('docs-progress-count');
        var totalDocs = document.querySelectorAll('.doc-card').length;

        /* ── Live counter of uploaded documents (step banner) ── */
        function updateProgress() {
            if (!progressCount) return;
            var done = 0;
            document.querySelectorAll('.doc-card').forEach(function (card) {
                if (card.querySelector('.file-input').value ||
                    card.querySelector('.status-icon').classList.contains('bg-emerald-100')) {
                    done++;
                }
            });
            progressCount.textContent = done + '/' + totalDocs;
        }

        fileInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var file = this.files[0];
                var card = this.closest('.doc-card');
                if (!card || !file) return;

                // ── Update card to "loaded" state ──
                card.classList.remove('border-surface-dim');
                card.classList.add('border-emerald-300', 'bg-emerald-50/30', 'dark:border-emerald-800/40', 'dark:bg-emerald-900/10');

                // ── Update status icon to checkmark ──
                var statusIcon = card.querySelector('.status-icon');
                statusIcon.className = 'status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400';
                statusIcon.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';

                // ── Show preview area ──
                var previewArea = card.querySelector('.preview-area');
                previewArea.classList.remove('hidden');

                var previewImg  = card.querySelector('.preview-img');
                var previewIcon = card.querySelector('.preview-icon');
                var previewName = card.querySelector('.preview-name');
                var previewSize = card.querySelector('.preview-size');

                previewName.textContent = file.name;
                previewSize.textContent = formatSize(file.size);

                // ── Show image thumbnail or file icon ──
                var isImage = file.type.startsWith('image/');
                if (isImage) {
                    previewImg.classList.remove('hidden');
                    previewIcon.classList.add('hidden');
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImg.classList.add('hidden');
                    previewIcon.classList.remove('hidden');
                    // Color the icon by file type
                    var ext = (file.name.split('.').pop() || '').toLowerCase();
                    if (ext === 'pdf') {
                        previewIcon.classList.add('text-red-500');
                    } else if (ext === 'doc' || ext === 'docx') {
                        previewIcon.classList.add('text-blue-500');
                    } else {
                        previewIcon.classList.add('text-outline');
                    }
                }

                // ── Update upload label to "replace" ──
                var uploadLabel = card.querySelector('.upload-label');
                uploadLabel.textContent = '{{ __('prevention_request.replace_file') }}';

                updateProgress();
            });
        });

        /* ── Remove button (event delegation: survives re-renders) ── */
        var docList = document.getElementById('doc-list');
        if (docList) {
            docList.addEventListener('click', function (e) {
                var btnRemove = e.target.closest('.remove-file');
                if (!btnRemove) return;
                var card = btnRemove.closest('.doc-card');
                if (!card) return;

                var input = card.querySelector('.file-input');
                if (input) input.value = '';

                card.querySelector('.preview-area').classList.add('hidden');
                card.classList.add('border-surface-dim');
                card.classList.remove('border-emerald-300', 'bg-emerald-50/30', 'dark:border-emerald-800/40', 'dark:bg-emerald-900/10');

                var statusIcon = card.querySelector('.status-icon');
                statusIcon.className = 'status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500';
                statusIcon.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>';

                var uploadLabel = card.querySelector('.upload-label');
                if (uploadLabel) uploadLabel.textContent = '{{ __('prevention_request.upload_file') }}';

                updateProgress();
            });
        }

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(0) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        form.addEventListener('submit', function () {
            btn.disabled = true;
            lbl.textContent = btn.dataset.loadingText;
        });
    }());
    </script>
</x-app-layout>
