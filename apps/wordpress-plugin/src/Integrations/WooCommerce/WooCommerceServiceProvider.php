<?php

namespace DBGPlatform\Integrations\WooCommerce;

class WooCommerceServiceProvider
{
    public function register(): void
    {
        add_action('woocommerce_loaded', [$this, 'bootWooCommerce']);
    }

    public function bootWooCommerce(): void
    {
        if (!function_exists('wc_get_order')) {
            return;
        }

        $sync = new WooCommerceOrderSync();

        add_action('woocommerce_new_order', [$sync, 'handleOrderCreated'], 10, 1);
        add_action('woocommerce_order_status_changed', [$sync, 'handleOrderStatusChanged'], 10, 3);
    }
}
