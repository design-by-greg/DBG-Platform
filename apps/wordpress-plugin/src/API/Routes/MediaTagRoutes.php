<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\MediaTagRepository;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class MediaTagRoutes
{
    private PermissionGate $gate;
    private MediaTagRepository $tags;
    private AuditLogger $audit;

    public function __construct()
    {
        $this->gate = new PermissionGate();
        $this->tags = new MediaTagRepository();
        $this->audit = new AuditLogger();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/media-tags', [
            ['methods' => 'GET', 'callback' => [$this, 'listTags'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'createTag'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);

        register_rest_route('dbg/v1', '/media-tags/(?P<id>\d+)', [
            ['methods' => 'DELETE', 'callback' => [$this, 'archiveTag'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);

        register_rest_route('dbg/v1', '/files/(?P<id>\d+)/tags', [
            ['methods' => 'GET', 'callback' => [$this, 'fileTags'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PUT', 'callback' => [$this, 'syncFileTags'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);
    }

    public function listTags(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => $this->tags->all(sanitize_key($request->get_param('status') ?? 'active'))]);
    }

    public function createTag(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        if (empty($payload['name'])) {
            return ApiResponse::validation(['Tag name is required.']);
        }

        $id = $this->tags->create($payload);
        $this->audit->record('created', 'media_tag', $id, $payload);

        return ApiResponse::created(['id' => $id, 'message' => 'Media tag created']);
    }

    public function archiveTag(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request['id'];
        $archived = $this->tags->archive($id);
        $this->audit->record('archived', 'media_tag', $id, ['archived' => $archived]);

        return ApiResponse::ok(['archived' => $archived]);
    }

    public function fileTags(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => $this->tags->tagsForFile((int) $request['id'])]);
    }

    public function syncFileTags(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $fileId = (int) $request['id'];
        $tagIds = (array) ($payload['tag_ids'] ?? []);
        $count = $this->tags->syncFileTags($fileId, $tagIds);

        $this->audit->record('tagged', 'file', $fileId, ['tag_ids' => $tagIds, 'count' => $count]);

        return ApiResponse::ok(['file_id' => $fileId, 'tag_count' => $count]);
    }
}
