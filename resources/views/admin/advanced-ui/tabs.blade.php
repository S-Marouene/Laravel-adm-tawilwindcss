@extends('admin.layouts.admin')

@section('title', __('nav.tabs'))

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
            </svg>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('nav.tabs') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Tabbed content panels with multiple style variants.</p>
        </div>
    </div>

    <!-- Underline Tabs -->
    <div class="ynex-card p-6 mb-6" x-data="{ tab: 'description' }">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Underline Tabs</h3>
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex gap-6 -mb-px" role="tablist">
                <template x-for="t in ['description', 'specs', 'reviews', 'support']" :key="t">
                    <button @click="tab = t" type="button" role="tab" :aria-selected="tab === t"
                        class="pb-3 text-sm font-medium capitalize transition-all duration-200 border-b-2 cursor-pointer"
                        :class="tab === t ? 'text-gov-600 dark:text-gov-400 border-gov-600 dark:border-gov-400' : 'text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        x-text="t">
                    </button>
                </template>
            </nav>
        </div>
        <div class="mt-5">
            <div x-show="tab === 'description'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">This is a powerful and versatile product designed to streamline your workflow. Built with modern technologies, it offers exceptional performance, reliability, and ease of use. Whether you are a beginner or an experienced professional, this tool adapts to your needs and helps you achieve more in less time.</p>
            </div>
            <div x-show="tab === 'specs'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-cloak>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50"><span class="text-xs font-medium text-gray-500 dark:text-gray-400">Version</span><p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5">2.4.1</p></div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50"><span class="text-xs font-medium text-gray-500 dark:text-gray-400">Size</span><p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5">48 KB</p></div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50"><span class="text-xs font-medium text-gray-500 dark:text-gray-400">Framework</span><p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5">Alpine.js 3.x</p></div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50"><span class="text-xs font-medium text-gray-500 dark:text-gray-400">License</span><p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-0.5">MIT</p></div>
                </div>
            </div>
            <div x-show="tab === 'reviews'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-cloak>
                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50"><div class="w-8 h-8 rounded-full bg-gov-100 dark:bg-gov-900/40 flex items-center justify-center flex-shrink-0"><span class="text-xs font-bold text-gov-600 dark:text-gov-400">SK</span></div><div><p class="text-sm font-medium text-gray-900 dark:text-gray-100">Sarah K.</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">"Excellent product! Very intuitive and well-designed."</p></div></div>
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50"><div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0"><span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">JM</span></div><div><p class="text-sm font-medium text-gray-900 dark:text-gray-100">John M.</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">"The documentation is fantastic. Got up and running in minutes."</p></div></div>
                </div>
            </div>
            <div x-show="tab === 'support'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-cloak>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">Need help? Our support team is available 24/7. Reach us via email at <span class="font-medium text-gov-600 dark:text-gov-400">support@example.com</span> or visit our <a href="#" class="text-gov-600 dark:text-gov-400 underline hover:no-underline">knowledge base</a> for tutorials and FAQs.</p>
            </div>
        </div>
    </div>

    <!-- Pill Tabs -->
    <div class="ynex-card p-6 mb-6" x-data="{ pill: 'home' }">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Pill Tabs</h3>
        <div class="flex flex-wrap gap-2 p-1.5 rounded-xl bg-gray-100 dark:bg-gray-700/60 mb-5">
            <template x-for="p in ['home', 'profile', 'messages', 'settings']" :key="p">
                <button @click="pill = p" type="button"
                    class="px-4 py-2 text-sm font-medium rounded-lg capitalize transition-all duration-200 cursor-pointer"
                    :class="pill === p ? 'bg-white dark:bg-gray-600 text-gov-700 dark:text-gov-300 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    x-text="p">
                </button>
            </template>
        </div>
        <div>
            <div x-show="pill === 'home'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="h-24 rounded-xl bg-gradient-to-br from-gov-500 to-gov-700 flex items-center justify-center text-white text-sm font-medium">Dashboard</div>
                    <div class="h-24 rounded-xl bg-gradient-to-br from-accent-500 to-accent-600 flex items-center justify-center text-white text-sm font-medium">Analytics</div>
                    <div class="h-24 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center text-white text-sm font-medium">Reports</div>
                </div>
            </div>
            <div x-show="pill === 'profile'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50"><div class="w-14 h-14 rounded-full bg-gradient-to-br from-gov-400 to-gov-600 flex items-center justify-center text-xl font-bold text-white">JD</div><div><h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">John Doe</h4><p class="text-xs text-gray-500 dark:text-gray-400">john.doe@example.com · Joined March 2025</p></div></div>
            </div>
            <div x-show="pill === 'messages'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                <div class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No new messages. Your inbox is clean!</div>
            </div>
            <div x-show="pill === 'settings'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                <div class="space-y-3"><label class="flex items-center gap-3"><input type="checkbox" checked class="w-4 h-4 text-gov-600 rounded border-gray-300 focus:ring-gov-500"><span class="text-sm text-gray-700 dark:text-gray-300">Email notifications</span></label><label class="flex items-center gap-3"><input type="checkbox" class="w-4 h-4 text-gov-600 rounded border-gray-300 focus:ring-gov-500"><span class="text-sm text-gray-700 dark:text-gray-300">SMS alerts</span></label></div>
            </div>
        </div>
    </div>

    <!-- Vertical Tabs -->
    <div class="ynex-card p-6" x-data="{ vert: 0 }">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Vertical Tabs</h3>
        <div class="flex flex-col md:flex-row gap-5">
            <div class="flex md:flex-col gap-1 md:min-w-[160px]">
                <template x-for="(v, i) in ['Account', 'Security', 'Notifications', 'Billing']" :key="i">
                    <button @click="vert = i" type="button"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 text-left cursor-pointer"
                        :class="vert === i ? 'bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300 border-l-2 border-gov-600 dark:border-gov-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-l-2 border-transparent'"
                        x-text="v">
                    </button>
                </template>
            </div>
            <div class="flex-1 p-5 rounded-xl bg-gray-50 dark:bg-gray-700/30">
                <div x-show="vert === 0"><h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Account Settings</h4><p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">Manage your account details, email preferences, and profile information. Keep your personal data up to date for the best experience.</p></div>
                <div x-show="vert === 1" x-cloak><h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Security</h4><p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">Configure two-factor authentication, manage active sessions, and review login history to keep your account secure.</p></div>
                <div x-show="vert === 2" x-cloak><h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Notifications</h4><p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">Choose which notifications you receive and how they are delivered. Customize alerts for each channel to stay informed your way.</p></div>
                <div x-show="vert === 3" x-cloak><h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Billing</h4><p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">View invoices, update payment methods, and manage your subscription plan. All billing information is encrypted and secure.</p></div>
            </div>
        </div>
    </div>
</div>
@endsection
