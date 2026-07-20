@extends('admin.layouts.admin')

@section('title', __('nav.carousel'))

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-sky-500 to-sky-700 text-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('nav.carousel') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Image and content carousels with navigation controls.</p>
        </div>
    </div>

    <!-- Basic Carousel -->
    <div class="ynex-card p-6 mb-6" x-data="basicCarousel()">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Basic Carousel with Autoplay</h3>
            <label class="flex items-center gap-2 cursor-pointer">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Autoplay</span>
                <div @click="autoplay = !autoplay; if(autoplay) startAutoplay(); else stopAutoplay()" class="relative w-9 h-5 rounded-full transition-colors duration-200" :class="autoplay ? 'bg-gov-600' : 'bg-gray-300 dark:bg-gray-600'">
                    <div class="absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform duration-200" :class="autoplay ? 'translate-x-4' : 'translate-x-0.5'"></div>
                </div>
            </label>
        </div>
        <div class="relative overflow-hidden rounded-xl bg-gray-900">
            <div class="flex transition-transform duration-500 ease-out" :style="`transform: translateX(-${current * 100}%)`">
                <template x-for="(slide, i) in slides" :key="i">
                    <div class="min-w-full h-64 md:h-80 flex items-center justify-center" :style="`background: linear-gradient(135deg, ${slide.color1}, ${slide.color2})`">
                        <div class="text-center px-6">
                            <p class="text-2xl md:text-3xl font-bold text-white mb-2" x-text="slide.title"></p>
                            <p class="text-sm text-white/70 max-w-md mx-auto" x-text="slide.subtitle"></p>
                        </div>
                    </div>
                </template>
            </div>
            <!-- Arrows -->
            <button @click="prev" type="button" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 backdrop-blur-sm flex items-center justify-center text-white transition-all cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button @click="next" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 backdrop-blur-sm flex items-center justify-center text-white transition-all cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            <!-- Dots -->
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                <template x-for="(slide, i) in slides" :key="i">
                    <button @click="goTo(i)" type="button" class="w-2 h-2 rounded-full transition-all duration-300 cursor-pointer" :class="current === i ? 'w-6 bg-white' : 'bg-white/40 hover:bg-white/60'"></button>
                </template>
            </div>
        </div>
        <div class="flex items-center justify-center gap-1 mt-3 text-xs text-gray-500 dark:text-gray-400">
            <span x-text="current + 1"></span>
            <span>/</span>
            <span x-text="slides.length"></span>
        </div>
    </div>

    <!-- Card Carousel (Partial Visibility) -->
    <div class="ynex-card p-6 mb-6" x-data="cardCarousel()">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Card Carousel</h3>
        <div class="relative">
            <button @click="scroll(-1)" type="button" class="absolute -left-3 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white dark:bg-gray-700 shadow-lg border border-gray-100 dark:border-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div ref="container" class="flex gap-4 overflow-x-auto scroll-smooth snap-x snap-mandatory scrollbar-hide pb-2" style="scrollbar-width: none; -ms-overflow-style: none;">
                <template x-for="(card, i) in cards" :key="i">
                    <div class="min-w-[260px] snap-start rounded-xl border border-gray-100 dark:border-gray-700 p-5 flex-shrink-0 bg-white dark:bg-gray-800 hover:shadow-md transition-shadow">
                        <div class="w-10 h-10 rounded-lg mb-3 flex items-center justify-center" :style="`background: ${card.bg}`">
                            <svg class="w-5 h-5" :style="`color: ${card.color}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon"/></svg>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1" x-text="card.title"></h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed" x-text="card.desc"></p>
                    </div>
                </template>
            </div>
            <button @click="scroll(1)" type="button" class="absolute -right-3 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white dark:bg-gray-700 shadow-lg border border-gray-100 dark:border-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    <!-- Testimonial Carousel -->
    <div class="ynex-card p-6" x-data="testimonialCarousel()">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Testimonials</h3>
        <div class="relative max-w-2xl mx-auto">
            <template x-for="(t, i) in testimonials" :key="i">
                <div x-show="current === i" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-8" class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-gov-400 to-gov-600 flex items-center justify-center text-xl font-bold text-white" x-text="t.initials"></div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed italic mb-4">"<span x-text="t.text"></span>"</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="t.name"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="t.role"></p>
                </div>
            </template>
            <div class="flex justify-center gap-2 mt-6">
                <template x-for="(t, i) in testimonials" :key="i">
                    <button @click="current = i" type="button" class="w-2.5 h-2.5 rounded-full transition-all duration-300 cursor-pointer" :class="current === i ? 'bg-gov-600 w-6' : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500'"></button>
                </template>
            </div>
            <div class="flex justify-center gap-3 mt-4">
                <button @click="prev" type="button" class="w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button @click="next" type="button" class="w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

