<?php

namespace DBGPlatform\API\Routes;

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
            [
                'methods' => 'GET',
                'callback' => [$this, 'listAssets'],
                'permission_callback' => [$this->gate, 'canRead'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'createAsset'],
                'permission_callback' => [$this->gate, 'canEditPosts'],
            ],
        ]);

        register_rest_route('dbg/v1', '/assets/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getAsset'],
                'permission_callback' => [$this->gate, 'canRead'],
            ],
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'updateAsset'],
                'permission_callback' => [$this->gate, 'canEditPosts'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'deleteAsset'],
                'permission_callback' => [$this->gate, 'canEditPosts'],
            ],
        ]);
    }

    public function listAssets(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response(['data' => $this->assets->all()], 200);
    }

    public function getAsset(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->assets->find((int) $request['id']);
        return $item ? new WP_REST_Response(['data' => $item], 200) : new WP_REST_Response(['message' => 'Asset not found'], 404);
    }

    public function createAsset(WP_REST_Request $request): WP_REST_Response
    {
        $id = $this->assets->create($request->get_json_params() ?: []);
        return new WP_REST_Response(['id' => $id, 'message' => 'Asset created'], 201);
    }

    public function updateAsset(WP_REST_Request $request): WP_REST_Response
    {
        $updated = $this->assets->update((int) $request['id'], $request->get_json_params() ?: []);
        return new WP_REST_Response(['updated' => $updated], 200);
    }

    public function deleteAsset(WP_REST_Request $request): WP_REST_Response
    {
        $deleted = $this->assets->delete((int) $request['id']);
        return new WP_REST_Response(['archived' => $deleted], 200);
    }
}
