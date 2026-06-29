<?php

namespace DBGPlatform\API\Routes;

use WP_REST_Request;
use WP_REST_Response;

class IdentityRoutes
{
    public function register(): void
    {
        register_rest_route('dbg/v1', '/organisations', [
            'methods' => 'GET',
            'callback' => [$this, 'listOrganisations'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function listOrganisations(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'data' => [],
            'message' => 'Identity API scaffold',
        ], 200);
    }
}
