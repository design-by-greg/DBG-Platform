<?php

namespace DBGPlatform\Core;

use DBGPlatform\API\RestServiceProvider;
use DBGPlatform\Admin\AdminServiceProvider;
use DBGPlatform\Database\DatabaseServiceProvider;
use DBGPlatform\Integrations\WooCommerce\WooCommerceServiceProvider;

class Plugin
{
    public function boot(): void
    {
        $this->registerProviders();
    }

    private function registerProviders(): void
    {
        (new DatabaseServiceProvider())->register();
        (new RestServiceProvider())->register();
        (new AdminServiceProvider())->register();
        (new WooCommerceServiceProvider())->register();
    }
}
