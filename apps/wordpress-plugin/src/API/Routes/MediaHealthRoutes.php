<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Files\MediaHealthService;
use DBGPlatform\Files\MediaMaintenanceService;
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

        register_rest_route('dbg/v1', '/media/maintenance', [
            ['methods' => 'POST', 'callback' => [$this, 'maintenance'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);
    }

    public function health(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => (new MediaHealthService())->summary()]);
    }

    public function maintenance(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $action = sanitize_key((string) ($payload['action'] ?? $request->get_param('action') ?? ''));

        if ($action === '') {
            return ApiResponse::validation(['Maintenance action is required.']);
        }

        $result = (new MediaMaintenanceService())->run($action);
        (new AuditLogger())->record('media_maintenance', 'media', null, $result);

        return ApiResponse::ok(['data' => $result]);
    }
}
