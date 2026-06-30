<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\AssetRepository;
use DBGPlatform\Database\Repositories\FileRecordRepository;
use DBGPlatform\Files\FileUploadService;
use DBGPlatform\Files\ThumbnailService;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class FileRoutes
{
    private PermissionGate $gate;
    private FileUploadService $uploads;
    private AuditLogger $audit;
    private ThumbnailService $thumbnails;

    public function __construct()
    {
        $this->gate = new PermissionGate();
        $this->uploads = new FileUploadService();
        $this->audit = new AuditLogger();
        $this->thumbnails = new ThumbnailService();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/files', [
            ['methods' => 'GET', 'callback' => [$this, 'listFiles'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'uploadFile'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);
        register_rest_route('dbg/v1', '/files/bulk', [['methods' => 'PATCH', 'callback' => [$this, 'bulkAction'], 'permission_callback' => [$this->gate, 'canEditPosts']]]);
        register_rest_route('dbg/v1', '/files/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getFile'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateFile'], 'permission_callback' => [$this->gate, 'canEditPosts']],
            ['methods' => 'DELETE', 'callback' => [$this, 'archiveFile'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);
    }

    public function listFiles(WP_REST_Request $request): WP_REST_Response
    {
        $tagIds = $request->get_param('tag_ids');
        if (is_string($tagIds)) { $tagIds = array_filter(array_map('trim', explode(',', $tagIds))); }
        $favorite = $request->get_param('is_favorite');

        $filters = [
            'organisation_id' => absint($request->get_param('organisation_id')),
            'project_id' => absint($request->get_param('project_id')),
            'folder_id' => absint($request->get_param('folder_id')),
            'asset_id' => absint($request->get_param('asset_id')),
            'tag_id' => absint($request->get_param('tag_id')),
            'tag_ids' => is_array($tagIds) ? array_map('absint', $tagIds) : [],
            'is_favorite' => $favorite === null ? '' : absint($favorite),
            'mime_type' => sanitize_text_field($request->get_param('mime_type') ?? ''),
            'status' => sanitize_key($request->get_param('status') ?? ''),
            'search' => sanitize_text_field($request->get_param('search') ?? ''),
            'sort_by' => sanitize_key($request->get_param('sort_by') ?? 'id'),
            'sort_order' => sanitize_key($request->get_param('sort_order') ?? 'DESC'),
        ];

        $result = (new FileRecordRepository())->paginated($filters, absint($request->get_param('page') ?? 1), absint($request->get_param('per_page') ?? $request->get_param('limit') ?? 25));
        return ApiResponse::ok(['data' => $result['items'], 'pagination' => $result['pagination'], 'sort' => $result['sort'], 'filters' => $filters]);
    }

    public function getFile(WP_REST_Request $request): WP_REST_Response
    {
        $file = (new FileRecordRepository())->find((int) $request['id']);
        return $file ? ApiResponse::ok(['data' => $file]) : ApiResponse::notFound('File not found');
    }

    public function updateFile(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $fileId = (int) $request['id'];
        $repository = new FileRecordRepository();
        $updated = false;
        $changes = [];

        if (array_key_exists('folder_id', $payload) || $request->get_param('folder_id') !== null) {
            $folderId = absint($payload['folder_id'] ?? $request->get_param('folder_id'));
            $updated = $repository->moveToFolder($fileId, $folderId) || $updated;
            $changes['folder_id'] = $folderId;
        }
        if (array_key_exists('is_favorite', $payload) || $request->get_param('is_favorite') !== null) {
            $favorite = (bool) absint($payload['is_favorite'] ?? $request->get_param('is_favorite'));
            $updated = $repository->setFavorite($fileId, $favorite) || $updated;
            $changes['is_favorite'] = $favorite ? 1 : 0;
        }
        if (!empty($payload['original_name']) || $request->get_param('original_name') !== null) {
            $name = sanitize_file_name((string) ($payload['original_name'] ?? $request->get_param('original_name')));
            if ($name === '') { return ApiResponse::validation(['original_name is required.']); }
            $updated = $repository->rename($fileId, $name) || $updated;
            $changes['original_name'] = $name;
        }

        $this->audit->record('updated', 'file', $fileId, ['updated' => $updated, 'changes' => $changes]);
        return ApiResponse::ok(['updated' => $updated, 'changes' => $changes]);
    }

    public function bulkAction(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $ids = array_values(array_filter(array_map('absint', (array) ($payload['ids'] ?? []))));
        $action = sanitize_key($payload['action'] ?? '');
        if (empty($ids)) { return ApiResponse::validation(['ids are required.']); }
        $repository = new FileRecordRepository();
        $count = 0;
        if ($action === 'archive') { $count = $repository->bulkArchive($ids); }
        elseif ($action === 'move') { $count = $repository->bulkMoveToFolder($ids, absint($payload['folder_id'] ?? 0)); }
        else { return ApiResponse::validation(['Unsupported bulk action.']); }
        $this->audit->record('bulk_' . $action, 'file', null, ['ids' => $ids, 'count' => $count, 'folder_id' => absint($payload['folder_id'] ?? 0)]);
        return ApiResponse::ok(['action' => $action, 'count' => $count, 'ids' => $ids]);
    }

    public function archiveFile(WP_REST_Request $request): WP_REST_Response
    {
        $archived = (new FileRecordRepository())->archive((int) $request['id']);
        $this->audit->record('archived', 'file', (int) $request['id'], ['archived' => $archived]);
        return ApiResponse::ok(['archived' => $archived]);
    }

    public function uploadFile(WP_REST_Request $request): WP_REST_Response
    {
        $files = $this->normaliseFiles($request->get_file_params());
        if (empty($files)) { return ApiResponse::validation(['file is required.']); }
        $context = ['organisation_id' => absint($request->get_param('organisation_id')), 'project_id' => absint($request->get_param('project_id'))];
        $folderId = absint($request->get_param('folder_id'));
        if ($context['organisation_id'] <= 0) { return ApiResponse::validation(['organisation_id is required.']); }
        $uploaded = []; $errors = [];
        foreach ($files as $file) {
            $result = $this->uploads->upload($file, $context);
            if (empty($result['success'])) { $errors[] = $result['message'] ?? 'Upload failed.'; continue; }
            $thumbnail = $this->thumbnails->generate($result);
            if (!empty($thumbnail['success'])) { $result['thumbnail_path'] = $thumbnail['thumbnail_path']; $result['thumbnail_url'] = $thumbnail['thumbnail_url']; }
            $assetId = (new AssetRepository())->create(['organisation_id' => $context['organisation_id'], 'project_id' => $context['project_id'], 'type' => 'document', 'name' => $result['original_name']]);
            $result['asset_id'] = $assetId; $result['folder_id'] = $folderId; $result['file_record_id'] = (new FileRecordRepository())->create($result);
            $this->audit->record('uploaded', 'file', $result['file_record_id'], $result);
            $uploaded[] = $result;
        }
        if (empty($uploaded) && !empty($errors)) { return ApiResponse::validation($errors); }
        return ApiResponse::created(['message' => count($uploaded) . ' file(s) uploaded', 'data' => $uploaded, 'errors' => $errors]);
    }

    private function normaliseFiles(array $params): array
    {
        if (!empty($params['file']) && is_array($params['file']['name'] ?? null)) { return $this->splitFileArray($params['file']); }
        if (!empty($params['files']) && is_array($params['files']['name'] ?? null)) { return $this->splitFileArray($params['files']); }
        if (!empty($params['file'])) { return [$params['file']]; }
        return [];
    }

    private function splitFileArray(array $fileArray): array
    {
        $files = [];
        foreach ((array) ($fileArray['name'] ?? []) as $index => $name) {
            if ((int) ($fileArray['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { continue; }
            $files[] = ['name' => $name, 'type' => $fileArray['type'][$index] ?? '', 'tmp_name' => $fileArray['tmp_name'][$index] ?? '', 'error' => $fileArray['error'][$index] ?? 0, 'size' => $fileArray['size'][$index] ?? 0];
        }
        return $files;
    }
}
