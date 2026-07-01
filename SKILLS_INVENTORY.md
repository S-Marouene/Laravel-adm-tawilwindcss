# Skills Inventory Document — Admin Maro (Laravel Project)

> **Generated:** July 1, 2026  
> **Purpose:** Comprehensive reference of all technologies, packages, patterns, and integrations used in this project. Use this document when adding new features to know what tools and conventions are already available.

---

## 1. Core Framework & Language

### Laravel Framework v13.17.0
- **Status:** Production-ready (the latest major version)
- **Config:** `config/app.php`
- **Key features used:** Eloquent ORM, Blade templating, Artisan CLI, Queues, Broadcasting/Events, Mail, Notifications, Validation, Authorization (Gates/Policies), Sessions, File Storage, Localization

### PHP v8.4.16
- **Status:** Latest stable
- **Key features used:** Attributes (PHP 8+ native), typed properties, enums, constructor property promotion, named arguments, match expressions

### Composer
- **Autoloading:** PSR-4 — `App\` → `app/`, `Database\Factories\` → `database/factories/`, `Database\Seeders\` → `database/seeders/`
- **Test autoloading:** `Tests\` → `tests/`

### Application Skeleton
- **Name:** `laravel/laravel` (default skeleton, customized)
- **Environment config:** `.env` file (not committed)
- **Bootstrap:** `bootstrap/app.php` (Laravel 11+ streamlined bootstrap)

---

## 2. Database System

### Connection
- **Default driver:** `sqlite` (`.env: DB_CONNECTION`)
- **Production driver:** `mysql` (configured in `config/database.php`, host `127.0.0.1:3306`, database `test`)
- **Available drivers configured:** sqlite, mysql, mariadb, pgsql, sqlsrv
- **Redis:** Configured for both default and cache connections

### ORM & Query Building
- **ORM:** Eloquent (full usage)
- **Model base class:** `Illuminate\Database\Eloquent\Model` via `Authenticatable`
- **User model:** `App\Models\User` — uses `HasFactory`, `Notifiable`, `HasRoles` (Spatie)

### Migrations
- **Format:** Anonymous class-based migrations (`return new class extends Migration`)
- **Key migrations:**
  | File | Purpose |
  |------|---------|
  | `0001_01_01_000000_create_users_table.php` | `users`, `password_reset_tokens`, `sessions` tables |
  | `0001_01_01_000001_create_cache_table.php` | Cache table |
  | `0001_01_01_000002_create_jobs_table.php` | Jobs/batches table |
  | `2026_06_30_131949_create_permission_tables.php` | Spatie permission tables (roles, permissions, model_has_roles, etc.) |
  | `2026_06_30_132000_add_is_admin_to_users_table.php` | `is_admin` boolean column on `users` |
  | `2026_06_30_133000_create_activity_logs_table.php` | `activity_logs` table |

### Seeders
| Seeder | Purpose |
|--------|---------|
| `DatabaseSeeder.php` | Creates test user + calls `RolePermissionSeeder` |
| `RolePermissionSeeder.php` | Creates 12 permissions, 1 admin role, 1 admin user (`admin@admin.com` / `password`) |

---

## 3. Authentication & Authorization

### Authentication
- **Package:** Laravel Breeze v2.4 (scaffolding)
- **Guard:** Session-based (`web` guard, `users` provider)
- **Model:** `App\Models\User` (extends `Authenticatable`)
- **Email verification:** Enabled (MustVerifyEmail contract available but not currently applied)
- **Password reset:** Token-based, 60-min expiry
- **Controllers:** Standard auth controllers in `App\Http\Controllers\Auth\`

### Authorization
- **Package:** `spatie/laravel-permission` v8.1
- **User model trait:** `Spatie\Permission\Traits\HasRoles`
- **Tables:** `permissions`, `roles`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
- **Permissions defined:** `view/create/edit/delete users`, `view/create/edit/delete roles`, `view/create/edit/delete permissions` (12 total)
- **Roles defined:** `admin` (all permissions)
- **Admin flag:** `users.is_admin` boolean column (legacy check, used alongside `hasRole('admin')`)
- **Admin middleware:** `App\Http\Middleware\AdminMiddleware` — checks `is_admin || hasRole('admin')`, returns 403 if neither

### Session
- **Driver:** `database` (sessions stored in DB)
- **Lifetime:** 120 minutes
- **Encryption:** Disabled
- **Serialization:** JSON

---

## 4. Frontend Integration

### Templating
- **Engine:** Laravel Blade (all views)
- **Component system:** X-Components (`<x-input-label>`, `<x-primary-button>`, `<x-guest-layout>`, `<x-app-layout>`, etc.)
- **Component classes:** `App\View\Components\AppLayout`, `App\View\Components\GuestLayout`
- **Layouts:** `layouts/app.blade.php` (authenticated), `layouts/guest.blade.php` (auth pages), `admin/layouts/admin.blade.php` (admin panel)
- **View inheritance:** `@extends`, `@section('content')`, `@yield('title')`

### JavaScript Bundling
- **Tool:** Vite v8.0
- **Plugin:** `laravel-vite-plugin` v3.1
- **Entry points:** `resources/js/app.js`, `resources/css/app.css`
- **HMR:** Enabled via `@vite()` directive in layouts

### Frontend Libraries
| Library | Version | Purpose |
|---------|---------|---------|
| **Alpine.js** | ^3.4.2 | Interactive UI components (locale switcher, mobile menu, stats counter animations) |
| **Tailwind CSS** | ^4.3.2 | Utility-first CSS framework (v4 with new `@import "tailwindcss"` syntax) |
| **@tailwindcss/forms** | ^0.5.11 | Form element reset/plugin |
| **@tailwindcss/vite** | ^4.0.0 | Tailwind v4 Vite plugin |
| **concurrently** | ^9.0.1 | Run Artisan + Vite + Queue concurrently in dev |

### Tailwind Configuration
- **v4 format:** `@import "tailwindcss"` in `app.css` (no `tailwind.config.js` needed for v4)
- **Custom theme colors** defined via `@theme` directive:
  - `gov-50` through `gov-900` (government blue palette)
  - `sand-50` through `sand-200`
  - `success`, `warning`, `danger`
- **Font:** Figtree (Google Fonts via bunny.net for privacy), Inter in CSS, Noto Sans Arabic / Tajawal for RTL
- **CSS features:** Custom `@theme` color palette, `service-card` class, skeleton loading animation, RTL support via `[dir="rtl"]` CSS overrides, skip-link for accessibility, touch targets (44px minimum), print styles

### Alpine.js Components (defined in `resources/js/app.js`)
| Component | Purpose |
|-----------|---------|
| `localeSwitcher` | Client-side locale switching with optimistic UI, `localStorage` persistence, server sync, transition overlay |
| `mobileMenu` | Toggle mobile navigation menu |
| `statsCounter` | Intersection Observer-based counter animation |
| **Plus:** Global smooth scroll for anchor links |

### Language Switcher (Locale System)
- **Frontend:** Alpine.js component with optimistic UI + cookie + localStorage
- **Backend:** `GET /locale/{locale}` route — sets session + cookie, returns JSON for AJAX
- **Middleware:** `App\Http\Middleware\LocalizationMiddleware` — reads locale from query → session → cookie → defaults to `fr`
- **Supported locales:** `fr` (default), `ar` (Arabic)
- **RTL support:** Comprehensive `[dir="rtl"]` CSS overrides in `app.css`

---

## 5. API & Routing

### Route Structure
| File | Middleware | Purpose |
|------|-----------|---------|
| `routes/web.php` | `web` | Public routes (welcome, dashboard, profile, locale switch) |
| `routes/auth.php` | `guest` / `auth` | Auth routes (login, register, password reset, verification) |
| `routes/admin.php` | `web` + `auth` + `verified` + `admin` | Admin panel (users, roles, permissions, activity logs) |
| `routes/console.php` | — | Artisan commands |

### Route Registration (`bootstrap/app.php`)
- `web.php` and `admin.php` both use the `web` middleware group
- `admin.php` adds `auth`, `verified`, and custom `admin` middleware

### Middleware Stack
| Middleware | Type | Purpose |
|-----------|------|---------|
| `LocalizationMiddleware` | Prepend to `web` | Sets app locale from session/cookie/query |
| `AdminMiddleware` | Named `admin` | Checks `is_admin` or `admin` role |

### API
- JSON rendering for API routes only (`$request->is('api/*')`)
- No RESTful API endpoints currently defined
- `routes/api.php` not present

---

## 6. Package Dependencies

### Production Packages (Composer)
| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/framework` | ^13.8 | Core Laravel framework |
| `laravel/tinker` | ^3.0 | Interactive REPL for debugging |
| `spatie/laravel-permission` | ^8.1 | Role & permission management |

### Development Packages (Composer)
| Package | Version | Purpose |
|---------|---------|---------|
| `fakerphp/faker` | ^1.23 | Fake data generation for testing/seeding |
| `laravel/breeze` | ^2.4 | Auth scaffolding (Blade + Alpine stack) |
| `laravel/pail` | ^1.2.5 | Log file tailing in console |
| `laravel/pao` | ^1.0.6 | Performance analysis tool |
| `laravel/pint` | ^1.27 | Code style fixer (PSR-12) |
| `mockery/mockery` | ^1.6 | Mocking framework for tests |
| `nunomaduro/collision` | ^8.6 | Beautiful error reporting in console |
| `pestphp/pest` | ^4.7 | PHP testing framework |
| `pestphp/pest-plugin-laravel` | ^4.1 | Pest integration for Laravel |

### Frontend Packages (npm)
| Package | Version | Purpose |
|---------|---------|---------|
| `alpinejs` | ^3.4.2 | Interactive UI components |
| `tailwindcss` | ^4.3.2 | Utility CSS framework |
| `@tailwindcss/forms` | ^0.5.11 | Tailwind form reset |
| `@tailwindcss/vite` | ^4.0.0 | Tailwind v4 Vite integration |
| `vite` | ^8.0.0 | Build tool |
| `laravel-vite-plugin` | ^3.1 | Laravel + Vite bridge |
| `concurrently` | ^9.0.1 | Run multiple dev commands |

---

## 7. Validation & Forms

### Validation Approach
- **Form Request classes:** Used for auth (`LoginRequest`) and profile (`ProfileUpdateRequest`)
- **Inline validation:** Used in auth controllers
- **Rules used:** `required`, `string`, `email`, `confirmed`, `min`, `max`, `unique`, `current_password`, `exists`

### Form Handling
- **CSRF:** Enabled via `@csrf` directive and `VerifyCsrfToken` middleware
- **Old input:** `old()` helper for re-populating forms after validation errors
- **Error display:** `$errors` MessageBag with `@error` directive and `<x-input-error>` component

---

## 8. File Handling & Storage

### Configuration (`config/filesystems.php`)
- **Default disk:** `local` (storage `storage/app/private`)
- **Public disk:** `storage/app/public` → symlinked to `public/storage`
- **S3:** Configured but not actively used

### Storage Links
- `public/storage` → `storage/app/public` (standard Laravel link)

---

## 9. Caching & Performance

### Cache Configuration (`config/cache.php`)
- **Default store:** `database`
- **Available stores:** array, database, file, storage, memcached, redis, dynamodb, octane, failover
- **Cache prefix:** App-name based

### Permission Caching
- **Spatie permission cache:** 24-hour expiry, auto-flushed on permission/role changes
- **Store:** Default cache store

### Queue Configuration (`config/queue.php`)
- **Default connection:** `database`
- **Available:** sync, database, beanstalkd, sqs, redis, deferred, background, failover
- **Job batching:** Configured

### View Caching
- **Compiled views:** `storage/framework/views/*.php`
- **Clear command:** `php artisan view:clear`

---

## 10. Activity Logging

### Custom Service: `App\Services\ActivityLogger`
- **Model:** `App\Models\ActivityLog`
- **Events logged:**
  - User login (`Login` event listener in `AppServiceProvider`)
  - User logout (`Logout` event listener in `AppServiceProvider`)
- **Static methods:** `log()`, `login()`, `logout()`, `created()`, `updated()`, `deleted()`
- **Data captured:** user_id, type, description, subject (morph), properties (JSON), IP, user agent
- **Table:** `activity_logs` with indexes on `[user_id, created_at]`, `type`, `created_at`

---

## 11. Localization & Translation System

### Structure
- **Files:** PHP array files in `lang/{locale}/` organized by namespace (14 files per locale)
- **JSON files:** `lang/{locale}.json` for auth/profile UI strings (added as fix)
- **Supported locales:** `fr` (French, default), `ar` (Arabic)

### Translation Files (per locale)
| File | Keys |
|------|------|
| `app.php` | `name`, `tagline`, `description` |
| `nav.php` | `home`, `services`, `dashboard`, `admin`, `profile`, `login`, `register`, `logout` |
| `dashboard.php` | `title`, `welcome` (`:name`), `subtitle` |
| `services.php` | `title`, `subtitle`, 13 services with name and desc |
| `stats.php` | `services`, `users`, `completed`, `satisfaction` |
| `action.php` | `start`, `continue`, `view`, `download`, `submit`, `cancel`, `save`, `search`, `filter`, `back`, `more` |
| `status.php` | `pending`, `processing`, `completed`, `rejected` |
| `login.php` | `title`, `email`, `password`, `remember`, `forgot`, `submit`, `no_account` |
| `register.php` | `title`, `name`, `submit`, `has_account` |
| `password.php` | `forgot`, `reset`, `send`, `confirm` |
| `lang.php` | `french`, `arabic` |
| `footer.php` | `rights`, `about`, `contact`, `help`, `privacy`, `terms` |
| `admin.php` | `users`, `users.desc`, `roles`, `roles.desc`, `permissions`, `permissions.desc`, `logs`, `logs.desc` |
| `auth.php` | `failed`, `password`, `throttle` |

### Laravel 13 Behavior Note
- Non-dot-notation keys (e.g., `__('Register')`) are resolved via **JSON files** first
- JSON files (`fr.json`, `ar.json`) must contain all auth/profile UI strings
- Without JSON files, the translator can return an array instead of a string for non-dot keys

---

## 12. Email / Notifications

### Mail Configuration (`config/mail.php`)
- **Default mailer:** `log` (development)
- **Available:** smtp, ses, postmark, resend, sendmail, log, array, failover, roundrobin
- **From address:** Configurable via `MAIL_FROM_ADDRESS`
- **Verification emails:** Standard Laravel email verification flow configured

---

## 13. Testing

### Framework
- **Test framework:** Pest PHP v4.7 + Pest Laravel plugin v4.1
- **PHPUnit:** Underlying XML config at `phpunit.xml`
- **Test suites:** `Unit` + `Feature`

### Test Configuration (phpunit.xml)
- **Environment:** `testing`
- **Database:** SQLite in-memory (`:memory:`)
- **Cache:** Array driver
- **Mail:** Array driver
- **Queue:** Sync driver
- **Session:** Array driver
- **BCRYPT rounds:** 4 (faster for tests)

### Test Structure
- **Auth tests:** Feature/Auth/ (Authentication, EmailVerification, PasswordConfirmation, PasswordReset, PasswordUpdate, Registration)
- **Profile tests:** Feature/ProfileTest.php
- **Example tests:** Feature/ExampleTest.php, Unit/ExampleTest.php

---

## 14. Admin Panel Structure

### Routes (`/admin/*`)
- `GET /` → Admin dashboard (stats, recent activity, recent users)
- `CRUD /users` → User management (index, create, edit, delete)
- `CRUD /roles` → Role management
- `CRUD /permissions` → Permission management
- `GET /activity-logs` → Activity log viewer
- `GET /activity-logs/{id}` → Activity log detail
- `DELETE /activity-logs/{id}` → Delete single log
- `DELETE /activity-logs/clear` → Clear all logs

### Admin Controllers (`App\Http\Controllers\Admin`)
- `AdminDashboardController` → Dashboard stats
- `UserController` → User CRUD
- `RoleController` → Role CRUD
- `PermissionController` → Permission CRUD
- `ActivityLogController` → Activity log viewing/management

### Admin Layout
- **File:** `admin/layouts/admin.blade.php`
- **Features:** Sidebar navigation, user dropdown, dark mode support, responsive sidebar (hidden on mobile)

---

## 15. Event System

### Registered Listeners
| Event | Listener |
|-------|----------|
| `Illuminate\Auth\Events\Login` | `App\Services\ActivityLogger::login()` |
| `Illuminate\Auth\Events\Logout` | `App\Services\ActivityLogger::logout()` |

### Registration
- In `App\Providers\AppServiceProvider::boot()`

---

## 16. Development Tools

### Dev Scripts (`composer.json`)
| Script | Command |
|--------|---------|
| `setup` | Install + env + key + migrate + npm build |
| `dev` | Run Artisan serve + queue listen + Vite concurrently |
| `test` | Config clear + run tests |

### Dev Server
- `php artisan serve` (standard dev server)
- Dev script runs on port 8000 (default)

### Code Quality
- **Laravel Pint** v1.27 for PSR-12 formatting
- **Laravel Pail** v1.2.5 for log watching
- **Laravel Pao** v1.0.6 for performance analysis

---

## 17. Custom Patterns & Architecture

### Service Classes
- **`App\Services\ActivityLogger`** — Static facade-like logging service
  - Pattern: Static methods calling fluent model creation
  - Features: Polymorphic subject attachment, IP logging, user agent capture

### Middleware
- **`LocalizationMiddleware`** — Prepended to `web` group
  - Locale resolution: query → session → cookie → default `fr`
  - Persists to session + cookies
- **`AdminMiddleware`** — Named `admin`
  - Checks `is_admin` flag OR `admin` Spatie role
  - Returns 403 on failure

### Bootstrap Pattern (Laravel 11+)
- **File:** `bootstrap/app.php`
- Uses the new `Application::configure()` fluent API
- Routes defined via closure (not separate providers)
- Middleware aliased via `->withMiddleware()` closure
- Exceptions configured via `->withExceptions()` closure

### View Components
| Component | Class/Nature | Purpose |
|-----------|-------------|---------|
| `<x-app-layout>` | `App\View\Components\AppLayout` | Authenticated layout (navigation + header) |
| `<x-guest-layout>` | `App\View\Components\GuestLayout` | Guest layout (simple centered card) |
| `<x-input-label>` | Anonymous (inline Blade) | Form label with error-safe value display |
| `<x-text-input>` | Anonymous | Styled text input |
| `<x-input-error>` | Anonymous | Validation error display |
| `<x-primary-button>` | Anonymous | Primary action button |
| `<x-danger-button>` | Anonymous | Destructive action button |
| `<x-secondary-button>` | Anonymous | Secondary action button |
| `<x-dropdown>` | Anonymous | Dropdown menu with Alpine.js |
| `<x-dropdown-link>` | Anonymous | Dropdown link item |
| `<x-nav-link>` | Anonymous | Navigation link with active state |
| `<x-responsive-nav-link>` | Anonymous | Mobile navigation link |
| `<x-modal>` | Anonymous | Modal dialog with Alpine.js |
| `<x-auth-session-status>` | Anonymous | Flash message display |
| `<x-application-logo>` | Anonymous | App logo SVG |
| `<x-language-switcher>` | Anonymous | Language selector dropdown |

---

## 18. Third-Party Integrations

| Service | Status | Details |
|---------|--------|---------|
| SES (AWS) | Configured | Email driver available |
| Postmark | Configured | Email driver available |
| Resend | Configured | Email driver available |
| Slack | Configured | Notification channel available |
| S3 (AWS) | Configured | File storage driver available |

---

## 19. Deployment & Environment

### Environment Variables (`.env`)
- Not committed to version control
- `.env.example` available as template
- Key variables: `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `DB_CONNECTION`, `DB_*`, `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`, `MAIL_*`

### Hosting Considerations
- **Database:** MySQL configured (default test DB name: `test`)
- **Session:** Database-driven (requires `sessions` table)
- **Cache:** Database-driven (requires `cache` table)
- **Queue:** Database-driven (requires `jobs` table)
- **Storage:** Local filesystem with optional S3
- **Build step:** `npm run build` for Vite production build

---
