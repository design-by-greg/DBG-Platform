# DBG Platform WordPress Plugin

Status: Development scaffold

## Purpose

This plugin connects WordPress and WooCommerce to DBG Platform domain concepts.

It must not become the full business domain. It is an interface and integration layer.

## Responsibilities

- Register DBG Platform REST routes in WordPress.
- Provide admin screens for configuration.
- Prepare WooCommerce integration hooks.
- Expose catalogue and project-related UI helpers.
- Connect to DBG Platform engines.

## Non-responsibilities

- Own supplier logic.
- Own the complete domain model.
- Store final business truth if a dedicated backend exists later.

## Structure

- dbg-platform.php
- src/Core
- src/API
- src/Admin
- src/Modules
- src/Integrations
- src/Database
- assets
- tests
