# SPEC-004 Commerce

Status: Draft

> **Implementation note (2026-07-03, see ADR-007 and `books/architecture/07-wordpress-base44-roles.md`):** Commerce is split by the "WordPress sells, Base44 operates" rule. Catalogue browsing, the configuration assistant, pricing and the cart (`Catalogue Item`, `Configuration`, `Price`, `Cart Item`) are storefront-facing and stay in WordPress — `src/API/Routes/CommerceRoutes.php` already has the `/catalogue` scaffold for this. The moment a cart becomes a real commitment (`Order Draft` → confirmed `Order`), it must be created in **ATLAS ERP (Base44)**, where `Order`/`OrderLine` already exist (app id `6a4686aa7eb2f854e0a293de`) — do not persist confirmed orders in WordPress tables. There is currently no receiver endpoint in Base44 for WordPress-originated orders yet (including the WooCommerce sync path, which is wired but disabled — `woocommerce_enabled: false`); building it requires first deciding how an anonymous/guest storefront customer maps to an ATLAS `Organisation`/`Contact` — do not invent that mapping ad hoc, raise it first.

## Mission

Commerce manages catalogue, assistant, price, cart and order draft.

## Objects

Catalogue Item
Product Source
Configuration
Price
Cart Item
Order Draft

## Rules

Commerce does not own production.
Commerce hides supplier complexity.
Catalogue creates new products.
Product Library handles reorder.
Supplier templates stay internal.

## Flow new order

Browse catalogue.
Start assistant.
Prepare configuration.
Get price.
Add cart item.
Create order draft.

## Flow reorder

Open Product Library.
Select validated product.
Choose quantity.
Create cart item.

## Acceptance

Browse catalogue.
Get price.
Add cart item.
Create order draft.
Reorder from Product Library.
