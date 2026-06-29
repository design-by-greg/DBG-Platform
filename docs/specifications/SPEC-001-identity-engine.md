# SPEC-001 — Identity Engine

Statut: Draft v1.0

## Mission

Identity Engine handles the platform identity layer.

It manages Organisations, Users, Memberships, Roles, Permissions and Audit Logs.

## Entities

- Organisation
- User
- Membership
- Role
- Permission
- AuditLog

## Rules

- One User may belong to multiple Organisations.
- One Organisation may have multiple Users.
- A Role is a set of Permissions.
- Roles must be configurable.
- Important actions must create an audit entry.

## Acceptance Criteria

- Create an Organisation.
- Add a User to an Organisation.
- Assign a Role.
- Check a Permission.
- Read the Audit Log.
