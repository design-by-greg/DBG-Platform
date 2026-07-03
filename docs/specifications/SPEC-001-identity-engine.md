# SPEC-001 — Identity Engine

Statut: Draft v1.0

> **Implementation note (2026-07-03, see ADR-007):** `Organisation` already exists as a real entity in **ATLAS ERP (Base44)**, app id `6a4686aa7eb2f854e0a293de`, with `OrganisationContact` as its current membership/contact analog. Any further Identity Engine work — `Membership`, `Role`, `Permission`, `AuditLog` for ERP-level access control — belongs in Base44 too, not in the WordPress plugin in this repo. Do not recreate `Organisation` locally (WordPress's own `wp_users`/roles system is a separate, already-existing concern for CMS/admin login only — it is not this Identity Engine and should not be merged with it).

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
