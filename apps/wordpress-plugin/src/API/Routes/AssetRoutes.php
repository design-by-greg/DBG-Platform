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
    }

    public function listAssets(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'data' => $this->assets->all(),
        ], 200);
    }

    public function createAsset(WP_REST_Request $request): WP_REST_Response
    {
        $id = $this->assets->create($request->get_json_params() ?: []);

        return new WP_REST_Response([
            'id' => $id,
            'message' => 'Asset created',
        ], 201);
    }
}
