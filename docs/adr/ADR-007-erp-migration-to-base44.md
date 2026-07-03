# ADR-007 ERP Business Logic Migration to Base44 (ATLAS ERP)

Status: Accepted

Date: 2026-07-02

Modules impacted: wordpress-plugin (Organisations, Projects, Quotes, Orders, Invoices, Payments, Assets), Base44 ATLAS ERP app.

## Context

An implementation audit found that the WordPress plugin (`apps/wordpress-plugin`) had accumulated full ERP business-domain logic — services, repositories, admin screens, and REST routes for Organisation, Project, Quote, Order, Invoice and Payment — directly contradicting `books/architecture/07-wordpress-base44-roles.md`, which states:

> WordPress sells. Base44 operates. DBG Platform domain model defines the truth.

Notably, `API/Routes/IdentityRoutes.php` was misleadingly named — it was in fact a complete Organisation CRUD REST API, not an identity/auth concern. `Admin/FormHandler.php` also performed direct Organisation/Project CRUD via `admin-post` forms, duplicating the removed admin handlers.

## Decision

1. All Organisation, Project, Quote, Order, Invoice and Payment business logic (services, event repositories, DB repositories, admin handlers/views, REST routes, and their `CREATE TABLE` statements in `Database/Migrator.php`) is removed from the WordPress plugin.
2. This domain model now lives entirely in **ATLAS ERP**, a dedicated Base44 app (id `6a4686aa7eb2f854e0a293de`), with 11 entities: Organisation, OrganisationContact, Project, Quote, QuoteLine, Order, OrderLine, Invoice, InvoiceLine, Payment, PaymentAllocation.
3. The WordPress plugin keeps only: Assets, Media/Files management, Audit logs, Settings (sync mode / remote API config), and the WooCommerce/Commerce bridge.
4. Assets still carry `organisation_id` / `project_id` fields (Assets are explicitly *not* owned by Base44 per the same ADR), but these are now foreign references into Base44 rather than local WordPress tables.

## Cross-system reference validation (bridge)

Since `AssetService` can no longer validate `organisation_id` / `project_id` against local tables, a validation bridge was designed:

- A backend function `validateReference` was created in the ATLAS ERP Base44 app. It accepts `{ type: "organisation"|"project", id, api_key }` and returns `{ valid, archived, name }`, guarded by a shared secret. Tested and working via internal agent calls.
- The WordPress plugin already had the right shape for this: `Settings\SettingsRepository` (`api_base_url`, `api_token`, `sync_mode`: local/remote/hybrid) and `Remote\RemoteClient` (generic authenticated POST client). No new WordPress infrastructure was needed for this.
- **Not yet wired end-to-end**: the ATLAS ERP app's public function URL (needed to fill `api_base_url` in WordPress settings) could not be retrieved through the current tooling — it is only visible inside the Base44 app editor UI. Until it's configured, the plugin keeps `sync_mode = local`, meaning `AssetService` only does shape validation (non-empty organisation_id) and trusts the reference — no silent failure, but no live existence check either.

## Consequences

- WordPress no longer duplicates or can drift from the ERP source of truth.
- The plugin dropped from ~5,400 lines of business logic to a pure interface/asset/media layer, matching its documented non-responsibilities.
- Follow-up task: once the ATLAS ERP function URL is available, set `sync_mode = hybrid` (or `remote`) and populate `api_base_url` / `api_token` in the plugin settings screen to activate live validation.
