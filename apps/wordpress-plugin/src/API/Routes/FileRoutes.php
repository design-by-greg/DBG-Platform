<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\AssetRepository;
use DBGPlatform\Database\Repositories\FileRecordRepository;
use DBGPlatform\Files\FileUploadService;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class FileRoutes
{
    private PermissionGate $gate;
    private FileUploadService $uploads;
    private AuditLogger $audit;

    public function __construct()
    {
        $this->gate = new PermissionGate();
        $this->uploads = new FileUploadService();
        $this->audit = new AuditLogger();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/files', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'listFiles'],
                'permission_callback' => [$this->gate, 'canRead'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'uploadFile'],
                'permission_callback' => [$this->gate, 'canEditPosts'],
            ],
        ]);

        register_rest_route('dbg/v1', '/files/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getFile'],
                'permission_callback' => [$this->gate, 'canRead'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'archiveFile'],
                'permission_callback' => [$this->gate, 'canEditPosts'],
            ],
        ]);
    }

    public function listFiles(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => (new FileRecordRepository())->all(100)]);
    }

    public function getFile(WP_REST_Request $request): WP_REST_Response
    {
        $file = (new FileRecordRepository())->find((int) $request['id']);
        return $file ? ApiResponse::ok(['data' => $file]) : ApiResponse::notFound('File not found');
    }

    public function archiveFile(WP_REST_Request $request): WP_REST_Response
    {
        $archived = (new FileRecordRepository())->archive((int) $request['id']);
        $this->audit->record('archived', 'file', (int) $request['id'], ['archived' => $archived]);
        return ApiResponse::ok(['archived' => $archived]);
    }

    public function uploadFile(WP_REST_Request $request): WP_REST_Response
    {
        $files = $request->get_file_params();
        $file = $files['file'] ?? null;

        if (!$file) {
            return ApiResponse::validation(['file is required.']);
        }

        $context = [
            'organisation_id' => absint($request->get_param('organisation_id')),
            'project_id' => absint($request->get_param('project_id')),
        ];

        if ($context['organisation_id'] <= 0) {
            return ApiResponse::validation(['organisation_id is required.']);
        }

        $result = $this->uploads->upload($file, $context);

        if (empty($result['success'])) {
            return ApiResponse::validation([$result['message'] ?? 'Upload failed.']);
        }

        $assetId = (new AssetRepository())->create([
            'organisation_id' => $context['organisation_id'],
            'project_id' => $context['project_id'],
            'type' => 'document',
            'name' => $result['original_name'],
        ]);

        $result['asset_id'] = $assetId;
        $result['file_record_id'] = (new FileRecordRepository())->create($result);

        $this->audit->record('uploaded', 'file', $result['file_record_id'], $result);

        return ApiResponse::created([
            'message' => 'File uploaded',
            'data' => $result,
        ]);
    }
}
