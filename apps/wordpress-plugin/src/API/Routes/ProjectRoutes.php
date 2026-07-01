<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\API\ApiValidator;
use DBGPlatform\Database\Repositories\ProjectRepository;
use DBGPlatform\Projects\ProjectEventRepository;
use DBGPlatform\Projects\ProjectService;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class ProjectRoutes
{
    private ProjectRepository $projects;
    private ProjectEventRepository $events;
    private ProjectService $service;
    private PermissionGate $gate;

    public function __construct()
    {
        $this->projects = new ProjectRepository();
        $this->events = new ProjectEventRepository();
        $this->service = new ProjectService();
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/projects', [
            ['methods' => 'GET', 'callback' => [$this, 'listProjects'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'createProject'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/projects/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getProject'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateProject'], 'permission_callback' => [$this->gate, 'canManage']],
            ['methods' => 'DELETE', 'callback' => [$this, 'archiveProject'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/projects/(?P<id>\d+)/restore', [['methods' => 'PATCH', 'callback' => [$this, 'restoreProject'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/projects/(?P<id>\d+)/status', [['methods' => 'PATCH', 'callback' => [$this, 'changeStatus'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/projects/(?P<id>\d+)/events', [['methods' => 'GET', 'callback' => [$this, 'projectEvents'], 'permission_callback' => [$this->gate, 'canRead']]]);
    }

    public function listProjects(WP_REST_Request $request): WP_REST_Response
    {
        $filters = [
            'organisation_id' => absint($request->get_param('organisation_id') ?? 0),
            'contact_id' => absint($request->get_param('contact_id') ?? 0),
            'owner_user_id' => absint($request->get_param('owner_user_id') ?? 0),
            'search' => sanitize_text_field($request->get_param('search') ?? ''),
            'type' => sanitize_key($request->get_param('type') ?? ''),
            'status' => sanitize_key($request->get_param('status') ?? ''),
            'priority' => sanitize_key($request->get_param('priority') ?? ''),
            'due_from' => sanitize_text_field($request->get_param('due_from') ?? ''),
            'due_to' => sanitize_text_field($request->get_param('due_to') ?? ''),
            'sort_by' => sanitize_key($request->get_param('sort_by') ?? 'id'),
            'sort_order' => sanitize_key($request->get_param('sort_order') ?? 'DESC'),
        ];
        $result = $this->projects->paginated($filters, absint($request->get_param('page') ?? 1), absint($request->get_param('per_page') ?? 25));
        return ApiResponse::ok(['data' => $result['items'], 'pagination' => $result['pagination'], 'sort' => $result['sort'], 'filters' => $filters, 'allowed' => $this->service->allowedValues()]);
    }

    public function getProject(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->projects->find((int) $request['id']);
        if (!$item) { return ApiResponse::notFound('Project not found'); }
        return ApiResponse::ok(['data' => $item, 'events' => $this->events->forProject((int) $request['id'], 50), 'allowed' => $this->service->allowedValues()]);
    }

    public function createProject(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload, true);
        if ($validation instanceof WP_REST_Response) { return $validation; }
        $errors = $this->service->validationErrors($payload, true);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        $id = $this->service->create($payload);
        return $id > 0 ? ApiResponse::created(['id' => $id, 'message' => 'Project created']) : ApiResponse::validation(['Project could not be created.']);
    }

    public function updateProject(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload, false);
        if ($validation instanceof WP_REST_Response) { return $validation; }
        $errors = $this->service->validationErrors($payload, false, (int) $request['id']);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        return ApiResponse::ok(['updated' => $this->service->update((int) $request['id'], $payload)]);
    }

    public function archiveProject(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['archived' => $this->service->archive((int) $request['id'])]); }
    public function restoreProject(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['restored' => $this->service->restore((int) $request['id'])]); }

    public function changeStatus(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $status = sanitize_key($payload['status'] ?? '');
        if ($status === '') { return ApiResponse::validation(['Status is required.']); }
        $allowed = $this->service->allowedValues();
        if (!in_array($status, $allowed['statuses'], true)) { return ApiResponse::validation(['Status is invalid.']); }
        return ApiResponse::ok(['updated' => $this->service->changeStatus((int) $request['id'], $status)]);
    }

    public function projectEvents(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => $this->events->forProject((int) $request['id'], absint($request->get_param('limit') ?? 100))]);
    }

    private function validatePayload(array $payload, bool $create): ?WP_REST_Response
    {
        $validator = new ApiValidator();
        if ($create) { $validator->positiveInt('organisation_id', 'Organisation ID', $payload)->required('name', 'Project name', $payload); }
        $allowed = $this->service->allowedValues();
        $validator->maxLength('project_number', 'Project number', 64, $payload)->maxLength('name', 'Project name', 255, $payload)->maxLength('currency', 'Currency', 8, $payload);
        if (isset($payload['type'])) { $validator->allowedValue('type', 'Type', $allowed['types'], $payload); }
        if (isset($payload['status'])) { $validator->allowedValue('status', 'Status', $allowed['statuses'], $payload); }
        if (isset($payload['priority'])) { $validator->allowedValue('priority', 'Priority', $allowed['priorities'], $payload); }
        if (isset($payload['currency'])) { $validator->allowedValue('currency', 'Currency', $allowed['currencies'], $payload); }
        foreach (['contact_id' => 'Contact ID', 'owner_user_id' => 'Owner user ID'] as $field => $label) {
            if (isset($payload[$field]) && $payload[$field] !== '' && $payload[$field] !== null) { $validator->positiveInt($field, $label, $payload); }
        }
        return $validator->passes() ? null : ApiResponse::validation($validator->errors());
    }
}
