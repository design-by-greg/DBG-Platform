<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\Database\Repositories\ProjectRepository;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class ProjectRoutes
{
    private ProjectRepository $projects;
    private PermissionGate $gate;

    public function __construct()
    {
        $this->projects = new ProjectRepository();
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/projects', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'listProjects'],
                'permission_callback' => [$this->gate, 'canRead'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'createProject'],
                'permission_callback' => [$this->gate, 'canEditPosts'],
            ],
        ]);
    }

    public function listProjects(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'data' => $this->projects->all(),
        ], 200);
    }

    public function createProject(WP_REST_Request $request): WP_REST_Response
    {
        $id = $this->projects->create($request->get_json_params() ?: []);

        return new WP_REST_Response([
            'id' => $id,
            'message' => 'Project created',
        ], 201);
    }
}
