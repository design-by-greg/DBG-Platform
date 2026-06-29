# 03 Data Model

Status: Draft v1.0

## Purpose

This document defines the first logical data model for DBG Platform.

## Core tables

### organisations

Represents companies, clubs, associations, public bodies or partners.

Fields:
- id
- name
- type
- status
- created_at
- updated_at

### brands

Visual identity attached to an Organisation.

Fields:
- id
- organisation_id
- name
- status
- created_at
- updated_at

### workspaces

Internal grouping layer for Projects.

Fields:
- id
- organisation_id
- brand_id
- name
- type
- status

### projects

Main user-facing work unit.

Fields:
- id
- organisation_id
- brand_id
- workspace_id
- name
- description
- status
- created_by
- created_at
- updated_at

### assets

Reusable business object owned by an Organisation.

Fields:
- id
- organisation_id
- project_id
- type
- name
- status
- current_version_id
- created_by
- created_at
- updated_at

### asset_versions

Version history of an Asset.

Fields:
- id
- asset_id
- version_number
- status
- created_by
- created_at

### resources

Files attached to Assets or Asset versions.

Fields:
- id
- asset_id
- asset_version_id
- storage_provider
- path
- mime_type
- created_at

### product_library_items

Validated products available for reorder.

Fields:
- id
- organisation_id
- asset_id
- source_product_id
- reorder_enabled
- status

### orders

Commercial events linked to Projects.

Fields:
- id
- organisation_id
- project_id
- status
- total_ht
- total_ttc
- created_at

### production_jobs

Operational production tasks generated from Orders.

Fields:
- id
- order_id
- supplier_id
- status
- due_date
- tracking_ref

## Rule

The database must support Project-first and Asset-first usage without depending on WordPress data structures.
