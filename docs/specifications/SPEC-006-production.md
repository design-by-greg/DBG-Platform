# SPEC-006 Production

Status: Draft

> **Implementation note (2026-07-03, see ADR-008):** Production is core ERP business logic and must be implemented in **ATLAS ERP (Base44)**, not in the WordPress plugin in this repo. The `Production Job`, `Quality Check`, and `Delivery` objects below already exist as Base44 entities (`ProductionJob`, `ProductionOperation`, `ProductionAssignment`, `ProductionCheck`, `ProductionEvent`, `Delivery`). WordPress may only ever read/display production status through the `ReferenceValidator`/bridge pattern (ADR-007) — it must never own a local `production_jobs` table, repository, or service class. This spec has already been rebuilt twice in the WordPress plugin by mistake; check ADR-008 before adding any Production code here.

## Mission

Production manages suppliers, production jobs, delays, quality checks and delivery.

## Objects

Supplier
Supplier Connector
Production Job
Production Status
Quality Check
Delivery
Tracking

## Rules

Production is supplier agnostic.
Supplier choice is internal.
The customer sees DBG status, not supplier complexity.
Templates are internal.
A job cannot start without payment and validation when required.

## Flow production

Order is confirmed.
Workflow checks payment and validation.
Production job is created.
Supplier or internal workshop is selected.
Production starts.
Quality check is completed.
Delivery is created.
Tracking is updated.

## Acceptance

Create production job.
Assign supplier.
Update production status.
Run quality check.
Create delivery tracking.
