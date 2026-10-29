<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ __('demandes.title') }}</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('demandes.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 flex-none text-emerald-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            {{-- Empty state --}}
            @if ($items->isEmpty())
                <div class="section-surface rounded-2xl border border-gray-200 dark:border-gray-700 p-10 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ __('demandes.empty_title') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ __('demandes.empty_subtitle') }}</p>
                    <a href="{{ url('/') }}#services" class="ui-btn-primary inline-flex items-center gap-2 px-5 py-2.5 text-sm">
                        {{ __('demandes.discover_services') }}
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($items as $item)
                        <article class="group section-surface rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
                            <div class="p-5">
                                <div class="flex flex-wrap items-start gap-4">
                                    {{-- Service icon (color per service) --}}
                                    @php
                                        $iconClass = match ($item['kind']) {
                                            'presence' => 'bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-300',
                                            'rescue'   => 'bg-teal-100 text-teal-600 dark:bg-teal-900/40 dark:text-teal-300',
                                            'works'    => 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300',
                                            default    => 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300',
                                        };
                                        $badgeClass = match ($item['status']) {
                                            'needs_correction'   => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                            'under_consultation' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                            'approved'           => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                            'served'             => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                                            'cancelled'          => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                            default              => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                        };
                                        $dotClass = match ($item['status']) {
                                            'needs_correction'   => 'bg-red-500',
                                            'under_consultation' => 'bg-purple-500',
                                            'approved'           => 'bg-emerald-500',
                                            'served'             => 'bg-teal-500',
                                            'cancelled'          => 'bg-gray-400',
                                            default              => 'bg-blue-500',
                                        };
                                    @endphp

                                    <div class="flex h-11 w-11 flex-none items-center justify-center rounded-xl {{ $iconClass }}">
                                        @switch ($item['kind'])
                                            @case ('prevention')
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                </svg>
                                            @break
                                            @case ('presence')
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            @break
                                            @case ('rescue')
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                </svg>
                                            @break
                                            @default
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM6.407 7.758L4 10h3l1.5-2 1.5 2h3l3-3m-9 9l-2.5 2.5a3 3 0 104.243 4.243L10 17m0 0l2-2"/>
                                                </svg>
                                        @endswitch
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        {{-- Type chip — mentions the type of the dossier --}}
                                        <p class="mb-1 inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $item['label'] }}
                                        </p>

                                        {{-- Status badge --}}
                                        <span class="ml-2 inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                                            {{ $item['status_label'] }}
                                        </span>

                                        <p class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $item['title'] }}
                                        </p>

                                        {{-- Meta info --}}
                                        @if (! empty($item['meta']))
                                            <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                                @foreach ($item['meta'] as $meta)
                                                    <span class="truncate">{{ $meta }}</span>
                                                @endforeach
                                                <span>{{ __('demandes.submitted_on', ['date' => $item['created_at']->format('d/m/Y')]) }}</span>
                                            </div>
                                        @endif

                                        {{-- Admin note for corrections / cancellation --}}
                                        @if (in_array($item['status'], ['needs_correction', 'cancelled']))
                                            <a href="{{ $item['url'] }}" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                {{ __('demandes.action_required') }} →
                                            </a>
                                        @endif
                                    </div>

                                    {{-- View details --}}

                                    <div class="flex-none self-center">
                                        <a href="{{ $item['url'] }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-gov-600 hover:bg-gov-50 dark:text-gov-400 dark:hover:bg-gov-900/30 transition-colors">
                                            {{ __('demandes.view_details') }}
                                            <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
