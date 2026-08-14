# SOFTWARE REQUIREMENTS SPECIFICATION (SRS)

## J4U Sponsorship Deal Management System (APCN 2027)

- **Document Version:** 2.0
- **Status:** In Development (data layer + deal management implemented)
- **Source documents:** PRD v1.0 (approved for development), AGENTS.md
- **Last updated:** 2026-08-12

---

## 1. Introduction

### 1.1 Purpose

This document specifies the software requirements for the **J4U Sponsorship Deal Management System** — an internal web application that serves as the **Single Source of Truth (SSOT)** for all sponsor deals at the APCN 2027 event. It digitizes the previously manual process into a centralized flow: sponsor package cataloguing, deal customization by the organizing committee (**Tim J4U**) based on doctor lobbying, payment term tracking, and monitoring of sponsor asset material deadlines (logos, videos, booth designs).

### 1.2 Scope

In scope:

- Master data management (items, packages, add-ons)
- Deal creation & customization (initiation, package selection, item modification, custom pricing, payment terms)
- Tracking & maintenance (material checklist, payment status, activity ledger)
- Dashboard / final summary (role-scoped)
- Strict Role-Based Access Control (RBAC)

Out of scope (future): public sponsor portal, invoicing/POS integration, document/asset upload storage, notifications.

### 1.3 Definitions & Abbreviations

| Term | Definition |
|---|---|
| **Deal** | A negotiated sponsorship agreement between a sponsor company and the event (via a doctor). |
| **Tim J4U (J4U)** | Organizing committee user role with full write access. |
| **Dokter (Doctor)** | Physician user role; read-only access to deals they initiated. |
| **Initiator** | The doctor who lobbied/brought in the sponsor. |
| **Item** | A single sponsorship deliverable (e.g. "Booth 3x3m", "Industry Symposium"). May require material(s). |
| **Package (Tier)** | A base bundle of items (Diamond, Platinum, White Gold, …) with a default price. |
| **Add-on** | A standalone item purchasable separately and attachable to a deal. |
| **PIC** | Person in Charge (sponsor contact). |
| **Material** | Sponsor asset due before/at the event (logo, video, booth design). |
| **Activity Log** | Immutable audit trail of every change on a deal. |
| **IDR** | Indonesian Rupiah, the only currency supported. |

---

## 2. Overall Description

### 2.1 Product Perspective

Internal web application. Authentication is handled by **Laravel Fortify** (email/password, passkeys, 2FA, email verification). The application is a full-page Livewire SPA-style app using the Flux UI kit and Tailwind CSS.

### 2.2 User Roles

| Role | Capabilities |
|---|---|
| **Tim J4U** (`users.role = 'j4u'`) | Full CRUD on master data, deals, payment terms, material status. One-way finalization of deals. Read access to all deals. |
| **Dokter** (`users.role = 'doctor'`) | Read-only. Sees only the deals where `deals.doctor_id = user.id`. No write actions anywhere. |

### 2.3 Operating Environment

| Aspect | Dev (current) | Production target |
|---|---|---|
| Backend | Laravel 13.x, PHP 8.3 | Laravel 13.x, PHP 8.3 |
| Frontend | Livewire 4 + Flux 2 + Tailwind | Same |
| Database | **MySQL 8.0.30** (via Laragon) | MySQL 8.x |
| Web server | Laragon (Apache/Nginx) or `php artisan serve` | Laragon/Nginx |
| Containerization | None — user runs MySQL + PHP directly (no Docker) | None |
| Tests | In-memory SQLite (default, fast) **and** MySQL via `phpunit.mysql.xml` (target-DB verification) | Both |

> **Deviations from PRD v1.0:** (1) the PRD recommended Laravel 11 + Livewire 3 — the actual stack is **Laravel 13 + Livewire 4 + Flux 2 + Fortify**; (2) the PRD recommended Docker + Nginx + PostgreSQL/MySQL — the project runs on **MySQL 8 via Laragon without Docker** (user's environment). All requirements remain valid.

### 2.4 Design & Implementation Constraints

- `id` (bigint auto-increment) primary keys; foreign keys named `<table>_id`.
- Money stored as `DECIMAL(15,2)`; displayed as `Rp 1.000.000` (dot thousands separator).
- All dates stored as `DATE`; timestamps as `TIMESTAMP`.
- PHP code formatted with Pint (PSR-12); static analysis via Larastan at level 7.
- Domain logic lives in **Actions** (`app/Actions/Deal/`) wrapped in `DB::transaction`; UI state lives in Livewire components; no business logic in Blade views.

---

## 3. Functional Requirements

Requirements are tagged **FR-<n>**. Priority: **M** (must), **S** (should), **C** (could). Status reflects the current implementation.

### 3.1 Authentication & Authorization

| ID | Priority | Status | Requirement |
|---|---|---|---|
| FR-01 | M | Done | The system shall authenticate users via Fortify (email + password, with optional passkeys and 2FA). |
| FR-02 | M | Done | The system shall enforce role-based access: `role` column on `users` with values `j4u` / `doctor` (enum `App\Enums\UserRole`). |
| FR-03 | M | Done | Routes creating/editing deals shall be restricted to J4U via the `role:j4u` middleware (`App\Http\Middleware\EnsureUserRole`). |
| FR-04 | M | Done | Doctors shall be denied (HTTP 403) any write route, including `/deals/create` and `/deals/{deal}/edit`. |
| FR-05 | M | Done | Doctors shall only access deals where `deals.doctor_id` equals their user id; viewing another doctor's deal returns 403. |
| FR-06 | M | Done | Unauthenticated users shall be redirected to the login page for all deal routes. |

### 3.2 Master Data Management (Module 1)

| ID | Priority | Status | Requirement |
|---|---|---|---|
| FR-07 | M | Done (model/migration) | The system shall store **items** (`items`): name, type (category), `requires_material` boolean. |
| FR-08 | M | Done (model/migration) | The system shall store **packages** (`packages`): name, `default_price`. A package relates many items via the `package_item` pivot. |
| FR-09 | S | Done | The system shall provide J4U CRUD screens for items, packages, and add-ons (add-ons are items flagged/attached as add-on at deal level). Catalog routes are J4U-only; items/packages referenced by deals are protected from deletion. |

### 3.3 Deal Management (Module 2)

| ID | Priority | Status | Requirement |
|---|---|---|---|
| FR-10 | M | Done | The system shall generate a unique `deal_number` automatically (format `J4U-<year>-NNNN`, sequential per year) on deal creation. |
| FR-11 | M | Done | The **initiation form** shall capture: doctor (initiator), sponsor company name, sponsor PIC name, PIC contact. |
| FR-12 | M | Done | The form shall let J4U select a base package, whose items are pre-checked; changing the package rebuilds the item list and pre-fills the final price with the package default. |
| FR-13 | M | Done | J4U shall be able to **remove base package items** (uncheck) and **add add-on items**, each add-on with an optional custom price. |
| FR-14 | M | Done | J4U shall input a **custom final price** manually (IDR) — deals may be flat-priced or discounted. |
| FR-15 | M | Done | The **payment terms configurator** shall allow an arbitrary number of terms (description, due date, amount), added/removed dynamically. |
| FR-16 | M | Done | Deal creation/update shall be atomic (`CreateDealAction` / `UpdateDealAction` in `DB::transaction`). |
| FR-17 | M | Done | Deals shall have status `draft` or `finalized` (enum `App\Enums\DealStatus`); only a finalized deal is considered confirmed. |
| FR-18 | M | Done | Deal item composition is stored in the `deal_items` pivot (with `is_addon`, `custom_price`), typed by the `App\Models\DealItem` pivot model. |

### 3.4 Tracking & Maintenance (Module 3)

| ID | Priority | Status | Requirement |
|---|---|---|---|
| FR-19 | M | Done | **Material checklist generation:** when a deal is set to `finalized`, the system automatically creates one `material_deadlines` row per deal item where `requires_material = true` (event `DealFinalized` → listener `GenerateMaterialDeadlines`). Idempotent: re-finalizing must not duplicate rows. |
| FR-20 | M | Done | J4U shall be able to set a material's `due_date` and mark it `received` (with `received_at` timestamp). |
| FR-21 | M | Done | **Payment tracker:** J4U shall toggle a payment term from `pending` to `paid` (`MarkPaymentTermPaidAction`). |
| FR-22 | M | Done | **Activity ledger:** every create/update on `Deal`, `PaymentTerm`, and `MaterialDeadline` is automatically recorded in `activity_logs` (Laravel observers, `getDirty()` old/new values, actor user id). |

### 3.5 Dashboard & Final Summary (Module 4)

| ID | Priority | Status | Requirement |
|---|---|---|---|
| FR-23 | M | Done | A deal list page (`DealList`) shall list deals with search (sponsor name) and status filter. J4U sees all deals; doctors see only their own. |
| FR-24 | M | Done | A deal summary page (`DealShow`) shall show: status, final price, payment progress (paid/total), material progress (received/total), deal details (company, PIC, doctor), item composition, payment terms, material checklist, and the activity log timeline. |
| FR-25 | M | Done | J4U shall be able to finalize a draft deal from the summary page and to mark payments/materials received there. |
| FR-26 | S | Done | Dashboard with aggregate statistics (total committed value, deals by status, payment progress, material progress). `DashboardService` computes role-scoped figures; `Dashboard` Livewire component renders stat cards + recent deals. |

### 3.6 User Management (Module 5)

| ID | Priority | Status | Requirement |
|---|---|---|---|
| FR-27 | M | Done | J4U-only user management screen (`/users*`, `role:j4u`) listing users with search (name/email), role badge, deal count, and edit/delete actions. |
| FR-28 | M | Done | J4U can create users (name, email, role `doctor`/`j4u`, password min 8 with confirmation). Admin-provisioned accounts are auto-verified — the mail driver is `log`, so verification emails cannot be delivered. |
| FR-29 | M | Done | J4U can edit users (name, email, role) and reset passwords; a blank password keeps the current one. |
| FR-30 | M | Done | Safety guards: a J4U cannot delete their own account, cannot delete a user who has deals (`deals.doctor_id` cascade would destroy the deals), and cannot change their own role (lockout prevention). |

---

## 4. Data Requirements

### 4.1 Entity-Relationship Model

```
users (id, name, role [j4u|doctor], email, password, ...auth columns)
  └─< doctor_id
sponsors (id, company_name, pic_name, pic_contact)
  └─< sponsor_id
packages (id, name, default_price)
  └─< package_id (nullable)
items (id, name, type?, requires_material)
package_item (package_id, item_id)          -- many-to-many
deals (id, deal_number UNIQUE, doctor_id, sponsor_id, package_id?, final_price, status [draft|finalized])
deal_items (deal_id, item_id, is_addon, custom_price?)  -- many-to-many w/ pivot casts
payment_terms (id, deal_id, description, due_date, amount, status [pending|paid])
material_deadlines (id, deal_id, item_id, material_name, due_date?, status [pending|received], received_at?)
activity_logs (id, deal_id, user_id?, action, details JSON, created_at)
```

### 4.2 Schema Notes

- `deals.package_id` is **nullable** — a deal may be built from custom items only.
- `material_deadlines.due_date` is **nullable**: deadlines are set by J4U after the checklist is generated (they are not known at finalize time). This deviates from the PRD's implied non-null and is documented in the migration comment.
- `deal_items` uses a dedicated pivot model `App\Models\DealItem` so `is_addon` (boolean) and `custom_price` (decimal:2) are properly cast.
- `activity_logs.details` is a JSON object; for updates it is a `field => {old, new}` map; for creates it mirrors the model attributes. `created_at` only (no `updated_at`).

### 4.3 Enumerations (PHP backed enums)

| Enum | Values |
|---|---|
| `App\Enums\UserRole` | `j4u`, `doctor` |
| `App\Enums\DealStatus` | `draft`, `finalized` |
| `App\Enums\PaymentStatus` | `pending`, `paid` |
| `App\Enums\MaterialStatus` | `pending`, `received` |

---

## 5. Business Rules

1. **BR-01 — RBAC:** Only J4U may write. Doctors are strictly read-only and scoped to their own deals.
2. **BR-02 — Customization:** A deal's item set may diverge from its base package (removals + add-ons). The agreed `final_price` is authoritative and manually input; it is not auto-computed.
3. **BR-03 — Payment terms:** The sum of all term amounts need not equal the final price (partial terms are allowed at J4U's discretion).
4. **BR-04 — Material checklist:** Generated once per item at finalization; never auto-removed; re-finalizing must not create duplicates.
5. **BR-05 — Finalization:** Only a finalized deal triggers material generation. Editing is allowed while `draft`; after `finalized` the deal is immutable via the UI (J4U only sees read-only summary + status toggles).
6. **BR-06 — Audit:** Every create/update is logged with actor and timestamp. No UI-level deletion of activity logs.

---

## 6. External Interface Requirements

### 6.1 User Interface

- UI built with **Livewire 4** components (`app/Livewire/DealList`, `DealForm`, `DealShow`) + **Flux 2** components and Tailwind CSS.
- Full-page Livewire views use a single `<section>` root element; the `layouts::app` layout is applied automatically by Livewire's `component_layout` config.
- Money displayed as `Rp 1.000.000`; dates as `d M Y`; statuses shown as colored badges (emerald = done/finalized, zinc = draft/pending).
- Validation error keys in Livewire components must match **camelCase property names** (Livewire 4 does not snake_case rule keys), e.g. rule `doctorId`, `flux:error name="doctorId"`.

### 6.2 API / CLI

- No public REST API in scope. Internal endpoints are Livewire full-page/component actions.
- Artisan/Tinker available for data operations; factories provided for tests and seeding.

---

## 7. Non-Functional Requirements

| ID | Category | Requirement |
|---|---|---|
| NFR-01 | Security | Passwords hashed (bcrypt); 2FA + passkeys supported via Fortify; route-level RBAC on all write routes; 403 for unauthorized access. |
| NFR-02 | Data integrity | All multi-table writes inside `DB::transaction`; unique constraint on `deals.deal_number`; foreign keys with `cascadeOnDelete` / `nullOnDelete` as specified. |
| NFR-03 | Performance | Deal lists eager-load relations; indexes on foreign keys and `deals.status`. Sufficient for an internal event-scale dataset (hundreds of deals). |
| NFR-04 | Maintainability | Actions/DTO pattern; Larastan level 7 clean; Pint formatting; observers for cross-cutting audit behavior. |
| NFR-05 | Usability | Reactive form (no page reloads) for deal customization; dynamic payment-term rows; confirm dialogs on destructive/finalizing actions. |
| NFR-06 | Testability | In-memory SQLite test DB; feature tests cover RBAC matrix, deal workflow, material generation, and audit logging (51 tests). |

---

## 8. Behavior Specifications (Logic Hooks)

### 8.1 DealFinalized → GenerateMaterialDeadlines

**Trigger:** `Deal.status` transitions `draft → finalized` (via `DealObserver::updated` → `event(new DealFinalized($deal))`).

**Action:** for each `deal_items` row whose item has `requires_material = true` and no existing `material_deadlines` row for `(deal_id, item_id)`, insert `MaterialDeadline(deal_id, item_id, material_name = item.name, due_date = null, status = pending)`.

**Idempotency:** duplicate rows are skipped (checked per item).

### 8.2 Activity Logging Observers

`DealObserver`, `PaymentTermObserver`, `MaterialDeadlineObserver`:

- `created()` → `action = '<model>.created'`, `details = <model attributes>`
- `updated()` → `action = '<model>.updated'`, `details = { field: { old, new } }` (from `getDirty()`/`getOriginal()`, enums scalarized)

Actor = `auth()->id()` (nullable for system actions). Actions recorded: `deal.created`, `deal.updated`, `payment_term.created`, `payment_term.updated`, `material_deadline.created`, `material_deadline.updated`.

---

## 9. Acceptance Criteria (Definition of Done)

Traceability to the PRD's checklist, with current status:

| # | Criterion | Status |
|---|---|---|
| AC-1 | Tim J4U can build a package from multiple items. | **Done** (FR-09), verified by `CatalogTest`. |
| AC-2 | J4U can create a new deal, override default package price, add add-ons, and freely set payment terms. | **Done** (FR-10…FR-18), verified by `DealWorkflowTest`. |
| AC-3 | After a deal is saved, the system automatically releases the material checklist to be charged to the sponsor. | **Done** (FR-19), verified by `DealMaterialGenerationTest`. |
| AC-4 | A doctor logs in and can **only** view the summary of sponsors they invited (no edit/delete buttons). | **Done** (FR-05, FR-23…FR-25), verified by `DealAccessControlTest`. |
| AC-5 | Every save/change by J4U on deal details is reflected in the Activity Log history. | **Done** (FR-22), verified by `ActivityLogTest`. |

---

## 10. Traceability Matrix (Requirement → Implementation)

| Artifact | Location |
|---|---|
| RBAC middleware | `app/Http/Middleware/EnsureUserRole.php`, registered as `role` alias in `bootstrap/app.php` |
| Routes | `routes/web.php` |
| Enums | `app/Enums/` |
| Models | `app/Models/` (`Sponsor`, `Item`, `Package`, `Deal`, `DealItem`, `PaymentTerm`, `MaterialDeadline`, `ActivityLog`) |
| DTO | `app/DTOs/Deal/DealData.php` |
| Actions | `app/Actions/Deal/` (`CreateDealAction`, `UpdateDealAction`, `FinalizeDealAction`, `MarkPaymentTermPaidAction`, `MarkMaterialReceivedAction`) |
| Observers | `app/Observers/` |
| Event / Listener | `app/Events/DealFinalized.php`, `app/Listeners/GenerateMaterialDeadlines.php` |
| Audit helper | `app/Support/ActivityLogger.php` |
| Livewire components | `app/Livewire/DealList.php`, `DealForm.php`, `DealShow.php`, `app/Livewire/Users/` (`UserIndex`, `UserForm`), `app/Livewire/Catalog/` |
| Views | `resources/views/livewire/deal-*.blade.php`, `resources/views/livewire/users/*.blade.php` |
| Migrations | `database/migrations/2026_08_12_0000*.php` |
| Tests | `tests/Feature/DealAccessControlTest.php`, `DealWorkflowTest.php`, `DealMaterialGenerationTest.php`, `ActivityLogTest.php` |

---

## 11. Open Items / Known Gaps

1. ~~Dashboard aggregate stats~~ — **done** (FR-26): `DashboardService` + `Dashboard` component, role-scoped, covered by `DashboardStatsTest`.
2. **Database seeding** for the sponsor catalog (Diamond/Platinum/White Gold tiers) — **done** (`SponsorCatalogSeeder`), incl. demo users `j4u@apcn2027.local` / `doctor@apcn2027.local` and one finalized example deal.
3. **Edit-after-finalize policy** is intentionally restricted (BR-05); reopening finalized deals is a future decision.
4. ~~Production DB target verification~~ — **done**: migrations, seed, and the full test suite verified on **MySQL 8.0.30** (`phpunit.mysql.xml`). No Docker: app runs via Laragon directly.
5. **User management** — **done** (FR-27…FR-30): J4U-only `/users` module (list/create/edit + guards), covered by `UserManagementTest`.

---

## 12. Glossary (Indonesian terms)

- **Termin** — installment/payment term.
- **DP (Uang Muka)** — down payment.
- **Final Summary** — the read-only deal summary page shown to doctors.
