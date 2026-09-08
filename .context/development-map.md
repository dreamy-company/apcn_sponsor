# FEATURE DEVELOPMENT MAP

## J4U Sponsorship Deal Management System (APCN 2027)

- **Version:** 1.1
- **Source:** SRS v2.1 (`.context/srs.md`)
- **Last updated:** 2026-09-08

---

## 1. How to Use This Map

Every row in the inventory table is an **SRS requirement** (FR-xx). Work is organized into **workstreams (WS-A…WS-E)** — each workstream is a shippable increment with its own tasks, dependencies, and Definition of Done (DoD). Follow the sequence numbers; a task's dependencies must be closed first.

**Legend:** ✅ Done · 🚧 In progress · ⬜ Planned · 🔒 Blocked by dependency

---

## 2. Feature Inventory (SRS Trace)

| SRS Req | Feature | Module | Status | Artifacts |
|---|---|---|---|---|
| FR-01…FR-06 | Auth + RBAC (Fortify, `role` middleware, doctor scoping) | Auth | ✅ | `EnsureUserRole`, `routes/web.php`, `User` |
| FR-07, FR-08 | Items & packages (models/migrations) | 1 | ✅ | `Item`, `Package`, `package_item` |
| FR-09 | **Master data CRUD UI** (items, packages, add-ons) | 1 | ✅ | `CatalogItemIndex/Form`, `CatalogPackageIndex/Form`, `app/Actions/Catalog/` |
| FR-10…FR-18 | Deal creation & customization (initiation, package select, add-ons, custom price, payment terms, atomic actions) | 2 | ✅ | `DealForm`, `CreateDealAction`, `UpdateDealAction`, `DealData` |
| FR-19…FR-22 | Tracking & maintenance (material checklist gen, payment tracker, activity ledger) | 3 | ✅ | `DealFinalized`, `GenerateMaterialDeadlines`, observers, `DealShow` |
| FR-23…FR-25 | Deal list + final summary (search, filter, progress, role-scoped) | 4 | ✅ | `DealList`, `DealShow` |
| FR-26 | **Dashboard aggregate stats** | 4 | ✅ | `DashboardService`, `Dashboard` (Livewire) |
| — | **Seed & demo data** (catalog tiers + demo users) | all | ✅ | `SponsorCatalogSeeder`, `DatabaseSeeder` |
| — | **Production hardening** (MySQL via Laragon, no Docker) | infra | 🚧 | → WS-D |
| — | **User management** (J4U-only list/create/edit, FR-27…FR-30) | all | ✅ | `UserIndex`, `UserForm`, `UserManagementTest` |
| — | Stretch: reopen finalized deals, material uploads, notifications | 3/4 | ⬜ | → WS-E |

---

## 3. Workstreams

### WS-A — Master Data CRUD (Module 1) — closes FR-09 / AC-1

Manage the sponsorship catalog exactly as the PDF Prospectus: items, packages (tiers), and add-on management.

| # | Task | Files | Deps | Status |
|---|---|---|---|---|
| A1 | **Item CRUD** — Livewire `CatalogItemIndex` (table + search) and `CatalogItemForm` (name, type, `requires_material` toggle); Actions `CreateItemAction` / `UpdateItemAction`; routes `/catalog/items` (J4U-only) | `app/Livewire/Catalog/…`, `app/Actions/Catalog/…`, `resources/views/livewire/catalog/…`, `routes/web.php` | — | ✅ |
| A2 | **Package CRUD** — `CatalogPackageIndex` / `CatalogPackageForm` with multi-select item picker (checkbox list, like `DealForm`'s items); Actions `CreatePackageAction` / `UpdatePackageAction`; routes `/catalog/packages` (J4U-only) | same pattern as A1 | A1 (item picker reuses item list) | ✅ |
| A3 | **DealForm integration** — confirm package items list & add-on list are sourced from the same catalog queries (already the case: `Item::orderBy('name')`, `Package::items()`) | `DealForm` | A1, A2 | ✅ |
| A4 | **Sidebar nav** — "Catalog" group (Items, Packages) with `briefcase`/`list-bullet` icons; only visible to J4U | `resources/views/layouts/app/sidebar.blade.php` | A1 | ✅ |
| A5 | **Tests** — J4U can create/edit/delete items & packages; doctor gets 403 on `/catalog/*`; deleted package item is detached (cascade); name required | `tests/Feature/CatalogTest.php` | A1–A4 | ✅ |

**DoD (WS-A):** J4U can build a package from several items and adjust the catalog without touching code; doctors cannot access catalog routes; all catalog mutations are covered by feature tests (✅ AC-1).

---

### WS-B — Dashboard & Final Summary (Module 4) — closes FR-26 / AC-4

Replace the starter placeholder dashboard with a role-aware summary.

| # | Task | Files | Deps | Status |
|---|---|---|---|---|
| B1 | **Stats queries** — aggregate: total committed (`SUM(final_price)`), deals by status, payment progress (paid vs total across terms), material progress; J4U = global, doctor = scoped to `doctor_id` | `app/Services/DashboardService.php` | — | ✅ |
| B2 | **Dashboard component** — stat cards (Total Value, Open Deals, Finalized, Outstanding Payments) + recent deals table + link to `/deals` | `app/Livewire/Dashboard.php`, `resources/views/livewire/dashboard.blade.php` | B1 | ✅ |
| B3 | **Wire the route** — point `/dashboard` at the component (currently `Route::view`) | `routes/web.php` | B2 | ✅ |
| B4 | **Tests** — doctor's dashboard shows only their figures; J4U sees global figures; zero-deal state renders | `tests/Feature/DashboardStatsTest.php` | B2 | ✅ |

**DoD (WS-B):** Both roles see an accurate, scoped summary on `/dashboard`; figures match the DB in tests (✅ AC-4 reinforced).

---

### WS-C — Seed & Demo Data

Make the app usable for a demo / first sign-in.

| # | Task | Files | Deps | Status |
|---|---|---|---|---|
| C1 | **Catalog seeder** — PRD example items ("Booth 3x3m", "Industry Symposium", "Welcome Reception Naming Rights", …) + tiers Diamond / Platinum / White Gold with default prices and `requires_material` flags; items attached to packages | `database/seeders/SponsorCatalogSeeder.php` | — | ✅ |
| C2 | **Demo users** — `j4u@apcn2027.local` (role j4u) and `doctor@apcn2027.local` (role doctor) with known passwords; wire into `DatabaseSeeder` | `database/seeders/DatabaseSeeder.php` | C1 | ✅ |
| C3 | **Sample deal** (optional) — one finalized deal w/ payment terms + generated materials for realistic screenshots | seeder or factory call | C2 | ✅ |

**DoD (WS-C):** `php artisan migrate:fresh --seed` yields a populated catalog, two logins, and (optionally) one example deal.

---

### WS-D — Production Hardening

| # | Task | Files | Deps | Status |
|---|---|---|---|---|
| D1 | **Target-DB verification** — migrations, seed, and full test suite run against **MySQL 8.0.30 (Laragon)**; dialect checks (decimal casts, JSON key order, booleans) pass | `phpunit.mysql.xml`, `database/migrations/` | — | ✅ |
| D2 | **Local serving (Laragon)** — app served via Laragon (no Docker); PHP 8.3 + MySQL 8.0.30; `php artisan serve` as fallback | Laragon config, `.env` | D1 | ✅ |
| D3 | **Env hardening** — `APP_DEBUG=false` in production, session/queue drivers, `.env.example` sync (MySQL values documented) | `.env.example`, config | D2 | 🚧 |

**DoD (WS-D):** `docker compose up` runs the app on the target DB; full suite green on PostgreSQL; deployment runbook documented.

---

### WS-F — Deal Revision: brand, subtotal, USD, guarantee letter, quotas ✅ done

Client revision of 2026-09-08. Adds BR-07…BR-12 and supersedes BR-03. See SRS §5.

| # | Task | Status | Notes |
|---|---|---|---|
| F1 | Schema: `sponsors.brand_name`, `deals.currency`/`subtotal`, dual catalog prices, term `notes`+verification, `guarantee_letters` | ✅ | 5 additive migrations; `default_price` renamed to `default_price_idr` (values preserved) |
| F2 | `<x-money>` component + `Currency` enum formatting | ✅ | Replaced hardcoded `Rp `+`number_format` in all 14 blades |
| F3 | Initiation: brand name + typeable doctor with inline create | ✅ | Sponsor identity is now (company, brand); reuses `CreateDoctorAction`; contact required |
| F4 | Package & Items: subtotal, currency switch, item search, inline item/package create | ✅ | Subtotal = package + checked add-ons (BR-07) |
| F5 | Payment terms: notes, running balance, finalize guard, per-term verification | ✅ | `UnbalancedPaymentTermsException` (BR-08); `wire:key` added to repeater rows |
| F6 | `UpdateDealAction` reconciles terms in place | ✅ | **Correctness fix** — editing previously deleted and recreated every term, destroying paid status and proofs |
| F7 | Guarantee letter (3-step flow + verification) | ✅ | Own table, own actions; counts toward payment progress, excluded from BR-08 |
| F8 | Quota visibility (`5/30` + remaining) and buyer detail | ✅ | Catalog indexes, dashboard Inventory panel, Brand column on buyer tables |
| F9 | Catalog reconciliation against the 2027 prospectus | ✅ | Every IDR price already matched; USD derived at ×18,100; 3 quota corrections. Open contradictions logged in SRS §11 |
| F10 | Currency-safe aggregates | ✅ | IDR and USD are reported separately and never summed together (BR-10) |
| F11 | `package_item.quantity` + `deal_items.quantity` | ✅ | Tier quantities from the inclusion matrix (Diamond ×5 booths, ×15 pax, every tier ×2 T-Banner); add-on units selectable per deal |
| F12 | Item quota counted in **units** | ✅ | `QuotaService::itemTakenCount()` sums `deal_items.quantity`; wizard + `FinalizeDealAction` check requested units against what remains (BR-11 revised) |

### WS-G — Revisi UI & guarantee letter (round 2) ✅ done

Umpan balik pemakaian, 2026-09-08. Merevisi BR-09, menambah catatan presentasi pada BR-06.

| # | Task | Status | Notes |
|---|---|---|---|
| G1 | Guarantee letter pindah ke payment term | ✅ | `guarantee_letters.payment_term_id` unik; `amount`/`currency` dihapus (ikut nominal termin); migrasi mem-backfill ke termin paling awal. Urutan drop FK → index → kolom supaya jalan di MySQL *dan* SQLite |
| G2 | Trait `HasTransferProof` | ✅ | Menghapus duplikasi helper proof antara `PaymentTerm` dan `GuaranteeLetter` |
| G3 | Kartu GL berdiri sendiri dihapus | ✅ | Kolom tabel jadi **Settlement**; alur 3 langkah tampil sebagai baris ekspansi di bawah terminnya |
| G4 | Progres pembayaran tidak dobel-hitung | ✅ | `totalPaid`/`totalTerms` murni dari payment terms |
| G5 | Activity Log bahasa Indonesia | ✅ | `App\Support\ActivityDescriber` + `ActivityLog::describe()`; slug di DB tidak diubah, `ActivityLogTest` tetap hijau tanpa disentuh |
| G6 | Field Inclusion | ✅ | `items.inclusion` (blurb katalog) + `deals.inclusion` (satu blok per deal). Override per-item sempat dibuat lalu **dicabut** atas umpan balik — textarea di tiap baris item membuat wizard tidak terpakai |
| G7 | Mask angka uang | ✅ | `<x-money-input>` + komponen Alpine di `resources/js/app.js`; `Money::plain()` membuang `,00` di sumbernya. Prop `money` milik Mary tidak dipakai — butuh bundel JS Mary yang tidak di-import proyek ini |
| G8 | Item terpilih dikelompokkan | ✅ | `selectedItemKeys` / `availableItemKeys`; search hanya menyaring grup "Other Items"; urutan `$items` tidak diubah karena `wire:model` terikat indeks |

### WS-E — Stretch / Backlog (not committed)

| # | Idea | Notes |
|---|---|---|
| E1 | Reopen finalized deals (edit) | Requires policy decision (BR-05) + re-versioned audit entries |
| E2 | Material file uploads / due-date reminders | New tables + storage driver |
| E6 | Resolve prospectus contradictions with the client | Conflicting prices/quotas listed in SRS §11.4 |
| E3 | Email notifications on payment/material deadlines | Queue + mail driver |
| E4 | Export deals to PDF/Excel (final summary for sponsors) | `barryvdh/laravel-dompdf` or CSV |

---

## 4. Suggested Execution Order

```
WS-C  ✅ done — seed data (catalog + demo users + example deal)
  ↓
WS-A  ✅ done — catalog CRUD (items, packages) → AC-1 closed
  ↓
WS-B  ✅ done — dashboard stats (FR-26 closed)
  ↓
User mgmt  ✅ done — `/users` module (FR-27…FR-30, `UserManagementTest`)
  ↓
WS-F  ✅ done — deal revision (brand, subtotal, USD, guarantee letter, quotas)
  ↓
WS-G  ✅ done — UI revision round 2 (GL in terms, plain-language log, inclusion, money mask)
  ↓
WS-D  ⬜ next — production hardening
  ↓
WS-E  ⬜ — stretch, as prioritized
```

WS-C first because a populated catalog makes WS-A and WS-B verifiable by hand. WS-A and WS-B are independent of each other (can be parallelized).

---

## 5. Test Coverage Map

| Area | Tests | Status |
|---|---|---|
| RBAC matrix (guest/doctor/J4U, own vs others' deals) | `DealAccessControlTest` | ✅ 7 tests |
| Deal creation workflow via form | `DealWorkflowTest` | ✅ 4 tests |
| Material generation on finalize | `DealMaterialGenerationTest` | ✅ 4 tests |
| Activity ledger | `ActivityLogTest` | ✅ 4 tests |
| Catalog CRUD (WS-A) | `CatalogTest` | ✅ 10 tests |
| Dashboard stats (WS-B) | `DashboardStatsTest` | ✅ 4 tests |
| Guarantee letter flow (WS-F) | `GuaranteeLetterTest` | ✅ 6 tests |
| Currency, subtotal, item search, inline create (WS-F) | `DealCurrencyAndSubtotalTest` | ✅ 7 tests |
| Term balance, verification, edit-preserves-proof (WS-F) | `PaymentTermBalanceTest` | ✅ 8 tests |
| Dashboard inventory panel (WS-F) | `DashboardInventoryTest` | ✅ 1 test |
| Item/package quantities & unit-based quota (WS-F) | `ItemQuantityTest` | ✅ 8 tests |
| Guarantee letter per payment term (WS-G) | `GuaranteeLetterTest` | ✅ 10 tests |
| Activity log wording (WS-G) | `ActivityLogPresentationTest` | ✅ 7 tests |
| Inclusion katalog + override (WS-G) | `ItemInclusionTest` | ✅ 6 tests |
| Pengelompokan item & format uang (WS-G) | `DealFormGroupingTest`, `WizardRenderSmokeTest` | ✅ 8 tests |

**Baseline:** 180 tests / 473 assertions green; PHPStan level 7 clean; Pint clean.

---

## 6. Definition of Done (per workstream)

Every workstream ships only when:

1. All its tasks are closed and dependencies met.
2. New behavior is covered by feature tests; full suite stays green.
3. PHPStan (level 7) and Pint pass on changed files.
4. SRS statuses (section 3 tables) and this map are updated to match.
5. Any PRD deviation is reported in the SRS (per AGENTS.md priority rules).
