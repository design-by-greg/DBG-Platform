# 04 Event Model

Status: Draft v1.0

## Purpose

Events allow engines to communicate without tight coupling.

## Event structure

Each event should contain:

- event_id
- event_type
- organisation_id
- project_id
- actor_id
- payload
- occurred_at

## Core events

### identity.organisation_created

Emitted when an Organisation is created.

### project.created

Emitted when a Project is created.

### asset.created

Emitted when an Asset is created.

### asset.version_created

Emitted when a new Asset version is created.

### asset.validated

Emitted when an Asset is validated.

### commerce.quote_requested

Emitted when a price or configuration quote is requested.

### commerce.order_created

Emitted when an Order is created.

### workflow.validation_requested

Emitted when a validation is required.

### workflow.validation_accepted

Emitted when a validation is accepted.

### workflow.validation_rejected

Emitted when a validation is rejected.

### production.job_created

Emitted when a Production Job is created.

### production.started

Emitted when production starts.

### production.completed

Emitted when production is completed.

### reorder.requested

Emitted when a user requests a reorder from a Product Library item.

## Rule

Every critical state change must emit an event and create an audit log entry.
