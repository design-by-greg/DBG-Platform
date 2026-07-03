# SPEC-002 — Project Engine

Status: Draft v1.0

> **Implementation note (2026-07-03, see ADR-007):** `Project` already exists as a real entity in **ATLAS ERP (Base44)**, app id `6a4686aa7eb2f854e0a293de`, linked to `Organisation`. Any further Project Engine work — `Workspace`, `ProjectMember`, `ProjectStatus`, `ProjectTimeline`, `ProjectContext` — should be added as Base44 entities too, not in the WordPress plugin in this repo. WordPress only ever references a `project_id` (validated live via the `ReferenceValidator`/bridge from ADR-007) when attaching Assets — it must never own a local `projects` table again.

## Mission

Project Engine manages Projects.

A Project groups assets, products, documents, orders and history around one communication objective.

## Entities

- Workspace
- Project
- ProjectMember
- ProjectStatus
- ProjectTimeline
- ProjectContext

## Rules

- A Project belongs to one Organisation.
- A Project may belong to one Brand.
- A Project contains Assets.
- A Project can contain Orders.
- A Project has a lifecycle.
- Orders are events inside a Project.

## Acceptance Criteria

- Create a Project.
- Attach Assets to a Project.
- Attach an Order to a Project.
- View project timeline.
- Archive a Project.
