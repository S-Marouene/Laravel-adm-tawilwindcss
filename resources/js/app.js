import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Locale Switcher Component
 * - Switches locale instantly (client-side)
 * - Updates dir attribute on <html>
 * - Stores preference in localStorage + cookies
 * - Smooth transition overlay on switch
 */
Alpine.data('localeSwitcher', () => ({
    open: false,
    currentLocale: document.documentElement.getAttribute('lang')?.substring(0, 2) || 'fr',
    switching: false,

    init() {
        // Restore from localStorage on mount (before any server response)
        const stored = localStorage.getItem('user_locale');
        if (stored && ['fr', 'ar'].includes(stored) && stored !== this.currentLocale) {
            this.applyLocale(stored, false);
        }

        // Listen for Escape key
        this.$watch('open', (value) => {
            if (value) {
                this.$nextTick(() => {
                    this.$el.querySelector('button').focus();
                });
            }
        });
    },

    switchLocale(locale) {
        if (this.switching || locale === this.currentLocale) {
            this.open = false;
            return;
        }

        this.open = false;
        this.switching = true;

        // Show transition overlay
        this.showTransitionOverlay();

        // 1. Update the page immediately (optimistic)
        this.applyLocale(locale, true);

        // 2. Persist to server (async – don't wait for it)
        this.syncToServer(locale);
    },

    applyLocale(locale, withReload = true) {
        this.currentLocale = locale;
        const dir = locale === 'ar' ? 'rtl' : 'ltr';

        // Update <html> attributes immediately (flips the page) 
        document.documentElement.setAttribute('lang', locale === 'ar' ? 'ar' : 'fr');
        document.documentElement.setAttribute('dir', dir);

        // Store in localStorage for instant retrieval on next visit
        localStorage.setItem('user_locale', locale);

        // Set a client-side cookie so the middleware can read it even if
        // the server-side fetch hasn't completed before reload
        document.cookie = `user_locale=${locale}; path=/; max-age=${60*60*24*365}; SameSite=Lax`;

        if (withReload) {
            // Reload page after transition overlay animation
            // The cookie ensures the server reads the correct locale on reload
            setTimeout(() => {
                window.location.reload();
            }, 400);
        }
    },

    syncToServer(locale) {
        // Fire-and-forget fetch to update server session + cookie
        fetch(`/locale/${locale}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).catch(() => {
            // Silent fail – client-side cookie + localStorage handle it
        });
    },

    showTransitionOverlay() {
        // Remove any existing overlay first
        const existing = document.getElementById('locale-transition');
        if (existing) existing.remove();

        // Create a brief overlay for smooth transition feel
        const overlay = document.createElement('div');
        overlay.id = 'locale-transition';
        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: #0F172A;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease-out;
        `;
        document.body.appendChild(overlay);

        // Trigger fade-in after paint
        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
        });
    },

    syncToServer(locale) {
        // Fire-and-forget fetch to update server session + cookie
        fetch(`/locale/${locale}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).catch(() => {
            // Silent fail – localStorage will handle it
        });
    },

    showTransitionOverlay() {
        // Create a brief overlay for smooth transition feel
        const overlay = document.createElement('div');
        overlay.id = 'locale-transition';
        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: #0F172A;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease-out;
        `;
        document.body.appendChild(overlay);

        // Trigger fade-in
        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
        });
    },
}));

// Mobile Menu Component
Alpine.data('mobileMenu', () => ({
    open: false
}));

// Stats Counter Animation
Alpine.data('statsCounter', (target) => ({
    count: 0,
    init() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCount(target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        observer.observe(this.$el);
    },
    animateCount(target) {
        let start = 0;
        const duration = 2000;
        const step = timestamp => {
            if (!start) start = timestamp;
            const progress = Math.min((timestamp - start) / duration, 1);
            this.count = Math.floor(progress * target);
            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };
        requestAnimationFrame(step);
    }
}));

// Smooth scroll for anchor links
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});

Alpine.start();
