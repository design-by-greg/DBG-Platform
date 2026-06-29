# 06 Commerce API

Status: Draft v1.0

## Endpoints

### GET /catalogue

List public catalogue items.

### GET /catalogue/{catalogue_item_id}

Get catalogue item details.

### POST /commerce/configurations

Create a product configuration.

Required fields:
- organisation_id
- project_id optional
- catalogue_item_id
- options

### POST /commerce/quotes

Create a price quote.

Required fields:
- organisation_id
- configuration_id

### POST /cart/items

Add an item to cart.

### POST /orders

Create an Order from cart or quote.

### POST /product-library/{item_id}/reorder

Create a reorder from a Product Library item.

## Events

- commerce.configuration_created
- commerce.quote_requested
- commerce.quote_created
- commerce.cart_item_added
- commerce.order_created
- reorder.requested
