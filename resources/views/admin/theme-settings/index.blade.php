@extends('admin.layouts.admin')

@section('title', __('admin.theme_settings'))

@section('content')
    @php
        $groupMeta = [
            'colors' => [
                'title' => __('admin.theme_colors'),
                'description' => __('admin.theme_colors_desc'),
                'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
                'classes' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300',
            ],
            'typography' => [
                'title' => __('admin.theme_typography'),
                'description' => __('admin.theme_typography_desc'),
                'icon' => 'M4 6h16M4 12h10M4 18h7',
                'classes' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300',
            ],
            'layout' => [
                'title' => __('admin.theme_layout'),
                'description' => __('admin.theme_layout_desc'),
                'icon' => 'M4 5a2 2 0 012-2h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm4 0v14m8-14v14',
                'classes' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
            ],
            'components' => [
                'title' => __('admin.theme_components'),
                'description' => __('admin.theme_components_desc'),
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'classes' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
            ],
        ];

        $labelFor = fn ($setting) => \Illuminate\Support\Facades\Lang::has("admin.theme_setting_{$setting->key}")
            ? __("admin.theme_setting_{$setting->key}")
            : $setting->label;
        $descriptionFor = fn ($setting) => \Illuminate\Support\Facades\Lang::has("admin.theme_setting_{$setting->key}_desc")
            ? __("admin.theme_setting_{$setting->key}_desc")
            : $setting->description;
        $optionLabelFor = fn ($option) => \Illuminate\Support\Facades\Lang::has("admin.theme_option_{$option}")
            ? __("admin.theme_option_{$option}")
            : \Illuminate\Support\Str::headline($option);
        $defaultColorValues = collect(\App\Models\ThemeSetting::defaults())
            ->where('group', 'colors')
            ->where('type', 'color')
            ->pluck('value', 'key');
    @endphp

    <div class="mb-8">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-gov-100 dark:bg-gov-900/50 text-gov-600 dark:text-gov-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013l-2.13 1.065A.75.75 0 019.75 19.75v-5.318a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-on-surface dark:text-gray-100">{{ __('admin.theme_settings') }}</h1>
                <p class="text-sm text-secondary dark:text-gray-400 mt-1">{{ __('admin.theme_settings_desc') }}</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.theme-settings.update') }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_22rem] gap-6">
            <div class="space-y-6">
                @foreach($themeSettings as $group => $settings)
                    @php($meta = $groupMeta[$group] ?? $groupMeta['components'])

                    <section class="admin-form-card">
                        <div class="px-6 py-4 border-b border-surface-dim dark:border-gray-700 bg-surface-container/50 dark:bg-gray-900/30">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg {{ $meta['classes'] }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $meta['icon'] }}"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-semibold text-on-surface dark:text-gray-100">{{ $meta['title'] }}</h2>
                                        <p class="text-sm text-secondary dark:text-gray-400">{{ $meta['description'] }}</p>
                                    </div>
                                </div>

                                @if($group === 'colors')
                                    <button type="button" id="reset-theme-colors" class="inline-flex items-center gap-2 rounded-lg border border-surface-dim bg-surface-container-lowest px-3 py-2 text-xs font-semibold text-on-surface transition hover:bg-surface-container focus:outline-none focus:ring-2 focus:ring-gov-500/30 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9M4.582 9H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        {{ __('admin.theme_colors_reset') }}
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                            @foreach($settings as $setting)
                                <div>
                                    <label for="{{ $setting->key }}" class="block text-sm font-medium text-on-surface dark:text-gray-300 mb-1.5">
                                        {{ $labelFor($setting) }}
                                    </label>

                                    @if($descriptionFor($setting))
                                        <p class="text-xs text-secondary dark:text-gray-400 mb-2">{{ $descriptionFor($setting) }}</p>
                                    @endif

                                    @if($setting->type === 'color')
                                        <div class="flex items-center gap-3">
                                            <input type="color" id="{{ $setting->key }}" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}" class="h-11 w-14 rounded-lg border border-surface-dim dark:border-gray-600 bg-surface-container-lowest dark:bg-gray-900 cursor-pointer">
                                            <input type="text" value="{{ old($setting->key, $setting->value) }}" class="theme-color-text block w-full px-4 py-2.5 text-sm text-on-surface dark:text-gray-100 bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 focus:outline-none transition-all duration-200" data-color-target="{{ $setting->key }}" maxlength="7">
                                        </div>
                                    @elseif($setting->type === 'select')
                                        <select id="{{ $setting->key }}" name="{{ $setting->key }}" class="block w-full px-4 py-2.5 text-sm text-on-surface dark:text-gray-100 bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 focus:outline-none transition-all duration-200">
                                            @foreach(($setting->options ?? []) as $option)
                                                <option value="{{ $option }}" {{ old($setting->key, $setting->value) === $option ? 'selected' : '' }}>
                                                    {{ $optionLabelFor($option) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <div class="flex items-center gap-3">
                                            <input type="number" id="{{ $setting->key }}" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}" min="10" max="28" class="block w-full px-4 py-2.5 text-sm text-on-surface dark:text-gray-100 bg-surface-container-lowest dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-gov-500/30 focus:border-gov-500 focus:outline-none transition-all duration-200">
                                            <span class="text-sm font-medium text-secondary dark:text-gray-400">px</span>
                                        </div>
                                    @endif

                                    @error($setting->key)
                                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <aside class="admin-form-card self-start xl:sticky xl:top-24">
                <div class="p-6 space-y-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-secondary dark:text-gray-400">{{ __('admin.theme_preview') }}</p>
                        <h2 class="mt-2 text-xl font-bold text-on-surface dark:text-gray-100">{{ setting('app_name', __('app.name')) }}</h2>
                        <p class="mt-2 text-sm text-secondary dark:text-gray-400">{{ __('admin.theme_preview_desc') }}</p>
                    </div>

                    <div id="theme-preview" class="rounded-xl border border-surface-dim dark:border-gray-700 overflow-hidden">
                        <div class="h-16 bg-gradient-to-r from-sidebar via-gov-600 to-accent-600"></div>
                        <div class="p-4 bg-surface-container-lowest dark:bg-gray-800">
                            <div class="h-2.5 w-24 rounded-full bg-gov-100 dark:bg-gov-900/40 mb-3"></div>
                            <div class="h-2.5 w-36 rounded-full bg-surface-container dark:bg-gray-700 mb-4"></div>
                            <button type="button" class="admin-add-button px-4 py-2 text-sm">{{ __('admin.theme_preview_button') }}</button>
                        </div>
                    </div>

                    <button type="submit" class="admin-add-button w-full px-6 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gov-500/40 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('action.save') }}
                    </button>
                </div>
            </aside>
        </div>
    </form>

    <script>
        (function() {
            var preview = document.getElementById('theme-preview');
            var defaultColors = @json($defaultColorValues);

            function fieldValue(name, fallback) {
                var field = document.querySelector('[name="' + name + '"]');
                return field ? field.value : fallback;
            }

            function buttonBackground(style) {
                if (style === 'solid') {
                    return 'var(--color-gov-600)';
                }

                if (style === 'outline') {
                    return 'transparent';
                }

                return 'linear-gradient(135deg, var(--color-gov-600), var(--color-gov-700) 55%, color-mix(in srgb, var(--color-accent-600) 52%, black))';
            }

            function applyPreview() {
                if (!preview) {
                    return;
                }

                var primary = fieldValue('primary_color', '#6B3FAF');
                var accent = fieldValue('accent_color', '#0891B2');
                var sidebar = fieldValue('sidebar_color', '#111C43');
                var pageBackground = fieldValue('page_background', '#F5F6FA');
                var radius = Math.max(0, Math.min(28, parseInt(fieldValue('border_radius', '12'), 10) || 12));
                var fontSize = Math.max(10, Math.min(28, parseInt(fieldValue('base_font_size', '16'), 10) || 16));
                var fontFamily = fieldValue('font_family', 'Inter');
                var density = fieldValue('interface_density', 'comfortable');
                var buttonStyle = fieldValue('button_style', 'gradient');
                var cardShadow = fieldValue('card_shadow', 'soft');
                var headingStyle = fieldValue('heading_style', 'balanced');
                var densityScale = density === 'compact' ? '0.86' : (density === 'spacious' ? '1.14' : '1');
                var shadow = cardShadow === 'none'
                    ? 'none'
                    : (cardShadow === 'deep' ? '0 24px 60px -34px rgba(15, 23, 42, 0.78)' : '0 1px 2px rgba(15, 23, 42, 0.04), 0 20px 42px -36px rgba(15, 23, 42, 0.55)');

                preview.style.setProperty('--app-font-family', '"' + fontFamily + '", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif');
                preview.style.setProperty('--app-base-font-size', fontSize + 'px');
                preview.style.setProperty('--app-radius', radius + 'px');
                preview.style.setProperty('--app-density', densityScale);
                preview.style.setProperty('--app-card-shadow', shadow);
                preview.style.setProperty('--app-heading-weight', headingStyle === 'strong' ? '800' : (headingStyle === 'light' ? '600' : '700'));
                preview.style.setProperty('--app-primary-button-background', buttonBackground(buttonStyle));
                preview.style.setProperty('--app-primary-button-color', buttonStyle === 'outline' ? 'var(--color-gov-700)' : 'white');
                preview.style.setProperty('--app-primary-button-border', buttonStyle === 'outline' ? '1px solid var(--color-gov-500)' : '1px solid transparent');
                preview.style.setProperty('--color-gov-50', 'color-mix(in srgb, ' + primary + ' 9%, white)');
                preview.style.setProperty('--color-gov-100', 'color-mix(in srgb, ' + primary + ' 18%, white)');
                preview.style.setProperty('--color-gov-500', primary);
                preview.style.setProperty('--color-gov-600', 'color-mix(in srgb, ' + primary + ' 88%, black)');
                preview.style.setProperty('--color-gov-700', 'color-mix(in srgb, ' + primary + ' 74%, black)');
                preview.style.setProperty('--color-accent-500', accent);
                preview.style.setProperty('--color-accent-600', 'color-mix(in srgb, ' + accent + ' 88%, black)');
                preview.style.setProperty('--color-sidebar', sidebar);
                preview.style.setProperty('--color-body', pageBackground);
                preview.style.fontFamily = 'var(--app-font-family)';
                preview.style.fontSize = 'var(--app-base-font-size)';
                preview.style.borderRadius = 'var(--app-radius)';
                preview.style.boxShadow = 'var(--app-card-shadow)';
                preview.style.background = 'linear-gradient(180deg, color-mix(in srgb, ' + pageBackground + ' 96%, white), color-mix(in srgb, ' + pageBackground + ' 88%, #E2E8F0))';
            }

            document.querySelectorAll('.theme-color-text').forEach(function(input) {
                var picker = document.getElementById(input.dataset.colorTarget);

                input.addEventListener('input', function() {
                    if (/^#[0-9A-Fa-f]{6}$/.test(input.value)) {
                        picker.value = input.value;
                        applyPreview();
                    }
                });

                picker.addEventListener('input', function() {
                    input.value = picker.value;
                    applyPreview();
                });
            });

            document.querySelectorAll('[name="font_family"], [name="base_font_size"], [name="heading_style"], [name="interface_density"], [name="border_radius"], [name="button_style"], [name="card_shadow"], [name="primary_color"], [name="accent_color"], [name="sidebar_color"], [name="page_background"]').forEach(function(field) {
                field.addEventListener('input', applyPreview);
                field.addEventListener('change', applyPreview);
            });

            var resetColorsButton = document.getElementById('reset-theme-colors');

            if (resetColorsButton) {
                resetColorsButton.addEventListener('click', function() {
                    Object.keys(defaultColors).forEach(function(key) {
                        var picker = document.getElementById(key);
                        var textInput = document.querySelector('.theme-color-text[data-color-target="' + key + '"]');

                        if (picker) {
                            picker.value = defaultColors[key];
                            picker.dispatchEvent(new Event('input', { bubbles: true }));
                        }

                        if (textInput) {
                            textInput.value = defaultColors[key];
                        }
                    });

                    applyPreview();
                });
            }

            applyPreview();
        })();
    </script>
@endsection
