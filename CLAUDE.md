# CLAUDE.md — Panel Space

Multi-tenant Laravel 11 SaaS panel for internet-cafe / coworking-space **Owners**. Sold as
three toggleable feature domains — **hotspot** (MikroTik internet-user management), **workspace**
(rooms), **booking** (reservations + shared sessions) — plus a separate **Admin** back-office.
Bilingual: English + Arabic (RTL).

## Stack & tooling

- **PHP 8.3**, **Laravel 11**, Sanctum, Tinker.
- **DB: SQLite** (`database/database.sqlite`, `DB_CONNECTION=sqlite`).
- **Frontend: Blade + Tailwind CSS 4 + Vite 8**, no JS framework (vanilla `<script>` only).
  CSS-first Tailwind config (no `tailwind.config.js`); entries `resources/css/app.css`, `resources/js/app.js`.
- Currency shown as EGP (`ج.م`).

### Commands (PowerShell on win32)
- Run app: `composer run dev` (serve + queue + pail + vite via concurrently) — or `php artisan serve` + `npm run dev`.
- Migrate: `php artisan migrate` / status: `php artisan migrate:status`.
- Seed: `php artisan db:seed` (Admin, Feature, Plan seeders + demo owner/data).
- Tests: `php artisan test` (only Laravel default stubs exist today — no real coverage yet).
- Lint: Laravel Pint (`./vendor/bin/pint`).

## Authentication — THREE session guards

Defined in `config/auth.php`; default guard is `owner`.
- **`owner`** → `App\Models\Owner` — the tenant/business customer. Login `Auth/LoginController` → `/dashboard`.
- **`admin`** → `App\Models\Admin` — platform operator. Login `Admin/AuthController` → `/admin/dashboard`.
- **`web`** → `App\Models\User` — the default Laravel stub, effectively **unused**.

Guest redirect (`bootstrap/app.php`): `admin/*` → `admin.login`, everything else → `login`.
Always resolve the current tenant with `auth('owner')->user()` / `auth('owner')->id()`.

## Multi-tenancy — READ THIS BEFORE WRITING ANY QUERY

- **Owner is the tenant root.** Nearly every domain model carries `owner_id` + `belongsTo(Owner)`.
- There is **NO global scope / tenancy package**. Isolation is enforced *manually per query*:
  every controller filters by `->where('owner_id', auth('owner')->id())` or goes through an
  `$owner->relation()`. **When adding any owner-scoped query, you MUST scope it the same way** —
  forgetting `owner_id` leaks another tenant's data. Prefer `firstOrFail()` on an owner-scoped query
  for route-model lookups rather than trusting the route id.

## Middleware (aliases in `bootstrap/app.php`)

- `subscription.active` → `CheckSubscription`: owner must `isSubscriptionActive()`, else → `subscription.expired`.
- `feature:<key>` → `CheckFeature`: owner must `hasFeature($key)` (`hotspot|workspace|booking`), else → `dashboard` w/ error.
- `SetLocale` (global web): reads `session('locale')`; switched via `LanguageController` / `POST /language/{locale}`.

Owner routes are wrapped in `['auth:owner','subscription.active']` and then sub-grouped by
`feature:hotspot` / `feature:workspace` / `feature:booking` in `routes/web.php`. Admin routes are under
`auth:admin` + `admin` prefix.

## Domain models (`app/Models/`)

- **Owner** — tenant root. `plan_id`, subscription dates, MikroTik creds, `is_active`. Feature helpers
  (`hasFeature/enableFeature/disableFeature`), subscription helpers (`isSubscriptionActive`,
  `subscriptionStatus`: never/disabled/expired/expiring_soon/active), plan-limit helpers
  (`canAddMoreUsers`, `remainingUserSlots`, `usagePercentage` — capped by `plan->max_members`).
- **HotspotUser** — internet end-user on a MikroTik router. `phone` is the router username; `speed_profile_id`.
- **Workspace** → **Room** (types: meeting/training/shared/office; `capacity`, `price_per_hour`).
  Room has booking-conflict logic (`hasConflict`) and shared-capacity helpers (`availableSharedSlots`, `isSharedFull`).
- **Booking** — time-slot reservation of a Room by a HotspotUser. Status pending/confirmed/completed/cancelled.
- **SharedSession** — pay-per-minute open/close seat in a `shared` room; on close auto-creates a completed `Booking`.
- **Plan** — `max_members`, `price_per_month`. **Subscription** — a paid renewal (owner+admin+plan+months+amount).
- **SpeedProfile** — reusable bandwidth preset per owner. **Feature** — flag catalog (`owner_features` pivot). **Admin**.
- **Notification** — in-app owner alerts (`type`, `level`, `title`, `body`, `action_url`, `reference`, `read_at`).
  `Owner->notifications()` overrides the unused Notifiable trait relation. Cross-cutting (not feature-gated).

## Services

- `app/Services/MikroTikService.php` — RouterOS binary-protocol client over raw sockets (login w/ MD5
  challenge, create/delete hotspot user, set speed, create/update/delete profiles, list active users).
  Constructed with an owner's host/port/user/pass.
- `app/Services/BookingService.php` — duration + price calculation (hours × price_per_hour).
- `app/Services/NotificationService.php` — idempotent alert generation (`refreshForOwner`): subscription
  expiry/expired, plan-limit-reached, today's & pending bookings. Each alert is keyed by a unique
  `reference` so re-runs never duplicate. Triggered on-demand (throttled 10 min, `layouts.app` view
  composer in `AppServiceProvider`) and hourly via `notifications:refresh` (`routes/console.php`).

## Conventions & gotchas

- Controllers are the primary logic layer; models hold domain helpers; two thin services for MikroTik + booking math.
- Blade views mirror routes: `resources/views/<domain>/*` for owner, `resources/views/admin/<domain>/*` for admin.
- Owner layout `layouts/app.blade.php`, admin layout `layouts/admin.blade.php`. Nav is feature-gated via `$currentOwner->hasFeature(...)`.
- **Known issue:** `layouts/app.blade.php` currently loads Tailwind from the CDN (`cdn.tailwindcss.com`)
  instead of the Vite-built asset, so the configured Vite/Tailwind-4 pipeline may not be wired into that layout.
- User-facing strings should go through the `lang/en` + `lang/ar` files (`__('app.key')`) for bilingual support.

## Current state (as of 2026-07)

Large **uncommitted** work-in-progress on `main`: `Customer` model/views were merged into `HotspotUser`;
Plans, Admin Financial dashboard, SharedSessions, Profile, and i18n (en/ar) were added. All migrations are applied.
