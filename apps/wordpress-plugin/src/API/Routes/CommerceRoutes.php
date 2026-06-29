<?php

namespace DBGPlatform\API\Routes;

use WP_REST_Request;
use WP_REST_Response;

class CommerceRoutes
{
    public function register(): void
    {
        register_rest_route('dbg/v1', '/catalogue', [
            'methods' => 'GET',
            'callback' => [$this, 'listCatalogue'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function listCatalogue(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'data' => [],
            'message' => 'Commerce API scaffold',
        ], 200);
    }
}
