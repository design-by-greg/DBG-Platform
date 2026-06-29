# Global Architecture — DBG Platform

**Status:** Draft v1.0

## Purpose

This document defines the high-level architecture of DBG Platform.

## Core principle

DBG Platform is not built around WordPress, WooCommerce or Base44.

DBG Platform is built around business domains.

## Layers

1. Domain Model
2. Business Rules
3. Engines
4. Events
5. Interfaces
6. Connectors
7. Storage

## Architecture

```text
Domain Model
  -> Business Rules
  -> Engines
  -> Events
  -> Interfaces
  -> Connectors
  -> Storage
```

## Interfaces

- WordPress is the public commerce interface.
- WooCommerce handles cart, checkout and order records.
- Base44 handles operations, CRM, BAT and production tracking.
- Canva handles visual documentation and design work.
- GitHub is the source of truth for product and technical documentation.

## Rule

Technology must serve the domain. The domain must not depend on technology.
