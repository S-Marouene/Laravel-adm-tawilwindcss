@extends('admin.layouts.admin')

@section('title', __('nav.stepper'))

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-gov-500 to-gov-700 text-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/>
            </svg>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('nav.stepper') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Multi-step form wizards with various styles and progress tracking.</p>
        </div>
    </div>

    <!-- 1. Basic Stepper (Linear) -->
    <div class="ynex-card p-6 mb-6" x-data="basicStepper()">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-5">Basic Stepper</h3>
        <!-- Nav -->
        <ul class="relative flex flex-row gap-x-2">
            <template x-for="(step, i) in steps" :key="i">
                <li class="flex items-center gap-x-2 shrink basis-0 flex-1 group" :class="{ 'cursor-pointer': !isDisabled(i) }" @click="!isDisabled(i) && goTo(i)">
                    <span class="min-w-7 min-h-7 inline-flex items-center text-xs align-middle">
                        <span class="size-7 flex justify-center items-center flex-shrink-0 font-medium rounded-full transition-all duration-300"
                            :class="{
                                'bg-gov-600 text-white shadow-lg shadow-gov-500/30 scale-110': isActive(i),
                                'bg-emerald-500 text-white': isCompleted(i),
                                'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400': !isActive(i) && !isCompleted(i)
                            }">
                            <svg x-show="isCompleted(i)" class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            <span x-show="!isCompleted(i)" x-text="i + 1"></span>
                        </span>
                        <span class="ms-2 text-sm font-medium transition-colors duration-200"
                            :class="{
                                'text-gov-600 dark:text-gov-400': isActive(i),
                                'text-emerald-600 dark:text-emerald-400': isCompleted(i),
                                'text-gray-500 dark:text-gray-400': !isActive(i) && !isCompleted(i)
                            }" x-text="step">
                        </span>
                    </span>
                    <div x-show="i < steps.length - 1" class="w-full h-px flex-1 transition-colors duration-500"
                        :class="isCompleted(i) ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'">
                    </div>
                </li>
            </template>
        </ul>
        <!-- Content -->
        <div class="mt-5 sm:mt-8">
            <div x-show="current < steps.length" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="p-5 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <template x-if="current === 0">
                            <div class="sm:col-span-2">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter Name"></div>
                                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email:</label><input type="email" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter Email"></div>
                                </div>
                            </div>
                        </template>
                        <template x-if="current === 1">
                            <div class="sm:col-span-2">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telephone:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter Telephone"></div>
                                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Mobile:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter Mobile"></div>
                                </div>
                            </div>
                        </template>
                        <template x-if="current === 2">
                            <div class="sm:col-span-2 space-y-4">
                                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">CardHolder Name:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter card Details"></div>
                                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Card number:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter card Details"></div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Expiry:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="MM/YY"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">CVV:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="***"></div></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div x-show="current === steps.length" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="p-8 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                        <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Hurray! Your Payment is Successful</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Thank you for your purchase.</p>
                </div>
            </div>
        </div>
        <!-- Buttons -->
        <div class="mt-5 flex justify-between items-center">
            <button @click="prev" x-show="!isFirst" type="button" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all disabled:opacity-50 disabled:pointer-events-none" :disabled="isFirst">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> Back
            </button>
            <div x-show="isFirst"><span></span></div>
            <div class="flex gap-2">
                <button x-show="current < steps.length" @click="next" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all disabled:opacity-50 disabled:pointer-events-none" :disabled="isLast">
                    Next <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button x-show="current === steps.length - 1" @click="finish" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Finish
                </button>
                <button x-show="current === steps.length" @click="reset" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Non-Linear Stepper -->
    <div class="ynex-card p-6 mb-6" x-data="nonLinearStepper()">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-5">Non-linear Stepper <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(click any step to jump)</span></h3>
        <ul class="relative flex flex-row gap-x-2">
            <template x-for="(step, i) in steps" :key="i">
                <li class="flex items-center gap-x-2 shrink basis-0 flex-1 group cursor-pointer" @click="goTo(i)">
                    <span class="min-w-7 min-h-7 inline-flex items-center text-xs align-middle">
                        <span class="size-7 flex justify-center items-center flex-shrink-0 font-medium rounded-full transition-all duration-300"
                            :class="{
                                'bg-gov-600 text-white shadow-lg shadow-gov-500/30 scale-110': isActive(i),
                                'bg-emerald-500 text-white': isCompleted(i),
                                'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400': !isActive(i) && !isCompleted(i)
                            }">
                            <svg x-show="isCompleted(i)" class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            <span x-show="!isCompleted(i)" x-text="i + 1"></span>
                        </span>
                        <span class="ms-2 text-sm font-medium transition-colors duration-200"
                            :class="{ 'text-gov-600 dark:text-gov-400': isActive(i), 'text-emerald-600 dark:text-emerald-400': isCompleted(i), 'text-gray-500 dark:text-gray-400': !isActive(i) && !isCompleted(i) }" x-text="step">
                        </span>
                    </span>
                    <div x-show="i < steps.length - 1" class="w-full h-px flex-1 transition-colors duration-500" :class="isCompleted(i) ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
                </li>
            </template>
        </ul>
        <div class="mt-5 sm:mt-8">
            <div x-show="current < steps.length" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="p-5 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Step <span x-text="current + 1"></span>: <span x-text="steps[current]"></span></span>
                        <button @click="completeStep(current)" class="text-xs font-semibold text-gov-600 dark:text-gov-400 hover:underline">Mark as Complete</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Field 1:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Field 2:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                    </div>
                </div>
            </div>
            <div x-show="current === steps.length" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="p-8 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                        <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">All Steps Completed!</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">You have completed all steps.</p>
                </div>
            </div>
        </div>
        <div class="mt-5 flex justify-between items-center">
            <button @click="prev" x-show="!isFirst" type="button" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> Back</button>
            <div class="flex gap-2">
                <button x-show="current < steps.length" @click="next" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all">Next <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                <button x-show="current === steps.length - 1" @click="finish" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Finish</button>
                <button x-show="current === steps.length" @click="reset" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reset</button>
            </div>
        </div>
    </div>

    <!-- 3. Skipped Stepper -->
    <div class="ynex-card p-6 mb-6" x-data="skippedStepper()">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-5">Skipped / Optional Stepper</h3>
        <ul class="relative flex flex-row gap-x-2">
            <template x-for="(step, i) in steps" :key="i">
                <li class="flex items-center gap-x-2 shrink basis-0 flex-1 group">
                    <span class="min-w-7 min-h-7 inline-flex items-center text-xs align-middle">
                        <span class="size-7 flex justify-center items-center flex-shrink-0 font-medium rounded-full transition-all duration-300"
                            :class="{
                                'bg-gov-600 text-white shadow-lg shadow-gov-500/30 scale-110': isActive(i),
                                'bg-emerald-500 text-white': isCompleted(i),
                                'bg-amber-400 text-white': isSkipped(i),
                                'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400': !isActive(i) && !isCompleted(i) && !isSkipped(i)
                            }">
                            <svg x-show="isCompleted(i)" class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            <svg x-show="isSkipped(i)" class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            <span x-show="!isCompleted(i) && !isSkipped(i)" x-text="i + 1"></span>
                        </span>
                        <div class="ms-2 inline-flex items-center gap-1">
                            <span class="text-sm font-medium transition-colors duration-200"
                                :class="{
                                    'text-gov-600 dark:text-gov-400': isActive(i),
                                    'text-emerald-600 dark:text-emerald-400': isCompleted(i),
                                    'text-amber-600 dark:text-amber-400': isSkipped(i),
                                    'text-gray-500 dark:text-gray-400': !isActive(i) && !isCompleted(i) && !isSkipped(i)
                                }" x-text="step">
                            </span>
                            <span x-show="isActive(i)" class="text-xs text-amber-500 dark:text-amber-400">(optional)</span>
                        </div>
                    </span>
                    <div x-show="i < steps.length - 1" class="w-full h-px flex-1 transition-colors duration-500"
                        :class="isCompleted(i) ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
                </li>
            </template>
        </ul>
        <div class="mt-5 sm:mt-8">
            <div x-show="current < steps.length" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="p-5 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Step <span x-text="current + 1"></span>: <span class="font-medium text-gray-900 dark:text-gray-100" x-text="steps[current]"></span> <span class="text-xs text-amber-500">(optional — you may skip this)</span></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Field 1:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Field 2:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                    </div>
                </div>
            </div>
            <div x-show="current === steps.length" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="p-8 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                        <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Finished!</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">All required steps completed.</p>
                </div>
            </div>
        </div>
        <div class="mt-5 flex justify-between items-center">
            <button @click="prev" x-show="!isFirst" type="button" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> Back</button>
            <div class="flex gap-2">
                <button @click="skip" x-show="current < steps.length" type="button" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-sm font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-all border border-amber-200 dark:border-amber-800"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg> Skip</button>
                <button x-show="current < steps.length" @click="next" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all">Next <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                <button x-show="current === steps.length - 1" @click="finish" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Finish</button>
                <button x-show="current === steps.length" @click="reset" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reset</button>
            </div>
        </div>
    </div>

    <!-- 4. Active Stepper (pre-set to step 2) -->
    <div class="ynex-card p-6 mb-6" x-data="activeStepper()">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-5">Active Stepper <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(starts at step 2)</span></h3>
        <ul class="relative flex flex-row gap-x-2">
            <template x-for="(step, i) in steps" :key="i">
                <li class="flex items-center gap-x-2 shrink basis-0 flex-1 group cursor-pointer" @click="goTo(i)">
                    <span class="min-w-7 min-h-7 inline-flex items-center text-xs align-middle">
                        <span class="size-7 flex justify-center items-center flex-shrink-0 font-medium rounded-full transition-all duration-300"
                            :class="{
                                'bg-gov-600 text-white shadow-lg shadow-gov-500/30 scale-110': isActive(i),
                                'bg-emerald-500 text-white': isCompleted(i),
                                'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400': !isActive(i) && !isCompleted(i)
                            }">
                            <svg x-show="isCompleted(i)" class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            <span x-show="!isCompleted(i)" x-text="i + 1"></span>
                        </span>
                        <span class="ms-2 text-sm font-medium transition-colors duration-200"
                            :class="{ 'text-gov-600 dark:text-gov-400': isActive(i), 'text-emerald-600 dark:text-emerald-400': isCompleted(i), 'text-gray-500 dark:text-gray-400': !isActive(i) && !isCompleted(i) }" x-text="step">
                        </span>
                    </span>
                    <div x-show="i < steps.length - 1" class="w-full h-px flex-1 transition-colors duration-500" :class="isCompleted(i) ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
                </li>
            </template>
        </ul>
        <div class="mt-5 sm:mt-8">
            <div x-show="current < steps.length" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="p-5 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Currently on <span class="font-medium text-gray-900 dark:text-gray-100" x-text="steps[current]"></span> (Step <span x-text="current + 1"></span>)</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Input 1:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Input 2:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                    </div>
                </div>
            </div>
            <div x-show="current === steps.length" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="p-8 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                        <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Hurray! Your Payment is Successful</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Thank you for your purchase.</p>
                </div>
            </div>
        </div>
        <div class="mt-5 flex justify-between items-center">
            <button @click="prev" x-show="!isFirst" type="button" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> Back</button>
            <div class="flex gap-2">
                <button x-show="current < steps.length" @click="next" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all">Next <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                <button x-show="current === steps.length - 1" @click="finish" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Finish</button>
                <button x-show="current === steps.length" @click="reset" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reset</button>
            </div>
        </div>
    </div>

    <!-- 5. Vertical Stepper -->
    <div class="ynex-card p-6 mb-6" x-data="verticalStepper()">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-5">Vertical Stepper</h3>
        <div class="flex flex-col md:flex-row gap-6">
            <div class="md:w-64 flex-shrink-0">
                <template x-for="(step, i) in steps" :key="i">
                    <div class="relative flex gap-3 pb-6 last:pb-0 cursor-pointer" @click="goTo(i)">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 flex-shrink-0"
                                :class="{
                                    'bg-gov-600 text-white shadow-lg shadow-gov-500/30 scale-110': isActive(i),
                                    'bg-emerald-500 text-white': isCompleted(i),
                                    'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400': !isActive(i) && !isCompleted(i)
                                }">
                                <svg x-show="isCompleted(i)" class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                <span x-show="!isCompleted(i)" x-text="i + 1"></span>
                            </div>
                            <div x-show="i < steps.length - 1" class="w-0.5 flex-1 mt-1 transition-colors duration-300 min-h-[2rem]" :class="isCompleted(i) ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
                        </div>
                        <div class="pb-1">
                            <p class="text-sm font-medium transition-colors duration-200"
                                :class="{
                                    'text-gov-700 dark:text-gov-300': isActive(i),
                                    'text-emerald-700 dark:text-emerald-300': isCompleted(i),
                                    'text-gray-500 dark:text-gray-400': !isActive(i) && !isCompleted(i)
                                }" x-text="step">
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Click to view details</p>
                        </div>
                    </div>
                </template>
            </div>
            <div class="flex-1">
                <div x-show="current < steps.length" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="p-5 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3" x-text="steps[current] + ' Details'"></h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">This section contains the details for <strong x-text="steps[current].toLowerCase()"></strong>. Fill in the required information and proceed to the next step when ready.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Input 1:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Input 2:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                        </div>
                    </div>
                </div>
                <div x-show="current === steps.length" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    <div class="p-6 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-center">
                        <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                            <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">All Steps Completed!</h4>
                    </div>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <button @click="prev" x-show="!isFirst" type="button" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> Back</button>
                    <div class="flex gap-2">
                        <button x-show="current < steps.length" @click="next" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all">Next <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                        <button x-show="current === steps.length - 1" @click="finish" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Finish</button>
                        <button x-show="current === steps.length" @click="reset" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. Icon Stepper -->
    <div class="ynex-card p-6 mb-6" x-data="iconStepper()">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-5">Icon Stepper</h3>
        <ul class="relative flex flex-row gap-x-2">
            <template x-for="(step, i) in steps" :key="i">
                <li class="flex items-center gap-x-2 shrink basis-0 flex-1 group cursor-pointer" @click="goTo(i)">
                    <span class="min-w-7 min-h-7 inline-flex items-center text-xs align-middle">
                        <span class="size-9 flex justify-center items-center flex-shrink-0 rounded-xl transition-all duration-300"
                            :class="{
                                'bg-gov-600 text-white shadow-lg shadow-gov-500/30 scale-110': isActive(i),
                                'bg-emerald-500 text-white': isCompleted(i),
                                'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500': !isActive(i) && !isCompleted(i)
                            }">
                            <svg x-show="isCompleted(i)" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <svg x-show="!isCompleted(i)" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" :d="step.icon"/></svg>
                        </span>
                        <span class="ms-2 text-sm font-medium transition-colors duration-200"
                            :class="{ 'text-gov-600 dark:text-gov-400': isActive(i), 'text-emerald-600 dark:text-emerald-400': isCompleted(i), 'text-gray-500 dark:text-gray-400': !isActive(i) && !isCompleted(i) }" x-text="step.label">
                        </span>
                    </span>
                    <div x-show="i < steps.length - 1" class="w-full h-px flex-1 transition-colors duration-500" :class="isCompleted(i) ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
                </li>
            </template>
        </ul>
        <div class="mt-5 sm:mt-8">
            <div x-show="current < steps.length" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="p-5 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Step <span x-text="current + 1"></span>: <span class="font-medium text-gray-900 dark:text-gray-100" x-text="steps[current].label"></span></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Field 1:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Field 2:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                    </div>
                </div>
            </div>
            <div x-show="current === steps.length" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="p-8 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                        <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">All Steps Completed!</h4>
                </div>
            </div>
        </div>
        <div class="mt-5 flex justify-between items-center">
            <button @click="prev" x-show="!isFirst" type="button" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> Back</button>
            <div class="flex gap-2">
                <button x-show="current < steps.length" @click="next" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all">Next <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                <button x-show="current === steps.length - 1" @click="finish" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Finish</button>
                <button x-show="current === steps.length" @click="reset" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reset</button>
            </div>
        </div>
    </div>

    <!-- 7. Pill Stepper -->
    <div class="ynex-card p-6" x-data="pillStepper()">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-5">Pill Stepper</h3>
        <div class="flex flex-wrap gap-2 p-1.5 rounded-xl bg-gray-100 dark:bg-gray-700/60 mb-5">
            <template x-for="(step, i) in steps" :key="i">
                <button @click="goTo(i)" type="button"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 cursor-pointer"
                    :class="{
                        'bg-white dark:bg-gray-600 text-gov-700 dark:text-gov-300 shadow-sm': isActive(i),
                        'bg-emerald-500 text-white': isCompleted(i) && !isActive(i),
                        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': !isActive(i) && !isCompleted(i)
                    }">
                    <span x-show="isCompleted(i)"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></span>
                    <span x-text="step"></span>
                </button>
            </template>
        </div>
        <div>
            <div x-show="current < steps.length" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="p-5 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                    <p class="text-sm text-gray-600 dark:text-gray-400">You are viewing: <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="steps[current]"></span></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Field 1:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Field 2:</label><input type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 transition-all text-sm" placeholder="Enter value"></div>
                    </div>
                </div>
            </div>
            <div x-show="current === steps.length" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="p-8 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                        <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Order Confirmed!</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Your order has been placed successfully.</p>
                </div>
            </div>
        </div>
        <div class="mt-5 flex justify-between items-center">
            <button @click="prev" x-show="!isFirst" type="button" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> Back</button>
            <div class="flex gap-2">
                <button x-show="current < steps.length" @click="next" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all">Next <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                <button x-show="current === steps.length - 1" @click="finish" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Finish</button>
                <button x-show="current === steps.length" @click="reset" type="button" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gov-600 hover:bg-gov-700 shadow-lg shadow-gov-500/20 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reset</button>
            </div>
        </div>
    </div>
</div>
@endsection
