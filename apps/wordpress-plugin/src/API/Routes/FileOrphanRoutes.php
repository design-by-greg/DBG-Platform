<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Database\Repositories\FileRecordRepository;
use DBGPlatform\Files\OrphanFileScanner;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class FileOrphanRoutes
{
    private PermissionGate $gate;

    public function __construct()
    {
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/files/orphans', [
            ['methods' => 'GET', 'callback' => [$this, 'orphans'], 'permission_callback' => [$this->gate, 'canRead']],
        ]);
    }

    public function orphans(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok([
            'records' => (new FileRecordRepository())->orphanRecords(),
            'physical_files' => (new OrphanFileScanner())->physicalOrphans(),
        ]);
    }
}
