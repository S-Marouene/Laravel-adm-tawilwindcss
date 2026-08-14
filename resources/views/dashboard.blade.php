<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                    {{ __('prevention_request.dashboard_title') }}
                </h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('prevention_request.dashboard_subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="px-6 py-8 sm:px-10 sm:py-10">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gov-50 text-gov-700 dark:bg-gov-900/30 dark:text-gov-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('prevention_request.card_title') }}</h3>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                                {{ __('prevention_request.card_description') }}
                            </p>
                        </div>

                        <a href="{{ route('prevention-requests.create') }}" class="ui-btn-primary inline-flex min-w-[11rem] items-center justify-center gap-2 px-5 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>{{ __('prevention_request.new_request') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
