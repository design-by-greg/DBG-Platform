# 05 Asset API

Status: Draft v1.0

## Endpoints

### GET /organisations/{organisation_id}/assets

List Assets for an Organisation.

### POST /organisations/{organisation_id}/assets

Create an Asset.

Required fields:
- type
- name
- project_id optional

### GET /assets/{asset_id}

Get Asset details.

### PATCH /assets/{asset_id}

Update Asset metadata.

### POST /assets/{asset_id}/versions

Create a new Asset version.

### GET /assets/{asset_id}/versions

List Asset versions.

### POST /assets/{asset_id}/resources

Attach a Resource to an Asset.

### POST /assets/{asset_id}/validate

Validate an Asset.

### GET /organisations/{organisation_id}/product-library

List validated reorderable Product Assets.

## Events

- asset.created
- asset.updated
- asset.version_created
- asset.resource_added
- asset.validated
- asset.archived
