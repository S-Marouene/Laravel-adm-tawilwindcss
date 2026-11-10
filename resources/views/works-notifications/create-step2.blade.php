@php
    $wizardSteps = [
        [
            'state'   => 'done',
            'title'   => __('works_notification.step_one_title'),
            'data'    => array_filter([
                $BuildingWorksDeclaration->isMoral() ? $BuildingWorksDeclaration->company_name : $BuildingWorksDeclaration->owner_name,
                $BuildingWorksDeclaration->owner_cin,
                $BuildingWorksDeclaration->phone,
                $BuildingWorksDeclaration->governorate?->name . ($BuildingWorksDeclaration->delegation ? ' — ' . $BuildingWorksDeclaration->delegation->name : ''),
            ]),
            'editUrl'   => route('building-works-declarations.step1.edit', $BuildingWorksDeclaration),
            'editLabel' => __('works_notification.edit_step1'),
        ],
        [
            'state'   => 'active',
            'title'   => __('works_notification.step_two_title'),
            'editing' => false,
        ],
    ];
@endphp

<x-app-layout>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Horizontal stepper --}}
            <x-onpc.dossier-stepper :steps="$wizardSteps" />

            {{-- Step status banner --}}
            <div class="bg-surface-container-lowest p-5 rounded border border-surface-dim border-l-4 border-l-primary flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <span class="text-label-caps text-primary uppercase tracking-wider">
                        {{ __('works_notification.step_of', ['step' => 2, 'total' => 2]) }}
                    </span>
                    <h2 class="text-headline-sm text-on-surface mt-0.5">{{ __('works_notification.step2_heading') }} — {{ __('works_notification.step_two_title') }}</h2>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('building-works-declarations.step1.edit', $BuildingWorksDeclaration) }}"
                       class="inline-flex items-center gap-1.5 rounded border border-surface-dim bg-surface-container-lowest px-3 py-2 text-xs font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">arrow_back</span>
                        {{ __('works_notification.back') }}
                    </a>
                </div>
            </div>

            {{-- Form card --}}
            <div class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800">

                {{-- Card header --}}
                <div class="border-b border-surface-dim px-6 py-5 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-on-surface dark:text-gray-100">{{ __('works_notification.step2_heading') }}</h3>
                    <p class="mt-1 text-sm text-secondary dark:text-gray-400">{{ __('works_notification.step2_description') }}</p>
                </div>

                <form method="POST"
                      action="{{ route('building-works-declarations.step2.store', $BuildingWorksDeclaration) }}"
                      class="p-6"
                      id="works-step2-form"
                      enctype="multipart/form-data">
                    @csrf

                    @if ($errors->any())
                        <div class="mb-6 rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                            <p class="font-bold">{{ __('works_notification.create_error_title') }}</p>
                            <ul class="mt-1.5 list-disc space-y-1 ps-5">
                                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Info banner --}}
                    <div class="mb-6 flex items-start gap-3 rounded border border-blue-200 bg-blue-50 p-4 text-sm dark:border-blue-800/40 dark:bg-blue-900/20">
                        <span class="material-symbols-outlined text-blue-600 text-lg mt-px shrink-0" aria-hidden="true">info</span>
                        <p class="text-blue-800 dark:text-blue-200 leading-relaxed">{{ __('works_notification.documents_info') }}</p>
                    </div>

                    {{-- Free procedure reminder --}}
                    <div class="mb-6 flex items-center gap-3 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                        <span class="material-symbols-outlined filled text-emerald-600 text-lg" aria-hidden="true">check_circle</span>
                        <p class="text-body-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ __('works_notification.free_info') }} — {{ __('works_notification.delay_info') }}</p>
                    </div>

                    {{-- Document list --}}
                    <div class="space-y-4">
                        @foreach ($documentTypes as $doc)
                            @php $uploaded = $uploadedDocs->get($doc->id); @endphp
                            <div class="doc-card rounded-xl border p-4 transition dark:border-gray-700
                                {{ $uploaded ? 'border-emerald-300 bg-emerald-50/30 dark:border-emerald-800/40 dark:bg-emerald-900/10' : 'border-surface-dim' }}"
                                data-doc-id="{{ $doc->id }}"
                                data-has-server="{{ $uploaded ? '1' : '0' }}">

                                <div class="flex items-start gap-3">
                                    {{-- Status icon --}}
                                    <span class="status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full
                                        {{ $uploaded ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500' }}">
                                        @if ($uploaded)
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        @else
                                            <span class="material-symbols-outlined text-base" aria-hidden="true">upload_file</span>
                                        @endif
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        {{-- Document name + badges --}}
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ $loop->iteration }}. {{ $doc->name }}</p>
                                            @if ($doc->is_conditional)
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('works_notification.conditional') }}</span>
                                            @endif
                                            @if ($doc->template_path)
                                                <a href="{{ route('building-works-declarations.document-template.download', $doc) }}"
                                                   class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold text-primary bg-primary/10 rounded border border-primary/25 transition hover:bg-primary/15 cursor-pointer dark:bg-primary/15 dark:border-primary/40"
                                                   title="{{ __('works_notification.download_form_hint') }}">
                                                    <span class="material-symbols-outlined text-[13px]" aria-hidden="true">download</span>
                                                    {{ __('works_notification.download_form') }}
                                                    <span class="text-primary/60">({{ $doc->templateExtension() }})</span>
                                                </a>
                                            @endif
                                        </div>

                                        {{-- Already-uploaded file indicator --}}
                                        @if ($uploaded)
                                            <div class="mt-2 flex items-center gap-2 text-xs text-emerald-600 dark:text-emerald-400">
                                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg>
                                                <span class="truncate">{{ $uploaded->original_name }}</span>
                                                <span class="flex-none text-outline">({{ number_format($uploaded->size / 1024, 0) }} KB)</span>
                                            </div>
                                        @endif

                                        {{-- Preview list (populated by JS on file select) --}}
                                        <div class="preview-list mt-3 hidden space-y-1.5"></div>

                                        {{-- Upload input — multiple files allowed --}}
                                        <div class="mt-3 upload-area">
                                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2 text-xs font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                <span class="upload-label">{{ $uploaded ? __('works_notification.add_file') : __('works_notification.upload_file') }}</span>
                                                <input type="file"
                                                    name="documents[{{ $doc->id }}][]"
                                                    class="sr-only file-input"
                                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                    multiple>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Form footer --}}
                    <div class="mt-8 flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim pt-5 dark:border-gray-700">
                        <a href="{{ route('building-works-declarations.step1.edit', $BuildingWorksDeclaration) }}"
                           class="inline-flex items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            {{ __('works_notification.back') }}
                        </a>
                        <button type="submit" id="step2-submit-btn"
                            class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                            data-loading-text="{{ __('works_notification.submitting') }}">
                            <span id="step2-submit-label">{{ __('works_notification.submit_dossier') }}</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
    (function () {
        var form = document.getElementById('works-step2-form');
        var btn  = document.getElementById('step2-submit-btn');
        var lbl  = document.getElementById('step2-submit-label');

        var LABEL_UPLOAD = '{{ __('works_notification.upload_file') }}';
        var LABEL_ADD    = '{{ __('works_notification.add_file') }}';
        var LABEL_REMOVE = '{{ __('works_notification.remove_file') }}';

        var fileInputs = document.querySelectorAll('.file-input');

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
            var input       = card.querySelector('.file-input');
            var hasServer   = card.dataset.hasServer === '1';
            var stagedCount = stagedFiles(input).length;
            var statusIcon  = card.querySelector('.status-icon');
            var uploadLabel = card.querySelector('.upload-label');
            var previewList = card.querySelector('.preview-list');

            if (previewList) previewList.classList.toggle('hidden', stagedCount === 0);

            if (stagedCount === 0 && !hasServer) {
                card.classList.add('border-surface-dim');
                card.classList.remove('border-emerald-300', 'bg-emerald-50/30', 'dark:border-emerald-800/40', 'dark:bg-emerald-900/10');
                statusIcon.className = 'status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500';
                statusIcon.innerHTML = '<span class="material-symbols-outlined text-base" aria-hidden="true">upload_file</span>';
            } else {
                card.classList.remove('border-surface-dim');
                card.classList.add('border-emerald-300', 'bg-emerald-50/30', 'dark:border-emerald-800/40', 'dark:bg-emerald-900/10');
                statusIcon.className = 'status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400';
                statusIcon.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
            }

            if (uploadLabel) {
                uploadLabel.textContent = (stagedCount > 0 || hasServer) ? LABEL_ADD : LABEL_UPLOAD;
            }
        }

        function renderPreviewList(card) {
            var input     = card.querySelector('.file-input');
            var list      = stagedFiles(input);
            var container = card.querySelector('.preview-list');
            if (!container) return;

            container.innerHTML = '';

            list.forEach(function (file, idx) {
                var row = document.createElement('div');
                row.className = 'flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-2.5 dark:border-emerald-800/40 dark:bg-emerald-900/10';

                row.innerHTML =
                    '<div class="staged-thumb flex h-10 w-10 flex-none items-center justify-center overflow-hidden rounded-lg bg-surface-container-lowest dark:bg-gray-800">' +
                        '<img class="hidden h-full w-full object-cover" alt="">' +
                        '<svg class="preview-icon h-5 w-5 text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>' +
                    '</div>' +
                    '<div class="min-w-0 flex-1">' +
                        '<p class="truncate text-xs font-semibold text-on-surface dark:text-gray-100"></p>' +
                        '<p class="text-[11px] text-outline"></p>' +
                    '</div>' +
                    '<span class="flex h-5 w-5 flex-none items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">' +
                        '<svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>' +
                    '</span>';

                row.querySelectorAll('p')[0].textContent = file.name;
                row.querySelectorAll('p')[1].textContent = formatSize(file.size);

                var badge = row.querySelector('.staged-thumb');
                var img   = badge.querySelector('img');
                var icon  = badge.querySelector('.preview-icon');
                if (file.type && file.type.indexOf('image/') === 0) {
                    img.classList.remove('hidden');
                    icon.classList.add('hidden');
                    var reader = new FileReader();
                    reader.onload = function (e) { img.src = e.target.result; };
                    reader.readAsDataURL(file);
                } else {
                    var ext = (file.name.split('.').pop() || '').toLowerCase();
                    if (ext === 'pdf')                     icon.classList.add('text-red-500');
                    else if (ext === 'doc' || ext === 'docx') icon.classList.add('text-blue-500');
                }

                var rm = document.createElement('button');
                rm.type      = 'button';
                rm.className = 'remove-file flex-none rounded-lg p-1 text-outline transition hover:bg-red-50 hover:text-red-500 cursor-pointer dark:hover:bg-red-900/20';
                rm.title     = LABEL_REMOVE;
                rm.dataset.idx = String(idx);
                rm.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
                row.appendChild(rm);

                container.appendChild(row);
            });
        }

        fileInputs.forEach(function (input) {
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

        document.querySelectorAll('.doc-card').forEach(function (card) {
            card.addEventListener('click', function (e) {
                var btnRemove = e.target.closest('.remove-file');
                if (!btnRemove || btnRemove.dataset.idx === undefined) return;
                var input = card.querySelector('.file-input');
                stagedFiles(input).splice(Number(btnRemove.dataset.idx), 1);
                syncInputFiles(input);
                renderPreviewList(card);
                refreshCardState(card);
            });
        });

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
