<?php

namespace DBGPlatform\Core;

use DBGPlatform\API\RestServiceProvider;
use DBGPlatform\Admin\AdminServiceProvider;
use DBGPlatform\Integrations\WooCommerce\WooCommerceServiceProvider;

class Plugin
{
    public function boot(): void
    {
        $this->registerProviders();
    }

    private function registerProviders(): void
    {
        (new RestServiceProvider())->register();
        (new AdminServiceProvider())->register();
        (new WooCommerceServiceProvider())->register();
    }
}
