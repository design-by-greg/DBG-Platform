# 02 Auth and Permissions

Status: Draft v1.0

## Purpose

All API calls must be authenticated and permission checked where required.

## Concepts

- User
- Organisation
- Membership
- Role
- Permission
- Token

## Rules

- A User can belong to multiple Organisations.
- Access is always scoped to an Organisation.
- Permissions are checked before any sensitive action.
- Important changes create audit log entries.
- Public endpoints must never expose internal supplier data.

## Permission examples

- organisation.view
- project.create
- project.update
- asset.create
- asset.update
- asset.validate
- order.create
- production.view
- workflow.validate
