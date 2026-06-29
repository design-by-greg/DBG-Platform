<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\API\ApiValidator;
use DBGPlatform\Audit\AuditLogger;
use DBGPlatform\Security\PermissionGate;
use DBGPlatform\Settings\SettingsRepository;
use WP_REST_Request;
use WP_REST_Response;

class SettingsRoutes
{
    private SettingsRepository $settings;
    private PermissionGate $gate;
    private AuditLogger $audit;

    public function __construct()
    {
        $this->settings = new SettingsRepository();
        $this->gate = new PermissionGate();
        $this->audit = new AuditLogger();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/settings', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getSettings'],
                'permission_callback' => [$this->gate, 'canManage'],
            ],
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'updateSettings'],
                'permission_callback' => [$this->gate, 'canManage'],
            ],
        ]);
    }

    public function getSettings(WP_REST_Request $request): WP_REST_Response
    {
        return ApiResponse::ok(['data' => $this->settings->all()]);
    }

    public function updateSettings(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $validation = $this->validatePayload($payload);

        if ($validation instanceof WP_REST_Response) {
            return $validation;
        }

        $settings = $this->settings->update($payload);
        $this->audit->record('updated', 'settings', null, ['sync_mode' => $settings['sync_mode']]);

        return ApiResponse::ok(['data' => $settings, 'message' => 'Settings updated']);
    }

    private function validatePayload(array $payload): ?WP_REST_Response
    {
        $validator = (new ApiValidator())
            ->allowedValue('sync_mode', 'Sync mode', ['local', 'remote', 'hybrid'], $payload);

        return $validator->passes() ? null : ApiResponse::validation($validator->errors());
    }
}
