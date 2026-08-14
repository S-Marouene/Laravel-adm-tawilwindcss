<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ __('prevention_request.index_title') }}</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('prevention_request.index_subtitle') }}</p>
            </div>
            <a href="{{ route('prevention-requests.create') }}" class="ui-btn-primary inline-flex items-center justify-center gap-2 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('prevention_request.new_request') }}</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any() || $loadError)
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                    <p class="font-semibold">{{ __('prevention_request.create_error_title') }}</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @if ($loadError)
                            <li>{{ $loadError }}</li>
                        @endif
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('prevention_request.id') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('prevention_request.cin') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('prevention_request.code_structure') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('prevention_request.num_demande') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('prevention_request.nom_prenom') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('prevention_request.date_demande') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('prevention_request.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($requests['data'] as $requestItem)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $requestItem['id'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $requestItem['cin'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $requestItem['code_structure'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $requestItem['num_demande'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $requestItem['nom_prenom'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $requestItem['date_demande'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-end text-sm">
                                        <div class="inline-flex items-center gap-2">
                                            <a href="{{ route('prevention-requests.edit', $requestItem['id']) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                {{ __('prevention_request.edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('prevention-requests.destroy', $requestItem['id']) }}" onsubmit="return confirm('{{ __('prevention_request.delete_confirm') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                                                    {{ __('prevention_request.delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('prevention_request.no_requests') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-200 px-4 py-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300 sm:flex-row sm:items-center sm:justify-between">
                    <p>{{ __('prevention_request.pagination_summary', ['from' => $requests['from'] ?? 0, 'to' => $requests['to'] ?? 0, 'total' => $requests['total'] ?? 0]) }}</p>
                    <div class="flex items-center gap-2">
                        @if (($requests['current_page'] ?? 1) > 1)
                            <a href="{{ request()->fullUrlWithQuery(['page' => ($requests['current_page'] ?? 1) - 1]) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('prevention_request.previous') }}</a>
                        @endif
                        @if (($requests['current_page'] ?? 1) < ($requests['last_page'] ?? 1))
                            <a href="{{ request()->fullUrlWithQuery(['page' => ($requests['current_page'] ?? 1) + 1]) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('prevention_request.next') }}</a>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
