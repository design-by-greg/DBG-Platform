<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Files\MediaHealthService;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class MediaHealthRoutes
{
    private PermissionGate $gate;

    public function __construct()
    {
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/media/health', [
            ['methods' => 'GET', 'callback' => [$this, 'health'], 'permission_callback' => [$this->gate, 'canRead']],
        ]);
    }

    public function health(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => (new MediaHealthService())->summary()]);
    }
}
