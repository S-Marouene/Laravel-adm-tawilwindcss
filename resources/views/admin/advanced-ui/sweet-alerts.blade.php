@extends('admin.layouts.admin')

@section('title', __('nav.sweet_alerts'))

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-700 text-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('nav.sweet_alerts') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Sweet alert dialogs, confirmations, and notification popups.</p>
        </div>
    </div>

    <!-- Alert Type Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6" x-data="alertManager()">
        <button @click="showAlert('success')" type="button" class="ynex-card p-5 text-center hover:border-emerald-300 cursor-pointer group">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Success</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Operation completed</p>
        </button>
        <button @click="showAlert('error')" type="button" class="ynex-card p-5 text-center hover:border-red-300 cursor-pointer group">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-red-50 dark:bg-red-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Error</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Something went wrong</p>
        </button>
        <button @click="showAlert('warning')" type="button" class="ynex-card p-5 text-center hover:border-amber-300 cursor-pointer group">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Warning</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Proceed with caution</p>
        </button>
        <button @click="showAlert('info')" type="button" class="ynex-card p-5 text-center hover:border-sky-300 cursor-pointer group">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-sky-50 dark:bg-sky-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-7 h-7 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Info</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">For your information</p>
        </button>
    </div>

    <!-- Toast Notifications Grid -->
    <div class="ynex-card p-6 mb-6" x-data="toastManager()">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Toast Notifications</h3>
            <button @click="showRandomToast" type="button" class="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-gov-600 hover:bg-gov-700 transition-all cursor-pointer">Random Toast</button>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <button @click="showToast('success', 'File uploaded successfully!')" type="button" class="px-4 py-3 rounded-xl text-sm font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-all border border-emerald-200 dark:border-emerald-800 cursor-pointer">✅ Success</button>
            <button @click="showToast('error', 'Failed to save changes.')" type="button" class="px-4 py-3 rounded-xl text-sm font-medium bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/50 transition-all border border-red-200 dark:border-red-800 cursor-pointer">❌ Error</button>
            <button @click="showToast('warning', 'Session will expire soon.')" type="button" class="px-4 py-3 rounded-xl text-sm font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-all border border-amber-200 dark:border-amber-800 cursor-pointer">⚠️ Warning</button>
            <button @click="showToast('info', 'New update available.')" type="button" class="px-4 py-3 rounded-xl text-sm font-medium bg-sky-50 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition-all border border-sky-200 dark:border-sky-800 cursor-pointer">ℹ️ Info</button>
        </div>

        <!-- Toast Container -->
        <div class="fixed top-4 right-4 z-[100] space-y-2 w-80" x-teleport="body">
            <template x-for="(toast, i) in toasts" :key="i">
                <div x-show="toast.show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-full" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-full"
                    class="flex items-start gap-3 p-4 rounded-xl shadow-xl border backdrop-blur-sm"
                    :class="{
                        'bg-emerald-50 dark:bg-emerald-900/80 border-emerald-200 dark:border-emerald-700': toast.type === 'success',
                        'bg-red-50 dark:bg-red-900/80 border-red-200 dark:border-red-700': toast.type === 'error',
                        'bg-amber-50 dark:bg-amber-900/80 border-amber-200 dark:border-amber-700': toast.type === 'warning',
                        'bg-sky-50 dark:bg-sky-900/80 border-sky-200 dark:border-sky-700': toast.type === 'info'
                    }">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold"
                            :class="{
                                'text-emerald-800 dark:text-emerald-200': toast.type === 'success',
                                'text-red-800 dark:text-red-200': toast.type === 'error',
                                'text-amber-800 dark:text-amber-200': toast.type === 'warning',
                                'text-sky-800 dark:text-sky-200': toast.type === 'info'
                            }" x-text="toast.title">
                        </p>
                        <p class="text-xs mt-0.5"
                            :class="{
                                'text-emerald-700 dark:text-emerald-300': toast.type === 'success',
                                'text-red-700 dark:text-red-300': toast.type === 'error',
                                'text-amber-700 dark:text-amber-300': toast.type === 'warning',
                                'text-sky-700 dark:text-sky-300': toast.type === 'info'
                            }" x-text="toast.message">
                        </p>
                    </div>
                    <button @click="dismissToast(i)" type="button" class="flex-shrink-0 w-6 h-6 rounded-lg flex items-center justify-center hover:bg-black/5 transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Confirmation Dialog Variants -->
    <div class="ynex-card p-6" x-data="confirmManager()">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Confirmation Dialogs</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button @click="confirmDelete = true" type="button" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/50 transition-all border border-red-200 dark:border-red-800 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Delete Confirmation
            </button>
            <button @click="confirmSubmit = true" type="button" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-all border border-emerald-200 dark:border-emerald-800 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Submit Confirmation
            </button>
            <button @click="confirmPrompt = true" type="button" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium bg-gov-50 dark:bg-gov-900/30 text-gov-700 dark:text-gov-300 hover:bg-gov-100 dark:hover:bg-gov-900/50 transition-all border border-gov-200 dark:border-gov-800 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Input Prompt
            </button>
        </div>

        <!-- Delete Confirm Overlay -->
        <div x-show="confirmDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div @click="confirmDelete = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div @click.outside="confirmDelete = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center z-10">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Are you sure?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">This action cannot be undone. The item will be permanently deleted.</p>
                <div class="flex justify-center gap-3">
                    <button @click="confirmDelete = false" type="button" class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-all cursor-pointer">Cancel</button>
                    <button @click="confirmDelete = false; showToast('success', 'Item deleted successfully.')" type="button" class="px-5 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all cursor-pointer">Delete</button>
                </div>
            </div>
        </div>

        <!-- Submit Confirm Overlay -->
        <div x-show="confirmSubmit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div @click="confirmSubmit = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div @click.outside="confirmSubmit = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center z-10">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Submit Application</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">You are about to submit your application. Please double-check all information before proceeding.</p>
                <div class="flex justify-center gap-3">
                    <button @click="confirmSubmit = false" type="button" class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-all cursor-pointer">Review</button>
                    <button @click="confirmSubmit = false; showToast('success', 'Application submitted!')" type="button" class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all cursor-pointer">Submit</button>
                </div>
            </div>
        </div>

        <!-- Input Prompt Overlay -->
        <div x-show="confirmPrompt" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div @click="confirmPrompt = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div @click.outside="confirmPrompt = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6 z-10">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Enter Details</h3>
                    <button @click="confirmPrompt = false" type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Reason</label>
                        <textarea rows="3" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Please provide a brief explanation..."></textarea>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="confirmPrompt = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-all cursor-pointer">Cancel</button>
                    <button @click="confirmPrompt = false; showToast('success', 'Your request has been submitted.')" type="button" class="px-4 py-2 text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 rounded-lg transition-all cursor-pointer">Submit</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes alert-pop {
        0% { opacity: 0; transform: scale(0.9) translateY(10px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }
</style>
@endsection
