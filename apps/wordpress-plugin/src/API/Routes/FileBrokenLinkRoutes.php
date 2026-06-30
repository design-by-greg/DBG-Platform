<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Files\BrokenLinkChecker;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class FileBrokenLinkRoutes
{
    private PermissionGate $gate;

    public function __construct()
    {
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/files/broken-links', [
            ['methods' => 'GET', 'callback' => [$this, 'brokenLinks'], 'permission_callback' => [$this->gate, 'canRead']],
        ]);
    }

    public function brokenLinks(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => (new BrokenLinkChecker())->check()]);
    }
}
