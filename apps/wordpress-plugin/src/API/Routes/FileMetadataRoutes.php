<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\FileMetadataRepository;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class FileMetadataRoutes
{
    private PermissionGate $gate;
    private FileMetadataRepository $metadata;
    private AuditLogger $audit;

    public function __construct()
    {
        $this->gate = new PermissionGate();
        $this->metadata = new FileMetadataRepository();
        $this->audit = new AuditLogger();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/files/(?P<id>\d+)/metadata', [
            ['methods' => 'GET', 'callback' => [$this, 'getMetadata'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PUT', 'callback' => [$this, 'syncMetadata'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);

        register_rest_route('dbg/v1', '/files/(?P<id>\d+)/metadata/(?P<key>[a-zA-Z0-9_\-]+)', [
            ['methods' => 'DELETE', 'callback' => [$this, 'deleteMetadata'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);
    }

    public function getMetadata(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => $this->metadata->allForFile((int) $request['id'])]);
    }

    public function syncMetadata(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $fileId = (int) $request['id'];
        $metadata = (array) ($payload['metadata'] ?? $payload);
        $count = $this->metadata->sync($fileId, $metadata);
        $this->audit->record('metadata_updated', 'file', $fileId, ['keys' => array_keys($metadata), 'count' => $count]);
        return ApiResponse::ok(['file_id' => $fileId, 'metadata_count' => $count]);
    }

    public function deleteMetadata(WP_REST_Request $request): WP_REST_Response
    {
        $fileId = (int) $request['id'];
        $key = sanitize_key((string) $request['key']);
        $deleted = $this->metadata->delete($fileId, $key);
        $this->audit->record('metadata_deleted', 'file', $fileId, ['key' => $key, 'deleted' => $deleted]);
        return ApiResponse::ok(['deleted' => $deleted]);
    }
}
