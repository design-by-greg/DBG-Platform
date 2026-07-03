<?php

namespace DBGPlatform\API;

use DBGPlatform\API\Routes\AssetRoutes;
use DBGPlatform\API\Routes\AuditRoutes;
use DBGPlatform\API\Routes\CommerceRoutes;
use DBGPlatform\API\Routes\FileBrokenLinkRoutes;
use DBGPlatform\API\Routes\FileDuplicateRoutes;
use DBGPlatform\API\Routes\FileMetadataRoutes;
use DBGPlatform\API\Routes\FileOrphanRoutes;
use DBGPlatform\API\Routes\FileRoutes;
use DBGPlatform\API\Routes\MediaFolderRoutes;
use DBGPlatform\API\Routes\MediaHealthRoutes;
use DBGPlatform\API\Routes\MediaTagRoutes;
use DBGPlatform\API\Routes\ProductionRoutes;
use DBGPlatform\API\Routes\SettingsRoutes;

class RestServiceProvider
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        (new AssetRoutes())->register();
        (new ProductionRoutes())->register();
        (new CommerceRoutes())->register();
        (new AuditRoutes())->register();
        (new SettingsRoutes())->register();
        (new FileRoutes())->register();
        (new MediaFolderRoutes())->register();
        (new MediaTagRoutes())->register();
        (new FileMetadataRoutes())->register();
        (new FileDuplicateRoutes())->register();
        (new FileOrphanRoutes())->register();
        (new FileBrokenLinkRoutes())->register();
        (new MediaHealthRoutes())->register();
    }
}
