@extends('admin.layouts.admin')

@section('title', __('nav.modals'))

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-purple-700 text-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M3 10v11h18V10"/>
            </svg>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('nav.modals') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Modal dialogs, popups, and overlay components.</p>
        </div>
    </div>

    <!-- Modal Buttons Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <button @click="$dispatch('open-modal', 'info')" type="button" class="ynex-card p-5 text-center hover:border-gov-300 cursor-pointer group">
            <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-sky-50 dark:bg-sky-900/30 flex items-center justify-center group-hover:scale-105 transition-transform">
                <svg class="w-6 h-6 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Info Modal</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Simple information dialog</p>
        </button>

        <button @click="$dispatch('open-modal', 'confirm')" type="button" class="ynex-card p-5 text-center hover:border-emerald-300 cursor-pointer group">
            <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center group-hover:scale-105 transition-transform">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Confirm Modal</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Confirmation dialog</p>
        </button>

        <button @click="$dispatch('open-modal', 'form')" type="button" class="ynex-card p-5 text-center hover:border-gov-300 cursor-pointer group">
            <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gov-50 dark:bg-gov-900/30 flex items-center justify-center group-hover:scale-105 transition-transform">
                <svg class="w-6 h-6 text-gov-600 dark:text-gov-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Form Modal</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Modal with form fields</p>
        </button>

        <button @click="$dispatch('open-modal', 'fullscreen')" type="button" class="ynex-card p-5 text-center hover:border-amber-300 cursor-pointer group">
            <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center group-hover:scale-105 transition-transform">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Fullscreen Modal</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Full overlay modal</p>
        </button>
    </div>

    <!-- Modal Render Area -->
    <div x-data="modalManager()" @keydown.escape.window="closeModal" @open-modal.window="openModal($event.detail)">
        <!-- Info Modal -->
        <div x-show="modalOpen && modalType === 'info'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div x-show="modalOpen" @click="closeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
            <div @click.outside="closeModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6 z-10">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-sky-100 dark:bg-sky-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Information</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">System notification</p>
                    </div>
                    <button @click="closeModal" type="button" class="ml-auto w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    <p>Your account has been successfully verified. You now have access to all premium features including advanced analytics, priority support, and team collaboration tools.</p>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button @click="closeModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-all">Close</button>
                    <button @click="closeModal" type="button" class="px-4 py-2 text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 rounded-lg transition-all">Got it</button>
                </div>
            </div>
        </div>

        <!-- Confirm Modal -->
        <div x-show="modalOpen && modalType === 'confirm'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div x-show="modalOpen" @click="closeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
            <div @click.outside="closeModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center z-10">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Confirm Action</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Are you sure you want to delete this item? This action cannot be undone.</p>
                <div class="flex justify-center gap-3">
                    <button @click="closeModal" type="button" class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-all">Cancel</button>
                    <button @click="closeModal" type="button" class="px-5 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all">Delete</button>
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <div x-show="modalOpen && modalType === 'form'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div x-show="modalOpen" @click="closeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
            <div @click.outside="closeModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 z-10">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Edit Profile</h3>
                    <button @click="closeModal" type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Full Name</label>
                        <input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" value="John Doe">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                        <input type="email" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" value="john@example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bio</label>
                        <textarea rows="3" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm">Full-stack developer passionate about Laravel & Alpine.js.</textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button @click="closeModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-all">Cancel</button>
                    <button @click="closeModal" type="button" class="px-4 py-2 text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 rounded-lg transition-all">Save Changes</button>
                </div>
            </div>
        </div>

        <!-- Fullscreen Modal -->
        <div x-show="modalOpen && modalType === 'fullscreen'" x-cloak class="fixed inset-0 z-50" style="display: none;">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-md"></div>
            <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="relative z-10 h-full flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
                    <h3 class="text-lg font-semibold text-white">Fullscreen Modal</h3>
                    <button @click="closeModal" type="button" class="w-9 h-9 rounded-lg flex items-center justify-center text-white/60 hover:text-white hover:bg-white/10 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-auto p-6">
                    <div class="max-w-3xl mx-auto">
                        <h2 class="text-2xl font-bold text-white mb-4">Content Area</h2>
                        <p class="text-gray-300 leading-relaxed mb-4">This is a fullscreen modal overlay. It covers the entire viewport and is useful for detailed content like image galleries, detailed forms, or long-form content that needs focus.</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="h-32 rounded-xl bg-white/10 flex items-center justify-center text-white/40 text-sm">Placeholder</div>
                            <div class="h-32 rounded-xl bg-white/10 flex items-center justify-center text-white/40 text-sm">Placeholder</div>
                            <div class="h-32 rounded-xl bg-white/10 flex items-center justify-center text-white/40 text-sm">Placeholder</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Demo Info Card -->
    <div class="ynex-card p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Modal Usage</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Click any card above to open a modal. Press <kbd class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs font-mono">Esc</kbd> or click outside to close.</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-sky-50 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300">Info Modal</span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">Confirm Modal</span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300">Form Modal</span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">Fullscreen</span>
        </div>
    </div>
</div>

@endsection
