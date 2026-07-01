<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\API\ApiValidator;
use DBGPlatform\Database\Repositories\OrganisationContactRepository;
use DBGPlatform\Database\Repositories\OrganisationSettingsRepository;
use DBGPlatform\Organisations\OrganisationContactService;
use DBGPlatform\Organisations\OrganisationSettingsService;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class OrganisationContactRoutes
{
    private PermissionGate $gate;
    private OrganisationContactRepository $contacts;
    private OrganisationSettingsRepository $settingsRepository;
    private OrganisationSettingsService $settingsService;
    private OrganisationContactService $service;

    public function __construct()
    {
        $this->gate = new PermissionGate();
        $this->contacts = new OrganisationContactRepository();
        $this->settingsRepository = new OrganisationSettingsRepository();
        $this->settingsService = new OrganisationSettingsService();
        $this->service = new OrganisationContactService();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/organisations/(?P<organisation_id>\d+)/contacts', [
            ['methods' => 'GET', 'callback' => [$this, 'listContacts'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'createContact'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/organisation-contacts/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getContact'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateContact'], 'permission_callback' => [$this->gate, 'canManage']],
            ['methods' => 'DELETE', 'callback' => [$this, 'archiveContact'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/organisation-contacts/(?P<id>\d+)/restore', [['methods' => 'PATCH', 'callback' => [$this, 'restoreContact'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/organisation-contacts/(?P<id>\d+)/main', [['methods' => 'PATCH', 'callback' => [$this, 'makeMainContact'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/organisations/(?P<organisation_id>\d+)/settings', [
            ['methods' => 'GET', 'callback' => [$this, 'getSettings'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updateSettings'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
    }

    public function listContacts(WP_REST_Request $request): WP_REST_Response
    {
        $filters = [
            'organisation_id' => absint($request['organisation_id']),
            'search' => sanitize_text_field($request->get_param('search') ?? ''),
            'department' => sanitize_text_field($request->get_param('department') ?? ''),
            'status' => sanitize_key($request->get_param('status') ?? ''),
            'is_primary' => $request->get_param('is_primary') === null ? '' : absint($request->get_param('is_primary')),
            'has_email' => absint($request->get_param('has_email') ?? 0),
            'missing_email' => absint($request->get_param('missing_email') ?? 0),
            'created_from' => sanitize_text_field($request->get_param('created_from') ?? ''),
            'created_to' => sanitize_text_field($request->get_param('created_to') ?? ''),
            'sort_by' => sanitize_key($request->get_param('sort_by') ?? 'id'),
            'sort_order' => sanitize_key($request->get_param('sort_order') ?? 'DESC'),
        ];
        $result = $this->contacts->paginated($filters, absint($request->get_param('page') ?? 1), absint($request->get_param('per_page') ?? 25));
        return ApiResponse::ok(['data' => $result['items'], 'pagination' => $result['pagination'], 'sort' => $result['sort'], 'filters' => $filters, 'departments' => $this->contacts->departments(absint($request['organisation_id']))]);
    }

    public function createContact(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validateContact($payload, true);
        if ($validation instanceof WP_REST_Response) { return $validation; }
        $id = $this->service->create(absint($request['organisation_id']), $payload);
        if ($id <= 0) { return ApiResponse::validation(['Organisation not found.']); }
        return ApiResponse::created(['id' => $id, 'message' => 'Contact created']);
    }

    public function getContact(WP_REST_Request $request): WP_REST_Response
    {
        $contact = $this->contacts->find((int) $request['id']);
        return $contact ? ApiResponse::ok(['data' => $contact]) : ApiResponse::notFound('Contact not found');
    }

    public function updateContact(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validateContact($payload, false);
        if ($validation instanceof WP_REST_Response) { return $validation; }
        return ApiResponse::ok(['updated' => $this->service->update((int) $request['id'], $payload)]);
    }

    public function archiveContact(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['archived' => $this->service->archive((int) $request['id'])]); }
    public function restoreContact(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['restored' => $this->service->restore((int) $request['id'])]); }
    public function makeMainContact(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['main' => $this->service->makeMain((int) $request['id'])]); }

    public function getSettings(WP_REST_Request $request): WP_REST_Response
    {
        $settings = $this->settingsService->find(absint($request['organisation_id']));
        if (!$settings) { return ApiResponse::notFound('Organisation not found'); }
        return ApiResponse::ok(['data' => $settings, 'allowed' => $this->settingsRepository->allowedValues()]);
    }

    public function updateSettings(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validateSettings($payload);
        if ($validation instanceof WP_REST_Response) { return $validation; }
        $settings = $this->settingsService->update(absint($request['organisation_id']), $payload);
        if (!$settings) { return ApiResponse::notFound('Organisation not found'); }
        return ApiResponse::ok(['data' => $settings, 'allowed' => $this->settingsRepository->allowedValues()]);
    }

    private function validateContact(array $payload, bool $create): ?WP_REST_Response
    {
        $validator = new ApiValidator();
        if ($create) { $validator->required('first_name', 'First name', $payload)->required('last_name', 'Last name', $payload); }
        $validator->minLength('first_name', 'First name', 2, $payload)->minLength('last_name', 'Last name', 2, $payload)->maxLength('first_name', 'First name', 120, $payload)->maxLength('last_name', 'Last name', 120, $payload)->maxLength('job_title', 'Job title', 190, $payload)->maxLength('email', 'Email', 190, $payload)->email('email', 'Email', $payload)->maxLength('phone', 'Phone', 64, $payload)->maxLength('mobile', 'Mobile', 64, $payload)->maxLength('department', 'Department', 120, $payload)->booleanish('is_primary', 'Primary contact', $payload);
        if (isset($payload['status'])) { $validator->allowedValue('status', 'Status', ['active', 'archived'], $payload); }
        return $validator->passes() ? null : ApiResponse::validation($validator->errors());
    }

    private function validateSettings(array $payload): ?WP_REST_Response
    {
        $allowed = $this->settingsRepository->allowedValues();
        $validator = (new ApiValidator())
            ->maxLength('default_language', 'Default language', 16, $payload)
            ->maxLength('default_currency', 'Default currency', 8, $payload)
            ->maxLength('default_project_status', 'Default project status', 64, $payload)
            ->allowedValue('default_language', 'Default language', $allowed['languages'], $payload)
            ->allowedValue('default_currency', 'Default currency', $allowed['currencies'], $payload)
            ->allowedValue('default_project_status', 'Default project status', $allowed['project_statuses'], $payload)
            ->booleanish('branding_enabled', 'Branding enabled', $payload);
        return $validator->passes() ? null : ApiResponse::validation($validator->errors());
    }
}
