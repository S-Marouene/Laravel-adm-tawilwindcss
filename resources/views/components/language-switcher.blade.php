<div x-data="localeSwitcher()" class="relative">
    <button @click="open = !open" type="button" class="flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ $variant === 'minimal' ? 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm' }}" aria-haspopup="true" :aria-expanded="open">
        <span x-show="currentLocale === 'fr'" class="text-base leading-none">🇫🇷</span>
        <span x-show="currentLocale === 'ar'" class="text-base leading-none" style="display: none;">🇹🇳</span>
        <span x-show="currentLocale === 'fr'" class="{{ $variant === 'minimal' ? 'hidden sm:inline' : '' }}">{{ __('lang.french') }}</span>
        <span x-show="currentLocale === 'ar'" class="{{ $variant === 'minimal' ? 'hidden sm:inline' : '' }}" style="display: none;">{{ __('lang.arabic') }}</span>
        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'chevron-rotate': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute mt-2 w-48 rounded-xl bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700 ring-1 ring-black/5 z-50" :class="currentLocale === 'ar' ? 'left-0' : 'right-0'" style="display: none;">
        <div class="py-1">
            <button @click="switchLocale('fr')" class="w-full flex items-center gap-3 px-4 py-3 text-sm transition-colors" :class="currentLocale === 'fr' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                <span class="text-lg">🇫🇷</span>
                <div class="text-left" :dir="'ltr'">
                    <p class="font-medium">Français</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Langue par défaut</p>
                </div>
                <svg x-show="currentLocale === 'fr'" class="w-4 h-4 ml-auto text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
            <div class="mx-3 border-t border-gray-100 dark:border-gray-700"></div>
            <button @click="switchLocale('ar')" class="w-full flex items-center gap-3 px-4 py-3 text-sm transition-colors" :class="currentLocale === 'ar' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                <span class="text-lg">🇹🇳</span>
                <div class="text-left" :dir="'ltr'">
                    <p class="font-medium">العربية</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">اللغة العربية</p>
                </div>
                <svg x-show="currentLocale === 'ar'" class="w-4 h-4 ml-auto text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
        </div>
    </div>
</div>
