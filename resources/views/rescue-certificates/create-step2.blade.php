<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.create_title') }}</h2>
                <p class="mt-0.5 text-sm text-secondary dark:text-gray-400">{{ __('rescue_certificate.create_subtitle') }}</p>
            </div>
            <a href="{{ route('rescue-certificates.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2 text-sm font-medium text-on-surface transition hover:bg-surface-container dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                {{ __('rescue_certificate.back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[280px,1fr]">

                {{-- Step sidebar --}}
                <aside class="rounded-2xl border border-surface-dim bg-surface-container-lowest p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 h-fit">
                    <ol class="space-y-0">
                        {{-- Step 1 — done --}}
                        <li class="flex items-start gap-3">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <div class="min-w-0 flex-1 pb-3">
                                <p class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.step_one_title') }}</p>
                                <p class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ __('rescue_certificate.step_done') }}</p>
                                {{-- Step 1 data --}}
                                <div class="mt-2 rounded-lg bg-surface-container px-3 py-2 text-xs space-y-1 dark:bg-gray-900/40">
                                    <p class="font-medium text-on-surface dark:text-gray-200">{{ $rescueCertificate->nom_prenom }}</p>
                                    <p class="text-secondary dark:text-gray-400">{{ $rescueCertificate->cin }}</p>
                                    @if($rescueCertificate->phone)
                                        <p class="text-secondary dark:text-gray-400"><span class="num-ltr">{{ $rescueCertificate->phone }}</span></p>
                                    @endif
                                    <p class="text-secondary dark:text-gray-400">{{ $rescueCertificate->governorate?->name }}</p>
                                    <p class="text-secondary dark:text-gray-400">{{ $rescueCertificate->delegation?->name }}</p>
                                </div>
                                <a href="{{ route('rescue-certificates.step1.edit', $rescueCertificate) }}"
                                   class="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-gov-200 bg-surface-container-lowest px-3 py-1.5 text-xs font-semibold text-gov-700 transition hover:bg-gov-50 dark:border-gov-700 dark:bg-gray-800 dark:text-gov-400 dark:hover:bg-gov-900/20">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    {{ __('rescue_certificate.edit_step1') }}
                                </a>
                            </div>
                        </li>
                        {{-- Connector (green = completed) --}}
                        <li class="flex"><span class="ms-[17px] w-0.5 h-6 bg-emerald-300 dark:bg-emerald-700"></span></li>
                        {{-- Step 2 — active --}}
                        <li class="flex items-start gap-3">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-gov-600 text-sm font-bold text-white ring-4 ring-gov-100 dark:ring-gov-900/40">2</span>
                            <div class="min-w-0 pt-1">
                                <p class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.step_two_title') }}</p>
                                <p class="mt-0.5 text-xs text-gov-600 dark:text-gov-400 font-medium">{{ __('rescue_certificate.step_status') }}</p>
                            </div>
                        </li>
                    </ol>
                </aside>

                {{-- Form card --}}
                <section class="rounded-2xl border border-surface-dim bg-surface-container-lowest shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-surface-dim px-6 py-5 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-on-surface dark:text-gray-100">{{ __('rescue_certificate.step2_heading') }}</h3>
                        <p class="mt-1 text-sm text-secondary dark:text-gray-400">{{ __('rescue_certificate.step2_description') }}</p>
                    </div>

                    <form method="POST" action="{{ route('rescue-certificates.step2.store', $rescueCertificate) }}" class="px-6 py-6" id="presence-step2-form" enctype="multipart/form-data">
                        @csrf

                        @if ($errors->any())
                            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                                <p class="font-semibold">{{ __('rescue_certificate.create_error_title') }}</p>
                                <ul class="mt-2 list-disc space-y-1 ps-5">
                                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Info banner --}}
                        <div class="mb-6 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm dark:border-blue-800/40 dark:bg-blue-900/20">
                            <svg class="h-5 w-5 flex-none text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-blue-700 dark:text-blue-300">{{ __('rescue_certificate.documents_info') }}</p>
                        </div>

                        {{-- Document list --}}
                        <div class="space-y-4">
                            @foreach ($documentTypes as $doc)
                                @php
                                    $uploaded = $uploadedDocs->get($doc->id);
                                @endphp
                                <div
                                    class="doc-card rounded-xl border p-4 transition dark:border-gray-700 {{ $uploaded ? 'border-emerald-300 bg-emerald-50/30 dark:border-emerald-800/40 dark:bg-emerald-900/10' : 'border-surface-dim' }}"
                                    data-doc-id="{{ $doc->id }}"
                                >
                                    <div class="flex items-start gap-3">
                                        {{-- Status icon --}}
                                        <span class="status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full {{ $uploaded ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500' }}">
                                            @if ($uploaded)
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            @else
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                            @endif
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            {{-- Document name --}}
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-semibold text-on-surface dark:text-gray-100">{{ $loop->iteration }}. {{ $doc->name }}</p>
                                                @if ($doc->is_conditional)
                                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('rescue_certificate.conditional') }}</span>
                                                @endif
                                            </div>

                                            {{-- Uploaded file info (server-side, from DB) --}}
                                            @if ($uploaded)
                                                <div class="mt-2 flex items-center gap-2 text-xs text-emerald-600 dark:text-emerald-400">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg>
                                                    <span class="truncate">{{ $uploaded->original_name }}</span>
                                                    <span class="flex-none text-outline">({{ number_format($uploaded->size / 1024, 0) }} KB)</span>
                                                </div>
                                            @endif

                                            {{-- Preview area (populated by JS on file select) --}}
                                            <div class="preview-area mt-3 hidden">
                                                <div class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-2.5 dark:border-emerald-800/40 dark:bg-emerald-900/10">
                                                    {{-- Preview image or file icon --}}
                                                    <div class="preview-thumb flex h-14 w-14 flex-none items-center justify-center overflow-hidden rounded-lg bg-surface-container-lowest dark:bg-gray-800">
                                                        <img class="preview-img hidden h-full w-full object-cover" alt="">
                                                        <svg class="preview-icon h-7 w-7 text-outline hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                    </div>
                                                    {{-- File info --}}
                                                    <div class="min-w-0 flex-1">
                                                        <p class="preview-name truncate text-xs font-semibold text-on-surface dark:text-gray-100"></p>
                                                        <p class="preview-size text-[11px] text-outline"></p>
                                                    </div>
                                                    {{-- Remove button --}}
                                                    <button type="button" class="remove-file flex-none rounded-lg p-1 text-outline transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20" title="{{ __('rescue_certificate.remove_file') }}">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Upload input --}}
                                            <div class="mt-3 upload-area">
                                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2 text-xs font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                    <span class="upload-label">{{ $uploaded ? __('rescue_certificate.replace_file') : __('rescue_certificate.upload_file') }}</span>
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
                                </div>
                            @endforeach
                        </div>

                        {{-- Footer --}}
                        <div class="mt-8 flex items-center justify-between gap-3 border-t border-surface-dim pt-5 dark:border-gray-700">
                            <a href="{{ route('rescue-certificates.step1.edit', $rescueCertificate) }}" class="inline-flex items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                {{ __('rescue_certificate.back') }}
                            </a>
                            <button
                                type="submit"
                                id="step2-submit-btn"
                                class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-900"
                                data-loading-text="{{ __('rescue_certificate.submitting') }}"
                            >
                                <span id="step2-submit-label">{{ __('rescue_certificate.submit_dossier') }}</span>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </div>
                    </form>
                </section>

            </div>
        </div>
    </div>

    <script>
    (function () {
        var form = document.getElementById('presence-step2-form');
        var btn = document.getElementById('step2-submit-btn');
        var lbl = document.getElementById('step2-submit-label');

        var fileInputs = document.querySelectorAll('.file-input');

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
                statusIcon.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';

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
                uploadLabel.textContent = '{{ __('rescue_certificate.replace_file') }}';

                // ── Remove button ──
                var removeBtn = card.querySelector('.remove-file');
                removeBtn.onclick = function () {
                    // Reset the input
                    input.value = '';
                    // Hide preview
                    previewArea.classList.add('hidden');
                    // Reset card state
                    card.classList.add('border-surface-dim');
                    card.classList.remove('border-emerald-300', 'bg-emerald-50/30', 'dark:border-emerald-800/40', 'dark:bg-emerald-900/10');
                    // Reset status icon
                    statusIcon.className = 'status-icon mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-surface-container text-outline dark:bg-gray-700 dark:text-gray-500';
                    statusIcon.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>';
                    // Reset upload label
                    uploadLabel.textContent = '{{ __('rescue_certificate.upload_file') }}';
                };
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
