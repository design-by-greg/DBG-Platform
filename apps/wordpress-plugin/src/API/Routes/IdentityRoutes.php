<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\API\ApiValidator;
use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Database\Repositories\OrganisationRepository;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class IdentityRoutes
{
    private OrganisationRepository $organisations;
    private PermissionGate $gate;
    private AuditLogger $audit;

    public function __construct()
    {
        $this->organisations = new OrganisationRepository();
        $this->gate = new PermissionGate();
        $this->audit = new AuditLogger();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/organisations', [
            ['methods' => 'GET', 'callback' => [$this, 'listOrganisations'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'createOrganisation'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);

        register_rest_route('dbg/v1', '/organisations/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getOrganisation'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateOrganisation'], 'permission_callback' => [$this->gate, 'canManage']],
            ['methods' => 'DELETE', 'callback' => [$this, 'deleteOrganisation'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
    }

    public function listOrganisations(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => $this->organisations->all()]);
    }

    public function getOrganisation(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->organisations->find((int) $request['id']);
        return $item ? ApiResponse::ok(['data' => $item]) : ApiResponse::notFound('Organisation not found');
    }

    public function createOrganisation(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload);
        if ($validation instanceof WP_REST_Response) {
            return $validation;
        }
        $id = $this->organisations->create($payload);
        $this->audit->record('created', 'organisation', $id, $payload);
        return ApiResponse::created(['id' => $id, 'message' => 'Organisation created']);
    }

    public function updateOrganisation(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload);
        if ($validation instanceof WP_REST_Response) {
            return $validation;
        }
        $updated = $this->organisations->update((int) $request['id'], $payload);
        $this->audit->record('updated', 'organisation', (int) $request['id'], $payload);
        return ApiResponse::ok(['updated' => $updated]);
    }

    public function deleteOrganisation(WP_REST_Request $request): WP_REST_Response
    {
        $deleted = $this->organisations->delete((int) $request['id']);
        $this->audit->record('archived', 'organisation', (int) $request['id']);
        return ApiResponse::ok(['archived' => $deleted]);
    }

    private function validatePayload(array $payload): ?WP_REST_Response
    {
        $validator = (new ApiValidator())
            ->required('name', 'Organisation name', $payload)
            ->allowedValue('type', 'Organisation type', ['company', 'club', 'association', 'public_body', 'partner'], $payload);

        return $validator->passes() ? null : ApiResponse::validation($validator->errors());
    }
}
