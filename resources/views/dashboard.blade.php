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
            <div class="grid gap-5 sm:grid-cols-2">
                {{-- Service 1: Prevention certificate --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-all duration-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <div class="px-6 py-8 sm:px-8 sm:py-8">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gov-50 text-gov-700 dark:bg-gov-900/30 dark:text-gov-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ __('services.prevention_certificate') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            {{ __('services.prevention_certificate.desc') }}
                        </p>
                        <a href="{{ route('prevention-requests.index') }}" class="ui-btn-primary mt-5 inline-flex w-full items-center justify-center gap-2 px-5 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-6 4h6m-7 4h8m-9 8h10a2 2 0 002-2V7.414a1 1 0 00-.293-.707l-3.414-3.414A1 1 0 0014.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span>{{ __('prevention_request.list_requests') }}</span>
                        </a>
                    </div>
                </div>

                {{-- Service 2: Preventive presence --}}
                <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm transition-all duration-300 hover:shadow-md dark:border-emerald-800/50 dark:bg-gray-800">
                    <div class="px-6 py-8 sm:px-8 sm:py-8">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ __('services.preventive_presence') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            {{ __('services.preventive_presence.desc') }}
                        </p>
                        <a href="{{ route('preventive-presence.index') }}" class="ui-btn-primary mt-5 inline-flex w-full items-center justify-center gap-2 px-5 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-6 4h6m-7 4h8m-9 8h10a2 2 0 002-2V7.414a1 1 0 00-.293-.707l-3.414-3.414A1 1 0 0014.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span>{{ __('preventive_presence.my_requests') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
