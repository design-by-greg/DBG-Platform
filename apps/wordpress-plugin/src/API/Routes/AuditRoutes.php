<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Database\Repositories\AuditLogRepository;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class AuditRoutes
{
    private AuditLogRepository $logs;
    private PermissionGate $gate;

    public function __construct()
    {
        $this->logs = new AuditLogRepository();
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/audit-logs', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'listLogs'],
                'permission_callback' => [$this->gate, 'canManage'],
            ],
        ]);
    }

    public function listLogs(WP_REST_Request $request): WP_REST_Response
    {
        $filters = [
            'action' => sanitize_key($request->get_param('action') ?? ''),
            'entity_type' => sanitize_key($request->get_param('entity_type') ?? ''),
            'actor_id' => absint($request->get_param('actor_id') ?? 0),
            'entity_id' => absint($request->get_param('entity_id') ?? 0),
        ];

        $limit = absint($request->get_param('limit') ?? 100);

        return ApiResponse::ok([
            'data' => $this->logs->search($filters, $limit),
            'filters' => $filters,
        ]);
    }
}
