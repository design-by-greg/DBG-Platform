# ADR-007 ERP Business Logic Migration to Base44 (ATLAS ERP)

Status: Accepted — bridge wired and live (2026-07-03)

Date: 2026-07-02 (updated 2026-07-03)

Modules impacted: wordpress-plugin (Organisations, Projects, Quotes, Orders, Invoices, Payments, Assets), Base44 ATLAS ERP app.

## Context

An implementation audit found that the WordPress plugin (`apps/wordpress-plugin`) had accumulated full ERP business-domain logic — services, repositories, admin screens, and REST routes for Organisation, Project, Quote, Order, Invoice and Payment — directly contradicting `books/architecture/07-wordpress-base44-roles.md`, which states:

> WordPress sells. Base44 operates. DBG Platform domain model defines the truth.

Notably, `API/Routes/IdentityRoutes.php` was misleadingly named — it was in fact a complete Organisation CRUD REST API, not an identity/auth concern. `Admin/FormHandler.php` also performed direct Organisation/Project CRUD via `admin-post` forms, duplicating the removed admin handlers.

## Decision

1. All Organisation, Project, Quote, Order, Invoice and Payment business logic (services, event repositories, DB repositories, admin handlers/views, REST routes, and their `CREATE TABLE` statements in `Database/Migrator.php`) is removed from the WordPress plugin.
2. This domain model now lives entirely in **ATLAS ERP**, a dedicated Base44 app (id `6a4686aa7eb2f854e0a293de`), with 11 entities: Organisation, OrganisationContact, Project, Quote, QuoteLine, Order, OrderLine, Invoice, InvoiceLine, Payment, PaymentAllocation.
3. The WordPress plugin keeps only: Assets, Media/Files management, Audit logs, Settings (sync mode / remote API config), and the WooCommerce/Commerce bridge.
4. Assets (and, once implemented, Production jobs) still carry `organisation_id` / `project_id` fields (Assets are explicitly *not* owned by Base44 per the same ADR), but these are now foreign references into Base44 rather than local WordPress tables.

## Cross-system reference validation (bridge) — now live

A backend function `validateReference` was created in the ATLAS ERP Base44 app. It accepts `{ type: "organisation"|"project", id, api_key }` and returns `{ valid, archived, name }`, guarded by a shared secret.

The WordPress plugin already had the right shape for this: `Settings\SettingsRepository` (`api_base_url`, `api_token`, `sync_mode`: local/remote/hybrid) and `Remote\RemoteClient` (generic authenticated POST client), plus a new `Remote\ReferenceValidator` that wraps the call and applies the sync-mode policy (see below). No duplicate infrastructure was created.

As of 2026-07-03, on the IONOS production install (`s1097820712.onlinehome.fr`):
- `api_base_url` = `https://mature-atlas-core-flow.base44.app/functions`
- `sync_mode` = `hybrid`
- Verified end-to-end: an unknown organisation id correctly comes back `valid:false`; a bad API key correctly returns HTTP 401.

### Sync mode semantics (`ReferenceValidator`)
- `local`: no remote call. Reference trusted as-is (shape validation only). Used for offline dev/testing.
- `hybrid` (current default): calls the bridge; if it answers clearly, that answer is used (blocks invalid/archived references). If the bridge is unreachable or errors, **fails open** — trusts the reference — so a network hiccup with Base44 never blocks WordPress work.
- `remote`: strict. If the bridge doesn't answer clearly, **fails closed** — treats the reference as invalid. For when live validation must be guaranteed.

### Bug found and fixed during wiring: organisation_id / project_id were typed as integers
While wiring the bridge, discovered that most of the WordPress-side code (Assets, Media folders, File records, Production jobs — repositories, services, REST routes, admin handlers/views) still treated `organisation_id` / `project_id` as WordPress-local auto-increment integers (`absint()`, number `<input>` fields, `%d` SQL placeholders), even though ADR-007 already made these foreign references into Base44 record ids (strings, not ints). The DB schema (`Database/Migrator.php`) was already correctly typed as `VARCHAR(64)` for these columns, but the PHP application layer around it wasn't. This meant real Base44 organisation/project ids (24-char hex strings) would have been silently truncated to `0` by `absint()` everywhere — assets and files would have all landed under `organisation_id = 0`.

Fixed across: `Assets/AssetService.php`, `Database/Repositories/{AssetRepository,MediaFolderRepository,FileRecordRepository,ProductionJobRepository}.php`, `Admin/{AssetAdminHandler,FormHandler,MediaAjaxUploadHandler,MediaMultipleUploadHandler}.php`, `API/Routes/{AssetRoutes,MediaFolderRoutes,FileRoutes}.php`, `Files/FileUploadService.php`, `Admin/Views/{assets.php,media.php}` (number inputs → text inputs). All now treat `organisation_id`/`project_id` as sanitized strings end-to-end, matching the DB schema and the Base44 id format.

## Consequences

- WordPress no longer duplicates or can drift from the ERP source of truth.
- The plugin dropped from ~5,400 lines of business logic to a pure interface/asset/media layer, matching its documented non-responsibilities.
- Live cross-system reference validation is active in production (IONOS) as of 2026-07-03, in `hybrid` mode.
- Follow-up: once real Organisations/Projects exist in ATLAS ERP, do a live smoke test of the "valid reference" happy path (only the "invalid id" and "bad key" paths were tested so far, since ATLAS ERP has no records yet).
