# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**APCN SPONSOR** — the "J4U Sponsorship Deal Management System", an internal single-source-of-truth web app for managing sponsor deals at APCN 2027. **Login is admin-only** (`j4u`, full CRUD). **Doctors are non-login `role=doctor` User records** (an initiator name + contact + `public_token`, managed under `/doctors`); they never authenticate — they view their own deals read-only through a **public per-doctor link** `/d/{public_token}`, gated by a single global access code (`settings` table, `public_access_code`, editable on the Doctors page).

Authoritative product docs live in `.context/` — read these before feature work:
- `.context/srs.md` — SRS v2.0: functional requirements (FR-xx), schema, business rules (BR-xx), acceptance criteria
- `.context/development-map.md` — workstream/task breakdown and status
- `AGENTS.md` — working principles and the mandated architecture pattern

When source and docs conflict, **source code wins** — then report the inconsistency.

## Stack

PHP 8.3 · Laravel 13 · Livewire 4 + **Mary UI (`robsontenorio/mary`) on daisyUI v5** + Tailwind v4 · Heroicons (via blade-heroicons) · Fortify (auth, passkeys, 2FA) · MySQL 8 in real use, SQLite in-memory for the default test suite. No Docker — the app is served locally via Laragon or `php artisan serve`.

> The UI was migrated off Flux 2 to daisyUI + Mary UI to implement the **APCN 2027 Design System**. `resources/css/app.css` is the single source of truth for the visual language (daisyUI `light`/`dark` themes + `@theme` brand tokens). Rule zero: **no hardcoded hex in Blade/JS** — everything flows through a token. Components are Mary's `<x-...>` (`x-button`, `x-input`, `x-select`, `x-card`, `x-modal`, `x-icon` with `o-`/`s-` Heroicon prefixes) plus plain daisyUI markup (`<table class="table">`, `badge badge-soft badge-*`). Chrome (sidebar/topbar, classes `.apcn-sidebar`/`.apcn-topbar`) is deep navy in **both** themes; light-cream is the default theme with a navy dark mode via Mary's `<x-theme-toggle>` (persists `mary-theme`/`mary-class` in localStorage; no-flash script in `partials/head.blade.php`). Toasts use Mary's `Toast` trait (`$this->success(...)`), rendered by `<x-toast />` in `layouts/app.blade.php`.

## Commands

```bash
composer setup                 # first-time: install, .env, key, migrate, npm build
composer dev                   # run server + queue + vite concurrently (main dev command)

composer test                  # full gate: config:clear + pint --test + phpstan + artisan test
composer lint                  # pint --parallel (format/fix)
composer lint:check            # pint --parallel --test (no changes)
composer types:check           # phpstan analyse (Larastan, level 7)

php artisan test                                   # feature/unit suite on in-memory SQLite (fast)
php artisan test --filter=DealWorkflowTest         # single test class
php artisan test --filter='it finalizes a deal'    # single test by name
php artisan test --configuration=phpunit.mysql.xml # verify against MySQL (needs apcn_sponsor_test DB)

php artisan migrate:fresh --seed   # catalog + admin login (admin@gmail.com / password) + demo doctors + access code (APCN2027)
```

CI on push to `main` (`.github/workflows/deploy.yml`) builds assets and rsyncs to the VPS, then runs `migrate --force` + `config/route/view/event:cache`. Any change to routes, config, or events must survive being cached in production.

## Architecture

Domain logic follows a strict layered pattern (see `AGENTS.md`). Do not put business logic in Blade or Livewire components.

- **Enums** (`app/Enums/`) — backed enums for roles/statuses: `UserRole`, `DealStatus`, `PaymentStatus`, `MaterialStatus`.
- **Models** (`app/Models/`) — Eloquent + relations. `deal_items` is a pivot with its own model `DealItem` (casts `is_addon` bool, `custom_price` decimal). Observers attached via `#[ObservedBy]`.
- **DTOs** (`app/DTOs/<Area>/`) — plain carriers built with `fromRequest()`/static factories; no Model dependencies. Properties are **camelCase** (e.g. `doctorId`, `finalPrice`).
- **Actions** (`app/Actions/<Area>/`) — single-purpose `VerbNounAction` classes with one `execute()`, each wrapped in `DB::transaction`. This is where multi-table writes belong.
- **Services** (`app/Services/`) — orchestrate multiple Actions / read aggregates (e.g. `DashboardService`).
- **Livewire** (`app/Livewire/`) — reactive full-page components; call Actions, hold UI state only.

### Key domain flows

- **RBAC** — the whole authenticated app is admin-only. `EnsureUserRole` middleware (`role:j4u`) guards write routes; `Fortify::authenticateUsing` (in `FortifyServiceProvider`) rejects any login where `role !== j4u`, so doctors cannot sign in even with credentials. Livewire mounts also `abort_unless(...->isJ4u())`. In `routes/web.php`, `/deals/create` is registered **before** `/deals/{deal}` so the literal isn't captured by the wildcard. The only non-auth route is the public portal `/d/{token}` (`App\Livewire\Public\DoctorPortal`, `layouts.public`).
- **Deal number** — `J4U-<year>-NNNN`, generated in `CreateDealAction` from `Deal::max('id')`.
- **Finalization → materials** — `DealObserver` fires `DealFinalized` when status goes `draft → finalized`; listener `GenerateMaterialDeadlines` (auto-discovered from `app/Listeners`, see `bootstrap/app.php` `withEvents`) creates one `material_deadlines` row per deal item with `requires_material = true`. Must stay **idempotent** — re-finalizing creates no duplicates.
- **Activity ledger** — `DealObserver`, `PaymentTermObserver`, `MaterialDeadlineObserver` write to `activity_logs` via `App\Support\ActivityLogger`. Creates log full attributes; updates log a `field => {old, new}` map from `getDirty()` (enums scalarized). Never delete activity logs.

## Conventions

- **Livewire 4 validation keys are camelCase** — rule keys must match the camelCase property (e.g. `doctorId`, not `doctor_id`). Livewire 4 does not snake_case rule keys. Mary inputs auto-bind errors from their `wire:model` name; set `error-field="..."` when the displayed error key differs (e.g. a confirm-password field showing the `password` error).
- **Money** — stored `DECIMAL(15,2)`; displayed as `Rp 1.000.000` (dot thousands separator). Dates stored `DATE`, shown `d M Y`.
- **`deals.package_id` and `material_deadlines.due_date` are nullable** — a deal may be custom-only; due dates are set by J4U after the checklist is generated.
- **Finalized deals are immutable via the UI** (BR-05) — editing is only allowed while `draft`.
- **Final price is authoritative and manually entered** (BR-02) — it is not computed from items; payment-term amounts need not sum to it (BR-03).
- New behavior must be covered by feature tests, and Pint + PHPStan (level 7) must stay clean before a task is done. Never invent business rules, APIs, or schema — verify against `.context/srs.md`.
