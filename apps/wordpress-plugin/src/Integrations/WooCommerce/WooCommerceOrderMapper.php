<?php

namespace DBGPlatform\Integrations\WooCommerce;

class WooCommerceOrderMapper
{
    public function map($order): array
    {
        if (!$order) {
            return [];
        }

        $items = [];

        foreach ($order->get_items() as $item) {
            $items[] = [
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => $item->get_total(),
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
            ];
        }

        return [
            'woocommerce_order_id' => $order->get_id(),
            'status' => $order->get_status(),
            'currency' => $order->get_currency(),
            'total' => $order->get_total(),
            'customer_id' => $order->get_customer_id(),
            'billing_email' => $order->get_billing_email(),
            'billing_name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'items' => $items,
        ];
    }
}
