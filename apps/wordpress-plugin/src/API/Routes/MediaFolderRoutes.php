<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\MediaFolderRepository;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class MediaFolderRoutes
{
    private PermissionGate $gate;
    private MediaFolderRepository $folders;
    private AuditLogger $audit;

    public function __construct()
    {
        $this->gate = new PermissionGate();
        $this->folders = new MediaFolderRepository();
        $this->audit = new AuditLogger();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/media-folders', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'listFolders'],
                'permission_callback' => [$this->gate, 'canRead'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'createFolder'],
                'permission_callback' => [$this->gate, 'canEditPosts'],
            ],
        ]);

        register_rest_route('dbg/v1', '/media-folders/(?P<id>\d+)', [
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'archiveFolder'],
                'permission_callback' => [$this->gate, 'canEditPosts'],
            ],
        ]);
    }

    public function listFolders(WP_REST_Request $request): WP_REST_Response
    {
        $filters = [
            'organisation_id' => sanitize_text_field((string) ($request->get_param('organisation_id') ?? '')),
            'project_id' => sanitize_text_field((string) ($request->get_param('project_id') ?? '')),
            'status' => sanitize_key($request->get_param('status') ?? 'active'),
        ];

        return ApiResponse::ok([
            'data' => $this->folders->all($filters),
            'filters' => $filters,
        ]);
    }

    public function createFolder(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];

        if (empty($payload['name'])) {
            return ApiResponse::validation(['Folder name is required.']);
        }

        if (trim((string) ($payload['organisation_id'] ?? '')) === '') {
            return ApiResponse::validation(['organisation_id is required.']);
        }

        $id = $this->folders->create($payload);
        $this->audit->record('created', 'media_folder', $id, $payload);

        return ApiResponse::created([
            'id' => $id,
            'message' => 'Media folder created',
        ]);
    }

    public function archiveFolder(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request['id'];
        $archived = $this->folders->archive($id);
        $this->audit->record('archived', 'media_folder', $id, ['archived' => $archived]);

        return ApiResponse::ok(['archived' => $archived]);
    }
}
