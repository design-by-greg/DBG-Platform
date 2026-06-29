# SPEC-003 Asset Engine v1

Status: Draft

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
