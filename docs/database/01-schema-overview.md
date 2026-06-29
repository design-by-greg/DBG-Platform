# 01 Schema Overview

Status: Draft v1.0

## Purpose

The database must support the DBG Platform domain model independently from WordPress, WooCommerce, Base44 and supplier systems.

## Main domains

- Identity
- Project
- Asset
- Commerce
- Production
- Workflow

## Core relationships

Organisation owns Brands, Workspaces, Projects, Assets and Product Library items.

Project groups Assets, Orders and timeline events.

Asset stores reusable business objects and their Resources.

Order is a commercial event linked to a Project.

Production Job is operational work created from an Order.

Workflow stores Events, validations and state changes.

## Rule

The database must keep business truth separate from external interfaces.
