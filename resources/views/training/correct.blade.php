@php
    $tr = $trainingRequest;
    $round = $correction['round'] ?? 1;
    $note  = $correction['note'] ?? null;
    $previous = $correction['previous'] ?? null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-on-surface">{{ __('training.correction_page_title') }}</h2>
                <p class="mt-0.5 text-sm text-secondary">{{ __('training.correction_page_subtitle', ['round' => $round]) }}</p>
            </div>
            <a href="{{ route('training.show', $tr) }}" class="flex items-center gap-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface border border-surface-dim text-xs font-semibold py-2 px-3 rounded transition-colors">
                <span class="material-symbols-outlined text-base text-secondary rtl:rotate-180" aria-hidden="true">arrow_back</span>
                {{ __('training.back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            @if ($note)
            <section class="mb-6 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800/30 dark:bg-amber-900/10">
                <div class="border-l-4 border-l-amber-600 p-5">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-10 h-10 rounded bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1">rate_review</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wide">{{ __('training.correction_title') }}</h3>
                        </div>
                    </div>
                    <p class="text-body-sm text-amber-900 dark:text-amber-200 whitespace-pre-line">{{ $note }}</p>
                </div>
            </section>
            @endif

            <form method="POST" action="{{ route('training.correct.submit', $tr) }}"
                  class="bg-surface-container-lowest rounded border border-surface-dim shadow-sm dark:border-gray-700 dark:bg-gray-800"
                  enctype="multipart/form-data">
                @csrf

                <div class="p-6 sm:p-8 space-y-6">
                    @if ($errors->any())
                        <div class="rounded border-l-4 border-l-red-500 bg-red-50 px-4 py-3 text-body-sm text-red-800" role="alert">
                            <ul class="list-disc space-y-1 ps-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif

                    @if ($previous && is_array($previous))
                    <div class="bg-surface-container-low border border-surface-dim rounded p-4">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary text-base">history</span>
                            {{ __('training.previous_values') }}
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-secondary">
                            @foreach ($previous as $key => $val)
                                @if ($val)
                                <div><span class="font-medium text-on-surface">{{ $key }}:</span> {{ $val }}</div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Fields to fix --}}
                    @if (!empty($fieldsToFix))
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-base">edit</span>
                            {{ __('training.correction_fields_section') }}
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            @if (in_array('nom_prenom', $fieldsToFix))
                                <div class="space-y-1.5">
                                    <label for="nom_prenom" class="block text-body-sm font-bold text-on-surface">{{ __('training.nom_prenom') }} <span class="text-primary">*</span></label>
                                    <input id="nom_prenom" name="nom_prenom" type="text" value="{{ old('nom_prenom', $tr->nom_prenom) }}" required class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                    @error('nom_prenom')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            @endif
                            @if (in_array('cin', $fieldsToFix))
                                <div class="space-y-1.5">
                                    <label for="cin" class="block text-body-sm font-bold text-on-surface">{{ __('training.cin') }} <span class="text-primary">*</span></label>
                                    <input id="cin" name="cin" type="text" inputmode="numeric" maxlength="20" value="{{ old('cin', $tr->cin) }}" required class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                    @error('cin')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            @endif
                            @if (in_array('phone', $fieldsToFix))
                                <div class="space-y-1.5">
                                    <label for="phone" class="block text-body-sm font-bold text-on-surface">{{ __('training.phone') }} <span class="text-primary">*</span></label>
                                    <input id="phone" name="phone" type="tel" maxlength="20" value="{{ old('phone', $tr->phone) }}" required class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                    @error('phone')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            @endif
                            @if (in_array('training_type', $fieldsToFix))
                                <div class="space-y-1.5">
                                    <label for="training_type" class="block text-body-sm font-bold text-on-surface">{{ __('training.training_type') }} <span class="text-primary">*</span></label>
                                    <select id="training_type" name="training_type" required class="block w-full h-11 px-3.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                        <option value="" disabled @selected(!old('training_type', $tr->training_type))>{{ __('training.training_type_select') }}</option>
                                        @foreach (\App\Models\TrainingRequest::TYPES as $key => $data)
                                            <option value="{{ $key }}" @selected(old('training_type', $tr->training_type) === $key)>{{ __('training.type_' . $key) }}</option>
                                        @endforeach
                                    </select>
                                    @error('training_type')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            @endif
                            @if (in_array('training_kind', $fieldsToFix))
                                <div class="space-y-1.5">
                                    <label class="block text-body-sm font-bold text-on-surface">{{ __('training.training_kind') }} <span class="text-primary">*</span></label>
                                    <div class="grid grid-cols-2 gap-3">
                                        @foreach (['new' => 'kind_new', 'recycling' => 'kind_recycling'] as $val => $lk)
                                            <label class="flex items-center gap-3 rounded-lg border border-surface-dim bg-surface-container px-4 py-3 cursor-pointer hover:border-primary/40 has-[:checked]:border-primary/60 has-[:checked]:bg-primary/5 dark:border-gray-700">
                                                <input type="radio" name="training_kind" value="{{ $val }}" @checked(old('training_kind', $tr->training_kind) === $val) class="h-4 w-4 text-primary">
                                                <span class="text-body-sm font-semibold text-on-surface">{{ __('training.' . $lk) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if (in_array('notes', $fieldsToFix))
                                <div class="sm:col-span-2 space-y-1.5">
                                    <label for="notes" class="block text-body-sm font-bold text-on-surface">{{ __('training.notes') }}</label>
                                    <textarea id="notes" name="notes" rows="3" class="block w-full px-3.5 py-2.5 text-body-md text-on-surface rounded border border-surface-dim bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('notes', $tr->notes) }}</textarea>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Docs to fix --}}
                    @if ($documentGroups->isNotEmpty())
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-secondary mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-base">upload_file</span>
                            {{ __('training.correction_docs_section') }}
                        </h4>
                        <div class="space-y-3">
                            @foreach ($documentGroups as $group)
                                <div class="rounded border border-surface-dim dark:border-gray-700 overflow-hidden">
                                    <div class="flex items-center justify-between gap-2 px-3 py-2 border-b border-surface-dim bg-surface-container-low dark:border-gray-700 dark:bg-gray-900/40">
                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-bold text-on-surface">{{ $group['name'] ?: __('training.participants_section') }}</p>
                                            @if ($group['cin'])
                                                <p class="text-[10px] text-secondary" dir="ltr">{{ __('training.cin') }} : {{ $group['cin'] }}</p>
                                            @endif
                                        </div>
                                        @if ($group['training_kind'] === \App\Models\TrainingRequest::KIND_RECYCLING)
                                            <span class="shrink-0 rounded border border-amber-200 bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800">{{ __('training.kind_recycling') }}</span>
                                        @endif
                                    </div>
                                    <div class="space-y-2 p-3">
                                        @foreach ($group['documents'] as $requirement)
                                            <div class="p-3 rounded border border-surface-dim bg-surface-container-low flex items-center justify-between gap-3">
                                                <p class="text-xs font-bold text-on-surface">{{ $requirement['name'] }}</p>
                                                <label class="inline-flex cursor-pointer items-center gap-2 rounded border border-surface-dim bg-surface-container-lowest px-3 py-2 text-xs font-semibold text-on-surface hover:bg-surface-container">
                                                    <span class="material-symbols-outlined text-[15px] text-primary">upload_file</span>
                                                    {{ __('training.upload_file') }}
                                                    <input type="file" name="documents[{{ $requirement['key'] }}][]" class="sr-only" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 border-t border-surface-dim px-6 py-5 dark:border-gray-700">
                    <a href="{{ route('training.show', $tr) }}" class="inline-flex items-center justify-center rounded border border-surface-dim bg-surface-container-lowest px-4 py-2.5 text-body-sm font-semibold text-on-surface transition hover:bg-surface-container">{{ __('training.back') }}</a>
                    <button type="submit" class="ui-btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 text-body-sm">
                        <span class="material-symbols-outlined text-[18px]">send</span>
                        {{ __('training.submit_dossier') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
