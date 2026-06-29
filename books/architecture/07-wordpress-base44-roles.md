# 07 WordPress and Base44 Roles

Status: Draft v1.0

## Purpose

This document defines the role of WordPress, WooCommerce and Base44 inside DBG Platform.

## WordPress

WordPress is the public interface.

It handles:
- public pages
- catalogue presentation
- content
- SEO
- product discovery
- customer-facing UI

WordPress must not own:
- business domain rules
- supplier logic
- production workflows
- asset ownership rules

## WooCommerce

WooCommerce handles:
- cart
- checkout
- payment flow
- order record
- customer account basics

WooCommerce must not own:
- Project model
- Asset model
- Product Library logic
- Production engine

## Base44

Base44 handles internal operations.

It manages:
- CRM
- production tracking
- BAT workflow
- internal dashboard
- commercial follow-up
- task management

Base44 must not own:
- public catalogue
- final source of truth for assets
- supplier abstraction model

## Rule

WordPress sells. Base44 operates. DBG Platform domain model defines the truth.
