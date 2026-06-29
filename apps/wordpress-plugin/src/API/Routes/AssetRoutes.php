<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\API\ApiValidator;
use DBGPlatform\Database\Repositories\AssetRepository;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class AssetRoutes
{
    private AssetRepository $assets;
    private PermissionGate $gate;

    public function __construct()
    {
        $this->assets = new AssetRepository();
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/assets', [
            ['methods' => 'GET', 'callback' => [$this, 'listAssets'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'createAsset'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);

        register_rest_route('dbg/v1', '/assets/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getAsset'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateAsset'], 'permission_callback' => [$this->gate, 'canEditPosts']],
            ['methods' => 'DELETE', 'callback' => [$this, 'deleteAsset'], 'permission_callback' => [$this->gate, 'canEditPosts']],
        ]);
    }

    public function listAssets(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => $this->assets->all()]);
    }

    public function getAsset(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->assets->find((int) $request['id']);
        return $item ? ApiResponse::ok(['data' => $item]) : ApiResponse::notFound('Asset not found');
    }

    public function createAsset(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload);
        if ($validation instanceof WP_REST_Response) {
            return $validation;
        }
        $id = $this->assets->create($payload);
        return ApiResponse::created(['id' => $id, 'message' => 'Asset created']);
    }

    public function updateAsset(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload);
        if ($validation instanceof WP_REST_Response) {
            return $validation;
        }
        $updated = $this->assets->update((int) $request['id'], $payload);
        return ApiResponse::ok(['updated' => $updated]);
    }

    public function deleteAsset(WP_REST_Request $request): WP_REST_Response
    {
        $deleted = $this->assets->delete((int) $request['id']);
        return ApiResponse::ok(['archived' => $deleted]);
    }

    private function validatePayload(array $payload): ?WP_REST_Response
    {
        $validator = (new ApiValidator())
            ->positiveInt('organisation_id', 'Organisation ID', $payload)
            ->allowedValue('type', 'Asset type', ['logo', 'product', 'bat', 'document', 'image', 'template'], $payload)
            ->required('name', 'Asset name', $payload);

        return $validator->passes() ? null : ApiResponse::validation($validator->errors());
    }
}
