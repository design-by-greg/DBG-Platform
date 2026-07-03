<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Database\Repositories\ProductionJobRepository;
use DBGPlatform\Production\ProductionEventRepository;
use DBGPlatform\Production\ProductionService;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class ProductionRoutes
{
    private ProductionJobRepository $jobs;
    private ProductionEventRepository $events;
    private ProductionService $service;
    private PermissionGate $gate;

    public function __construct()
    {
        $this->jobs = new ProductionJobRepository();
        $this->events = new ProductionEventRepository();
        $this->service = new ProductionService();
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/production-jobs', [
            ['methods' => 'GET', 'callback' => [$this, 'listJobs'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'createJob'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/production-jobs/(?P<id>[0-9]+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getJob'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateJob'], 'permission_callback' => [$this->gate, 'canManage']],
            ['methods' => 'DELETE', 'callback' => [$this, 'archiveJob'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/production-jobs/(?P<id>[0-9]+)/restore', [['methods' => 'PATCH', 'callback' => [$this, 'restoreJob'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/production-jobs/(?P<id>[0-9]+)/status', [['methods' => 'PATCH', 'callback' => [$this, 'changeStatus'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/production-jobs/(?P<id>[0-9]+)/events', [['methods' => 'GET', 'callback' => [$this, 'jobEvents'], 'permission_callback' => [$this->gate, 'canRead']]]);
    }

    public function listJobs(WP_REST_Request $request): WP_REST_Response
    {
        $filters = [
            'organisation_id' => absint($request->get_param('organisation_id') ?? 0),
            'project_id' => absint($request->get_param('project_id') ?? 0),
            'order_id' => absint($request->get_param('order_id') ?? 0),
            'status' => sanitize_key($request->get_param('status') ?? ''),
            'priority' => sanitize_key($request->get_param('priority') ?? ''),
        ];
        $result = $this->jobs->paginated($filters, absint($request->get_param('page') ?? 1), absint($request->get_param('per_page') ?? 25));
        return ApiResponse::ok(['data' => $result['items'], 'pagination' => $result['pagination'], 'filters' => $filters, 'allowed' => $this->service->allowedValues()]);
    }

    public function getJob(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->service->findFull((int) $request['id']);
        if (!$item) { return ApiResponse::notFound('Production job not found'); }
        return ApiResponse::ok(['data' => $item, 'events' => $this->events->forJob((int) $request['id'], 50), 'allowed' => $this->service->allowedValues()]);
    }

    public function createJob(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $errors = $this->service->validationErrors($payload, true);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        $id = $this->service->create($payload);
        return $id > 0 ? ApiResponse::created(['id' => $id]) : ApiResponse::validation(['Production job could not be created.']);
    }

    public function updateJob(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $errors = $this->service->validationErrors($payload, false, (int) $request['id']);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        return ApiResponse::ok(['updated' => $this->service->update((int) $request['id'], $payload)]);
    }

    public function archiveJob(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['archived' => $this->service->archive((int) $request['id'])]); }
    public function restoreJob(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['restored' => $this->service->restore((int) $request['id'])]); }
    public function changeStatus(WP_REST_Request $request): WP_REST_Response { $payload = $request->get_json_params() ?: []; return ApiResponse::ok(['updated' => $this->service->changeStatus((int) $request['id'], sanitize_key($payload['status'] ?? ''))]); }
    public function jobEvents(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['data' => $this->events->forJob((int) $request['id'], absint($request->get_param('limit') ?? 100))]); }
}
