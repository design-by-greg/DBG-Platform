<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class IdentityRoutes
{
    private OrganisationRepository $organisations;
    private PermissionGate $gate;

    public function __construct()
    {
        $this->organisations = new OrganisationRepository();
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/organisations', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'listOrganisations'],
                'permission_callback' => [$this->gate, 'canRead'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'createOrganisation'],
                'permission_callback' => [$this->gate, 'canManage'],
            ],
        ]);

        register_rest_route('dbg/v1', '/organisations/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getOrganisation'],
                'permission_callback' => [$this->gate, 'canRead'],
            ],
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'updateOrganisation'],
                'permission_callback' => [$this->gate, 'canManage'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'deleteOrganisation'],
                'permission_callback' => [$this->gate, 'canManage'],
            ],
        ]);
    }

    public function listOrganisations(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response(['data' => $this->organisations->all()], 200);
    }

    public function getOrganisation(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->organisations->find((int) $request['id']);
        return $item ? new WP_REST_Response(['data' => $item], 200) : new WP_REST_Response(['message' => 'Organisation not found'], 404);
    }

    public function createOrganisation(WP_REST_Request $request): WP_REST_Response
    {
        $id = $this->organisations->create($request->get_json_params() ?: []);
        return new WP_REST_Response(['id' => $id, 'message' => 'Organisation created'], 201);
    }

    public function updateOrganisation(WP_REST_Request $request): WP_REST_Response
    {
        $updated = $this->organisations->update((int) $request['id'], $request->get_json_params() ?: []);
        return new WP_REST_Response(['updated' => $updated], 200);
    }

    public function deleteOrganisation(WP_REST_Request $request): WP_REST_Response
    {
        $deleted = $this->organisations->delete((int) $request['id']);
        return new WP_REST_Response(['archived' => $deleted], 200);
    }
}
