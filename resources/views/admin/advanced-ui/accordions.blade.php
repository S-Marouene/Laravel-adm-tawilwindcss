@extends('admin.layouts.admin')

@section('title', __('nav.accordions'))

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-amber-700 text-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('nav.accordions') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Expandable accordion panels and collapsible sections.</p>
        </div>
    </div>

    <!-- Single-Open Accordion (FAQ Style) -->
    <div class="ynex-card p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Single-Open Accordion (FAQ Style)</h3>
        <div x-data="accordionDemo()" class="space-y-2">
            <template x-for="(faq, i) in faqs" :key="i">
                <div class="rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden transition-all duration-200"
                    :class="{ 'border-gov-200 dark:border-gov-700 shadow-sm': active === i }">
                    <button @click="active = active === i ? null : i" type="button" class="w-full flex items-center justify-between px-5 py-4 text-left cursor-pointer transition-colors"
                        :class="active === i ? 'bg-gov-50 dark:bg-gov-900/30' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                        <span class="text-sm font-medium" :class="active === i ? 'text-gov-700 dark:text-gov-300' : 'text-gray-900 dark:text-gray-100'" x-text="faq.q"></span>
                        <svg class="w-5 h-5 flex-shrink-0 ml-3 transition-transform duration-300" :class="{ 'rotate-180': active === i }"
                            :class="active === i ? 'text-gov-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="active === i">
                        <div class="px-5 pb-4 text-sm text-gray-600 dark:text-gray-400 leading-relaxed" :class="active === i ? 'bg-gov-50/50 dark:bg-gov-900/20' : ''" x-text="faq.a"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Multi-Open Accordion -->
    <div class="ynex-card p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Multi-Open Accordion</h3>
        <div x-data="accordionDemo()" class="space-y-2">
            <template x-for="(item, i) in items" :key="i">
                <div class="rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden transition-all duration-200"
                    :class="{ 'border-emerald-200 dark:border-emerald-800 shadow-sm': open.includes(i) }">
                    <button @click="open = open.includes(i) ? open.filter(x => x !== i) : [...open, i]" type="button" class="w-full flex items-center justify-between px-5 py-4 text-left cursor-pointer transition-colors bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold transition-all duration-300"
                                :class="open.includes(i) ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'"
                                x-text="i + 1">
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="item.title"></span>
                        </div>
                        <svg class="w-5 h-5 flex-shrink-0 ml-3 text-gray-400 transition-transform duration-300" :class="{ 'rotate-45': open.includes(i) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                    <div x-show="open.includes(i)">
                        <div class="px-5 pb-4 text-sm text-gray-600 dark:text-gray-400 leading-relaxed bg-gray-50/50 dark:bg-gray-700/20" x-text="item.desc"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Nested Accordion -->
    <div class="ynex-card p-6">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Nested Accordion</h3>
        <div x-data="accordionDemo()" class="space-y-2">
            <template x-for="(group, gi) in nested" :key="gi">
                <div class="rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <button @click="section = section === gi ? null : gi; subsection = null" type="button" class="w-full flex items-center justify-between px-5 py-4 text-left cursor-pointer bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br flex items-center justify-center" :class="group.gradient">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="group.name"></span>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform duration-300" :class="{ 'rotate-180': section === gi }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="section === gi">
                        <div class="px-5 pb-4 space-y-1">
                            <template x-for="(sub, si) in group.children" :key="si">
                                <div class="rounded-lg border border-gray-100 dark:border-gray-700 overflow-hidden ml-4">
                                    <button @click.stop="subsection = subsection === si ? null : si" type="button" class="w-full flex items-center justify-between px-4 py-3 text-left cursor-pointer bg-gray-50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="sub.name"></span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': subsection === si }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="subsection === si">
                                        <div class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 leading-relaxed bg-white dark:bg-gray-800" x-text="sub.content"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

