import Alpine from 'alpinejs';
import 'boxicons/css/boxicons.min.css';

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
        this.currentLocale = locale;
        const dir = locale === 'ar' ? 'rtl' : 'ltr';
        document.documentElement.setAttribute('lang', locale === 'ar' ? 'ar' : 'fr');
        document.documentElement.setAttribute('dir', dir);
        localStorage.setItem('user_locale', locale);

        // 2. Persist to server (async) then reload once complete
        const serverSync = fetch(`/locale/${locale}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).catch(() => {
            // Silent fail – cookie + localStorage handle it
        });

        // 3. Set client-side cookie (not encrypted, excluded from EncryptCookies)
        document.cookie = `user_locale=${locale}; path=/; max-age=${60*60*24*365}; SameSite=Lax`;

        // 4. Wait for server sync (up to 3s) then reload
        Promise.race([
            serverSync,
            new Promise(resolve => setTimeout(resolve, 3000))
        ]).finally(() => {
            // Remove transition overlay
            const overlay = document.getElementById('locale-transition');
            if (overlay) overlay.remove();
            window.location.reload();
        });
    },

    applyLocale(locale, withReload = true) {
        // Used for init() restore — no reload needed
        this.currentLocale = locale;
        const dir = locale === 'ar' ? 'rtl' : 'ltr';

        document.documentElement.setAttribute('lang', locale === 'ar' ? 'ar' : 'fr');
        document.documentElement.setAttribute('dir', dir);
        localStorage.setItem('user_locale', locale);
        document.cookie = `user_locale=${locale}; path=/; max-age=${60*60*24*365}; SameSite=Lax`;
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

/**
 * Theme Toggle Component
 * - Toggles dark class on <html>
 * - Persists preference in localStorage
 * - Default is light mode
 */
Alpine.data('themeToggle', () => ({
    isDark: localStorage.getItem('theme') === 'dark',

    init() {
        const stored = localStorage.getItem('theme');
        if (stored === 'dark') {
            document.documentElement.classList.add('dark');
            this.isDark = true;
        } else {
            document.documentElement.classList.remove('dark');
            this.isDark = false;
        }
    },

    toggle() {
        this.isDark = !this.isDark;
        if (this.isDark) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    },
}));

/**
 * Fullscreen Toggle Component
 */
Alpine.data('fullscreenToggle', () => ({
    isFullscreen: false,

    toggle() {
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            this.openFullscreen();
        } else {
            this.closeFullscreen();
        }
    },

    openFullscreen() {
        const el = document.documentElement;
        if (el.requestFullscreen) {
            el.requestFullscreen();
        } else if (el.webkitRequestFullscreen) {
            el.webkitRequestFullscreen();
        }
        this.isFullscreen = true;
    },

    closeFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        }
        this.isFullscreen = false;
    },

    init() {
        document.addEventListener('fullscreenchange', () => {
            this.isFullscreen = !!document.fullscreenElement;
        });
        document.addEventListener('webkitfullscreenchange', () => {
            this.isFullscreen = !!document.webkitFullscreenElement;
        });
    }
}));

/**
 * Search Modal Component
 */
Alpine.data('searchModal', () => ({
    open: false,
    query: '',
    results: [],
    recentSearches: ['Dashboard', 'Users', 'Roles', 'Settings'],

    init() {
        this.$watch('open', (val) => {
            if (val) {
                this.$nextTick(() => {
                    this.$el.querySelector('input').focus();
                });
            }
        });

        // Listen for keyboard shortcut (Ctrl+K / Cmd+K)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.open = !this.open;
            }
            if (e.key === 'Escape') {
                this.open = false;
            }
        });
    },

    search() {
        if (!this.query.trim()) {
            this.results = [];
            return;
        }
        // Simple client-side search suggestions
        const pages = [
            { title: 'Dashboard', url: '/admin', icon: 'bx bx-home' },
            { title: 'Users', url: '/admin/users', icon: 'bx bx-user' },
            { title: 'Roles', url: '/admin/roles', icon: 'bx bx-shield' },
            { title: 'Permissions', url: '/admin/permissions', icon: 'bx bx-lock' },
            { title: 'Activity Logs', url: '/admin/activity-logs', icon: 'bx bx-history' },
            { title: 'Settings', url: '/admin/settings', icon: 'bx bx-cog' },
            { title: 'Stepper', url: '/admin/advanced-ui/stepper', icon: 'bx bx-sort' },
            { title: 'Modals', url: '/admin/advanced-ui/modals', icon: 'bx bx-window' },
            { title: 'Accordions', url: '/admin/advanced-ui/accordions', icon: 'bx bx-chevron-down' },
            { title: 'Tabs', url: '/admin/advanced-ui/tabs', icon: 'bx bx-tab' },
            { title: 'Carousel', url: '/admin/advanced-ui/carousel', icon: 'bx bx-slideshow' },
            { title: 'Tooltips', url: '/admin/advanced-ui/tooltips', icon: 'bx bx-info-circle' },
            { title: 'Dropdowns', url: '/admin/advanced-ui/dropdowns', icon: 'bx bx-chevron-down' },
            { title: 'Sweet Alerts', url: '/admin/advanced-ui/sweet-alerts', icon: 'bx bx-bell' },
        ];
        const q = this.query.toLowerCase();
        this.results = pages.filter(p =>
            p.title.toLowerCase().includes(q)
        );
    }
}));

/**
 * Notification Dropdown Component
 */
Alpine.data('notificationPanel', () => ({
    open: false,
    notifications: [],
    unreadCount: 0,

    init() {
        this.fetchNotifications();
    },

    fetchNotifications() {
        // For now, we'll load from the page's meta data if available
        const el = document.getElementById('notification-data');
        if (el) {
            try {
                this.notifications = JSON.parse(el.textContent || '[]');
                this.unreadCount = this.notifications.filter(n => !n.read_at).length;
            } catch(e) {
                this.notifications = [];
            }
        }
    },

    markAllRead() {
        this.notifications.forEach(n => n.read_at = new Date().toISOString());
        this.unreadCount = 0;
    },

    removeNotification(index) {
        this.notifications.splice(index, 1);
        this.unreadCount = this.notifications.filter(n => !n.read_at).length;
    }
}));

/**
 * Sidebar Manager Component
 * Manages the desktop sidebar collapsed/expanded state
 * Persists preference in localStorage
 */
Alpine.data('sidebarManager', () => ({
    collapsed: localStorage.getItem('sidebar_collapsed') === 'true',
    mobileOpen: false,

    get sidebarWidth() {
        return this.collapsed ? 'w-16' : 'w-60';
    },

    toggle() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('sidebar_collapsed', this.collapsed);
    },

    toggleMobile() {
        this.mobileOpen = !this.mobileOpen;
    },

    closeMobile() {
        this.mobileOpen = false;
    }
}));

// ==============================
// ADVANCED UI DEMO COMPONENTS
// ==============================

// ==============================
// STEPPER COMPONENTS
// ==============================

/**
 * Basic Stepper - Linear step-by-step wizard
 */
Alpine.data('basicStepper', () => ({
    current: 0,
    steps: ['Name & Email', 'Contact', 'Payment'],
    get isFirst() { return this.current === 0; },
    get isLast() { return this.current === this.steps.length - 1; },
    next() { if (!this.isLast) this.current++; },
    prev() { if (!this.isFirst) this.current--; },
    goTo(i) { this.current = i; },
    reset() { this.current = 0; },
    finish() { this.current = this.steps.length; },
    isActive(i) { return this.current === i; },
    isCompleted(i) { return this.current > i; },
    isDisabled(i) { return i > this.current + 1; }
}));

/**
 * Non-Linear Stepper - Can jump to any step
 */
Alpine.data('nonLinearStepper', () => ({
    current: 0,
    completed: [],
    steps: ['Name & Email', 'Contact', 'Payment'],
    get isFirst() { return this.current === 0; },
    get isLast() { return this.current === this.steps.length - 1; },
    next() { if (!this.isLast) { if (!this.completed.includes(this.current)) this.completed.push(this.current); this.current++; } },
    prev() { if (!this.isFirst) this.current--; },
    goTo(i) { this.current = i; },
    reset() { this.current = 0; this.completed = []; },
    finish() { if (!this.completed.includes(this.current)) this.completed.push(this.current); this.current = this.steps.length; },
    isActive(i) { return this.current === i; },
    isCompleted(i) { return this.completed.includes(i); },
    completeStep(i) { if (!this.completed.includes(i)) this.completed.push(i); }
}));

/**
 * Skipped Stepper - Steps can be skipped
 */
Alpine.data('skippedStepper', () => ({
    current: 0,
    skipped: [],
    completed: [],
    steps: ['Name & Email', 'Contact', 'Payment'],
    get isFirst() { return this.current === 0; },
    get isLast() { return this.current === this.steps.length - 1; },
    next() { if (!this.completed.includes(this.current)) this.completed.push(this.current); if (!this.isLast) this.current++; },
    prev() { if (!this.isFirst) this.current--; },
    goTo(i) { this.current = i; },
    reset() { this.current = 0; this.skipped = []; this.completed = []; },
    finish() { if (!this.completed.includes(this.current)) this.completed.push(this.current); this.current = this.steps.length; },
    skip() { this.skipped.push(this.current); if (!this.isLast) this.current++; },
    isActive(i) { return this.current === i; },
    isCompleted(i) { return this.completed.includes(i); },
    isSkipped(i) { return this.skipped.includes(i); }
}));

/**
 * Active Stepper - Pre-set to step 2
 */
Alpine.data('activeStepper', () => ({
    current: 1,
    completed: [0],
    steps: ['Name & Email', 'Contact', 'Payment'],
    get isFirst() { return this.current === 0; },
    get isLast() { return this.current === this.steps.length - 1; },
    next() { if (!this.completed.includes(this.current)) this.completed.push(this.current); if (!this.isLast) this.current++; },
    prev() { if (!this.isFirst) this.current--; },
    goTo(i) { this.current = i; },
    reset() { this.current = 0; this.completed = []; },
    finish() { if (!this.completed.includes(this.current)) this.completed.push(this.current); this.current = this.steps.length; },
    isActive(i) { return this.current === i; },
    isCompleted(i) { return this.completed.includes(i); }
}));

/**
 * Vertical Stepper - Vertical layout with connecting lines
 */
Alpine.data('verticalStepper', () => ({
    current: 0,
    completed: [],
    steps: ['Application', 'Review', 'Approval', 'Completion'],
    get isFirst() { return this.current === 0; },
    get isLast() { return this.current === this.steps.length - 1; },
    next() { if (!this.completed.includes(this.current)) this.completed.push(this.current); if (!this.isLast) this.current++; },
    prev() { if (!this.isFirst) this.current--; },
    goTo(i) { this.current = i; },
    reset() { this.current = 0; this.completed = []; },
    finish() { if (!this.completed.includes(this.current)) this.completed.push(this.current); this.current = this.steps.length; },
    isActive(i) { return this.current === i; },
    isCompleted(i) { return this.completed.includes(i); }
}));

/**
 * Icon Stepper - Steps with SVG icons
 */
Alpine.data('iconStepper', () => ({
    current: 0,
    completed: [],
    steps: [
        { label: 'Account', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
        { label: 'Security', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
        { label: 'Payment', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
        { label: 'Done', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    ],
    get isFirst() { return this.current === 0; },
    get isLast() { return this.current === this.steps.length - 1; },
    next() { if (!this.completed.includes(this.current)) this.completed.push(this.current); if (!this.isLast) this.current++; },
    prev() { if (!this.isFirst) this.current--; },
    goTo(i) { this.current = i; },
    reset() { this.current = 0; this.completed = []; },
    finish() { if (!this.completed.includes(this.current)) this.completed.push(this.current); this.current = this.steps.length; },
    isActive(i) { return this.current === i; },
    isCompleted(i) { return this.completed.includes(i); }
}));

/**
 * Pill Stepper - Pill/tab style step navigation
 */
Alpine.data('pillStepper', () => ({
    current: 0,
    completed: [],
    steps: ['Cart', 'Checkout', 'Payment', 'Confirmation'],
    get isFirst() { return this.current === 0; },
    get isLast() { return this.current === this.steps.length - 1; },
    next() { if (!this.completed.includes(this.current)) this.completed.push(this.current); if (!this.isLast) this.current++; },
    prev() { if (!this.isFirst) this.current--; },
    goTo(i) { this.current = i; },
    reset() { this.current = 0; this.completed = []; },
    finish() { if (!this.completed.includes(this.current)) this.completed.push(this.current); this.current = this.steps.length; },
    isActive(i) { return this.current === i; },
    isCompleted(i) { return this.completed.includes(i); }
}));

/**
 * Modal Manager (admin/advanced-ui/modals)
 * Manages multiple modal dialogs (info, confirm, form, fullscreen)
 */
Alpine.data('modalManager', () => ({
    modalOpen: false,
    modalType: null,
    openModal(type) {
        this.modalType = type;
        this.modalOpen = true;
        document.body.style.overflow = 'hidden';
    },
    closeModal() {
        this.modalOpen = false;
        document.body.style.overflow = '';
        setTimeout(() => { this.modalType = null; }, 200);
    }
}));

/**
 * Accordion Demo (admin/advanced-ui/accordions)
 * Single-open, multi-open, and nested accordion examples
 */
Alpine.data('accordionDemo', () => ({
    // Single-open accordion state
    active: null,
    // Multi-open accordion state
    open: [],
    // Nested accordion state
    section: null,
    subsection: null,

    faqs: [
        { q: 'What is an accordion component?', a: 'An accordion is a vertically stacked set of interactive headings that each contain a title, content snippet, or thumbnail representing a section of content. The headings function as controls that enable users to reveal or hide their associated sections of content.' },
        { q: 'When should I use accordions?', a: 'Accordions are ideal for FAQs, step-by-step guides, mobile navigation menus, and any scenario where you need to present a large amount of content in a compact, organized manner without overwhelming the user.' },
        { q: 'Can I open multiple accordions at once?', a: 'Yes! The multi-open variant allows users to expand multiple sections simultaneously. This is useful for comparison scenarios or when users need to reference content from different sections at the same time.' },
        { q: 'Are accordions accessible?', a: 'Properly implemented accordions follow WAI-ARIA design patterns. They should use appropriate ARIA attributes including aria-expanded, aria-controls, and role="region" to ensure screen readers can navigate them effectively.' },
    ],
    items: [
        { title: 'Getting Started', desc: 'Begin by installing the package via npm or yarn. Once installed, import the component into your project and register it with Alpine.js. The setup process takes less than five minutes.' },
        { title: 'Configuration', desc: 'Customize the behavior by setting options like animation duration, auto-collapse behavior, and default open states. All options are fully documented with examples.' },
        { title: 'Theming', desc: 'Apply your own styles using CSS custom properties or Tailwind CSS classes. The component supports both light and dark themes out of the box with zero additional configuration.' },
        { title: 'API Reference', desc: 'The component exposes a comprehensive API including programmatic open/close methods, event callbacks for state changes, and lifecycle hooks for custom integrations.' },
    ],
    nested: [
        {
            name: 'UI Components',
            gradient: 'from-gov-500 to-gov-700',
            children: [
                { name: 'Buttons', content: 'Button components include primary, secondary, ghost, and danger variants. Each supports loading states, icon positioning, and multiple sizes from xs to xl.' },
                { name: 'Forms', content: 'Form components cover inputs, selects, checkboxes, radios, and textareas. All come with validation states, labels, and error message handling.' },
            ]
        },
        {
            name: 'Data Display',
            gradient: 'from-emerald-500 to-emerald-700',
            children: [
                { name: 'Tables', content: 'Data tables feature sorting, filtering, pagination, and row selection. They are fully responsive and support sticky headers for long datasets.' },
                { name: 'Charts', content: 'Chart components visualize data with bar, line, pie, and area charts. They animate on render and support interactive tooltips and legends.' },
            ]
        },
        {
            name: 'Feedback',
            gradient: 'from-amber-500 to-amber-700',
            children: [
                { name: 'Alerts', content: 'Alert banners display success, warning, error, and info messages. They can be dismissed, auto-hide after a configurable duration, and stack vertically.' },
                { name: 'Skeleton', content: 'Skeleton loaders provide placeholder content while data is being fetched. Multiple shapes and sizes are available for different layout patterns.' },
            ]
        },
    ]
}));

/**
 * Basic Carousel (admin/advanced-ui/carousel)
 * Full-width sliding carousel with autoplay
 */
Alpine.data('basicCarousel', () => ({
    current: 0,
    autoplay: true,
    interval: null,
    slides: [
        { title: 'Welcome to Advanced UI', subtitle: 'Explore beautiful, interactive components built with Alpine.js', color1: '#1E40AF', color2: '#3730A3' },
        { title: 'Fully Responsive', subtitle: 'All components adapt seamlessly to any screen size', color1: '#0D9488', color2: '#0F766E' },
        { title: 'Dark Mode Ready', subtitle: 'Built-in dark mode support for every component', color1: '#6D28D9', color2: '#4C1D95' },
        { title: 'Easy to Customize', subtitle: 'Tailwind CSS makes styling simple and flexible', color1: '#DC2626', color2: '#991B1B' },
    ],
    startAutoplay() { this.interval = setInterval(() => { this.next(); }, 4000); },
    stopAutoplay() { clearInterval(this.interval); this.interval = null; },
    next() { this.current = (this.current + 1) % this.slides.length; },
    prev() { this.current = (this.current - 1 + this.slides.length) % this.slides.length; },
    goTo(i) { this.current = i; },
    init() { if (this.autoplay) this.startAutoplay(); },
    destroy() { this.stopAutoplay(); }
}));

/**
 * Card Carousel (admin/advanced-ui/carousel)
 * Horizontal scrollable card carousel
 */
Alpine.data('cardCarousel', () => ({
    cards: [
        { title: 'Analytics Dashboard', desc: 'Real-time metrics and insights for your business performance and growth tracking.', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', bg: '#EEF2FF', color: '#4F46E5' },
        { title: 'User Management', desc: 'Efficiently manage users, roles, and permissions with an intuitive interface.', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z', bg: '#F0FDF4', color: '#16A34A' },
        { title: 'Email Templates', desc: 'Beautiful, responsive email templates for all your transactional and marketing needs.', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', bg: '#FEF3C7', color: '#D97706' },
        { title: 'File Manager', desc: 'Organize, upload, and share files with a powerful cloud-based file management system.', icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z', bg: '#F3E8FF', color: '#9333EA' },
        { title: 'Calendar', desc: 'Stay organized with a feature-rich calendar supporting events, reminders, and sharing.', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', bg: '#FCE7F3', color: '#DB2777' },
        { title: 'Inbox & Chat', desc: 'Real-time messaging system with threads, attachments, and read receipts.', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', bg: '#E0F2FE', color: '#0284C7' },
    ],
    scroll(dir) {
        const container = this.$refs.container;
        const scrollAmount = container.querySelector('.snap-start').offsetWidth + 16;
        container.scrollBy({ left: dir * scrollAmount, behavior: 'smooth' });
    }
}));

/**
 * Testimonial Carousel (admin/advanced-ui/carousel)
 * Auto-rotating testimonial slider
 */
Alpine.data('testimonialCarousel', () => ({
    current: 0,
    testimonials: [
        { initials: 'AK', name: 'Amira Khelifi', role: 'Product Manager', text: 'This admin panel has completely transformed how we manage our backend operations. The UI is incredibly intuitive and the components are well-crafted.' },
        { initials: 'MB', name: 'Mehdi Benali', role: 'Lead Developer', text: 'The quality of the codebase is outstanding. Every component is thoughtfully designed with accessibility and performance in mind.' },
        { initials: 'SL', name: 'Sofia Lahmar', role: 'UX Designer', text: 'I am impressed by the attention to detail in the design system. The dark mode implementation is flawless and the animations are buttery smooth.' },
        { initials: 'RA', name: 'Riadh Amrani', role: 'CTO', text: 'We evaluated several admin panels before choosing this one. The combination of Laravel and Alpine.js provides the perfect balance of power and simplicity.' },
    ],
    next() { this.current = (this.current + 1) % this.testimonials.length; },
    prev() { this.current = (this.current - 1 + this.testimonials.length) % this.testimonials.length; },
    init() { setInterval(() => { this.next(); }, 5000); }
}));

/**
 * Alert Manager (admin/advanced-ui/sweet-alerts)
 * Creates SweetAlert-style dialogs dynamically
 */
Alpine.data('alertManager', () => ({
    showAlert(type) {
        const alerts = {
            success: { title: 'Success!', message: 'Your operation has been completed successfully.', icon: '✅', color: 'emerald' },
            error: { title: 'Error!', message: 'Something went wrong. Please try again later.', icon: '❌', color: 'red' },
            warning: { title: 'Warning!', message: 'Please review before proceeding further.', icon: '⚠️', color: 'amber' },
            info: { title: 'Info!', message: 'Here is some useful information for you.', icon: 'ℹ️', color: 'sky' },
        };
        const alert = alerts[type];

        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
        overlay.style.backdropFilter = 'blur(4px)';

        overlay.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center animate-fade-in" style="animation: alert-pop 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;">
                <div class="text-5xl mb-4">${alert.icon}</div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">${alert.title}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">${alert.message}</p>
                <button onclick="this.closest('.fixed').remove()" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white bg-${alert.color}-600 hover:bg-${alert.color}-700 transition-all cursor-pointer">OK</button>
            </div>
        `;

        document.body.appendChild(overlay);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
    }
}));

/**
 * Toast Manager (admin/advanced-ui/sweet-alerts)
 * Manages toast notification stack
 */
Alpine.data('toastManager', () => ({
    toasts: [],
    toastId: 0,

    init() {
        window.addEventListener('show-toast', (e) => {
            this.showToast(e.detail.type, e.detail.message);
        });
    },

    showToast(type, message) {
        const titles = { success: 'Success', error: 'Error', warning: 'Warning', info: 'Info' };
        const id = this.toastId++;
        this.toasts.push({ id, type, title: titles[type], message, show: true });
        setTimeout(() => { this.dismissToastById(id); }, 4000);
    },

    dismissToast(index) {
        this.toasts[index].show = false;
        setTimeout(() => { this.toasts.splice(index, 1); }, 300);
    },

    dismissToastById(id) {
        const index = this.toasts.findIndex(t => t.id === id);
        if (index !== -1) this.dismissToast(index);
    },

    showRandomToast() {
        const types = ['success', 'error', 'warning', 'info'];
        const messages = [
            'Operation completed successfully!',
            'Something went wrong. Please retry.',
            'This action requires your confirmation.',
            'New notification received.',
            'File uploaded successfully.',
            'Settings saved successfully.',
            'Connection restored.',
            'Update available v2.5.0',
        ];
        const type = types[Math.floor(Math.random() * types.length)];
        const msg = messages[Math.floor(Math.random() * messages.length)];
        this.showToast(type, msg);
    }
}));

/**
 * Confirm Manager (admin/advanced-ui/sweet-alerts)
 * Handles confirmation dialog state and cross-component toast communication
 */
Alpine.data('confirmManager', () => ({
    confirmDelete: false,
    confirmSubmit: false,
    confirmPrompt: false,
    showToast(type, message) {
        const toastEvent = new CustomEvent('show-toast', { detail: { type, message } });
        window.dispatchEvent(toastEvent);
    }
}));

Alpine.start();
