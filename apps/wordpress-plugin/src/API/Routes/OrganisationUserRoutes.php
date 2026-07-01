<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\API\ApiValidator;
use DBGPlatform\Database\Repositories\OrganisationUserRepository;
use DBGPlatform\Organisations\OrganisationUserService;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class OrganisationUserRoutes
{
    private PermissionGate $gate;
    private OrganisationUserRepository $repository;
    private OrganisationUserService $service;
    private array $roles = ['owner', 'administrator', 'manager', 'sales', 'designer', 'production', 'support', 'viewer'];

    public function __construct()
    {
        $this->gate = new PermissionGate();
        $this->repository = new OrganisationUserRepository();
        $this->service = new OrganisationUserService();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/organisations/(?P<organisation_id>\d+)/users', [
            ['methods' => 'GET', 'callback' => [$this, 'listUsers'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'addUser'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/organisation-users/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getUser'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateUser'], 'permission_callback' => [$this->gate, 'canManage']],
            ['methods' => 'DELETE', 'callback' => [$this, 'archiveUser'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/organisation-users/(?P<id>\d+)/restore', [
            ['methods' => 'PATCH', 'callback' => [$this, 'restoreUser'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/organisation-users/(?P<id>\d+)/owner', [
            ['methods' => 'PATCH', 'callback' => [$this, 'makeOwner'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
    }

    public function listUsers(WP_REST_Request $request): WP_REST_Response
    {
        $filters = [
            'organisation_id' => absint($request['organisation_id']),
            'user_id' => absint($request->get_param('user_id') ?? 0),
            'role' => sanitize_key($request->get_param('role') ?? ''),
            'status' => sanitize_key($request->get_param('status') ?? ''),
            'is_owner' => $request->get_param('is_owner') === null ? '' : absint($request->get_param('is_owner')),
            'sort_by' => sanitize_key($request->get_param('sort_by') ?? 'id'),
            'sort_order' => sanitize_key($request->get_param('sort_order') ?? 'DESC'),
        ];
        $result = $this->repository->paginated($filters, absint($request->get_param('page') ?? 1), absint($request->get_param('per_page') ?? 25));
        return ApiResponse::ok(['data' => $this->hydrateUsers($result['items']), 'pagination' => $result['pagination'], 'sort' => $result['sort'], 'filters' => $filters, 'roles' => $this->roles]);
    }

    public function addUser(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload, true);
        if ($validation instanceof WP_REST_Response) { return $validation; }
        if (isset($payload['role']) && $payload['role'] === 'owner') { $payload['is_owner'] = true; }
        $id = $this->service->add(absint($request['organisation_id']), absint($payload['user_id'] ?? 0), $payload);
        if ($id <= 0) { return ApiResponse::validation(['Organisation or user not found, or organisation is archived.']); }
        return ApiResponse::created(['id' => $id, 'message' => 'Organisation user added']);
    }

    public function getUser(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->repository->find((int) $request['id']);
        if (!$item) { return ApiResponse::notFound('Organisation user not found'); }
        return ApiResponse::ok(['data' => $this->hydrateUser($item)]);
    }

    public function updateUser(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload, false);
        if ($validation instanceof WP_REST_Response) { return $validation; }
        if (isset($payload['role']) && $payload['role'] === 'owner') { $payload['is_owner'] = true; }
        return ApiResponse::ok(['updated' => $this->service->update((int) $request['id'], $payload)]);
    }

    public function archiveUser(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['archived' => $this->service->archive((int) $request['id'])]); }
    public function restoreUser(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['restored' => $this->service->restore((int) $request['id'])]); }
    public function makeOwner(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['owner' => $this->service->makeOwner((int) $request['id'])]); }

    private function validatePayload(array $payload, bool $create): ?WP_REST_Response
    {
        $validator = new ApiValidator();
        if ($create) { $validator->positiveInt('user_id', 'User ID', $payload); }
        if (isset($payload['role'])) { $validator->allowedValue('role', 'Role', $this->roles, $payload); }
        if (isset($payload['status'])) { $validator->allowedValue('status', 'Status', ['active', 'archived'], $payload); }
        $validator->booleanish('is_owner', 'Owner', $payload);
        return $validator->passes() ? null : ApiResponse::validation($validator->errors());
    }

    private function hydrateUsers(array $items): array { return array_map([$this, 'hydrateUser'], $items); }

    private function hydrateUser(array $item): array
    {
        $user = get_userdata(absint($item['user_id'] ?? 0));
        $item['user'] = $user ? ['id' => $user->ID, 'display_name' => $user->display_name, 'email' => $user->user_email, 'login' => $user->user_login] : null;
        return $item;
    }
}
