<?php

namespace DBGPlatform\API\Routes;

use DBGPlatform\API\ApiResponse;
use DBGPlatform\Database\Repositories\PaymentRepository;
use DBGPlatform\Payments\PaymentEventRepository;
use DBGPlatform\Payments\PaymentService;
use DBGPlatform\Security\PermissionGate;
use WP_REST_Request;
use WP_REST_Response;

class PaymentRoutes
{
    private PaymentRepository $payments;
    private PaymentEventRepository $events;
    private PaymentService $service;
    private PermissionGate $gate;

    public function __construct()
    {
        $this->payments = new PaymentRepository();
        $this->events = new PaymentEventRepository();
        $this->service = new PaymentService();
        $this->gate = new PermissionGate();
    }

    public function register(): void
    {
        register_rest_route('dbg/v1', '/payments', [
            ['methods' => 'GET', 'callback' => [$this, 'listPayments'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'POST', 'callback' => [$this, 'createPayment'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/payments/(?P<id>[0-9]+)', [
            ['methods' => 'GET', 'callback' => [$this, 'getPayment'], 'permission_callback' => [$this->gate, 'canRead']],
            ['methods' => 'PATCH', 'callback' => [$this, 'updatePayment'], 'permission_callback' => [$this->gate, 'canManage']],
            ['methods' => 'DELETE', 'callback' => [$this, 'archivePayment'], 'permission_callback' => [$this->gate, 'canManage']],
        ]);
        register_rest_route('dbg/v1', '/payments/(?P<id>[0-9]+)/restore', [['methods' => 'PATCH', 'callback' => [$this, 'restorePayment'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/payments/(?P<id>[0-9]+)/status', [['methods' => 'PATCH', 'callback' => [$this, 'changeStatus'], 'permission_callback' => [$this->gate, 'canManage']]]);
        register_rest_route('dbg/v1', '/payments/(?P<id>[0-9]+)/events', [['methods' => 'GET', 'callback' => [$this, 'paymentEvents'], 'permission_callback' => [$this->gate, 'canRead']]]);
    }

    public function listPayments(WP_REST_Request $request): WP_REST_Response
    {
        $filters = [
            'organisation_id' => absint($request->get_param('organisation_id') ?? 0),
            'invoice_id' => absint($request->get_param('invoice_id') ?? 0),
            'order_id' => absint($request->get_param('order_id') ?? 0),
            'contact_id' => absint($request->get_param('contact_id') ?? 0),
            'provider' => sanitize_key($request->get_param('provider') ?? ''),
            'method' => sanitize_key($request->get_param('method') ?? ''),
            'status' => sanitize_key($request->get_param('status') ?? ''),
        ];
        $result = $this->payments->paginated($filters, absint($request->get_param('page') ?? 1), absint($request->get_param('per_page') ?? 25));
        return ApiResponse::ok(['data' => $result['items'], 'pagination' => $result['pagination'], 'filters' => $filters]);
    }

    public function getPayment(WP_REST_Request $request): WP_REST_Response
    {
        $item = $this->service->findWithAllocations((int) $request['id']);
        if (!$item) { return ApiResponse::notFound('Payment not found'); }
        return ApiResponse::ok(['data' => $item, 'events' => $this->events->forPayment((int) $request['id'], 50)]);
    }

    public function createPayment(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $errors = $this->service->validationErrors($payload, true);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        $id = $this->service->create($payload);
        return $id > 0 ? ApiResponse::created(['id' => $id]) : ApiResponse::validation(['Payment could not be created.']);
    }

    public function updatePayment(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params() ?: [];
        $errors = $this->service->validationErrors($payload, false, (int) $request['id']);
        if (!empty($errors)) { return ApiResponse::validation($errors); }
        return ApiResponse::ok(['updated' => $this->service->update((int) $request['id'], $payload)]);
    }

    public function archivePayment(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['archived' => $this->service->archive((int) $request['id'])]); }
    public function restorePayment(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['restored' => $this->service->restore((int) $request['id'])]); }
    public function changeStatus(WP_REST_Request $request): WP_REST_Response { $payload = $request->get_json_params() ?: []; return ApiResponse::ok(['updated' => $this->service->changeStatus((int) $request['id'], sanitize_key($payload['status'] ?? ''))]); }
    public function paymentEvents(WP_REST_Request $request): WP_REST_Response { return ApiResponse::ok(['data' => $this->events->forPayment((int) $request['id'], absint($request->get_param('limit') ?? 100))]); }
}
