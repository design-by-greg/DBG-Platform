# ADR-008 Production Domain Migration to Base44 (ATLAS ERP)

Status: Accepted

Date: 2026-07-03 (updated same day — see Addendum)

Modules impacted: wordpress-plugin (Production), Base44 ATLAS ERP app.

## Context

Following the same pattern that led to ADR-007 (Organisation/Project/Quote/Order/Invoice/Payment leaking into WordPress), a new "Production" domain was found being built directly in the WordPress plugin: `ProductionJobRepository`, `ProductionOperationRepository`, `ProductionAssignmentRepository`, `ProductionCheckRepository` (`Database/Repositories/`), and `ProductionEventRepository` (`Production/`).

This directly contradicts `books/architecture/07-wordpress-base44-roles.md`, which explicitly states:

> WordPress must not own: ... production workflows.
> Base44 handles internal operations. It manages: ... production tracking.

And `docs/specifications/SPEC-006-production.md`, which ties Production directly to Order state ("A job cannot start without payment and validation", "Order is confirmed → Production job is created") — i.e. genuine ERP business logic, not a WordPress-facing concern.

Caught early this time: the WordPress-side files were not yet wired into any service provider, had no admin UI, and no `Database/Migrator.php` tables were created for them — so removal carries zero runtime risk, unlike the ~5,400-line cleanup in ADR-007.

## Decision

1. The Production domain (jobs, operations, resource assignments, quality checks, audit events, deliveries) is modeled entirely in **ATLAS ERP** (Base44, app id `6a4686aa7eb2f854e0a293de`), as 6 new entities: `ProductionJob`, `ProductionOperation`, `ProductionAssignment`, `ProductionCheck`, `ProductionEvent`, `Delivery`.
2. The entity design carries over the real modeling work already done in the (now-removed) WordPress repository classes — job numbering (`PR-YYYY-NNNN`), status/priority enums, planned vs actual timestamps, operation sort order, resource assignment types, checklist required/completed flags — rather than the more abstract shape in `docs/database/05-production-tables.sql`.
3. `supplier_id` is kept as a loose string reference (no live foreign key) — Production does not depend on any of the 4 legacy Base44 apps (including `DBG Master Engine`, which already has its own `Supplier` entity for a different purpose: product/pricing sourcing). If/when a unified Supplier concept is needed across ATLAS, that's a separate future decision, not assumed here.
4. The now-empty WordPress-side files are deleted: `Database/Repositories/{ProductionJobRepository,ProductionOperationRepository,ProductionAssignmentRepository,ProductionCheckRepository}.php`, `Production/ProductionEventRepository.php`, and the (now-empty) `Production/` directory.

## Consequences

- Production tracking (a core internal-operations concern per `07-wordpress-base44-roles.md`) is now correctly scoped to Base44 from the start, instead of being cleaned up later at higher cost.
- No WordPress admin UI, REST routes, or DB tables ever shipped for this domain — nothing public-facing needs to change.
- Follow-up: if a WordPress-facing view of production status is ever needed (e.g. showing "in production" on a customer-facing order), it should go through the same cross-system reference/read bridge pattern established in ADR-007 (`ReferenceValidator` / `RemoteClient`), not local tables.

## Addendum (same day, ~1h later)

A follow-up commit (`daf5039`, author `design-by-greg <contact@designbygreg.fr>`, "Add production service validation") re-introduced `apps/wordpress-plugin/src/Production/ProductionService.php` — a 179-line service class with real business logic (create/update/archive/restore, status-transition rules, cross-entity validation against Organisation/Project/Order) directly in the plugin.

This file was **already broken on arrival**: it imports `OrganisationRepository`, `ProjectRepository`, `OrderRepository`, and the four `Production*Repository` classes removed by this same ADR — none of which exist in the codebase anymore. It could not have run without a fatal error.

Removed again, no functional loss (nothing referenced it, no service provider registered it).

**Open question for Gregory:** this is the second time Production-domain business logic has been independently added to the WordPress plugin after being migrated out. If another process, script, or collaborator is developing directly against the old `docs/specifications/SPEC-006-production.md` (which still describes Production as a DBG-Platform/WordPress module), it will keep regenerating this conflict. Worth checking the source of these commits and pointing it at ATLAS ERP (Base44) instead, or updating SPEC-006 to reflect the ADR-008 decision so nothing builds against the stale spec.
