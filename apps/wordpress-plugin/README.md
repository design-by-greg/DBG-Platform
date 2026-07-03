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

### 0.2.2 — 2026-07-03

Removed dead/broken code duplicated between `Admin/FormHandler.php` and `Admin/AssetAdminHandler.php`:

- `FormHandler::createAsset()`, `updateAsset()` were unreachable — `AssetAdminHandler` registers on the same `admin_post_dbg_create_asset` / `dbg_update_asset` hooks first and always `exit`s after redirecting, so these methods never ran.
- `FormHandler::uploadMedia()` was likewise unreachable — `MediaMultipleUploadHandler` registers on `admin_post_dbg_upload_media` at priority 1 (before `FormHandler`'s default priority) and always exits.
- `FormHandler::deleteAsset()` was removed: no admin view ever submits to `admin_post_dbg_delete_asset` (the UI only offers Archive/Restore), and the method called `AssetRepository::delete()`, which doesn't exist — it would have fatal-errored if ever reached.
- `FormHandler` now only owns the hooks it's the sole handler for: settings, file archive/download/version-upload, media folder create/move. Asset CRUD stays exclusively in `AssetAdminHandler`.

### 0.2.1 — 2026-07-03

Wired the cross-system reference validation bridge to ATLAS ERP (`validateReference` Base44 function) and fixed a data-typing bug found in the process:

- Added `Remote\ReferenceValidator`, which calls the bridge and applies the `sync_mode` policy: `local` = no-op (trust), `hybrid` = trust on bridge failure/unreachable (fail open), `remote` = block on bridge failure (fail closed).
- **Bug fix**: `organisation_id` / `project_id` were still being treated as WordPress-local integers (`absint()`, numeric `<input>` fields, `%d` SQL params) across Assets, Media folders, File records, Production jobs, and their admin/REST layers — even though these are now Base44 record ids (strings). The DB schema (`Migrator.php`) was already correctly `VARCHAR(64)`, but the PHP layer wasn't, meaning real Base44 ids would have been silently truncated to `0`. Fixed end-to-end: `Assets/AssetService.php`, `Database/Repositories/{AssetRepository,MediaFolderRepository,FileRecordRepository,ProductionJobRepository}.php`, `Admin/{AssetAdminHandler,FormHandler,MediaAjaxUploadHandler,MediaMultipleUploadHandler}.php`, `API/Routes/{AssetRoutes,MediaFolderRoutes,FileRoutes}.php`, `Files/FileUploadService.php`, admin views (number inputs → text inputs).
- Production settings on IONOS (`s1097820712.onlinehome.fr`): `sync_mode = hybrid`, `api_base_url` pointed at the ATLAS ERP function. Verified live: invalid organisation id correctly rejected, bad API key correctly returns 401.

### 0.2.0 — 2026-07-02

Removed business-domain logic that had leaked into this plugin, violating `books/architecture/07-wordpress-base44-roles.md` ("WordPress sells. Base44 operates."):

- Removed: Organisation, OrganisationContact, OrganisationUser, OrganisationSettings, Project, Quote, Order, Invoice, Payment services, their event/DB repositories, admin screens, views, and REST routes (including the misleadingly-named `IdentityRoutes`, which was actually full Organisation CRUD).
- Removed the corresponding `dbg_*` database tables from the activation migrator (organisations, organisation_contacts, organisation_users, organisation_settings, projects, project_events, quotes, quote_lines, quote_events, orders, order_lines, order_events, invoices, invoice_lines, invoice_events, payments, payment_allocations, payment_events).
- This business domain now lives entirely in **ATLAS ERP** (Base44 app).
- Kept: Assets, Media/Files, Audit logs, Settings (sync mode), Commerce/WooCommerce bridge, Identity/permission plumbing used by those.
- `AssetService` and the Assets admin views no longer validate `organisation_id`/`project_id` against local tables (those repositories no longer exist) — they now only do shape validation. A proper cross-system validation bridge to the ATLAS ERP API is a follow-up task, not yet implemented.
