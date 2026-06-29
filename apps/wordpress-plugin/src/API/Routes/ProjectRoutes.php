<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\API\ApiValidator;
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
            ['methods' => 'GET', 'callback' => [$this, 'listProjects'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'createProject'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);

        register_rest_route('dbg/v1', '/projects/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getProject'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateProject'], 'permission_callback' => [$this->gate, 'canEditPosts']],
            ['methods' => 'DELETE', 'callback' => [$this, 'deleteProject'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);
    }

    public function listProjects(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => $this->projects->all()]);
    }

    public function getProject(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->projects->find((int) $request['id']);
        return $item ? ApiResponse::ok(['data' => $item]) : ApiResponse::notFound('Project not found');
    }

    public function createProject(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload);
        if ($validation instanceof WP_REST_Response) {
            return $validation;
        }
        $id = $this->projects->create($payload);
        return ApiResponse::created(['id' => $id, 'message' => 'Project created']);
    }

    public function updateProject(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload);
        if ($validation instanceof WP_REST_Response) {
            return $validation;
        }
        $updated = $this->projects->update((int) $request['id'], $payload);
        return ApiResponse::ok(['updated' => $updated]);
    }

    public function deleteProject(WP_REST_Request $request): WP_REST_Response
    {
        $deleted = $this->projects->delete((int) $request['id']);
        return ApiResponse::ok(['archived' => $deleted]);
    }

    private function validatePayload(array $payload): ?WP_REST_Response
    {
        $validator = (new ApiValidator())
            ->positiveInt('organisation_id', 'Organisation ID', $payload)
            ->required('name', 'Project name', $payload);

        return $validator->passes() ? null : ApiResponse::validation($validator->errors());
    }
}
