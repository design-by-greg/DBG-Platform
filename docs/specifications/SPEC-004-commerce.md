# SPEC-004 Commerce

Status: Draft

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
