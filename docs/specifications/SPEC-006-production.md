# SPEC-006 Production

Status: Draft

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
