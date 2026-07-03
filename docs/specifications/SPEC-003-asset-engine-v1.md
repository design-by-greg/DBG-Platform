# SPEC-003 Asset Engine v1

Status: Draft

> **Implementation note (2026-07-03, see ADR-007 and `books/architecture/07-wordpress-base44-roles.md`):** Unlike the other engines in this doc, Asset Engine is **correctly split, already implemented, and should stay that way**: the WordPress plugin owns the actual files, versions, folders, tags and media health tooling (`src/Files/`, `src/Assets/`, `src/Admin/Media*`) — that part is intentionally NOT in Base44. What moved to Base44 is only the *ownership reference*: an Asset's `organisation_id`/`project_id` must point at a real ATLAS ERP record, checked live through `ReferenceValidator`/`RemoteClient` (never a local `organisations`/`projects` table — that mistake was already made and reverted once, see ADR-007). The "Reorder Product Asset" flow below, which hands off to Commerce/Order, is the one part of this spec that continues into Base44 (see SPEC-004's note) once a cart/order is actually created.

## Mission

Asset Engine manages the reusable visual, commercial and production elements owned by an Organisation.

## Core objects

- Asset
- Asset Type
- Asset Version
- Asset Relation
- Resource
- Status

## Asset types for MVP

- Logo
- Product
- BAT
- Document
- Image
- Template

## Rules

- An Asset belongs to one Organisation.
- An Asset can be attached to one Project.
- An Asset can have multiple versions.
- A Resource is a file attached to an Asset.
- A validated Product becomes reusable for reorder.
- Assets can be linked together.

## Workflows

### Create Asset

1. User creates an Asset.
2. Platform assigns owner Organisation.
3. User attaches Resources if needed.
4. Asset starts as Draft.

### Validate Asset

1. Asset is submitted for validation.
2. Authorized User validates.
3. Asset becomes Validated.
4. Workflow Engine records event.

### Reorder Product Asset

1. User opens Product Library.
2. User selects validated Product Asset.
3. User chooses quantity or variants.
4. Commerce Engine prepares Order.

## Acceptance criteria

- Create Asset.
- Upload or attach Resource.
- Create Asset Version.
- Link Assets.
- Mark Product Asset as reorderable.
