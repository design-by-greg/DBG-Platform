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
        // Future WooCommerce hooks will be registered here.
    }
}
