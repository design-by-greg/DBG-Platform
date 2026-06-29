# 07 Indexes and Constraints

Status: Draft v1.0

## Purpose

Indexes and constraints keep the platform fast, consistent and auditable.

## Required indexes

### organisations

- index on type
- index on status

### users

- unique index on email
- index on status

### memberships

- index on organisation_id
- index on user_id
- unique index on organisation_id and user_id

### projects

- index on organisation_id
- index on workspace_id
- index on status

### assets

- index on organisation_id
- index on project_id
- index on type
- index on status

### asset_versions

- index on asset_id
- unique index on asset_id and version_number

### resources

- index on organisation_id
- index on asset_id
- index on asset_version_id

### orders

- index on organisation_id
- index on project_id
- index on status

### production_jobs

- index on order_id
- index on supplier_id
- index on status

### events

- index on organisation_id
- index on project_id
- index on event_type
- index on occurred_at

### audit_logs

- index on organisation_id
- index on actor_id
- index on entity_type and entity_id
- index on created_at

## Rule

Every table that contains organisation data must be queryable by organisation_id where applicable.
