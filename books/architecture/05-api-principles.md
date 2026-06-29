# 05 API Principles

Status: Draft v1.0

## Purpose

The API must expose business concepts, not technical implementation details.

## Principles

1. API is domain-first.
2. API must not expose supplier complexity.
3. API must support Organisations, Projects and Assets as first-class resources.
4. API must support auditability.
5. API must be compatible with WordPress, Base44 and future interfaces.

## Resource examples

- /organisations
- /organisations/{id}/projects
- /projects/{id}/assets
- /assets/{id}/versions
- /projects/{id}/orders
- /orders/{id}/production-jobs
- /product-library
- /events

## Rule

The API must remain stable even if the supplier, WordPress theme or internal tools change.
