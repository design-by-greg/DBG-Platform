<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Database\Repositories\FileRecordRepository;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class FileDuplicateRoutes
{
    private PermissionGate $gate;
    private FileRecordRepository $files;

    public function __construct()
    {
        $this->gate = new PermissionGate();
        $this->files = new FileRecordRepository();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/files/duplicates', [
            ['methods' => 'GET', 'callback' => [$this, 'duplicates'], 'permission_callback' => [$this->gate, 'canRead']],
        ]);

        register_rest_route('dbg/v1', '/files/duplicates/(?P<hash>[a-fA-F0-9]{64})', [
            ['methods' => 'GET', 'callback' => [$this, 'duplicatesForHash'], 'permission_callback' => [$this->gate, 'canRead']],
        ]);
    }

    public function duplicates(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => $this->files->duplicateGroups()]);
    }

    public function duplicatesForHash(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => $this->files->duplicatesForHash((string) $request['hash'])]);
    }
}
