<?php

namespace DBGPlatform\API\Routes;

use WP_REST_Request;
use WP_REST_Response;

class ProjectRoutes
{
    public function register(): void
    {
        register_rest_route('dbg/v1', '/projects', [
            'methods' => 'GET',
            'callback' => [$this, 'listProjects'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function listProjects(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'data' => [],
            'message' => 'Project API scaffold',
        ], 200);
    }
}
