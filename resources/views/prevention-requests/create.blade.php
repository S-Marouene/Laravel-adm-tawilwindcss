<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ __('prevention_request.create_title') }}</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('prevention_request.create_subtitle') }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                {{ __('prevention_request.back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
                <aside class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <ol class="space-y-4">
                        <li class="flex items-start gap-3">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-gov-600 text-sm font-bold text-white">1</span>
                            <div class="min-w-0 pt-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('prevention_request.step_one_title') }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('prevention_request.step_status') }}</p>
                            </div>
                        </li>
                    </ol>
                </aside>

                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('prevention_request.step_heading') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('prevention_request.step_description') }}</p>
                    </div>

                    <form method="POST" action="#" class="px-6 py-6">
                        @csrf
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="cin" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('prevention_request.cin') }}</label>
                                <input id="cin" name="cin" type="text" inputmode="numeric" maxlength="20" class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" placeholder="{{ __('prevention_request.cin_placeholder') }}">
                            </div>

                            <div>
                                <label for="code_structure" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('prevention_request.code_structure') }}</label>
                                <select id="code_structure" name="code_structure" class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" data-structures-url="{{ route('prevention-requests.structures') }}">
                                    <option value="">{{ __('prevention_request.code_structure_loading') }}</option>
                                </select>
                            </div>

                            <div>
                                <label for="num_demande" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('prevention_request.num_demande') }}</label>
                                <input id="num_demande" name="num_demande" type="number" min="0" class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" placeholder="{{ __('prevention_request.num_demande_placeholder') }}">
                            </div>

                            <div>
                                <label for="nom_prenom" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('prevention_request.nom_prenom') }}</label>
                                <input id="nom_prenom" name="nom_prenom" type="text" maxlength="255" class="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gov-500 focus:ring-gov-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" placeholder="{{ __('prevention_request.nom_prenom_placeholder') }}">
                            </div>
                        </div>

                        <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('prevention_request.cancel') }}</a>
                            <button type="button" class="ui-btn-primary inline-flex items-center justify-center gap-2 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                                {{ __('prevention_request.continue') }}
                                <svg class="h-4 w-4 {{ app()->getLocale() === 'ar' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var select = document.getElementById('code_structure');

            if (! select) {
                return;
            }

            fetch(select.dataset.structuresUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    if (! response.ok) {
                        throw new Error('Unable to load structures');
                    }

                    return response.json();
                })
                .then(function (payload) {
                    var structures = payload.data || [];

                    select.innerHTML = '';

                    var placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = structures.length
                        ? '{{ __('prevention_request.code_structure_select') }}'
                        : '{{ __('prevention_request.code_structure_empty') }}';
                    select.appendChild(placeholder);

                    structures.forEach(function (structure) {
                        var option = document.createElement('option');
                        option.value = structure.code_structure;
                        option.textContent = structure.label;
                        select.appendChild(option);
                    });
                })
                .catch(function () {
                    select.innerHTML = '';

                    var option = document.createElement('option');
                    option.value = '';
                    option.textContent = '{{ __('prevention_request.structures_unavailable') }}';
                    select.appendChild(option);
                });
        });
    </script>
</x-app-layout>
