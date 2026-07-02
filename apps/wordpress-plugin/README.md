# DBG Platform WordPress Plugin

Status: Development scaffold

## Purpose

This plugin connects WordPress and WooCommerce to DBG Platform domain concepts.

It must not become the full business domain. It is an interface and integration layer.

## Responsibilities

- Register DBG Platform REST routes in WordPress.
- Provide admin screens for configuration.
- Prepare WooCommerce integration hooks.
- Expose catalogue and project-related UI helpers.
- Connect to DBG Platform engines.

## Non-responsibilities

- Own supplier logic.
- Own the complete domain model.
- Store final business truth if a dedicated backend exists later.

## Structure

- dbg-platform.php
- src/Core
- src/API
- src/Admin
- src/Modules
- src/Integrations
- src/Database
- assets
- tests

## Changelog

### 0.2.0 — 2026-07-02

Removed business-domain logic that had leaked into this plugin, violating `books/architecture/07-wordpress-base44-roles.md` ("WordPress sells. Base44 operates."):

- Removed: Organisation, OrganisationContact, OrganisationUser, OrganisationSettings, Project, Quote, Order, Invoice, Payment services, their event/DB repositories, admin screens, views, and REST routes (including the misleadingly-named `IdentityRoutes`, which was actually full Organisation CRUD).
- Removed the corresponding `dbg_*` database tables from the activation migrator (organisations, organisation_contacts, organisation_users, organisation_settings, projects, project_events, quotes, quote_lines, quote_events, orders, order_lines, order_events, invoices, invoice_lines, invoice_events, payments, payment_allocations, payment_events).
- This business domain now lives entirely in **ATLAS ERP** (Base44 app).
- Kept: Assets, Media/Files, Audit logs, Settings (sync mode), Commerce/WooCommerce bridge, Identity/permission plumbing used by those.
- `AssetService` and the Assets admin views no longer validate `organisation_id`/`project_id` against local tables (those repositories no longer exist) — they now only do shape validation. A proper cross-system validation bridge to the ATLAS ERP API is a follow-up task, not yet implemented.
