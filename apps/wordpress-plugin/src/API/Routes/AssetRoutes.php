<?php

namespace DBGPlatform\API\Routes;

use WP_REST_Request;
use WP_REST_Response;

class AssetRoutes
{
    public function register(): void
    {
        register_rest_route('dbg/v1', '/assets', [
            'methods' => 'GET',
            'callback' => [$this, 'listAssets'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function listAssets(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'data' => [],
            'message' => 'Asset API scaffold',
        ], 200);
    }
}
