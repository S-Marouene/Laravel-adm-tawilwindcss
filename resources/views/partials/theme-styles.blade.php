@php
    $theme = \App\Models\ThemeSetting::getCssValues();
    $fontFamily = $theme['font_family'] ?? 'Roboto';
    $baseFontSize = (int) ($theme['base_font_size'] ?? 15);
    $radius = (int) ($theme['border_radius'] ?? 4);
    $density = $theme['interface_density'] ?? 'comfortable';
    $buttonStyle = $theme['button_style'] ?? 'gradient';
    $cardShadow = $theme['card_shadow'] ?? 'soft';
    $headingStyle = $theme['heading_style'] ?? 'balanced';

    $densityScale = match ($density) {
        'compact' => '0.86',
        'spacious' => '1.14',
        default => '1',
    };

    $cardShadowValue = match ($cardShadow) {
        'none' => 'none',
        'deep' => '0 24px 60px -34px rgba(15, 23, 42, 0.78)',
        default => '0 1px 2px rgba(15, 23, 42, 0.04), 0 20px 42px -36px rgba(15, 23, 42, 0.55)',
    };

    $headingWeight = match ($headingStyle) {
        'light' => '600',
        'strong' => '800',
        default => '700',
    };

    $buttonBackground = match ($buttonStyle) {
        'solid' => 'var(--color-primary)',
        'outline' => 'transparent',
        default => 'linear-gradient(135deg, var(--color-primary), var(--color-primary-container))',
    };

    $buttonColor = $buttonStyle === 'outline' ? 'var(--color-primary)' : 'var(--color-on-primary)';
    $buttonBorder = $buttonStyle === 'outline' ? '1px solid var(--color-primary)' : '1px solid transparent';
@endphp

<style>
    :root {
        --app-font-family: "{{ $fontFamily }}", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        --app-base-font-size: {{ max(10, min(28, $baseFontSize)) }}px;
        --app-radius: {{ max(0, min(28, $radius)) }}px;
        --app-density: {{ $densityScale }};
        --app-card-shadow: {{ $cardShadowValue }};
        --app-heading-weight: {{ $headingWeight }};
        --app-primary-button-background: {{ $buttonBackground }};
        --app-primary-button-color: {{ $buttonColor }};
        --app-primary-button-border: {{ $buttonBorder }};
        --color-gov-50: color-mix(in srgb, {{ $theme['primary_color'] ?? '#9E001F' }} 8%, white);
        --color-gov-100: color-mix(in srgb, {{ $theme['primary_color'] ?? '#9E001F' }} 16%, white);
        --color-gov-200: color-mix(in srgb, {{ $theme['primary_color'] ?? '#9E001F' }} 30%, white);
        --color-gov-300: color-mix(in srgb, {{ $theme['primary_color'] ?? '#9E001F' }} 45%, white);
        --color-gov-400: color-mix(in srgb, {{ $theme['primary_color'] ?? '#9E001F' }} 66%, white);
        --color-gov-500: {{ $theme['primary_color'] ?? '#9E001F' }};
        --color-gov-600: color-mix(in srgb, {{ $theme['primary_color'] ?? '#9E001F' }} 88%, black);
        --color-gov-700: color-mix(in srgb, {{ $theme['primary_color'] ?? '#9E001F' }} 74%, black);
        --color-gov-800: color-mix(in srgb, {{ $theme['primary_color'] ?? '#9E001F' }} 48%, black);
        --color-gov-900: color-mix(in srgb, {{ $theme['primary_color'] ?? '#9E001F' }} 28%, black);
        --color-accent-50: color-mix(in srgb, {{ $theme['accent_color'] ?? '#9A5200' }} 8%, white);
        --color-accent-100: color-mix(in srgb, {{ $theme['accent_color'] ?? '#9A5200' }} 16%, white);
        --color-accent-500: {{ $theme['accent_color'] ?? '#9A5200' }};
        --color-accent-600: color-mix(in srgb, {{ $theme['accent_color'] ?? '#9A5200' }} 88%, black);
        --color-sidebar: {{ $theme['sidebar_color'] ?? '#283044' }};
        --color-body: {{ $theme['page_background'] ?? '#FAF8FF' }};
    }
</style>
