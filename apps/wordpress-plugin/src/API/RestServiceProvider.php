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
use DBGPlatform\API\Routes\IdentityRoutes;
use DBGPlatform\API\Routes\MediaFolderRoutes;
use DBGPlatform\API\Routes\MediaHealthRoutes;
use DBGPlatform\API\Routes\MediaTagRoutes;
use DBGPlatform\API\Routes\OrderRoutes;
use DBGPlatform\API\Routes\ProjectRoutes;
use DBGPlatform\API\Routes\QuoteRoutes;
use DBGPlatform\API\Routes\SettingsRoutes;

class RestServiceProvider
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        (new IdentityRoutes())->register();
        $contactRoutes = 'DBGPlatform\\API\\Routes\\OrganisationContactRoutes';
        if (class_exists($contactRoutes)) { (new $contactRoutes())->register(); }
        $userRoutes = 'DBGPlatform\\API\\Routes\\OrganisationUserRoutes';
        if (class_exists($userRoutes)) { (new $userRoutes())->register(); }
        (new ProjectRoutes())->register();
        (new AssetRoutes())->register();
        (new QuoteRoutes())->register();
        (new OrderRoutes())->register();
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
