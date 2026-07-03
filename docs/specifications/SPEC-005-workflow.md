# SPEC-005 Workflow

Status: Draft

> **Implementation note (2026-07-03):** Unlike the other engines, Workflow's home is **not yet decided** — no ADR covers it. It's genuinely cross-cutting: some events originate in WordPress (Asset created, BAT requested/validated) and some in ATLAS ERP/Base44 (Organisation/Project/Order created, Production started/completed, Delivery completed). Do not build a standalone Workflow engine in either codebase yet — raise the architecture question first (most likely shape: Base44 as the central event/state engine, with WordPress-originated events pushed in through the same bridge pattern as ADR-007, but that needs an explicit decision and probably its own ADR before any code is written).

## Mission

Workflow manages events, validations, notifications and state changes.

## Objects

Event
Workflow
Workflow Step
Notification
Validation Request
State Change

## Rules

Workflow coordinates modules.
Modules publish events.
Workflow reacts to events.
Important state changes are logged.

## Main events

Organisation created
Project created
Asset created
Asset validated
Price requested
Order created
BAT requested
BAT validated
Production started
Production completed
Delivery completed
Reorder requested

## Flow BAT validation

Asset is submitted.
Workflow creates validation request.
User validates or rejects.
Status is updated.
Event is published.

## Acceptance

Receive event.
Trigger workflow.
Create validation request.
Send notification.
Update status.
Log action.
