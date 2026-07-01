<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\API\ApiValidator;
use DBGPlatform\Assets\AssetEventRepository;
use DBGPlatform\Assets\AssetService;
use DBGPlatform\Database\Repositories\AssetRepository;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class AssetRoutes
{
    private AssetRepository $assets;
    private AssetEventRepository $events;
    private AssetService $service;
    private PermissionGate $gate;

    public function __construct()
    {
        $this->assets = new AssetRepository();
        $this->events = new AssetEventRepository();
        $this->service = new AssetService();
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/assets', [
            ['methods' => 'GET', 'callback' => [$this, 'listAssets'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'createAsset'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/assets/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getAsset'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateAsset'], 'permission_callback' => [$this->gate, 'canManage']],
            ['methods' => 'DELETE', 'callback' => [$this, 'archiveAsset'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/assets/(?P<id>\d+)/restore', [['methods' => 'PATCH', 'callback' => [$this, 'restoreAsset'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/assets/(?P<id>\d+)/approval', [['methods' => 'PATCH', 'callback' => [$this, 'changeApproval'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/assets/(?P<id>\d+)/version', [['methods' => 'PATCH', 'callback' => [$this, 'bumpVersion'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/assets/(?P<id>\d+)/events', [['methods' => 'GET', 'callback' => [$this, 'assetEvents'], 'permission_callback' => [$this->gate, 'canRead']]]);
    }

    public function listAssets(WP_REST_Request $request): WP_REST_Response
    {
        $filters = [
            'organisation_id' => absint($request->get_param('organisation_id') ?? 0),
            'project_id' => absint($request->get_param('project_id') ?? 0),
            'parent_asset_id' => absint($request->get_param('parent_asset_id') ?? 0),
            'current_file_record_id' => absint($request->get_param('current_file_record_id') ?? 0),
            'search' => sanitize_text_field($request->get_param('search') ?? ''),
            'type' => sanitize_key($request->get_param('type') ?? ''),
            'category' => sanitize_key($request->get_param('category') ?? ''),
            'status' => sanitize_key($request->get_param('status') ?? ''),
            'approval_status' => sanitize_key($request->get_param('approval_status') ?? ''),
            'sort_by' => sanitize_key($request->get_param('sort_by') ?? 'id'),
            'sort_order' => sanitize_key($request->get_param('sort_order') ?? 'DESC'),
        ];
        $result = $this->assets->paginated($filters, absint($request->get_param('page') ?? 1), absint($request->get_param('per_page') ?? 25));
        return ApiResponse::ok(['data' => $result['items'], 'pagination' => $result['pagination'], 'sort' => $result['sort'], 'filters' => $filters, 'allowed' => $this->service->allowedValues()]);
    }

    public function getAsset(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->assets->find((int) $request['id']);
        if (!$item) { return ApiResponse::notFound('Asset not found'); }
        return ApiResponse::ok(['data' => $item, 'events' => $this->events->forAsset((int) $request['id'], 50), 'allowed' => $this->service->allowedValues()]);
    }

    public function createAsset(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload, true);
        if ($validation instanceof WP_REST_Response) { return $validation; }
        $errors = $this->service->validationErrors($payload, true);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        $id = $this->service->create($payload);
        return $id > 0 ? ApiResponse::created(['id' => $id, 'message' => 'Asset created']) : ApiResponse::validation(['Asset could not be created.']);
    }

    public function updateAsset(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload, false);
        if ($validation instanceof WP_REST_Response) { return $validation; }
        $errors = $this->service->validationErrors($payload, false, (int) $request['id']);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        return ApiResponse::ok(['updated' => $this->service->update((int) $request['id'], $payload)]);
    }

    public function archiveAsset(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['archived' => $this->service->archive((int) $request['id'])]); }
    public function restoreAsset(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['restored' => $this->service->restore((int) $request['id'])]); }

    public function changeApproval(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $status = sanitize_key($payload['approval_status'] ?? '');
        if ($status === '') { return ApiResponse::validation(['Approval status is required.']); }
        $allowed = $this->service->allowedValues();
        if (!in_array($status, $allowed['approval_statuses'], true)) { return ApiResponse::validation(['Approval status is invalid.']); }
        return ApiResponse::ok(['updated' => $this->service->changeApprovalStatus((int) $request['id'], $status)]);
    }

    public function bumpVersion(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['updated' => $this->service->bumpVersion((int) $request['id'])]); }
    public function assetEvents(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['data' => $this->events->forAsset((int) $request['id'], absint($request->get_param('limit') ?? 100))]); }

    private function validatePayload(array $payload, bool $create): ?WP_REST_Response
    {
        $validator = new ApiValidator();
        if ($create) { $validator->positiveInt('organisation_id', 'Organisation ID', $payload)->required('name', 'Asset name', $payload); }
        $allowed = $this->service->allowedValues();
        $validator->maxLength('uuid', 'UUID', 36, $payload)->maxLength('name', 'Asset name', 255, $payload);
        if (isset($payload['type'])) { $validator->allowedValue('type', 'Type', $allowed['types'], $payload); }
        if (isset($payload['category'])) { $validator->allowedValue('category', 'Category', $allowed['categories'], $payload); }
        if (isset($payload['status'])) { $validator->allowedValue('status', 'Status', $allowed['statuses'], $payload); }
        if (isset($payload['approval_status'])) { $validator->allowedValue('approval_status', 'Approval status', $allowed['approval_statuses'], $payload); }
        foreach (['project_id' => 'Project ID', 'parent_asset_id' => 'Parent asset ID', 'current_file_record_id' => 'Current file record ID', 'version_number' => 'Version number'] as $field => $label) {
            if (isset($payload[$field]) && $payload[$field] !== '' && $payload[$field] !== null) { $validator->positiveInt($field, $label, $payload); }
        }
        if (isset($payload['metadata']) && !is_array($payload['metadata'])) { return ApiResponse::validation(['Metadata must be an object.']); }
        return $validator->passes() ? null : ApiResponse::validation($validator->errors());
    }
}
