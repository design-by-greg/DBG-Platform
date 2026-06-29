# SPEC-002 — Project Engine

Status: Draft v1.0

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
