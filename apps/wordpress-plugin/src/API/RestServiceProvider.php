<?php

namespace DBGPlatform\API;

use DBGPlatform\API\Routes\AssetRoutes;
use DBGPlatform\API\Routes\AuditRoutes;
use DBGPlatform\API\Routes\CommerceRoutes;
use DBGPlatform\API\Routes\IdentityRoutes;
use DBGPlatform\API\Routes\ProjectRoutes;

class RestServiceProvider
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        (new IdentityRoutes())->register();
        (new ProjectRoutes())->register();
        (new AssetRoutes())->register();
        (new CommerceRoutes())->register();
        (new AuditRoutes())->register();
    }
}
