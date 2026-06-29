# 06 Module Boundaries

Status: Draft v1.0

## Purpose

This document defines what each engine owns and what it must not own.

## Identity Engine

Owns:
- Organisations
- Users
- Memberships
- Roles
- Permissions

Does not own:
- Projects
- Assets
- Orders
- Production

## Project Engine

Owns:
- Workspaces
- Projects
- Project timeline
- Project status

Does not own:
- Files
- Pricing
- Supplier logic

## Asset Engine

Owns:
- Assets
- Resources
- Asset versions
- Asset relations
- Product Library items

Does not own:
- Checkout
- Payment
- Production execution

## Commerce Engine

Owns:
- Catalogue view
- Configuration
- Quote
- Cart item
- Order draft

Does not own:
- Supplier production status
- BAT validation
- Asset storage

## Workflow Engine

Owns:
- Events
- Validation requests
- Notifications
- State transitions

Does not own:
- Business data itself

## Production Engine

Owns:
- Production jobs
- Supplier assignment
- Quality checks
- Delivery status

Does not own:
- Checkout
- Customer identity
- Product Library

## Rule

If two engines need the same data, one must own it and the other must consume it through an event or contract.
